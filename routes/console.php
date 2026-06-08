<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule: Send draft upload reminders 3 days before deadline
app(Schedule::class)->command('reminders:draft-upload')
    ->daily()
    ->at('08:00')
    ->onOneServer();

// Schedule: Send all deadline reminders (H-3, H-2, H-1)
app(Schedule::class)->command('app:send-deadline-reminders')
    ->dailyAt('07:00');