<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'fingerprint' => 'required|string|max:255',
            'fcm_token' => 'required|string',
            'platform' => 'sometimes|in:android,ios',
            'app_version' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $device = UserDevice::updateOrCreate(
            ['fingerprint' => $request->fingerprint],
            [
                'user_id' => $user->id,
                'fcm_token' => $request->fcm_token,
                'platform' => $request->input('platform', 'android'),
                'app_version' => $request->input('app_version'),
                'last_seen_at' => now(),
            ]
        );

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

        if ($device) {
            $device->update([
                'last_seen_at' => now(),
                'free_move' => $request->boolean('free_move', $device->free_move),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received.',
        ]);
    }
}