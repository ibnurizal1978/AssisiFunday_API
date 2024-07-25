<?php
/* MAGICLEAN */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Validator;
use Session;
use View;
use App\User;
use App\Models\NavigationModel;
use Illuminate\Support\Facades\Route;

class LogController extends Controller
{

    protected $layout = "/";

    public function otp()
    {
        $data = DB::table('tbl_otp')
            ->select('email', 'email_sent_status', 'otp_code', 'otp_used_status', 'updated_at', 'created_at')
            ->paginate(30);
        return view('log/otp', ['data' => $data]);
    }

    public function emailVoucher()
    {
        $data = DB::table('tbl_email_voucher')
            ->select('email', 'created_at')
            ->paginate(30);
        return view('log/voucher', ['data' => $data]);
    }

}
