<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QuoteEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $quote;

    public function __construct($quote)
    {
        $this->onQueue('email');
        $this->quote = $quote;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        [\Mail::to($this->quote->email)->send(new \App\Mail\Quote($this->quote)),
        \Mail::to(config('app.admin_email'))->send(new \App\Mail\Quote($this->quote)), ];
    }
}
