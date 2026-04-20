<?php

use App\Services\KpiService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── KPI auto-generate: 1st of every month at 00:05 ───────────────────────────
Schedule::call(function () {
    app(KpiService::class)->generateMonthlyKPI();
})
    ->name('kpi:generate-monthly')
    ->monthlyOn(1, '00:05')
    ->withoutOverlapping()
    ->onOneServer();

// ── Task deadline reminders: every day at 08:00 ───────────────────────────────
Schedule::command('kpi:check-deadlines --days=3')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

// ── FCM push deadline reminders: every day at 07:00 ──────────────────────────
Schedule::command('notify:deadline-reminder')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();
