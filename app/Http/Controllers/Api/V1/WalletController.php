<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\PayoutRequest;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function index(Request $request, WalletService $wallet)
    {
        $user = $request->user();
        $currency = $wallet->currencyFor($user);

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (WalletTransaction $t) => [
                'reference' => $t->reference,
                'type' => $t->type,
                'currency' => $t->currency ?? $currency,
                'amount' => Money::number($t->amount),
                'status' => $t->status,
                'meta' => $t->meta,
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'currency' => $currency,
            'balance' => Money::number($wallet->balance($user)),
            'min_payout' => Money::number($wallet->minPayout($user)),
            'transactions' => $transactions,
        ]);
    }

    public function requestPayout(Request $request, WalletService $wallet)
    {
        $user = $request->user();
        $currency = $wallet->currencyFor($user);
        $isVendor = $user->isVendor();

        $validator = Validator::make($request->all(), [
            'amount' => $isVendor ? 'required|numeric|min:0.001' : 'required|integer|min:1',
            'method' => 'required|in:bank,airtime',
            'account' => 'required_if:method,bank|array',
            'account.bank_code' => 'required_if:method,bank|string',
            'account.account_number' => 'required_if:method,bank|string',
            'account.account_name' => 'nullable|string',
            'phone' => 'required_if:method,airtime|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $balance = $wallet->balance($user);
        $amount = Money::round((float) $request->amount);
        $minPayout = $wallet->minPayout($user);

        if ($balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance.',
            ], 422);
        }

        if ($amount < $minPayout) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum payout is ' . Money::format($minPayout, $currency) . '.',
            ], 422);
        }

        $pendingExists = PayoutRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

        if ($pendingExists) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending payout request.',
            ], 422);
        }

        $holdHours = (int) (AppSetting::get('payout_hold_hours', 72) ?? 72);

        $payout = PayoutRequest::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $currency,
            'method' => $request->method,
            'destination' => $request->method === 'bank' ? $request->account : ['phone' => $request->phone],
            'status' => PayoutRequest::STATUS_REQUESTED,
            'hold_until_at' => now()->addHours($holdHours),
        ]);

        $wallet->debit($user, $amount, ['payout_request_id' => $payout->id]);

        return response()->json([
            'success' => true,
            'message' => 'Payout request received. Funds will be released after the holding period.',
            'payout' => [
                'id' => $payout->id,
                'amount' => Money::number($payout->amount),
                'currency' => $payout->currency,
                'status' => $payout->status,
                'hold_until_at' => $payout->hold_until_at?->toIso8601String(),
            ],
        ], 201);
    }
}
