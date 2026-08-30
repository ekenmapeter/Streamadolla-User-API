<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Campaign auto-advance ─────────────────────────────────────────────
// The command self-loops every 60s internally for ~4m50s, so tracks are
// checked every minute even though the cron only fires every 5 minutes.
Schedule::command('campaigns:execute')->everyMinute()->withoutOverlapping();

// ── Premium Proxy Rotation ──────────────────────────────────────────── 
// Automatically fetch and distribute new Elite IPs every hour.
Schedule::command('proxies:fetch-premium')->hourly()->withoutOverlapping();

// ── AudioReach: complete campaigns that hit their listen target ──────
Schedule::command('campaigns:finalize')->everyFiveMinutes()->withoutOverlapping();

// ── AudioReach: weekly payout sweep (listeners) ──────────────────────
Schedule::job(new \App\Jobs\PayoutCycleJob)->weekly()->mondays()->at('09:00');

// ── AudioReach: push play commands to Free Move listeners ─────────────
Schedule::command('autoplay:push')->everyTwoMinutes()->withoutOverlapping();

