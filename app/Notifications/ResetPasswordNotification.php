<?php

namespace App\Notifications;

use App\Helpers\HostUri;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Sichikawa\LaravelSendgridDriver\SendGrid;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable, SendGrid;

    private $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->onQueue('email');
        $uri_fun = new HostUri();
        $this->url = $uri_fun->hostUrl()."/reset_password/$token";
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

        return (new MailMessage)
            ->subject('Password Reset Link')
            ->markdown('mail.users.resetpassword', ['user' => $notifiable, 'url' => $url]);
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
