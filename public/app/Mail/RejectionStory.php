<?php

namespace App\Mail;
use App\Helpers\StoryHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RejectionStory extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $user;
    protected $student;
    protected $story;
    protected $reason;

    public function __construct($user, $story, $student, $reason)
    {
        $this->user = $user;
        $this->story = $story;
        $this->student = $student;
        $this->reason = $reason;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
        
        $storyHelper = new StoryHelper();
        $storyData = $storyHelper->storyModelData($this->story);
        return $this->
        subject('TalentBox Entry Rejected')
        ->view('mail.story_rejected')
        ->with([
            'user' => $this->user,
            'story' => $storyData,
            'contact' => config('app.client_url').'/contact-us',
            'student' => $this->student,
            'reason' => $this->reason,

        ]);
    }
}
