<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $vendor;

    public function __construct($vendor)
    {
        $this->onQueue('registration');
        $this->vendor = $vendor;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('DanceBuzz Partner Approval Mail')
            ->view('email.vendorstatusupdate')
            ->with(['vendor' => $this->vendor, 'contact' => config('app.client_url').'/contact-us']);
    }
}
