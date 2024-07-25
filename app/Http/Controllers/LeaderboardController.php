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

class LeaderboardController extends Controller
{

    protected $layout = "/";

    public function view()
    {
        $data = DB::table('tbl_leaderboard as a')
            ->join('tbl_user as b', 'a.email', '=', 'b.email')
            ->select('a.email', 'full_name', 'score', 'time', 'a.updated_at', 'a.created_at')
            ->paginate(20);
        return view('leaderboard/view', ['data' => $data]);
    }


    public function logout()
    {
        Session::flush();
        return redirect('/');
    }

    public function home()
    {
        $user = DB::table('tbl_user_internal')->get();
        return view('home', ['user' => $user]);
    }

}
