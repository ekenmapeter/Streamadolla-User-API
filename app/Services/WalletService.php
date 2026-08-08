<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function balance(User $user): int
    {
        $credited = WalletTransaction::where('user_id', $user->id)
            ->where('status', WalletTransaction::STATUS_CREDITED)
            ->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS])
            ->sum('amount');

        $paidOut = WalletTransaction::where('user_id', $user->id)
            ->where('status', WalletTransaction::STATUS_CREDITED)
            ->where('type', WalletTransaction::TYPE_PAYOUT)
            ->sum('amount');

        return (int) $credited - (int) $paidOut;
    }

    public function credit(User $user, int $amount, string $type = WalletTransaction::TYPE_REWARD, array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $meta) {
            $before = $this->balance($user);

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'reference' => WalletTransaction::nextReference(),
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $before + $amount,
                'status' => WalletTransaction::STATUS_CREDITED,
                'meta' => $meta,
            ]);

            return $transaction;
        });
    }

    public function debit(User $user, int $amount, array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $meta) {
            $before = $this->balance($user);

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'reference' => WalletTransaction::nextReference(),
                'type' => WalletTransaction::TYPE_PAYOUT,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $before - $amount,
                'status' => WalletTransaction::STATUS_CREDITED,
                'meta' => $meta,
            ]);

            return $transaction;
        });
    }

    public function minPayout(): int
    {
        return (int) (AppSetting::get('min_payout', 1000) ?? 1000);
    }
}