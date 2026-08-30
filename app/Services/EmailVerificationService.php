<?php

namespace App\Services;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    private const PREFIX = 'email_verify:';

    private const TTL_SECONDS = 600;

    private const RESEND_COOLDOWN_SECONDS = 300;

    public function issueCode(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(self::PREFIX . $user->email, [
            'code' => $code,
            'attempts' => 0,
        ], self::TTL_SECONDS);

        try {
            Mail::to($user->email)->send(new EmailVerificationMail($user, $code));
        } catch (\Throwable $e) {
            report($e);
        }

        return $code;
    }

    public function verify(User $user, string $code): bool
    {
        $entry = Cache::get(self::PREFIX . $user->email);

        if (! $entry || ! hash_equals((string) $entry['code'], (string) $code)) {
            if ($entry) {
                $entry['attempts']++;
                Cache::put(self::PREFIX . $user->email, $entry, self::TTL_SECONDS);
            }

            return false;
        }

        Cache::forget(self::PREFIX . $user->email);

        $user->forceFill(['email_verified_at' => now()])->save();

        return true;
    }

    public static function resendCooldownKey(User $user): string
    {
        return 'email_verify_resend:' . $user->id;
    }

    public function setResendCooldown(User $user): void
    {
        Cache::put(
            self::resendCooldownKey($user),
            now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->timestamp,
            self::RESEND_COOLDOWN_SECONDS
        );
    }

    public function resendAfter(User $user): int
    {
        $expiresAt = Cache::get(self::resendCooldownKey($user));

        return $expiresAt ? max(0, (int) $expiresAt - now()->timestamp) : 0;
    }
}