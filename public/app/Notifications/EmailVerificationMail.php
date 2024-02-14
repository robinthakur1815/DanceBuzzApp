<?php

namespace App\Notifications;

use App\Helpers\HostUri;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationMail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $user;
    protected $password;

    public function __construct($user, $password, $token)
    {
        $this->onQueue('email');
        $this->user = $user;
        $this->password = $password;
        $uri = new HostUri();
        $this->url = $uri->hostUrl()."/user-management/verify/$token";
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = $this->url;
        $password = $this->password;

        return (new MailMessage)
            ->subject('Successfully Registered on DB CMS')
            ->markdown('mail.users.welcome', ['user' => $notifiable, 'url' => $url, 'password' => $password]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
