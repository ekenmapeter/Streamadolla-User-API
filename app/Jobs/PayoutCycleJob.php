<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\PayoutRequest;
use App\Services\PaystackService;
use App\Support\Money;
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
        $usdToNgn = (float) (AppSetting::get('usd_ngn_rate', 1344.50) ?? 1344.50);

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
                // Paystack settles in Naira. Vendor wallets are denominated in
                // USD, so convert them at the configured rate first.
                $isUsd = strtoupper((string) $payout->currency) === Money::USD;
                $rate = $isUsd ? $usdToNgn : 1.0;

                if ($isUsd && $rate <= 0) {
                    $payout->update([
                        'status' => PayoutRequest::STATUS_PROCESSING,
                        'note' => 'USD/NGN rate is not configured — manual payout required.',
                    ]);

                    continue;
                }

                $ngnAmount = (float) $payout->amount * $rate;

                $data = $paystack->initialize([
                    'amount' => (int) round($ngnAmount * 100),
                    'email' => $payout->user?->email,
                    'reference' => 'PAYOUT-' . strtoupper($payout->id . '-' . uniqid()),
                    'metadata' => [
                        'payout_request_id' => $payout->id,
                        'kind' => 'payout',
                        'currency' => $payout->currency,
                        'ngn_amount' => $ngnAmount,
                        'usd_ngn_rate' => $rate,
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