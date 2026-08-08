<?php

namespace App\Jobs;

use App\Models\ListenSession;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RewardSessionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $sessionId)
    {
    }

    public function handle(WalletService $wallet): void
    {
        $session = ListenSession::with(['assignment.campaign', 'listener.listenerProfile'])
            ->find($this->sessionId);

        if (! $session) {
            return;
        }

        if ($session->status !== ListenSession::STATUS_REWARDED) {
            return;
        }

        $alreadyRewarded = WalletTransaction::where('user_id', $session->listener_id)
            ->where('type', WalletTransaction::TYPE_REWARD)
            ->where('meta->session_id', $session->id)
            ->exists();

        if ($alreadyRewarded) {
            return;
        }

        $reward = (int) ($session->assignment?->campaign?->reward_per_review ?? 0);

        if ($reward <= 0) {
            return;
        }

        $transaction = $wallet->credit($session->listener, $reward, WalletTransaction::TYPE_REWARD, [
            'session_id' => $session->id,
            'campaign_id' => $session->assignment->campaign_id,
        ]);

        $session->listener?->listenerProfile?->addEarnings($reward);

        Log::info('Session rewarded', [
            'session_id' => $session->id,
            'transaction' => $transaction->reference,
            'amount' => $reward,
        ]);
    }
}