<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class CommandController extends Controller
{
    private $messaging;

    public function __construct()
    {
        $credentialsPath = base_path(config('firebase.credentials'));

        // Smart search: If the configured file is missing, look for ANY firebase json in storage/app
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
            throw new \RuntimeException("Firebase credentials file not found. We checked the config path AND searched storage/app. Please ensure your Firebase JSON file is in storage/app.");
        }

        $factory = (new Factory)
            ->withServiceAccount($credentialsPath);

        $this->messaging = $factory->createMessaging();
    }

    // Send command to ALL online devices
    public function sendToAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:play,pause,stop,open',
            'platform' => 'nullable|in:spotify,youtube,apple_music',
            'spotify_uri' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'apple_music_url' => 'nullable|string',
            'media_url' => 'nullable|string',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get all active devices (online or already streaming)
        $devices = Device::whereIn('status', ['online', 'streaming'])->get();

        if ($devices->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No online devices available'
            ], 400);
        }

        // Collect all FCM tokens
        $tokens = $devices->pluck('fcm_token')->filter()->toArray();

        // Create the FCM message with silent data and high priority
        $platform = $request->platform ?? 'spotify';
        $mediaUrl = $request->media_url;
        
        if (!$mediaUrl) {
            if ($platform === 'spotify') {
                $mediaUrl = $request->spotify_uri;
            } elseif ($platform === 'apple_music') {
                $mediaUrl = $request->apple_music_url;
            } else {
                $mediaUrl = $request->youtube_url;
            }
        }
        
        // Final fallback if still empty
        $mediaUrl = $mediaUrl ?? '';
        
        $action = $request->action;

        $message = CloudMessage::new()
            ->withData([
                'command' => $platform == 'spotify' ? 'play_spotify' : ($platform == 'apple_music' ? 'play_applemusic' : ($platform == 'youtube' ? 'play_youtube' : $action)),
                'track_id' => $mediaUrl, // Aliases for different naming conventions
                'youtube_url' => $mediaUrl,
                'media_url' => $mediaUrl,
                'action' => $action,
                'platform' => $platform,
                'timestamp' => now()->timestamp,
                'command_id' => Str::uuid()->toString()
            ])
            ->withAndroidConfig([
                'priority' => 'high',
                'ttl' => '3600s'
            ])
            ->withApnsConfig([
                'headers' => [
                    'apns-priority' => '10',
                    'apns-push-type' => 'background'
                ],
                'payload' => [
                    'aps' => [
                        'content-available' => 1
                    ]
                ]
            ]);

        // Send multicast message
        $report = $this->messaging->sendMulticast($message, $tokens);

        // Update devices status based on action
        $newStatus = ($action === 'stop' || $action === 'pause') ? 'online' : 'streaming';
        
        Device::whereIn('id', $devices->pluck('id'))
            ->update(['status' => $newStatus, 'last_seen' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Command sent to devices',
            'stats' => [
                'total_devices' => count($tokens),
                'successful' => $report->successes()->count(),
                'failed' => $report->failures()->count()
            ]
        ]);
    }

    // Send command to specific device
    public function sendToDevice(Request $request, Device $device)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:play,pause,stop,open',
            'platform' => 'nullable|in:spotify,youtube,apple_music',
            'spotify_uri' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'apple_music_url' => 'nullable|string',
            'media_url' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $platform = $request->platform ?? 'spotify';
        $mediaUrl = $request->media_url;
        
        if (!$mediaUrl) {
            if ($platform === 'spotify') {
                $mediaUrl = $request->spotify_uri;
            } elseif ($platform === 'apple_music') {
                $mediaUrl = $request->apple_music_url;
            } else {
                $mediaUrl = $request->youtube_url;
            }
        }
        
        $mediaUrl = $mediaUrl ?? '';
        $action = $request->action;

        $message = CloudMessage::withTarget('token', $device->fcm_token)
            ->withData([
                'command' => $platform == 'spotify' ? 'play_spotify' : ($platform == 'apple_music' ? 'play_applemusic' : ($platform == 'youtube' ? 'play_youtube' : $action)),
                'track_id' => $mediaUrl,
                'youtube_url' => $mediaUrl,
                'media_url' => $mediaUrl,
                'action' => $action,
                'platform' => $platform,
                'timestamp' => now()->timestamp,
                'command_id' => Str::uuid()->toString()
            ])
            ->withAndroidConfig(['priority' => 'high'])
            ->withApnsConfig([
                'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                'payload' => ['aps' => ['content-available' => 1]]
            ]);

        try {
            $this->messaging->send($message);

            $newStatus = ($action === 'stop' || $action === 'pause') ? 'online' : 'streaming';
            $device->update([
                'status' => $newStatus,
                'last_seen' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Command sent to device'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send command: ' . $e->getMessage()
            ], 500);
        }
    }

    // Send command to specific group of devices
    public function sendToGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:devices,id',
            'action' => 'required|in:play,pause,stop,open',
            'platform' => 'nullable|in:spotify,youtube,apple_music',
            'spotify_uri' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'apple_music_url' => 'nullable|string',
            'media_url' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $devices = Device::whereIn('id', $request->device_ids)->get();
        $tokens = $devices->pluck('fcm_token')->filter()->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid devices found'
            ], 400);
        }

        $platform = $request->platform ?? 'spotify';
        $mediaUrl = $request->media_url;
        
        if (!$mediaUrl) {
            if ($platform === 'spotify') {
                $mediaUrl = $request->spotify_uri;
            } elseif ($platform === 'apple_music') {
                $mediaUrl = $request->apple_music_url;
            } else {
                $mediaUrl = $request->youtube_url;
            }
        }
        
        $mediaUrl = $mediaUrl ?? '';
        $action = $request->action;

        $message = CloudMessage::new()
            ->withData([
                'command' => $platform == 'spotify' ? 'play_spotify' : ($platform == 'apple_music' ? 'play_applemusic' : ($platform == 'youtube' ? 'play_youtube' : $action)),
                'track_id' => $mediaUrl,
                'youtube_url' => $mediaUrl,
                'media_url' => $mediaUrl,
                'action' => $action,
                'platform' => $platform,
                'timestamp' => now()->timestamp,
                'command_id' => Str::uuid()->toString()
            ])
            ->withAndroidConfig(['priority' => 'high'])
            ->withApnsConfig([
                'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                'payload' => ['aps' => ['content-available' => 1]]
            ]);

        $report = $this->messaging->sendMulticast($message, $tokens);

        $newStatus = ($action === 'stop' || $action === 'pause') ? 'online' : 'streaming';
        Device::whereIn('id', $devices->pluck('id'))
            ->update(['status' => $newStatus, 'last_seen' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Command sent to device group',
            'stats' => [
                'total_devices' => count($tokens),
                'successful' => $report->successes()->count(),
                'failed' => $report->failures()->count()
            ]
        ]);
    }
}
