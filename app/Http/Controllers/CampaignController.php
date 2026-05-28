<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignTrack;
use App\Models\Device;
use App\Models\DeviceAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;

class CampaignController extends Controller
{
    public function __construct()
    {
    }

    /**
     * List all campaigns.
     * GET /api/campaigns
     */
    public function index()
    {
        $campaigns = Campaign::with('tracks')->withCount('assignments')->get();

        return response()->json([
            'success'   => true,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Create a new campaign with tracks.
     * POST /api/campaigns
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'platform' => 'required|in:spotify,youtube,apple_music',
            'tracks'   => 'required|array|min:1',
            'tracks.*.media_url'          => 'required|string',
            'tracks.*.media_title'        => 'nullable|string|max:255',
            'tracks.*.duration_seconds'   => 'nullable|integer|min:30|max:7200',
        ]);

        $campaign = Campaign::create([
            'name'     => $request->name,
            'platform' => $request->platform,
        ]);

        foreach ($request->tracks as $i => $trackData) {
            CampaignTrack::create([
                'campaign_id'      => $campaign->id,
                'media_url'        => $trackData['media_url'],
                'media_title'      => $trackData['media_title'] ?? null,
                'position_order'   => $i,
                'duration_seconds' => $trackData['duration_seconds'] ?? 180,
            ]);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Campaign created with ' . count($request->tracks) . ' tracks',
            'campaign' => $campaign->load('tracks'),
        ]);
    }

    /**
     * Update a campaign (name, tracks).
     * PUT /api/campaigns/{campaign}
     */
    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'platform' => 'sometimes|in:spotify,youtube,apple_music',
            'tracks'   => 'sometimes|array|min:1',
            'tracks.*.media_url'          => 'required_with:tracks|string',
            'tracks.*.media_title'        => 'nullable|string|max:255',
            'tracks.*.duration_seconds'   => 'nullable|integer|min:30|max:7200',
        ]);

        if ($request->has('name')) {
            $campaign->name = $request->name;
        }
        if ($request->has('platform')) {
            $campaign->platform = $request->platform;
        }
        $campaign->save();

        // If tracks are provided, replace them all
        if ($request->has('tracks')) {
            $campaign->tracks()->delete();
            foreach ($request->tracks as $i => $trackData) {
                CampaignTrack::create([
                    'campaign_id'      => $campaign->id,
                    'media_url'        => $trackData['media_url'],
                    'media_title'      => $trackData['media_title'] ?? null,
                    'position_order'   => $i,
                    'duration_seconds' => $trackData['duration_seconds'] ?? 180,
                ]);
            }
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Campaign updated',
            'campaign' => $campaign->load('tracks'),
        ]);
    }

    /**
     * Delete a campaign. Stops all active assignments for it first.
     * DELETE /api/campaigns/{campaign}
     */
    public function destroy(Campaign $campaign)
    {
        // Stop all active assignments for this campaign
        DeviceAssignment::where('campaign_id', $campaign->id)
            ->whereIn('status', ['pending', 'playing', 'paused'])
            ->update(['status' => 'stopped']);

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted',
        ]);
    }

    /**
     * Deploy a campaign to selected devices.
     * POST /api/campaigns/{campaign}/deploy
     *
     * Body: { "device_ids": [1, 2, 3] }
     */
    public function deploy(Request $request, Campaign $campaign)
    {
        $request->validate([
            'device_ids' => 'required|array|min:1',
            'device_ids.*' => 'exists:devices,id',
        ]);

        $tracks = $campaign->tracks()->orderBy('position_order')->get();
        if ($tracks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign has no tracks',
            ], 422);
        }

        $sendResults = ['successful' => 0, 'failed' => 0, 'errors' => []];
        $assignments = [];

        $deviceIds = $request->device_ids;
        $deviceCount = count($deviceIds);
        $trackCount = $tracks->count();
        $baseItemsPerDevice = floor($trackCount / $deviceCount);

        foreach ($deviceIds as $index => $deviceId) {
            $device = Device::find($deviceId);
            if (!$device || !$device->fcm_token) continue;

            // Stop any existing active assignments for this device
            DeviceAssignment::where('device_id', $device->id)
                ->whereIn('status', ['pending', 'playing', 'paused'])
                ->update(['status' => 'stopped']);

            $firstTrack = $tracks->first();
            
            // Create assignment
            $assignment = DeviceAssignment::create([
                'device_id'         => $device->id,
                'campaign_id'       => $campaign->id,
                'campaign_track_id' => $firstTrack->id,
                'platform'          => $campaign->platform,
                'media_url'         => $firstTrack->media_url,
                'media_title'       => $firstTrack->media_title ?? $campaign->name . ' - ' . $firstTrack->media_url,
                'status'            => 'pending',
                'assigned_at'       => now(),
            ]);

            // Send FCM to start playing first track
            try {
                $command = $campaign->platform === 'spotify'
                    ? 'play_spotify'
                    : ($campaign->platform === 'apple_music' ? 'play_applemusic' : 'play_youtube');

                $message = CloudMessage::withTarget('token', $device->fcm_token)
                    ->withData([
                        'command'       => $command,
                        'track_id'      => $firstTrack->media_url,
                        'youtube_url'   => $firstTrack->media_url,
                        'media_url'     => $firstTrack->media_url,
                        'proxy_url'     => (string)($device->proxy_url ?? ''), // Automated proxy setup
                        'action'        => 'play',
                        'platform'      => $campaign->platform,
                        'assignment_id' => (string) $assignment->id,
                        'timestamp'     => (string) now()->timestamp,
                        'command_id'    => Str::uuid()->toString(),
                    ])
                    ->withAndroidConfig(['priority' => 'high', 'ttl' => '3600s'])
                    ->withApnsConfig([
                        'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                        'payload' => ['aps' => ['content-available' => 1]]
                    ]);

                $messaging = $this->getMessaging();
                if (!$messaging) throw new \Exception("Firebase Messaging initialization failed.");
                $messaging->send($message);

                $assignment->update(['status' => 'playing', 'started_at' => now()]);
                $device->update(['status' => 'streaming', 'last_seen' => now()]);
                $sendResults['successful']++;
                $assignments[] = $assignment;
            } catch (\Exception $e) {
                $assignment->update(['status' => 'failed']);
                $sendResults['failed']++;
                $sendResults['errors'][] = [
                    'device_id' => $device->id,
                    'error'     => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success'     => true,
            'message'     => "Campaign '{$campaign->name}' deployed to {$sendResults['successful']} device(s)",
            'assignments' => $assignments,
            'stats'       => $sendResults,
        ]);
    }

    private function getMessaging()
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

        if (!file_exists($credentialsPath)) return null;

        try {
            return (new Factory)->withServiceAccount($credentialsPath)->createMessaging();
        } catch (\Exception $e) {
            return null;
        }
    }
}
