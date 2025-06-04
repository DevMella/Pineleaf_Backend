<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        $resetUrl = url('/reset-password-form?token=' . $this->token); 

        return $this->subject('Reset Your Password')
            ->view('emails.password_reset')
            ->with([
                'fullName' => $this->user->fullName,
                'resetUrl' => $resetUrl,
            ]);
    }
}
