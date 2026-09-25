<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Switch off events and course batches the day after their start date. The
// first web request of each day runs the same sweep (ExpirePastContent
// middleware), so this is the belt to that pair of braces.
Schedule::command('content:expire-past')->dailyAt('00:01');
