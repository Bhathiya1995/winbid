<?php

namespace App\Console;

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
        $schedule->call('App\Http\Controllers\CampaignController@activateCamapign')->daily();
        $schedule->call('App\Http\Controllers\CampaignController@sendAllCampaignSms')->dailyAt('07:00');
        $schedule->call('App\Http\Controllers\CampaignController@dailyPayments')->dailyAt('00:01');
        $schedule->call('App\Http\Controllers\CampaignController@renew')->hourlyAt(55);
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
