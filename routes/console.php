<?php

use Illuminate\Support\Facades\Schedule;

// Schedule automated 3-day rent due alerts daily at 08:30 AM
Schedule::command('hostel:send-reminders')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->runInBackground();

// Schedule weekly Cloud-ZIP backup to Gmail on Friday at 21:00
Schedule::command('backup:email')
    ->weeklyOn(5, '21:00')
    ->withoutOverlapping()
    ->runInBackground();
