<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Schedule: Send all deadline reminders (H-3, H-2, H-1)
app(Schedule::class)->command('app:send-deadline-reminders')
    ->dailyAt('07:00');

// Schedule: Send reviewer reminders
Schedule::command('app:send-reviewer-reminders')->dailyAt('08:00');
