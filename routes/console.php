<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('crm:lead-reminder')->everyTwoHours();
Schedule::command('crm:due-followup-reminder')->dailyAt('06:00')->timezone('Asia/Kolkata');
Schedule::command('crm:due-followup-reminder')->dailyAt('17:35')->timezone('Asia/Kolkata');
