<?php

namespace App\Providers;

use App\Console\Commands\SendDraftUploadReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(Schedule $schedule)
    {
        // reminder setiap hari jam 08:00
        $schedule->command('reminders:draft-upload')
            ->daily()
            ->at('08:00')
            ->onOneServer();
    }
}