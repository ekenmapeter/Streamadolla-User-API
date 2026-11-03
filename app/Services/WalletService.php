<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function currencyFor(User $user): string
    {
        return $user->isVendor() ? Money::USD : Money::NGN;
    }

    public function balance(User $user): int|float
    {
        $currency = $this->currencyFor($user);

        $credited = WalletTransaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('status', WalletTransaction::STATUS_CREDITED)
            ->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS, WalletTransaction::TYPE_RENTAL, WalletTransaction::TYPE_VENDOR])
            ->sum('amount');

        $paidOut = WalletTransaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('status', WalletTransaction::STATUS_CREDITED)
            ->where('type', WalletTransaction::TYPE_PAYOUT)
            ->sum('amount');

        return Money::number((float) $credited - (float) $paidOut);
    }

    public function credit(User $user, int|float $amount, string $type = WalletTransaction::TYPE_REWARD, array $meta = []): WalletTransaction
    {
        $amount = Money::round($amount);
        $currency = $this->currencyFor($user);

        return DB::transaction(function () use ($user, $amount, $currency, $type, $meta) {
            $before = $this->balance($user);

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'reference' => WalletTransaction::nextReference(),
                'type' => $type,
                'currency' => $currency,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => Money::round($before + $amount),
                'status' => WalletTransaction::STATUS_CREDITED,
                'meta' => $meta,
            ]);

            return $transaction;
        });
    }

    public function debit(User $user, int|float $amount, array $meta = []): WalletTransaction
    {
        $amount = Money::round($amount);
        $currency = $this->currencyFor($user);

        return DB::transaction(function () use ($user, $amount, $currency, $meta) {
            $before = $this->balance($user);

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'reference' => WalletTransaction::nextReference(),
                'type' => WalletTransaction::TYPE_PAYOUT,
                'currency' => $currency,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => Money::round($before - $amount),
                'status' => WalletTransaction::STATUS_CREDITED,
                'meta' => $meta,
            ]);

            return $transaction;
        });
    }

    public function minPayout(?User $user = null): int|float
    {
        if ($user?->isVendor()) {
            return Money::number((float) (AppSetting::get('vendor_min_payout', 1.0) ?? 1.0));
        }

        return Money::number((float) (AppSetting::get('min_payout', 1000) ?? 1000));
    }
}
