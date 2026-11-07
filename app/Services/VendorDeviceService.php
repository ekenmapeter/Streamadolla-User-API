<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\DeviceSession;
use App\Models\User;
use App\Models\UserDevice;

/**
 * Keeps a vendor's fleet of phones in sync with the command-site devices
 * table. Each app installation registers a UserDevice (fingerprint + FCM
 * token) under the vendor account; this service mirrors it as a fleet
 * Device row (`user-{fingerprint}`) so the admin command center can push
 * campaign assignments straight to it.
 */
class VendorDeviceService
{
    public function syncFleetDevice(User $user, UserDevice $userDevice, ?string $previousFingerprint = null): ?Device
    {
        if (! $user->isVendor()) {
            return null;
        }

        $newDeviceId = 'user-' . $userDevice->fingerprint;

        // Include soft-deleted rows: they still occupy the unique device_id
        // and fcm_token indexes, so they must be merged/restored instead of
        // causing an insert failure.
        $existing = Device::withTrashed()
            ->where('device_id', $newDeviceId)
            ->first();

        $oldDevice = null;
        if ($previousFingerprint && $previousFingerprint !== $userDevice->fingerprint) {
            $oldDevice = Device::withTrashed()
                ->where('device_id', 'user-' . $previousFingerprint)
                ->where('user_id', $user->id)
                ->first();
        }

        if ($oldDevice && $existing && $oldDevice->id !== $existing->id) {
            $this->mergeInto($oldDevice, $existing);
            $oldDevice = null;
        }

        if ($oldDevice && ! $existing) {
            $this->clearDuplicateToken($userDevice->fcm_token, $oldDevice->id);

            $oldDevice->update([
                'device_id' => $newDeviceId,
                'user_id' => $user->id,
                'fcm_token' => $userDevice->fcm_token,
                'name' => $oldDevice->name ?? $user->name . "'s phone",
                'metadata' => $this->metadata($user, $userDevice),
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
                'name' => $existing?->name ?? $user->name . "'s phone",
                'metadata' => $this->metadata($user, $userDevice),
                'status' => 'online',
                'last_seen' => now(),
            ]
        );
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

    private function metadata(User $user, UserDevice $userDevice): array
    {
        return [
            'user_fingerprint' => $userDevice->fingerprint,
            'user_email' => $user->email,
            'role' => $user->role,
            'user_device_id' => $userDevice->id,
        ];
    }
}
