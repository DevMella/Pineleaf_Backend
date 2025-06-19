<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawalConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $transaction;
    public $withdraw;

    public function __construct($user, $transaction, $withdraw)
    {
        $this->user = $user;
        $this->transaction = $transaction;
        $this->withdraw = $withdraw;
    }

    public function build()
    {
        return $this->subject('Withdrawal Confirmed')
                    ->view('emails.withdrawal_confirmed');
    }

    
    public function attachments(): array
    {
        return [];
    }
}
