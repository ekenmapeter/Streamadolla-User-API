<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\RentalRewardJob;
use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\DeviceRental;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\RentalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RentalController extends Controller
{
    public function offer(Request $request, RentalService $rentals)
    {
        $user = $request->user();
        $active = DeviceRental::active()->ownedBy($user->id)->first();

        return response()->json([
            'success' => true,
            'rental' => [
                'enabled' => $rentals->rentalEnabled(),
                'rate_per_stream' => $rentals->ratePerStream(),
                'status' => $active ? $active->status : 'none',
                'has_fcm_device' => UserDevice::where('user_id', $user->id)->whereNotNull('fcm_token')->exists(),
                'active' => $active ? $this->rentalPayload($active) : null,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $rentals = DeviceRental::ownedBy($user->id)
            ->withCount('streamPlays')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (DeviceRental $r) => $this->rentalPayload($r));

        $todayStreams = $user->streamPlays()
            ->where('status', \App\Models\StreamPlay::STATUS_REWARDED)
            ->whereDate('created_at', today())
            ->count();

        return response()->json([
            'success' => true,
            'today_streams' => $todayStreams,
            'rentals' => $rentals,
        ]);
    }

    public function start(Request $request, RentalService $rentals)
    {
        $user = $request->user();

        if (! $rentals->rentalEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Device rental is currently disabled.',
            ], 422);
        }

        $existing = DeviceRental::active()->ownedBy($user->id)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active rental.',
                'rental' => $this->rentalPayload($existing),
            ], 422);
        }

        $userDevice = UserDevice::where('user_id', $user->id)
            ->whereNotNull('fcm_token')
            ->orderByDesc('last_seen_at')
            ->first();

        if (! $userDevice) {
            return response()->json([
                'success' => false,
                'message' => 'Register your phone first (enable notifications in the app).',
            ], 422);
        }

        $device = $rentals->registerUserDevice($user, $userDevice);

        $rental = DeviceRental::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'status' => DeviceRental::STATUS_ACTIVE,
            'rate_per_stream' => $rentals->ratePerStream(),
            'started_at' => now(),
        ]);

        $userDevice->update(['free_move' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Your device is now rented to the platform. You will be paid per stream.',
            'rental' => $this->rentalPayload($rental),
        ], 201);
    }

    public function pause(Request $request, RentalService $rentals)
    {
        $rental = $this->activeRentalFor($request->user());

        if (! $rental) {
            return response()->json(['success' => false, 'message' => 'No active rental.'], 422);
        }

        if ($rental->device) {
            $rentals->stopDevicePlayback($rental->device);
        }

        $rental->update(['status' => DeviceRental::STATUS_PAUSED]);

        return response()->json([
            'success' => true,
            'message' => 'Rental paused. Your phone will not receive new tracks.',
            'rental' => $this->rentalPayload($rental->fresh()),
        ]);
    }

    public function stop(Request $request, RentalService $rentals)
    {
        $rental = DeviceRental::ownedBy($request->user()->id)
            ->whereIn('status', [DeviceRental::STATUS_ACTIVE, DeviceRental::STATUS_PAUSED])
            ->latest()
            ->first();

        if (! $rental) {
            return response()->json(['success' => false, 'message' => 'No active rental.'], 422);
        }

        $rentals->terminateRental($rental);

        return response()->json([
            'success' => true,
            'message' => 'Rental ended. Thanks for renting your device!',
            'rental' => $this->rentalPayload($rental->fresh()),
        ]);
    }

    public function assignmentStatus(Request $request, DeviceAssignment $assignment, RentalService $rentals)
    {
        $user = $request->user();

        $device = $assignment->device;

        if (! $device || $device->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:playing,paused,stopped,failed,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if (in_array($request->status, ['stopped', 'completed']) && $assignment->started_at && (now()->timestamp - $assignment->started_at->timestamp) < 10) {
            return response()->json([
                'success' => true,
                'message' => 'Ignored stale status update due to recent track transition',
            ]);
        }

        $update = ['status' => $request->status];

        if ($request->status === 'playing' && ! $assignment->started_at) {
            $update['started_at'] = now();
        }

        $assignment->update($update);

        $device->update([
            'status' => in_array($request->status, ['playing', 'paused']) ? 'streaming' : 'online',
            'last_seen' => now(),
        ]);

        if ($request->status === 'completed' && $assignment->track_id) {
            RentalRewardJob::dispatch($assignment->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment status updated',
            'assignment' => $this->assignmentPayload($assignment->fresh()),
        ]);
    }

    private function activeRentalFor(User $user): ?DeviceRental
    {
        return DeviceRental::active()->ownedBy($user->id)->first();
    }

    private function rentalPayload(DeviceRental $rental): array
    {
        return [
            'id' => $rental->id,
            'status' => $rental->status,
            'rate_per_stream' => (int) $rental->rate_per_stream,
            'started_at' => $rental->started_at?->toIso8601String(),
            'terminated_at' => $rental->terminated_at?->toIso8601String(),
            'total_streams' => (int) $rental->total_streams,
            'total_earned' => (int) $rental->total_earned,
        ];
    }

    private function assignmentPayload(DeviceAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'status' => $assignment->status,
            'platform' => $assignment->platform,
            'media_url' => $assignment->media_url,
            'media_title' => $assignment->media_title,
            'track_id' => $assignment->track_id,
            'started_at' => $assignment->started_at?->toIso8601String(),
        ];
    }
}