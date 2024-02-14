<?php

namespace App\Console;

use App\Enums\ReminderType;
use Illuminate\Console\Scheduling\Schedule;
use App\Adapters\DynamicUrl\DynamicUrlService;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CheckPayment::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('queue:restart')->hourly();

        $schedule->command('reminder:send '.ReminderType::Classes)->hourly();

        $schedule->command('reminder:send '.ReminderType::LiveClass)->hourly();

        $schedule->command('reminder:send '.ReminderType::WorkshopEvents)->hourly();

        $schedule->command('check:payment')
          ->everyFiveMinutes();

        $schedule->call(function () {
            (new DynamicUrlService())->createDynamicUrlForAllFeed();
        })->everyFiveMinutes();

        $schedule->call(function () {
            (new DynamicUrlService())->createDynamicUrlForAllCampaign();
        })->everyFiveMinutes();
        $schedule->call(function () {
            (new DynamicUrlService())->createDynamicUrlForAllYoungExperts();
        })->everyFiveMinutes();
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
