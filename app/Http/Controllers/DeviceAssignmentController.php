<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AssignmentController;
use App\Models\DeviceAssignment;
use App\Services\FirebaseMessagingService;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class DeviceAssignmentController extends Controller
{
    public function index()
    {
        $assignments = DeviceAssignment::with(['device', 'campaign', 'campaignTrack'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('assignments.index', compact('assignments'));
    }

    public function stats()
    {
        $assignments = DeviceAssignment::with(['campaignTrack'])
            ->whereIn('status', ['playing', 'pending', 'paused'])
            ->get()
            ->map(function ($a) {
                $duration = $a->campaignTrack ? $a->campaignTrack->duration_seconds : 180;
                $elapsed = 0;

                if ($a->started_at) {
                    $diff = now()->timestamp - $a->started_at->timestamp;
                    $elapsed = $diff > 0 ? $diff : 0;
                }

                $timeRemaining = max(0, $duration - $elapsed);

                return [
                    'id' => $a->id,
                    'status' => $a->status,
                    'remaining' => $timeRemaining,
                    'duration' => $duration,
                    'media_title' => $a->media_title,
                    'media_url' => $a->media_url,
                    'campaign_track_id' => $a->campaign_track_id,
                    'started_at' => $a->started_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'assignments' => $assignments
        ]);
    }

    public function retry(DeviceAssignment $assignment)
    {
        $assignment->loadMissing(['device.user', 'campaign']);

        $device = $assignment->device;
        $campaign = $assignment->campaign;

        if (! $device) {
            return back()->with('error', 'This assignment has no device.');
        }

        if (! in_array($assignment->status, ['stopped', 'failed'], true)) {
            return back()->with('error', 'Only stopped or failed assignments can be retried.');
        }

        // Stop anything else that may still be running on this phone.
        DeviceAssignment::where('device_id', $device->id)
            ->where('id', '!=', $assignment->id)
            ->whereIn('status', ['pending', 'playing', 'paused'])
            ->update(['status' => 'stopped']);

        $assignedAt = now();
        $mediaUrl = $assignment->media_url;
        $mediaTitle = $assignment->media_title;
        $platform = $assignment->platform;
        $duration = 180;

        if ($campaign) {
            $tracks = $campaign->tracks()->orderBy('position_order')->get();

            if ($tracks->isEmpty()) {
                return back()->with('error', 'This campaign has no tracks to retry.');
            }

            $seed = "{$device->id}_{$campaign->id}_" . (int) $assignedAt->timestamp;
            $shuffled = $tracks->sortBy(fn ($t) => md5($seed . $t->id))->values();
            $startIndex = abs(crc32($seed . '_start')) % $shuffled->count();
            $first = $shuffled[$startIndex];

            $mediaUrl = $first->media_url;
            $mediaTitle = $first->media_title ?? $campaign->name . ' - ' . $first->media_url;
            $platform = $campaign->platform;
            $duration = (int) ($first->duration_seconds ?? 180);

            $assignment->update([
                'campaign_track_id' => $first->id,
                'shuffled_index' => $startIndex,
                'cycle_track_count' => 0,
                'is_interstitial' => false,
                'platform' => $platform,
                'media_url' => $mediaUrl,
                'media_title' => $mediaTitle,
                'status' => 'pending',
                'assigned_at' => $assignedAt,
                'started_at' => null,
            ]);
        } else {
            $assignment->update([
                'cycle_track_count' => 0,
                'is_interstitial' => false,
                'status' => 'pending',
                'assigned_at' => $assignedAt,
                'started_at' => null,
            ]);
        }

        $isVendor = (bool) $device->user?->isVendor();
        $sent = false;

        if ($device->fcm_token) {
            $sent = app(FirebaseMessagingService::class)->sendToToken($device->fcm_token, [
                'command' => AssignmentController::platformCommand($platform),
                'track_id' => (string) $mediaUrl,
                'youtube_url' => (string) $mediaUrl,
                'apple_music_url' => (string) $mediaUrl,
                'media_url' => (string) $mediaUrl,
                'duration_seconds' => (string) $duration,
                'proxy_url' => (string) ($device->proxy_url ?? ''),
                'action' => 'play',
                'platform' => (string) $platform,
                'assignment_id' => (string) $assignment->id,
                'timestamp' => (string) now()->timestamp,
                'command_id' => Str::uuid()->toString(),
            ]);
        }

        // Vendor phones stay pending until they confirm the start; their
        // heartbeat/pending-assignment pull will pick this up if FCM missed.
        $assignment->update([
            'status' => $isVendor ? 'pending' : ($sent ? 'playing' : 'failed'),
            'started_at' => $isVendor ? null : ($sent ? now() : null),
        ]);

        $device->update([
            'status' => ($sent || $isVendor) ? 'streaming' : 'online',
            'last_seen' => now(),
        ]);

        return back()->with(
            'success',
            $sent
                ? 'Campaign retry sent to the device.'
                : 'Campaign retry queued. The phone will pick it up on its next check-in.'
        );
    }

    public function destroySingle(DeviceAssignment $assignment)
    {
        $device = $assignment->device;
        
        // Send stop command if it was active
        if ($assignment->isActive() && $device && $device->fcm_token) {
            try {
                $messaging = $this->getMessaging();
                if ($messaging) {
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'       => 'stop',
                            'action'        => 'stop',
                            'assignment_id' => (string) $assignment->id,
                            'timestamp'     => (string) now()->timestamp,
                            'command_id'    => \Illuminate\Support\Str::uuid()->toString(),
                        ])
                        ->withAndroidConfig(['priority' => 'high']);
                    $messaging->send($message);
                }
            } catch (\Exception $e) {}
        }

        $assignment->delete();
        
        if ($device && $device->assignments()->active()->count() === 0) {
            $device->update(['status' => 'online']);
        }

        return back()->with('success', 'Assignment deleted successfully.');
    }

    public function clearAll()
    {
        $activeAssignments = DeviceAssignment::with('device')->whereIn('status', ['playing', 'pending', 'paused'])->get();
        $messaging = $this->getMessaging();
        
        if ($messaging) {
            foreach ($activeAssignments as $assignment) {
                $device = $assignment->device;
                if ($device && $device->fcm_token) {
                    try {
                        $message = CloudMessage::withTarget('token', $device->fcm_token)
                            ->withData([
                                'command'       => 'stop',
                                'action'        => 'stop',
                                'assignment_id' => (string) $assignment->id,
                                'timestamp'     => (string) now()->timestamp,
                                'command_id'    => Str::uuid()->toString(),
                            ])
                            ->withAndroidConfig(['priority' => 'high']);
                        $messaging->send($message);
                    } catch (\Exception $e) {
                        // ignore errors on clear
                    }
                }
            }
        }

        DeviceAssignment::truncate();
        \App\Models\Device::where('status', 'streaming')->update(['status' => 'online']);
        
        return back()->with('success', 'All assignments cleared and stop commands sent to devices.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No assignments selected.');
        }

        $assignments = DeviceAssignment::with('device')->whereIn('id', $ids)->get();
        $messaging = $this->getMessaging();
        $deletedCount = 0;

        foreach ($assignments as $assignment) {
            $device = $assignment->device;

            if ($assignment->isActive() && $device && $device->fcm_token) {
                try {
                    if ($messaging) {
                        $message = CloudMessage::withTarget('token', $device->fcm_token)
                            ->withData([
                                'command'       => 'stop',
                                'action'        => 'stop',
                                'assignment_id' => (string) $assignment->id,
                                'timestamp'     => (string) now()->timestamp,
                                'command_id'    => Str::uuid()->toString(),
                            ])
                            ->withAndroidConfig(['priority' => 'high']);
                        $messaging->send($message);
                    }
                } catch (\Exception $e) {}
            }

            $assignment->delete();
            $deletedCount++;
        }

        // Reset device statuses for devices that no longer have active assignments
        $deviceIds = $assignments->pluck('device_id')->unique();
        foreach ($deviceIds as $deviceId) {
            $device = \App\Models\Device::find($deviceId);
            if ($device && $device->assignments()->active()->count() === 0) {
                $device->update(['status' => 'online']);
            }
        }

        return back()->with('success', "{$deletedCount} assignment(s) deleted successfully.");
    }

    public function runWorker()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('campaigns:execute');
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Worker executed successfully.',
                'output'  => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Worker failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getMessaging()
    {
        try {
            return app(\App\Services\FirebaseMessagingService::class)->messaging();
        } catch (\Exception $e) {
            return null;
        }
    }
}
