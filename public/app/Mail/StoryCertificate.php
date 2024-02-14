<?php

namespace App\Mail;

use App\User;
use App\Story;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoryCertificate extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $userId;
    protected $storyId;
    protected $url ;

    public function __construct($userId, $storyId,$url)
    {
        $this->onQueue('email');
        $this->userId  = $userId;
        $this->storyId = $storyId;
        $this->url = $url ;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $storyId = $this->storyId;
        $userId = $this->userId;
        $user = User::where('id', $userId)->first();

        return $this
            ->subject('Thank You for participating in Colorothon Season 13')
            ->view('mail.colorothon_story_submitted')
            ->attach($this->url)
            ->with([
                'user' => $user
            ]);
    }
}
