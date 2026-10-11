<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\StreamPlay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    public function devices(Request $request)
    {
        $user = $request->user();

        $devices = $user->devices()
            ->withCount([
                'sessions as streams_today' => fn ($q) => $q
                    ->where('status', 'rewarded')
                    ->whereDate('completed_at', today()),
            ])
            ->orderByDesc('last_seen')
            ->get()
            ->map(fn (Device $device) => $this->devicePayload($device));

        $summary = [
            'total_devices' => $devices->count(),
            'online_devices' => $devices->whereIn('status', ['online', 'streaming'])->count(),
            'streaming_devices' => $devices->where('status', 'streaming')->count(),
            'streams_today' => (int) $devices->sum('streams_today'),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'devices' => $devices,
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $user = $request->user();

        if (! $device->user_id || $device->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $device->update(['name' => trim($request->name)]);

        return response()->json([
            'success' => true,
            'message' => 'Device renamed.',
            'device' => $this->devicePayload($device->fresh()),
        ]);
    }

    public function earnings(Request $request)
    {
        $user = $request->user();

        $streams = $user->streamPlays()
            ->with(['assignment.device:id,name,device_id', 'track:id,title'])
            ->where('status', StreamPlay::STATUS_REWARDED)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (StreamPlay $play) => [
                'id' => $play->id,
                'reward_amount' => (int) $play->reward_amount,
                'platform' => $play->platform,
                'media_url' => $play->media_url,
                'media_title' => $play->assignment?->media_title ?? $play->track?->title,
                'device_name' => $play->assignment?->device?->name,
                'created_at' => $play->created_at?->toIso8601String(),
            ]);

        $rewarded = StreamPlay::STATUS_REWARDED;
        $today = today();

        $summary = [
            'streams_today' => (int) $user->streamPlays()->where('status', $rewarded)->whereDate('created_at', $today)->count(),
            'total_streams' => (int) $user->streamPlays()->where('status', $rewarded)->count(),
            'total_earned' => (int) $user->streamPlays()->where('status', $rewarded)->sum('reward_amount'),
            'earned_today' => (int) $user->streamPlays()->where('status', $rewarded)->whereDate('created_at', $today)->sum('reward_amount'),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'streams' => $streams,
        ]);
    }

    private function devicePayload(Device $device): array
    {
        return [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'name' => $device->name,
            'status' => $device->status,
            'last_seen' => $device->last_seen?->toIso8601String(),
            'platform' => $device->metadata['platform'] ?? null,
            'streams_today' => (int) ($device->streams_today ?? 0),
            'total_streams' => (int) $device->total_streams,
            'total_earned' => (int) $device->total_earned,
        ];
    }
}