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
