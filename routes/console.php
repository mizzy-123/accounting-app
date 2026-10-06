<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('accounting:process-recurring')->daily();
Schedule::command('accounting:process-depreciation')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping(120);
