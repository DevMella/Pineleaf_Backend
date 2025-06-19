<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user, $browser, $location, $loginTime;
    
     public function __construct($user, $browser, $location, $loginTime)
    {
        $this->user = $user;
        $this->browser = $browser;
        $this->location = $location;
        $this->loginTime = $loginTime;
    }

    public function build()
    {
        return $this->subject('New Login Detected on Your Pineleaf Account')
                    ->view('emails.login_notification');
    }
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
