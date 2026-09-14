<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\DeviceRental;
use App\Models\User;
use App\Models\UserDevice;

class RentalService
{
    public const STALE_MINUTES = 15;

    public function rentalEnabled(): bool
    {
        return (bool) (AppSetting::get('rental_enabled', true) ?? true);
    }

    public function ratePerStream(): int
    {
        return max(1, (int) (AppSetting::get('rental_rate_per_stream', 5) ?? 5));
    }

    public function registerUserDevice(User $user, UserDevice $userDevice): Device
    {
        $deviceId = 'user-' . $userDevice->fingerprint;

        Device::where('fcm_token', $userDevice->fcm_token)
            ->where('device_id', '!=', $deviceId)
            ->update(['fcm_token' => null]);

        return Device::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'user_id' => $user->id,
                'fcm_token' => $userDevice->fcm_token,
                'name' => $user->name . "'s phone",
                'metadata' => [
                    'user_fingerprint' => $userDevice->fingerprint,
                    'user_email' => $user->email,
                ],
                'status' => 'online',
                'last_seen' => now(),
            ]
        );
    }

    public function stopDevicePlayback(Device $device): void
    {
        $active = DeviceAssignment::with('device')
            ->where('device_id', $device->id)
            ->whereIn('status', ['pending', 'playing', 'paused'])
            ->get();

        $messaging = app(FirebaseMessagingService::class);

        foreach ($active as $assignment) {
            if ($device->fcm_token) {
                $messaging->sendToToken($device->fcm_token, [
                    'command' => 'stop',
                    'action' => 'stop',
                    'assignment_id' => (string) $assignment->id,
                ]);
            }
            $assignment->update(['status' => 'stopped']);
        }

        $device->update(['status' => 'online', 'last_seen' => now()]);
    }

    public function terminateRental(DeviceRental $rental, bool $markDeviceOffline = true): void
    {
        if ($rental->device) {
            $this->stopDevicePlayback($rental->device);
            if ($markDeviceOffline) {
                $rental->device->update(['status' => 'offline']);
            }
        }

        $rental->update([
            'status' => DeviceRental::STATUS_TERMINATED,
            'terminated_at' => $rental->terminated_at ?? now(),
        ]);
    }

    public function isStale(DeviceRental $rental): bool
    {
        $device = $rental->device;

        return ! $device
            || ! $device->fcm_token
            || ! $device->last_seen
            || $device->last_seen->lt(now()->subMinutes(self::STALE_MINUTES));
    }
}