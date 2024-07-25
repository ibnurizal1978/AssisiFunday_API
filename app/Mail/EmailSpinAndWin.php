<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailSpinAndWin extends Mailable
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
        return $this->from($address = 'afd@assisihospice.org.sg', $name = 'Assisi Funday')
                   ->view('email/emailSpinAndWin')
                   ->subject('Prize Collection for Assisi Fun Day Spot, Spin & Win Game')
                   ->with(
                    [
                        'code' => $this->data,
                    ]);
    }
}
