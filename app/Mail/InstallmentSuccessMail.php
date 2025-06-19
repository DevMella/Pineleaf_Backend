<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstallmentSuccessMail extends Mailable
{
 
 use Queueable, SerializesModels;

    public $firstName;
    public $transaction;
    public function __construct($transaction,$firstName)
    {
        $this->firstName = $firstName;
        $this->transaction = $transaction;
    }

    public function build()
    {
        return $this->subject('Installment Successful')
                    ->view('emails.installment_success')
                     ->with([
                    'transaction' => $this->transaction,
                    'firstName' => $this->firstName,
                ]);
    }
}
