<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PurchaseSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $amount;
    public $ref_no;

    public function __construct($firstName, $amount, $ref_no)
    {
        $this->firstName = $firstName;
        $this->amount = $amount;
        $this->ref_no = $ref_no;
    }

    public function build()
    {
        return $this->subject('Purchase Successful')
                    ->view('emails.purchase-success');
    }
}
