<?php

namespace App\Services;

use App\Models\Device;
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

        // When the app upgrades from a backed-up/cloned UUID to a hardware
        // ID, move the existing fleet row instead of creating a duplicate.
        // This keeps assignments, totals and sessions attached to the phone.
        $oldDevice = null;
        if ($previousFingerprint && $previousFingerprint !== $userDevice->fingerprint) {
            $oldDevice = Device::where('device_id', 'user-' . $previousFingerprint)
                ->where('user_id', $user->id)
                ->first();
        }

        $duplicateTokens = Device::where('fcm_token', $userDevice->fcm_token)
            ->where('device_id', '!=', $newDeviceId);

        if ($oldDevice) {
            $duplicateTokens->where('id', '!=', $oldDevice->id);
        }

        $duplicateTokens->update(['fcm_token' => null]);

        $existing = Device::where('device_id', $newDeviceId)->first();

        if ($oldDevice && ! $existing) {
            $oldDevice->update([
                'device_id' => $newDeviceId,
                'user_id' => $user->id,
                'fcm_token' => $userDevice->fcm_token,
                'name' => $oldDevice->name ?? $user->name . "'s phone",
                'metadata' => [
                    'user_fingerprint' => $userDevice->fingerprint,
                    'user_email' => $user->email,
                    'role' => $user->role,
                    'user_device_id' => $userDevice->id,
                ],
                'status' => 'online',
                'last_seen' => now(),
            ]);

            return $oldDevice->fresh();
        }

        return Device::updateOrCreate(
            ['device_id' => $newDeviceId],
            [
                'user_id' => $user->id,
                'fcm_token' => $userDevice->fcm_token,
                'name' => $existing?->name ?? $user->name . "'s phone",
                'metadata' => [
                    'user_fingerprint' => $userDevice->fingerprint,
                    'user_email' => $user->email,
                    'role' => $user->role,
                    'user_device_id' => $userDevice->id,
                ],
                'status' => 'online',
                'last_seen' => now(),
            ]
        );
    }
}
