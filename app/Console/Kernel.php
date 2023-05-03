<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

use App\Integrations\JohnDeere\SyncAssetsTask;
use App\Integrations\JohnDeere\SyncFlagsTask;
use App\Jobs\ProcessIntegrationJob;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // ONLY RUN ON LIVE
        if(stripos(base_path(), 'live') !== false){

            // MyJohnDeere Asset Syncer
            $schedule->call(new SyncAssetsTask)->name('JDSyncAssets')
            ->everyFifteenMinutes()
            ->withoutOverlapping(30)
            ->onOneServer()
            ->runInBackground();

            // MyJohnDeere Flag Syncer
            $schedule->call(new SyncFlagsTask)->name('JDSyncFlags')
            ->everyFifteenMinutes()
            ->withoutOverlapping(30)
            ->onOneServer()
            ->runInBackground();

            // MyJohnDeere Job Queue Processor
            $schedule->command('queue:work --stop-when-empty --tries=3 --timeout=120 --backoff=15')
            ->everyMinute()
            ->withoutOverlapping(30)
            ->onOneServer()
            ->runInBackground();

            // Connections Monitor
            //$schedule->call(new ConnectionsMonitor)->name('ConnectionsMonitor');

        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
