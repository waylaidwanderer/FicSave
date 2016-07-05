<?php

namespace App\Console;

use App\Helper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {
            Helper::deleteOldFiles(storage_path('app/tmp'), 15);
        })->everyMinute();
        $schedule->exec('service nginx restart && service php7.0-fpm restart')->everyTenMinutes();
    }
}
