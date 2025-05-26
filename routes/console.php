<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command('check:star-users')
    // ->everyMinute() // Uncomment this line to run the command every minute
    // ->hourly() // Uncomment this line to run the command hourly instead
    ->daily() // Uncomment this line to run the command daily instead
    ->description('Check and mark users as star if their total purchases reach 200 million')
    ->withoutOverlapping()
    ->timezone('Africa/Lagos')
    ->appendOutputTo(storage_path('logs/star-users.log')) // Add logging
    ->onSuccess(function () {
        Log::info('Star users check completed successfully');
    })
    ->onFailure(function () {
        Log::error('Star users check failed');
    });

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');