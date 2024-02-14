<?php

namespace App\Mail;

use App\Helpers\StoryHelper;
use App\Story;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StorySubmission extends Mailable  implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $userId;
    protected $storyId;
    protected $status;
    protected $certificate_available;

    public function __construct($userId, $storyId, $certificate_available, $status = true)
    {
        $this->onQueue('email');
        $this->userId  = $userId;
        $this->storyId = $storyId;
        $this->certificate_available = $certificate_available;
        $this->status  = $status;
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
        $certificate_available = $this->certificate_available;
        $storyHelper = new StoryHelper();
        $story = Story::where('id', $storyId)->first();
        $user = User::where('id', $userId)->first();
        $storyData = $storyHelper->storyModelData($story);

        return $this
            ->subject('TalentBox Entry Submitted')
            ->view('mail.story_submitted')
            ->with([
                'user' => $user,
                'userId' => $userId,
                'story' => $storyData,
                'certificate_available' => $certificate_available,
                'contact' => config('app.client_url').'/contact-us',
                'status' => $this->status,
            ]);
    }
}
