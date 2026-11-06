<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function register(Request $request, \App\Services\VendorDeviceService $vendorDevices)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'fingerprint' => 'required|string|max:255',
            'previous_fingerprint' => 'nullable|string|max:255',
            'fcm_token' => 'required|string',
            'platform' => 'sometimes|in:android,ios',
            'app_version' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fingerprint = $request->fingerprint;
        $previousFingerprint = $request->input('previous_fingerprint');
        $device = null;

        // A restored/cloned install may have an old UUID. Re-key that row to
        // the phone's hardware ID instead of creating a second fleet device,
        // which would leave campaigns attached to the wrong phone.
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
        // assign campaigns straight to it.
        $vendorDevices->syncFleetDevice($user, $device, $previousFingerprint);

        return response()->json([
            'success' => true,
            'message' => 'Device registered.',
            'device' => [
                'id' => $device->id,
                'fingerprint' => $device->fingerprint,
            ],
        ]);
    }

    public function heartbeat(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'fingerprint' => 'required|string|max:255',
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
        \App\Models\Device::where('device_id', 'user-' . $device->fingerprint)
            ->where('user_id', $user->id)
            ->update(['last_seen' => now(), 'status' => 'online']);

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received.',
            // If the stored token was cleared (e.g. Firebase said it was
            // unregistered), ask the app to refresh and register again.
            're_register' => empty($device->fcm_token),
        ]);
    }
}
