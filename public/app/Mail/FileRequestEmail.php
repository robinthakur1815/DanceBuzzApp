<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FileRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $user;
    protected $storyData;
    protected $urls;

    public function __construct($user, $storyData, $urls, $student_name)
    {
        $this->onQueue('email');
        $this->user = $user;
        $this->storyData = $storyData;
        $this->urls = $urls;
        $this->student_name = $student_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('File download request')
            ->view('mail.file_download_request')
            ->with([
                'user' => $this->user,
                'story' => $this->storyData,
                'contact' => config('app.client_url').'/contact-us',
                'urls' => $this->urls,
                'student_name' => $this->student_name,
            ]);
    }
}
