<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVoucher extends Mailable
{
    use Queueable, SerializesModels;
    public $data;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $image = env('APP_URL')."/public/images/edm/banner.jpg";
        return $this->from('noreply@magicleanfortunetown.com')
                   ->view('email/emailVoucher')
                   ->subject('Your Magiclean Promo Code Prize is Here')
                   ->with(['image' => $image]);
    }
}
