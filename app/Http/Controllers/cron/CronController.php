<?php

namespace App\Http\Controllers\cron;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\WeeklyReport;
use URL;

class CronController extends Controller
{

    public function deleteSession()
    {
        /* check if session more than 2 hours */
        DB::select("DELETE FROM tbl_user_session WHERE created_at < now() - interval 2 hour");
    }

    public function weeklyReport()
    {
        $data = '';
        Mail::to('ibnurizal@gmail.com')->send(new WeeklyReport($data));
    }

}