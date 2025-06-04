<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class RegistrationSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $firstName;

    public function __construct(User $user)
    {
        $this->user = $user; // assign the user object

        // get first name from fullName
        $this->firstName = explode(' ', $user->fullName)[0];
    }

    public function build()
    {
        return $this->subject('Welcome to Pineleaf Estate')
                    ->view('emails.registration_success')
                    ->with([
                        'firstName' => $this->firstName,
                        'user' => $this->user, // in case you want more data in the view
                    ]);
    }
}
