<?php

namespace App\Http\Controllers;

use App\Models\CommonModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\Paginator;
use App\Http\Controllers\CommonController;
use App\Models\DepartmentModel;
use Session;

Paginator::useBootstrap();

use Validator;

class UsersController extends Controller
{
    public function view()
    {
        DB::enableQueryLog();
        $data = DB::table('tbl_user')
            ->select('user_id', 'active_status', 'sms_checklist', 'email_checklist', 'email_sent_status', 'email', 'full_name', 'updated_at', 'created_at')
            ->get();
           
        return view('users/view', ['data' => $data]);
    }

    public function search(Request $r)
    {
        DB::enableQueryLog();
        $user = DB::table('tbl_user')
            ->where('username', 'LIKE', "%" . $r->txt_search . "%")
            ->paginate(20);
        $user->appends(['txt_search' => $r->txt_search]);
        //dd(DB::getQueryLog());
        return view('users/usersView', ['user' => $user]);
    }

    public function detail($id)
    {
        DB::enableQueryLog();
        $id             = Crypt::decrypt($id); //decrypt user_id
        $user           = DB::table('tbl_user as a') //get user data except nav_menu
            ->select('a.user_id as user_id', 'username', 'full_name', 'a.user_active_status')
            ->where('a.user_id', '=', $id)
            ->get();

        $nav_id         = DB::table('tbl_nav as y') //get all nav menu, will compare to user data
            ->select(
                "*",
                DB::raw("(SELECT count(*) FROM tbl_nav_user x WHERE x.nav_id = y.nav_id AND user_id = $id) as ada")
            )
            ->where('parent_id', '>', 0)
            ->where('visible_status', '=', 1)
            ->orderby('nav_name')
            ->get();

        return view('users/usersDetail', ['user' => $user, 'nav_id' => $nav_id]);
    }

    public function new()
    {

        $partner_id       = DB::table('tbl_partner')
            ->get();

        $nav_id         = DB::table('tbl_nav')
            ->where('parent_id', '>', 0)
            ->orderBy('nav_name')
            ->get();

        return view('users/usersNew', ['partner_id' => $partner_id, 'nav_id' => $nav_id]);
    }

    public function add(Request $r)
    {

        $error_message = [
            'username.required'         => 'Username is mandatory. 5 characters minimum. Number or alphabet is allowed',
            'username.min'              => 'Username is mandatory. 5 characters minimum. Number or alphabet is allowed',
            'username.alpha_num'        => 'Username is mandatory. 5 characters minimum. Number or alphabet is allowed',
            'full_name.required'        => 'full name is mandatory. 3 characters minimum',
            'full_name.min'             => 'full name is mandatory. 3 characters minimum',
            'partner_id.required'       => 'Please choose partner',
            'txt_password.required'     => 'Minimum password length 6 digits, consist of number and alphabet',
            'txt_password.min'          => 'Minimum password length 6 digits, consist of number and alphabet',
            'txt_password.alpha_num'    => 'Minimum password length 6 digits, consist of number and alphabet',
            'nav_id.required'           => 'Please choose module access'
        ];

        $this->validate($r, [
            'username'                  => 'required|min:5|alpha_num',
            'full_name'                 => 'required|min:3 ',
            'partner_id'                => 'required',
            'txt_password'              => 'required|min:6|alpha_num',
            'nav_id'                    => 'required'
        ], $error_message);

        //check if there is a duplicate user
        $duplicate          = DB::table('tbl_user')
            ->where('username', '=', $r->username)
            ->count();

        if ($duplicate > 0) {
            return redirect('/users/usersNew')->with('danger', 'Duplicate username. This username already exist');
        }

        $nav_id = $r->nav_id;
        DB::table('tbl_user')->insert([
            'username'              => $r->username,
            'full_name'             => $r->full_name,
            'user_active_status'    => $r->user_active_status,
            'partner_key'           => $r->partner_id,
            'password'              => Hash::make($r->txt_password),
            'created_date'          => Carbon::now(),
            'client_id'             => Session::get('client_id'),
            'user_timezone'         => 'Asia/Jakarta',
            'user_last_login'       => Carbon::now()
        ]);

        foreach ($nav_id as $key => $value) {
            $arrData = array(
                'nav_id' => $nav_id[$key]
            );
            DB::table('tbl_nav_user')->insert([
                'nav_menu_id' => $nav_id[$key],
                'nav_id' => $nav_id[$key],
                'user_id'     => 1,
                'client_id'   => Session::get('client_id')
            ]);
        }
        return redirect('/users/view')->with('status', 'Data added successfully');
    }

    public function edit(Request $r)
    {
        $user_id = Crypt::decrypt($r->input('user_id'));

        $error_message = [
            'username.required'         => 'Username is mandatory. 5 characters minimum. Number or alphabet is allowed',
            'username.min'              => 'Username is mandatory. 5 characters minimum. Number or alphabet is allowed',
            'full_name.required'        => 'full name is mandatory. 5 characters minimum',
            'full_name.min'             => 'full name is mandatory. 5 characters minimum',
            'nav_id.required'           => 'Please choose module access'
        ];

        $this->validate($r, [
            'username'                  => 'required|min:5',
            'full_name'                 => 'required ',
            'nav_id'                    => 'required'
        ], $error_message);

        if ($r->input('password') != '') {
            $pass_message = [
                'password.required'         => 'Minimum password length 6 digits, consist of number and alphabet',
                'password.min'              => 'Minimum password length 6 digits, consist of number and alphabet',
                'password.alpha_num'        => 'Minimum password length 6 digits, consist of number and alphabet',
            ];

            $this->validate($r, [
                'password'                  => 'required|min:6|alpha_num',
            ], $pass_message);
        }

        //any duplicate data?
        $duplicate = DB::table('tbl_user')
            ->where('username', $r->input('username'))
            ->where('user_id', '!=', $user_id)
            ->count();
        if ($duplicate > 0) {
            return redirect()->back()->with('status', 'Duplicate username');
        }

        $nav_id = $r->nav_id;
        if ($r->input('password') != '') {
            DB::table('tbl_user')
                ->where('user_id', $user_id)
                ->update([
                    'full_name'             => $r->input('full_name'),
                    'username'              => $r->input('username'),
                    'password'              => Hash::make($r->input('password')),
                    'user_active_status'    => $r->input('user_active_status')
                ]);
        } else {
            DB::table('tbl_user')
                ->where('user_id', $user_id)
                ->update([
                    'full_name'             => $r->input('full_name'),
                    'username'              => $r->input('username'),
                    'user_active_status'    => $r->input('user_active_status')
                ]);
        }


        DB::table('tbl_nav_user')
            ->where('user_id', $user_id)
            ->delete();

        foreach ($nav_id as $key => $value) {
            $arrData = array(
                'nav_menu_id' => $nav_id[$key]
            );
            DB::table('tbl_nav_user')->insert([
                'nav_menu_id' => $nav_id[$key],
                'nav_id'      => $nav_id[$key],
                'user_id'     => $user_id,
                'client_id'   => Session::get('client_id')
            ]);
        }

        return redirect('/users/view')->with('status', 'Data updated successfully');
    }



    public function password()
    {
        return view('profile/password');
    }
}
