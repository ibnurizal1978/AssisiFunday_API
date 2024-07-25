<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyReport extends Mailable
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
        return $this->from('noreply@magicleanfortunetown.com')
                   ->view('email/weeklyReport')
                   ->subject('Weekly Report')
                   ->attach(public_path('/report/daily-report-'.date('dmY').'.xlsx'), [
                        'as' => 'daily-report-'.date('dmY').'.xlsx',
                        'mime' => 'application/pdf',
                    ]);
    }
}
