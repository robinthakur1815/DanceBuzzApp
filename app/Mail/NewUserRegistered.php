<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $user;
    protected $password;
    protected $url;

    public function __construct($user, $password, $url)
    {
        $this->password = $password;
        $this->user = $user;
        $this->url = $url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Successfully Registered on DanceBuzz CMS')
            ->view('mail.users.newregister')
            ->with(['user' => $this->user, 'url' => $this->url, 'password' => $this->password]);
    }
}
