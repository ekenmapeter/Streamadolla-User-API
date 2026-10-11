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
    public function syncFleetDevice(User $user, UserDevice $userDevice): ?Device
    {
        if (! $user->isVendor()) {
            return null;
        }

        $deviceId = 'user-' . $userDevice->fingerprint;

        Device::where('fcm_token', $userDevice->fcm_token)
            ->where('device_id', '!=', $deviceId)
            ->update(['fcm_token' => null]);

        $existing = Device::where('device_id', $deviceId)->first();

        return Device::updateOrCreate(
            ['device_id' => $deviceId],
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