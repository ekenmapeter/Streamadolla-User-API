<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\PayoutRequest;
use App\Services\PaystackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PayoutCycleJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function handle(PaystackService $paystack): void
    {
        $holdHours = (int) (AppSetting::get('payout_hold_hours', 72) ?? 72);

        $requests = PayoutRequest::where('status', PayoutRequest::STATUS_REQUESTED)
            ->where('hold_until_at', '<=', now())
            ->with('user')
            ->limit(50)
            ->get();

        foreach ($requests as $payout) {
            if (! config('services.paystack.secret_key')) {
                $payout->update(['status' => PayoutRequest::STATUS_PROCESSING, 'note' => 'Paystack not configured — manual payout required.']);

                continue;
            }

            try {
                $data = $paystack->initialize([
                    'amount' => $payout->amount * 100,
                    'email' => $payout->user?->email,
                    'reference' => 'PAYOUT-' . strtoupper($payout->id . '-' . uniqid()),
                    'metadata' => [
                        'payout_request_id' => $payout->id,
                        'kind' => 'payout',
                    ],
                ]);

                $payout->update([
                    'status' => PayoutRequest::STATUS_PROCESSING,
                    'provider_reference' => $data['reference'] ?? null,
                ]);

                Log::info('Payout marked for processing', [
                    'payout_id' => $payout->id,
                    'reference' => $data['reference'] ?? null,
                ]);
            } catch (\Throwable $e) {
                Log::error('Payout init failed', ['payout_id' => $payout->id, 'error' => $e->getMessage()]);
            }
        }
    }
}