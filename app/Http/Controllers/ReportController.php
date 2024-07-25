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

class ReportController extends Controller
{

    protected $layout = "/";

    public function report1()
    {
        $data = DB::table('tbl_leaderboard as a')
            ->join('tbl_user as b', 'a.email', '=', 'b.email')
            ->select('a.email', 'full_name', 'score', 'time', 'a.updated_at', 'a.created_at')
            ->orderby('created_at', 'DESC')
            ->paginate(20);
        return view('report/report1', ['data' => $data]);
    }

    public function report2()
    {
        $data = DB::table('tbl_leaderboard as a')
            ->join('tbl_user as b', 'a.email', '=', 'b.email')
            ->select('a.email', 'full_name', 'score', 'time', 'a.updated_at', 'a.created_at')
            ->orderby('created_at', 'DESC')
            ->paginate(20);
        return view('report/report2', ['data' => $data]);
    }

}
