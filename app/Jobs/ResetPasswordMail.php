<?php

namespace App\Jobs;

use App\Helpers\HostUri;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $url;
    protected $token;
    protected $user;
    protected $email;

    public function __construct($token, $email, $user)
    {
        $this->onQueue('email');
        // $this->onQueue('forgot-email');
        $uri_fun = new HostUri();
        $this->url = $uri_fun->hostUrl()."/reset_password/$token";
        $this->token = $token;
        $this->email = $email;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = $this->url;
        \Mail::to($this->email)->send(new \App\Mail\ResetPassword($this->user, $this->url));
    }
}
