<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('saas:generate-invoices')->dailyAt('01:00');
Schedule::command('billing:automation')->everyMinute()->withoutOverlapping();
