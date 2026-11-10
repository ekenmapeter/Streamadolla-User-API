<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\DeviceSession;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Str;

/**
 * Keeps a vendor's fleet of phones in sync with the command-site devices
 * table. Each app installation registers a UserDevice (fingerprint + FCM
 * token) under the vendor account; this service mirrors it as a fleet
 * Device row (`user-{fingerprint}`) so the admin command center can push
 * campaign assignments straight to it.
 */
class VendorDeviceService
{
    public function syncFleetDevice(
        User $user,
        UserDevice $userDevice,
        ?string $previousFingerprint = null,
        array $extraMetadata = [],
    ): ?Device {
        if (! $user->isVendor()) {
            return null;
        }

        $newDeviceId = 'user-'.$userDevice->fingerprint;

        // Include soft-deleted rows: they still occupy the unique device_id
        // and fcm_token indexes, so they must be merged/restored instead of
        // causing an insert failure.
        $existing = Device::withTrashed()
            ->where('device_id', $newDeviceId)
            ->first();

        $oldDevice = null;
        if ($previousFingerprint && $previousFingerprint !== $userDevice->fingerprint) {
            $oldDevice = Device::withTrashed()
                ->where('device_id', 'user-'.$previousFingerprint)
                ->where('user_id', $user->id)
                ->first();
        }

        if ($oldDevice && $existing && $oldDevice->id !== $existing->id) {
            $this->mergeInto($oldDevice, $existing);
            $oldDevice = null;
        }

        // Keep fields such as the model when a heartbeat self-heals a row
        // without sending them again.
        $metadata = array_merge(
            ($existing ?? $oldDevice)?->metadata ?? [],
            $this->metadata($user, $userDevice, $extraMetadata),
        );

        if ($oldDevice && ! $existing) {
            $this->clearDuplicateToken($userDevice->fcm_token, $oldDevice->id);

            $oldDevice->update([
                'device_id' => $newDeviceId,
                'user_id' => $user->id,
                'fcm_token' => $userDevice->fcm_token,
                'name' => $this->desiredName($user, $userDevice->fingerprint, $metadata, $oldDevice),
                'metadata' => $metadata,
                'status' => 'online',
                'last_seen' => now(),
            ]);

            return $oldDevice->fresh();
        }

        $this->clearDuplicateToken($userDevice->fcm_token, $existing?->id);

        if ($existing?->trashed()) {
            $existing->restore();
        }

        return Device::updateOrCreate(
            ['device_id' => $newDeviceId],
            [
                'user_id' => $user->id,
                'fcm_token' => $userDevice->fcm_token,
                'name' => $this->desiredName($user, $userDevice->fingerprint, $metadata, $existing),
                'metadata' => $metadata,
                'status' => 'online',
                'last_seen' => now(),
            ]
        );
    }

    /**
     * Give every existing vendor device that still has the old shared name
     * its own fingerprint suffix. The app later upgrades the name to include
     * the phone model when it registers.
     */
    public function backfillNames(): int
    {
        $updated = 0;

        Device::with('user')
            ->whereNotNull('user_id')
            ->where('device_id', 'like', 'user-%')
            ->chunkById(100, function ($devices) use (&$updated) {
                foreach ($devices as $device) {
                    $user = $device->user;

                    if (! $user || ! $user->isVendor() || ! $this->isGenericName(trim((string) $device->name), $user)) {
                        continue;
                    }

                    $fingerprint = Str::after($device->device_id, 'user-');
                    $name = $this->desiredName(
                        $user,
                        $fingerprint,
                        $device->metadata ?? [],
                        $device,
                    );

                    if ($name !== $device->name) {
                        $device->update(['name' => $name]);
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /**
     * A phone keeps a custom name if the vendor gave it one. Otherwise it
     * gets a unique default such as "Grace's Pixel 8 (A1B2)" so two phones
     * from the same fleet never look identical in the app or command center.
     */
    private function desiredName(
        User $user,
        string $fingerprint,
        array $metadata,
        ?Device $existing = null,
    ): string {
        $current = trim((string) ($existing?->name ?? ''));

        if ($current !== '' && ! $this->isGenericName($current, $user)) {
            return $current;
        }

        $model = trim((string) ($metadata['model'] ?? ''));
        $label = $model !== '' ? $model : 'phone';
        $suffix = $this->fingerprintSuffix($fingerprint);
        $base = Str::limit("{$user->name}'s {$label}", 88, '');
        $candidate = "{$base} ({$suffix})";

        $counter = 2;
        while ($this->nameExistsForVendor($user, $candidate, $existing?->id)) {
            $candidate = "{$base} ({$suffix}-{$counter})";
            $counter++;
        }

        return $candidate;
    }

    private function nameExistsForVendor(User $user, string $name, ?int $exceptId = null): bool
    {
        $query = Device::withTrashed()
            ->where('user_id', $user->id)
            ->where('name', $name);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    private function isGenericName(string $name, User $user): bool
    {
        if (in_array($name, ['Stream Farm Phone', 'Unnamed device'], true)) {
            return true;
        }

        // The old default, plus the suffix-only name generated before an app
        // update starts sending the phone model. Both should be upgraded once
        // the model is known; a custom name is left untouched.
        $default = preg_quote($user->name."'s phone", '/');

        return (bool) preg_match('/^'.$default.'( \([A-Z0-9-]+\))?$/', $name);
    }

    private function fingerprintSuffix(string $fingerprint): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $fingerprint) ?? '';
        $suffix = strtoupper(substr($clean, -4));

        return $suffix !== '' ? $suffix : strtoupper(substr(md5($fingerprint), 0, 4));
    }

    private function clearDuplicateToken(?string $token, ?int $exceptId = null): void
    {
        if (! $token) {
            return;
        }

        $query = Device::where('fcm_token', $token);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['fcm_token' => null]);
    }

    private function mergeInto(Device $old, Device $surviving): void
    {
        DeviceAssignment::where('device_id', $old->id)
            ->update(['device_id' => $surviving->id]);

        DeviceSession::where('device_id', $old->id)
            ->update(['device_id' => $surviving->id]);

        if ($surviving->trashed()) {
            $surviving->restore();
        }

        $surviving->update([
            'total_streams' => (int) $surviving->total_streams + (int) $old->total_streams,
            'total_earned' => (float) $surviving->total_earned + (float) $old->total_earned,
        ]);

        $old->forceDelete();
    }

    private function metadata(User $user, UserDevice $userDevice, array $extra = []): array
    {
        $metadata = [
            'user_fingerprint' => $userDevice->fingerprint,
            'user_email' => $user->email,
            'role' => $user->role,
            'user_device_id' => $userDevice->id,
        ];

        foreach (['model', 'os_version'] as $key) {
            $value = $extra[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $metadata[$key] = trim($value);
            }
        }

        return $metadata;
    }
}
