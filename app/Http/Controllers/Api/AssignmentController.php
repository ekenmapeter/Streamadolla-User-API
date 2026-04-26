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

    public function __construct()
    {
        $credentialsPath = base_path(config('firebase.credentials'));

        if (!file_exists($credentialsPath)) {
            $storagePath = storage_path('app');
            $files = glob($storagePath . '/*.json');
            foreach ($files as $file) {
                if (str_contains($file, 'firebase-adminsdk')) {
                    $credentialsPath = $file;
                    break;
                }
            }
        }

        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException("Firebase credentials file not found.");
        }

        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Create assignment(s) — assign media to one or more devices.
     *
     * POST /api/assignments
     * {
     *   "device_ids": [1, 2, 3],       // array of device IDs
     *   "platform": "spotify",           // or "youtube"
     *   "media_url": "spotify:track:xxx",
     *   "media_title": "My Track"        // optional
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_ids'   => 'required|array|min:1',
            'device_ids.*' => 'exists:devices,id',
            'platform'     => 'required|in:spotify,youtube',
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
            // Stop any existing active assignments for this device
            DeviceAssignment::forDevice($device->id)
                ->active()
                ->update(['status' => 'stopped']);

            // Create new assignment
            $assignment = DeviceAssignment::create([
                'device_id'   => $device->id,
                'platform'    => $request->platform,
                'media_url'   => $request->media_url,
                'media_title' => $request->media_title,
                'status'      => 'pending',
                'assigned_at' => now(),
            ]);

            $assignments[] = $assignment;

            // Send FCM to this device
            if ($device->fcm_token) {
                try {
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'       => $request->platform === 'spotify' ? 'play_spotify' : 'play_youtube',
                            'track_id'      => $request->media_url,
                            'youtube_url'   => $request->media_url,
                            'media_url'     => $request->media_url,
                            'action'        => 'play',
                            'platform'      => $request->platform,
                            'assignment_id' => (string) $assignment->id,
                            'timestamp'     => (string) now()->timestamp,
                            'command_id'    => Str::uuid()->toString(),
                        ])
                        ->withAndroidConfig(['priority' => 'high', 'ttl' => '3600s'])
                        ->withApnsConfig([
                            'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                            'payload' => ['aps' => ['content-available' => 1]]
                        ]);

                    $this->messaging->send($message);

                    // Mark assignment as playing immediately since FCM was sent successfully
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

    /**
     * List all assignments, optionally filtered.
     *
     * GET /api/assignments?status=active&device_id=5
     */
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

    /**
     * Device reports a status update for an assignment.
     *
     * PUT /api/assignments/{id}/status
     * { "status": "playing" }
     */
    public function updateStatus(Request $request, DeviceAssignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:playing,paused,stopped,failed,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Prevent race condition: If a track was recently started (within 10 seconds), 
        // completely ignore delayed 'stopped' or 'completed' signals from the app's previous track.
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

        // Also update device status
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

    /**
     * Cancel/stop an assignment.
     *
     * DELETE /api/assignments/{id}
     */
    public function destroy(DeviceAssignment $assignment)
    {
        // Send stop command to the device
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
                // Log but don't fail the deletion
            }
        }

        $assignment->update(['status' => 'stopped']);

        if ($device) {
            $device->update(['status' => 'online', 'last_seen' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment cancelled',
        ]);
    }

    /**
     * Send a control command (pause/resume/stop) to an existing assignment.
     *
     * POST /api/assignments/{id}/control
     * { "action": "pause" }
     */
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

            // For play/resume, include the media info
            if ($action === 'play') {
                $data['command']     = $assignment->platform === 'spotify' ? 'play_spotify' : 'play_youtube';
                $data['platform']    = $assignment->platform;
                $data['media_url']   = $assignment->media_url;
                $data['track_id']    = $assignment->media_url;
                $data['youtube_url'] = $assignment->media_url;
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

            // Update assignment status
            $newStatus = match($action) {
                'play'  => 'playing',
                'pause' => 'paused',
                'stop'  => 'stopped',
            };
            $assignment->update(['status' => $newStatus]);

            // Update device status
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

    /**
     * Move to the next track in the campaign subset.
     */
    public function nextTrack(DeviceAssignment $assignment)
    {
        $device = $assignment->device;
        if (!$device || !$device->fcm_token) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 400);
        }

        if ($assignment->campaign_id && $assignment->campaign_track_id) {
            $currentTrack = $assignment->campaignTrack;
            $campaign = $assignment->campaign;
            
            $tracks = $campaign->tracks()->orderBy('position_order')->get();
            $shuffledTracks = $tracks->values();
            
            $currentIndex = $shuffledTracks->search(fn($t) => $t->id === $currentTrack->id);
            
            if ($currentIndex !== false) {
                // 1. Send stop command first to cleanly halt the device
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

                // Give the device 3 full seconds to process the stop command and avoid conflicts
                sleep(3);

                // 2. Determine next track with INFINITE LOOP within subset
                $nextIndex = $currentIndex + 1;
                if ($nextIndex > $assignment->subset_end_index) {
                    $nextIndex = $assignment->subset_start_index;
                }

                $nextTrack = $shuffledTracks->get($nextIndex);
                
                $assignment->update([
                    'campaign_track_id' => $nextTrack->id,
                    'media_url' => $nextTrack->media_url,
                    'media_title' => $nextTrack->media_title ?? $campaign->name . ' - ' . $nextTrack->media_url,
                    'started_at' => now(),
                    'status' => 'playing'
                ]);

                try {
                    $command = $assignment->platform === 'spotify' ? 'play_spotify' : 'play_youtube';
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'       => $command,
                            'track_id'      => $nextTrack->media_url,
                            'youtube_url'   => $nextTrack->media_url,
                            'media_url'     => $nextTrack->media_url,
                            'action'        => 'play',
                            'platform'      => $assignment->platform,
                            'assignment_id' => (string) $assignment->id,
                            'timestamp'     => (string) now()->timestamp,
                            'command_id'    => Str::uuid()->toString(),
                        ])
                        ->withAndroidConfig(['priority' => 'high']);
                    
                    $this->messaging->send($message);
                    return response()->json(['success' => true, 'message' => 'Next track sent in loop']);
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
