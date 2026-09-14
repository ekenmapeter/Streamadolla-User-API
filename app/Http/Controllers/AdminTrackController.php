<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Services\PaystackService;
use App\Services\RentalService;
use Illuminate\Http\Request;

class AdminTrackController extends Controller
{
    public function index(Request $request)
    {
        $query = Track::with('artist:id,name,email')->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                    ->orWhere('artist_name', 'like', "%{$q}%")
                    ->orWhereHas('artist', fn ($a) => $a->where('name', 'like', "%{$q}%"));
            });
        }

        $tracks = $query->withCount('streamPlays')->paginate(20)->withQueryString();

        $pending = Track::where('status', Track::STATUS_PENDING_APPROVAL)->count();

        return view('admin.tracks', compact('tracks', 'pending'));
    }

    public function approve(Request $request, Track $track, PaystackService $paystack)
    {
        if (! in_array($track->status, [Track::STATUS_PENDING_APPROVAL, Track::STATUS_PAID, Track::STATUS_PENDING_PAYMENT])) {
            return back()->withErrors(['track' => 'This track cannot be approved from its current status.']);
        }

        if (config('services.paystack.secret_key') && $track->payment_reference) {
            try {
                $verification = $paystack->verify($track->payment_reference);

                if (($verification['status'] ?? '') !== 'success') {
                    return back()->withErrors(['payment' => 'Paystack reports this payment as not successful. Verify manually before approving.']);
                }
            } catch (\Throwable $e) {
                return back()->withErrors(['payment' => 'Could not verify payment with Paystack: ' . $e->getMessage()]);
            }
        }

        $track->update([
            'status' => Track::STATUS_LIVE,
            'approved_at' => now(),
        ]);

        return back()->with('status', "Track '{$track->title}' approved and added to the track list.");
    }

    public function reject(Request $request, Track $track)
    {
        $track->update(['status' => Track::STATUS_REJECTED]);

        return back()->with('status', "Track '{$track->title}' rejected.");
    }

    public function pause(Request $request, Track $track, RentalService $rentals)
    {
        $this->stopActivePlays($track, $rentals);

        $track->update(['status' => Track::STATUS_PAUSED]);

        return back()->with('status', "Track '{$track->title}' paused. It will stop being distributed.");
    }

    public function resume(Request $request, Track $track)
    {
        if ($track->status !== Track::STATUS_PAUSED) {
            return back()->withErrors(['track' => 'Only paused tracks can be resumed.']);
        }

        if (! $track->hasBudgetRemaining()) {
            return back()->withErrors(['track' => 'This track has exhausted its stream budget.']);
        }

        $track->update(['status' => Track::STATUS_LIVE]);

        return back()->with('status', "Track '{$track->title}' is live again.");
    }

    private function stopActivePlays(Track $track, RentalService $rentals): void
    {
        $assignments = $track->assignments()
            ->whereIn('status', ['pending', 'playing', 'paused'])
            ->with('device')
            ->get();

        foreach ($assignments as $assignment) {
            if ($assignment->device) {
                $rentals->stopDevicePlayback($assignment->device);
            }
            $assignment->update(['status' => 'stopped']);
        }
    }
}