<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubscriberMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $subscriber;

    public function __construct($subscriber)
    {
        $this->onQueue('email');
        $this->subscriber = $subscriber;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        [
            \Mail::to($this->subscriber->email)->send(new \App\Mail\Subscriber($this->subscriber)),
            \Mail::to(config('app.admin_email'))->send(new \App\Mail\Subscriber($this->subscriber)),
        ];
    }
}
