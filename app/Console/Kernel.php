<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GenerateSalary;
use App\Console\Commands\CalculateSalary;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        Commands\GenerateSalary::class,
        Commands\CalculateSalary::class,
    ];

    

    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('generate:salary')
        ->dailyAt('00:01')
        ->timezone(env('TIME_ZONE', 'Asia/Calcutta'));

        $schedule->command('calculate:salary')
        ->dailyAt('23:59')
        ->timezone(env('TIME_ZONE', 'Asia/Calcutta'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
