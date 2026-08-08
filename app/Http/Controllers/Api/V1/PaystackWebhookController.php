<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\DistributeCampaignJob;
use App\Models\PromoCampaign;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackService $paystack)
    {
        $signature = (string) $request->header('x-paystack-signature', '');

        if (! $paystack->verifyWebhookSignature($signature, $request->getContent())) {
            return response()->json(['status' => 'invalid signature'], 401);
        }

        $event = $request->input('event');
        if ($event !== 'charge.success') {
            Log::info('Paystack webhook ignored', ['event' => $event]);

            return response()->json(['status' => 'ignored']);
        }

        $data = $request->input('data', []);
        $reference = (string) ($data['reference'] ?? '');
        $metadata = $data['metadata'] ?? [];

        try {
            $campaign = PromoCampaign::where('payment_reference', $reference)
                ->orWhere('id', $metadata['campaign_id'] ?? 0)
                ->first();
        } catch (\Throwable $e) {
            $campaign = null;
        }

        if (! $campaign) {
            Log::warning('Paystack webhook: no matching campaign', ['reference' => $reference, 'metadata' => $metadata]);

            return response()->json(['status' => 'ok', 'note' => 'campaign not found']);
        }

        if ($data['status'] === 'success' && (float) $campaign->amount_paid_ngn === (int) $data['amount']) {
            $campaign->update([
                'status' => PromoCampaign::STATUS_ACTIVE,
                'funded_at' => now(),
            ]);

            DistributeCampaignJob::dispatch($campaign->id);
        }

        return response()->json(['status' => 'ok']);
    }
}