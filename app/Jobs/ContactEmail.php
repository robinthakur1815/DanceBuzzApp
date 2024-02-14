<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ContactEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $contact;

    public function __construct($contact)
    {
        $this->onQueue('email');
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        [\Mail::to($this->contact->email)->send(new \App\Mail\Contact($this->contact)),
        \Mail::to(config('app.admin_email'))->send(new \App\Mail\Contact($this->contact)), ];
    }
}
