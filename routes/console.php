<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check hourly for attendees due a 3-day or 24-hour reminder.
Schedule::command('events:send-reminders')->hourly()->withoutOverlapping();
