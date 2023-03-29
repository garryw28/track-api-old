<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\FirebaseScheduler',
        'App\Console\Commands\ParserScheduler',
        'App\Console\Commands\IntegrationScheduler',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('firebasescheduler:sync')->daily();
        $schedule->command('parserscheduler:sync')->daily();
        $schedule->command('integrationscheduler:sync')->daily();
    }
}
/*php artisan reservationscheduler:sync*/