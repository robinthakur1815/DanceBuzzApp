<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Helpers\UserHelper;
use App\Order;
use Illuminate\Console\Command;

class CheckPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To check payment of events and updated accordingly status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $orderIds = Order::where('payment_status',PaymentStatus::Pending)->pluck('id')->toArray();
        // $userHelper = new UserHelper;

        // for ($i=0; $i <count($orderIds) ; $i++) {
        //     $userHelper->checkPaymentStatus($orderIds[$i]);
        // }
    }
}
