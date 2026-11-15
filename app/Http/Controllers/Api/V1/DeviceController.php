<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    /**
     * How long a device must have been quiet before its fingerprint can be
     * migrated by another install. A live phone must never lose its identity
     * because a cloned app installation claims the same previous ID.
     */
    private const REKEY_STALE_MINUTES = 15;

    public function register(Request $request, \App\Services\VendorDeviceService $vendorDevices)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'fingerprint' => 'required|string|max:255',
            'previous_fingerprint' => 'nullable|string|max:255',
            'fcm_token' => 'required|string',
            'platform' => 'sometimes|in:android,ios',
            'app_version' => 'nullable|string|max:30',
            'model' => 'nullable|string|max:191',
            'os_version' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fingerprint = $request->fingerprint;
        $previousFingerprint = $this->rekeyablePreviousFingerprint(
            $user,
            $fingerprint,
            $request->input('previous_fingerprint'),
        );
        $device = null;

        // A genuinely restored install may have an old random UUID. Re-key
        // that row to the phone's hardware ID instead of creating a second
        // fleet device. Never do this for a cloned install that points at a
        // live phone, or the source phone's row/name/assignments move to the
        // clone every time the app restarts.
        if ($previousFingerprint && $previousFingerprint !== $fingerprint) {
            $old = UserDevice::where('fingerprint', $previousFingerprint)
                ->where('user_id', $user->id)
                ->first();

            $alreadyExists = UserDevice::where('fingerprint', $fingerprint)->exists();

            if ($old && ! $alreadyExists) {
                $old->update([
                    'fingerprint' => $fingerprint,
                    'fcm_token' => $request->fcm_token,
                    'platform' => $request->input('platform', 'android'),
                    'app_version' => $request->input('app_version'),
                    'last_seen_at' => now(),
                ]);

                $device = $old;
            }
        }

        $device ??= UserDevice::updateOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'user_id' => $user->id,
                'fcm_token' => $request->fcm_token,
                'platform' => $request->input('platform', 'android'),
                'app_version' => $request->input('app_version'),
                'last_seen_at' => now(),
            ]
        );

        // Vendors: mirror the phone into the fleet so the command center can
        // assign campaigns straight to it. A mirroring failure must never
        // abort registration — otherwise the app stops sending heartbeats
        // and the phone disappears from the admin until it is relaunched.
        try {
            $vendorDevices->syncFleetDevice(
                $user,
                $device,
                $previousFingerprint,
                $request->only(['model', 'os_version']),
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device registered.',
            'device' => [
                'id' => $device->id,
                'fingerprint' => $device->fingerprint,
            ],
        ]);
    }

    public function heartbeat(Request $request, \App\Services\VendorDeviceService $vendorDevices)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'fingerprint' => 'required|string|max:255',
            'model' => 'nullable|string|max:191',
            'os_version' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $device = UserDevice::where('fingerprint', $request->fingerprint)
            ->where('user_id', $user->id)
            ->first();

        if (! $device) {
            return response()->json([
                'success' => true,
                'message' => 'Device not registered yet.',
                're_register' => true,
            ]);
        }

        $device->update([
            'last_seen_at' => now(),
            'free_move' => $request->boolean('free_move', $device->free_move),
        ]);

        // Keep the linked fleet device fresh while the phone is rented.
        $fleetDevice = \App\Models\Device::withTrashed()
            ->where('device_id', 'user-'.$device->fingerprint)
            ->where('user_id', $user->id)
            ->first();

        if ($fleetDevice) {
            $metadata = $fleetDevice->metadata ?? [];

            foreach (['model', 'os_version'] as $key) {
                if ($request->filled($key)) {
                    $metadata[$key] = trim((string) $request->input($key));
                }
            }

            $fleetDevice->update([
                'last_seen' => now(),
                'status' => 'online',
                'fcm_token' => $device->fcm_token,
                'metadata' => $metadata,
            ]);
        }

        // Self-heal: if the fleet row was deleted, merged, or lost its token,
        // recreate it from this heartbeat instead of waiting for a re-login.
        if ($user->isVendor() && (! $fleetDevice || $fleetDevice->trashed() || empty($fleetDevice->fcm_token))) {
            try {
                $vendorDevices->syncFleetDevice(
                    $user,
                    $device,
                    null,
                    $request->only(['model', 'os_version']),
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received.',
            // If the stored token was cleared (e.g. Firebase said it was
            // unregistered), ask the app to refresh and register again.
            're_register' => empty($device->fcm_token),
        ]);
    }

    /**
     * Only allow a previous fingerprint to be taken over when it is a legacy
     * random UUID and the old install has gone quiet. Hardware-ID based
     * fingerprints are never migrated: a cloned app installation copying the
     * source phone's prefs would otherwise steal a live device row.
     */
    private function rekeyablePreviousFingerprint(
        User $user,
        string $fingerprint,
        ?string $previous,
    ): ?string {
        if (! $previous || $previous === $fingerprint) {
            return null;
        }

        if (str_starts_with($previous, 'hw-')) {
            return null;
        }

        $old = UserDevice::where('fingerprint', $previous)
            ->where('user_id', $user->id)
            ->first();

        if (! $old) {
            return null;
        }

        $fleet = Device::withTrashed()
            ->where('device_id', 'user-'.$previous)
            ->where('user_id', $user->id)
            ->first();

        $lastSeen = collect([$old->last_seen_at, $fleet?->last_seen])
            ->filter()
            ->sortDesc()
            ->first();

        if ($lastSeen && $lastSeen->gt(now()->subMinutes(self::REKEY_STALE_MINUTES))) {
            return null;
        }

        return $previous;
    }
}
