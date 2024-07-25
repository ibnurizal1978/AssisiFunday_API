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

class LoginController extends Controller
{

    protected $layout = "/";

    public function login(Request $r)
    {

        $username = $r->input('txt_username');
        $password = $r->input('txt_password');

        $error_message = [
            'txt_username.required' => 'Please fill username',
            'txt_password.required' => 'Please fill password'
        ];

        $this->validate($r, [
            'txt_username' => 'required',
            'txt_password' => 'required'
        ], $error_message);

        $data = DB::table('tbl_user_internal')
            ->select('user_internal_id', 'username', 'password')
            ->where('active_status', '=', 1)
            ->where('username', '=', $username)
            ->limit(1)
            ->get();

        if (!$data->isEmpty()) {
            if (Hash::check($password, $data[0]->password)) {
                $user_internal_id = $data[0]->user_internal_id;

                Session::put('username', $data[0]->username);
                Session::put('user_internal_id', $data[0]->user_internal_id);
                Session::put('LOGIN', TRUE);

                return view('home', ['title' => 'Magiclean']);
            } else {
                session::flash('error', 'Wrong username or password');
                return redirect('/');
            }
        } else {
            Session::flash('error', 'Wrong username or password');
            return redirect('/');
        }
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
