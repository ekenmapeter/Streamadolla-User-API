<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class AssignmentController extends Controller
{
    private $messaging;

    public static function platformCommand(string $platform): string
    {
        return match ($platform) {
            'spotify' => 'play_spotify',
            'apple_music' => 'play_applemusic',
            'tidal' => 'play_tidal',
            'iheart', 'iheartradio' => 'play_iheart',
            'audiomack' => 'play_audiomack',
            default => 'play_youtube',
        };
    }

    public function __construct()
    {
        $this->messaging = app(\App\Services\FirebaseMessagingService::class)->messaging();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_ids'   => 'required|array|min:1',
            'device_ids.*' => 'exists:devices,id',
            'platform'     => 'required|in:spotify,youtube,apple_music,tidal,iheart,audiomack',
            'media_url'    => 'required|string',
            'media_title'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $devices = Device::whereIn('id', $request->device_ids)->get();
        $assignments = [];
        $sendResults = ['successful' => 0, 'failed' => 0, 'errors' => []];

        foreach ($devices as $device) {
            DeviceAssignment::forDevice($device->id)
                ->active()
                ->update(['status' => 'stopped']);

            $assignment = DeviceAssignment::create([
                'device_id'   => $device->id,
                'platform'    => $request->platform,
                'media_url'   => $request->media_url,
                'media_title' => $request->media_title,
                'status'      => 'pending',
                'assigned_at' => now(),
            ]);

            $assignments[] = $assignment;

            if ($device->fcm_token) {
                try {
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'         => self::platformCommand($request->platform),
                            'track_id'        => $request->media_url,
                            'youtube_url'     => $request->media_url,
                            'apple_music_url' => $request->media_url,
                            'media_url'       => $request->media_url,
                            'action'          => 'play',
                            'platform'        => $request->platform,
                            'assignment_id'   => (string) $assignment->id,
                            'timestamp'       => (string) now()->timestamp,
                            'command_id'      => Str::uuid()->toString(),
                        ])
                        ->withAndroidConfig(['priority' => 'high', 'ttl' => '3600s'])
                        ->withApnsConfig([
                            'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                            'payload' => ['aps' => ['content-available' => 1]]
                        ]);

                    $this->messaging->send($message);

                    $assignment->update(['status' => 'playing', 'started_at' => now()]);
                    $device->update(['status' => 'streaming', 'last_seen' => now()]);
                    $sendResults['successful']++;
                } catch (\Exception $e) {
                    $assignment->update(['status' => 'failed']);
                    $sendResults['failed']++;
                    $sendResults['errors'][] = [
                        'device_id' => $device->id,
                        'error'     => $e->getMessage()
                    ];
                }
            }
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Assignments created and commands sent',
            'assignments' => $assignments,
            'stats'       => $sendResults,
        ]);
    }

    public function index(Request $request)
    {
        $query = DeviceAssignment::with('device')->orderBy('assigned_at', 'desc');

        if ($request->status === 'active') {
            $query->active();
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->device_id) {
            $query->forDevice($request->device_id);
        }

        $assignments = $query->limit(100)->get();

        return response()->json([
            'success'     => true,
            'assignments' => $assignments,
        ]);
    }

    public function updateStatus(Request $request, DeviceAssignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:playing,paused,stopped,failed,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if (in_array($request->status, ['stopped', 'completed']) && $assignment->started_at && now()->diffInSeconds($assignment->started_at) < 10) {
            return response()->json([
                'success'    => true,
                'message'    => 'Ignored stale status update due to recent track transition',
                'assignment' => $assignment,
            ]);
        }

        $update = ['status' => $request->status];

        if ($request->status === 'playing' && !$assignment->started_at) {
            $update['started_at'] = now();
        }

        $assignment->update($update);

        $newDeviceStatus = in_array($request->status, ['playing', 'paused'])
            ? 'streaming'
            : 'online';

        $assignment->device->update([
            'status'    => $newDeviceStatus,
            'last_seen' => now(),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Assignment status updated',
            'assignment' => $assignment->fresh(),
        ]);
    }

    public function destroy(DeviceAssignment $assignment)
    {
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
                    ->withAndroidConfig(['priority' => 'high'])
                    ->withApnsConfig([
                        'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                        'payload' => ['aps' => ['content-available' => 1]]
                    ]);

                $this->messaging->send($message);
            } catch (\Exception $e) {
            }
        }

        $assignment->delete();

        if ($device && $device->assignments()->active()->count() === 0) {
            $device->update(['status' => 'online', 'last_seen' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted',
        ]);
    }

    public function control(Request $request, DeviceAssignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:play,pause,stop',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $device = $assignment->device;
        $action = $request->action;

        if (!$device || !$device->fcm_token) {
            return response()->json(['success' => false, 'message' => 'Device not found or no FCM token'], 400);
        }

        try {
            $data = [
                'action'        => $action,
                'assignment_id' => (string) $assignment->id,
                'timestamp'     => (string) now()->timestamp,
                'command_id'    => Str::uuid()->toString(),
            ];

            if ($action === 'play') {
                $data['command']     = self::platformCommand($assignment->platform);
                $data['platform']    = $assignment->platform;
                $data['media_url']   = $assignment->media_url;
                $data['track_id']        = $assignment->media_url;
                $data['youtube_url']     = $assignment->media_url;
                $data['apple_music_url'] = $assignment->media_url;
            } else {
                $data['command'] = $action;
            }

            $message = CloudMessage::withTarget('token', $device->fcm_token)
                ->withData($data)
                ->withAndroidConfig(['priority' => 'high'])
                ->withApnsConfig([
                    'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                    'payload' => ['aps' => ['content-available' => 1]]
                ]);

            $this->messaging->send($message);

            $newStatus = match($action) {
                'play'  => 'playing',
                'pause' => 'paused',
                'stop'  => 'stopped',
            };
            $assignment->update(['status' => $newStatus]);

            $deviceStatus = $action === 'stop' ? 'online' : 'streaming';
            $device->update(['status' => $deviceStatus, 'last_seen' => now()]);

            return response()->json([
                'success' => true,
                'message' => "Command '$action' sent to device",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function nextTrack(DeviceAssignment $assignment)
    {
        $device = $assignment->device;
        if (!$device || !$device->fcm_token) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 400);
        }

        if ($assignment->campaign_id && $assignment->campaign_track_id) {
            $campaign = $assignment->campaign;

            $tracks = $campaign->tracks()->orderBy('position_order')->get();

            // Same deterministic shuffle as ExecuteCampaigns
            $assignedAt = $assignment->assigned_at ?? $assignment->created_at ?? now();
            $seed = "{$assignment->device_id}_{$assignment->campaign_id}_" . (int)$assignedAt->timestamp;
            $shuffledTracks = $tracks->sortBy(fn($t) => md5($seed . $t->id))->values();

            $trackCount = $shuffledTracks->count();
            $currentIndex = (int)($assignment->shuffled_index ?? 0);
            $isInterstitial = (bool)$assignment->is_interstitial;
            $cycleCount = (int)($assignment->cycle_track_count ?? 0);

            if ($currentIndex >= $trackCount) {
                $currentIndex = 0;
            }

            // 1. Send stop command first
            try {
                $stopMsg = CloudMessage::withTarget('token', $device->fcm_token)
                    ->withData([
                        'command'       => 'stop',
                        'action'        => 'stop',
                        'assignment_id' => (string) $assignment->id,
                        'timestamp'     => (string) now()->timestamp,
                        'command_id'    => Str::uuid()->toString(),
                    ])
                    ->withAndroidConfig(['priority' => 'high']);
                $this->messaging->send($stopMsg);
            } catch (\Exception $e) {}

            sleep(3);

            $campaignMediaUrl = $campaign->interstitial_media_url;
            $interstitialInterval = $campaign->interstitial_every;

            if ($isInterstitial) {
                // Interstitial finished → resume playlist at next track
                $nextIndex = ($currentIndex < $trackCount - 1) ? $currentIndex + 1 : 0;
                $nextTrack = $shuffledTracks->get($nextIndex);

                $assignment->update([
                    'campaign_track_id' => $nextTrack->id,
                    'shuffled_index'    => $nextIndex,
                    'cycle_track_count' => 0,
                    'is_interstitial'   => false,
                    'media_url'         => $nextTrack->media_url,
                    'media_title'       => $nextTrack->media_title ?? $campaign->name . ' - ' . $nextTrack->media_url,
                    'started_at'        => now(),
                    'status'            => 'playing',
                ]);

                try {
                    $command = self::platformCommand($assignment->platform);
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'         => $command,
                            'track_id'        => $nextTrack->media_url,
                            'youtube_url'     => $nextTrack->media_url,
                            'apple_music_url' => $nextTrack->media_url,
                            'media_url'       => $nextTrack->media_url,
                            'action'          => 'play',
                            'platform'        => $assignment->platform,
                            'assignment_id'   => (string) $assignment->id,
                            'timestamp'       => (string) now()->timestamp,
                            'command_id'      => Str::uuid()->toString(),
                        ])
                        ->withAndroidConfig(['priority' => 'high']);

                    $this->messaging->send($message);
                    return response()->json(['success' => true, 'message' => 'Resumed from interstitial']);
                } catch (\Exception $e) {
                    $assignment->update(['status' => 'failed']);
                    return response()->json(['success' => false, 'message' => $e->getMessage()]);
                }

            } elseif ($interstitialInterval && $campaignMediaUrl && $cycleCount >= $interstitialInterval) {
                // Play interstitial instead of next playlist track
                $assignment->update([
                    'is_interstitial'   => true,
                    'media_url'         => $campaignMediaUrl,
                    'media_title'       => 'Interstitial - ' . basename($campaignMediaUrl),
                    'started_at'        => now(),
                    'status'            => 'playing',
                ]);

                try {
                    $command = self::platformCommand($assignment->platform);
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'         => $command,
                            'track_id'        => $campaignMediaUrl,
                            'youtube_url'     => $campaignMediaUrl,
                            'apple_music_url' => $campaignMediaUrl,
                            'media_url'       => $campaignMediaUrl,
                            'action'          => 'play',
                            'platform'        => $assignment->platform,
                            'assignment_id'   => (string) $assignment->id,
                            'timestamp'       => (string) now()->timestamp,
                            'command_id'      => Str::uuid()->toString(),
                        ])
                        ->withAndroidConfig(['priority' => 'high']);

                    $this->messaging->send($message);
                    return response()->json(['success' => true, 'message' => 'Interstitial playing']);
                } catch (\Exception $e) {
                    $assignment->update(['status' => 'failed']);
                    return response()->json(['success' => false, 'message' => $e->getMessage()]);
                }

            } else {
                // Normal track advance
                $nextIndex = ($currentIndex < $trackCount - 1) ? $currentIndex + 1 : 0;
                $nextTrack = $shuffledTracks->get($nextIndex);

                $assignment->update([
                    'campaign_track_id' => $nextTrack->id,
                    'shuffled_index'    => $nextIndex,
                    'cycle_track_count' => $cycleCount + 1,
                    'is_interstitial'   => false,
                    'media_url'         => $nextTrack->media_url,
                    'media_title'       => $nextTrack->media_title ?? $campaign->name . ' - ' . $nextTrack->media_url,
                    'started_at'        => now(),
                    'status'            => 'playing',
                ]);

                try {
                    $command = self::platformCommand($assignment->platform);
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'         => $command,
                            'track_id'        => $nextTrack->media_url,
                            'youtube_url'     => $nextTrack->media_url,
                            'apple_music_url' => $nextTrack->media_url,
                            'media_url'       => $nextTrack->media_url,
                            'action'          => 'play',
                            'platform'        => $assignment->platform,
                            'assignment_id'   => (string) $assignment->id,
                            'timestamp'       => (string) now()->timestamp,
                            'command_id'      => Str::uuid()->toString(),
                        ])
                        ->withAndroidConfig(['priority' => 'high']);

                    $this->messaging->send($message);
                    return response()->json(['success' => true, 'message' => 'Next track sent']);
                } catch (\Exception $e) {
                    $assignment->update(['status' => 'failed']);
                    return response()->json(['success' => false, 'message' => $e->getMessage()]);
                }
            }
        }

        $assignment->update(['status' => 'completed']);
        $device->update(['status' => 'online']);

        try {
            $message = CloudMessage::withTarget('token', $device->fcm_token)
                ->withData(['command' => 'stop', 'action' => 'stop', 'assignment_id' => (string) $assignment->id])
                ->withAndroidConfig(['priority' => 'high']);
            $this->messaging->send($message);
        } catch (\Exception $e) {}

        return response()->json(['success' => true, 'message' => 'Assignment completed']);
    }
}
