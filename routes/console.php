<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule: Send all deadline reminders (H-3, H-2, H-1)
Schedule::command('app:send-deadline-reminders')
    ->dailyAt('07:00');
