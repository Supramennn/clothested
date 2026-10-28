<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('midtrans:expire-pending')->everyFiveMinutes()->withoutOverlapping();

// Non-functional requirement: daily PostgreSQL dumps kept 14 days by default.
Schedule::command('db:backup')->dailyAt('01:00')->withoutOverlapping();
