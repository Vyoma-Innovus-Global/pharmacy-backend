<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        if (config('services.pending_payments.cron_enabled')) {
            $schedule->command('payments:verify-pending', [
                '--limit' => config('services.pending_payments.cron_limit'),
            ])
                ->cron(config('services.pending_payments.cron_schedule'))
                ->withoutOverlapping(30);
        }
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
