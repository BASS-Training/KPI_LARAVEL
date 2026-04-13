<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\KpiService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(KpiService::class)->generateMonthlyKPI();
})
    ->name('kpi:generate-monthly')
    ->monthlyOn(1, '00:05')
    ->withoutOverlapping()
    ->onOneServer();
