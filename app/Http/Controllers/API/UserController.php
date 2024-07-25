<?php
/*---------------- ASSISI 2022 -----------------------------*/
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailValidation;
use App\Mail\EmailForgotPassword;
use App\Mail\EmailVoucher;
use URL;
use Illuminate\Support\Facades\Crypt;

class UserController extends Controller
{

    public function signUp(request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
          'first_name'  => ['regex:/^[a-zA-Z0-9\s]+$/', 'required','max:200'],
          'last_name'   => ['regex:/^[a-zA-Z0-9\s]+$/', 'required', 'max:200'],
          'phone'       => ['regex:/^[a-zA-Z0-9\s]+$/', 'required', 'max:200'],
          'gender'      => ['nullable','regex:/^[a-zA-Z0-9\s]+$/','max:200'],
          'email'       => ['email', 'required','max:100'],
          'age'         => ['nullable','regex:/^[a-zA-Z0-9#-<>\s]+$/','max:20'],
          'address'     => ['regex:/^[a-zA-Z0-9#-.\s]+$/','max:200'],
          'zip_code'    => ['digits_between:3,10'],
          'password'    => ['string', 'required','max:100'],
        ]);

        if($validate->fails())
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'validation failed',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'SGP';
        $email      = $r->input('email');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$email.$date);

        if($token <> $r->input('token'))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if email is already in db */
        $duplicate   = DB::table('tbl_user')
            ->where('email', '=', $r->email)
            ->count();

        if ($duplicate > 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'duplicate email address',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'duplicate email address';
            return response()->json($response, 400);
        }
        /* end check if email is already in db */

        DB::table('tbl_user')->insert([
            'email'              => $r->email,
            'first_name'         => $r->first_name,
            'last_name'          => $r->last_name,
            'phone'              => $r->phone,
            'gender'             => $r->gender,
            'password'           => md5($r->password),
            'age'                => $r->age,
            'address1'           => $r->address,
            'zip_code'           => $r->zip_code,
            'active_status'      => 0,
            'token'              => hash("sha256",$r->email),
            'created_at'         => now()
        ]);

        /* ====== LOG ===== */
        $log = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->get();
        DB::table('tbl_log')->insert([
            'user_id'         => $log[0]->user_id,
            'action'        => 'SIGN_UP',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG ===== */

        /* email validation */
        $data = [
            'email'     => hash("sha256",$r->email),
            'full_name' => $r->first_name.' '.$r->last_name,
        ];
        Mail::to($r->email)->send(new EmailValidation($data));
        /* end email validation */

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'succcess',
            'value'         => json_encode($r->all()),
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'success, waiting for user to activate account';
        return response()->json($response, 200);

    }

    public function activation($id)
    {

        /* check if email is there */
        $check   = DB::table('tbl_user')
        ->where('token', '=', $id)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'unknown email',
                'value'         => $id,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'unknown email';
            return response()->json($response, 400);
        }
        /* end check if email is there */

        /* if more than 30 minutes, data cannot activated */
        $count = DB::table('tbl_user')
        ->where('token', $id)
        ->where('active_status', 0)
        ->where('created_at', '>', Carbon::now()->subMinutes(30)->toDateTimeString())
        ->count();

        if($count == 0)
        {
          /* ====== LOG API ===== */
          DB::table('tbl_log_api')->insert([
              'function'      => (__FUNCTION__),
              'file_name'     => basename(__FILE__, '.php'),
              'message'       => 'activation time out',
              'value'         => $id,
              'url'           => URL::current(),
              'created_at'    => Carbon::now()
              ]);
          /* ===== LOG API ===== */

          $response['status'] = 'error';
          $response['message'] = 'activation time out';
          return response()->json($response, 400);
        }
        /* end */

        DB::table('tbl_user') //update tbl_user set active = 1
        ->where('token', $id)
        ->update([
            'active_status'      => 1,
            'updated_at'         => now()
        ]);

        /* LOG */
        $data = DB::table('tbl_user')
        ->where('token', $id)
        ->get();

        $log = DB::table('tbl_user')
        ->where('email', '=', $data[0]->email)
        ->get();

        DB::table('tbl_log')->insert([
            'user_id'       => $log[0]->user_id,
            'action'        => 'ACTIVATION',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /* LOG */

        DB::table('tbl_lucky_draw')->insert([
          'user_id'          => $data[0]->user_id,
          'via'              => 'Activation',
          'created_at'       => now()
        ]);

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'user activated',
            'value'         => $id,
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'user is activated';
        return response()->json($response, 200);

    }

    public function login(request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'email'     => ['email', 'required','max:100'],
            'password'  => ['string', 'required','max:100']
        ]);

        if($validate->fails())
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'validation failed',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'LGN';
        $email      = $r->input('email');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$email.$date);

        if($token <> $r->input('token'))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if email is on db */
        $duplicate   = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        // ->where('active_status', '=', 1)
        ->count();

        if ($duplicate == 0)
        {
          /* ====== LOG API ===== */
          DB::table('tbl_log_api')->insert([
              'function'      => (__FUNCTION__),
              'file_name'     => basename(__FILE__, '.php'),
              'message'       => 'account not found',
              'value'         => json_encode($r->all()),
              'url'           => URL::current(),
              'created_at'    => Carbon::now()
              ]);
          /* ===== LOG API ===== */

          $response['status'] = 'error';
          $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check if email is on db */

        /* check if email is active */
        $duplicate   = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->where('active_status', '=', 1)
        ->count();

        if ($duplicate == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'user inactive',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'user is inactive';
            return response()->json($response, 400);
        }
        /* end */

        /* check if session is active */
        /*$duplicate   = DB::table('tbl_user_session as a')
        ->join('tbl_user as b', 'a.user_id', 'b.user_id')
        ->where('email', '=', $r->email)
        ->count();

        if ($duplicate > 0)
        {
            $response['status'] = 'error';
            $response['message'] = 'duplicate login session';
            return response()->json($response, 400);
        }*/
        /* end */

        /* check if password is true */
        $data   = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->where('active_status', '=', 1)
        ->get();

        if ($data[0]->password != md5($r->password))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'incorrect password',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end */

        // $data = DB::table('tbl_user')
        // ->where('email', '=', $r->email)
        // ->get();

        // foreach($data as $datas)
        // {
        //   $data_active_dv = DB::table('tbl_discount_voucher')
        //     ->select('code', 'value', 'max_qty')
        //     ->where('user_id', $data[0]->user_id)
        //     ->where('status', 0)
        //     ->get();

        //   $data_inactive_dv = DB::table('tbl_discount_voucher')
        //     ->select('code', 'value', 'max_qty', 'order_code')
        //     ->where('user_id', $data[0]->user_id)
        //     ->where('status', 1)
        //     ->get();

        //   //$datas->active_voucher_list = $data_active_dv;
        //   //$datas->inactive_voucher_list = $data_inactive_dv;
        // }

        /* check is it first time login? */
        $count = DB::table('tbl_user_session')
        ->where('user_id', $data[0]->user_id)
        ->count();

        if($count == 0)
        {
          $first_time_login = 'true';
        }else{
          $first_time_login = 'false';
        }
        /* end */


        /* check is it first time login for today? */
        $now = date('Y-m-d');

        $count2 = DB::table('tbl_user_session')
        ->where('user_id', $data[0]->user_id)
        ->whereDate('created_at', $now)
        ->count();

        if($count2 == 0)
        {

          $flag_donation = 0;
          $flag_topup = 0;

        //   /*  update flag */
        //   DB::table('tbl_user')
        //   ->where('user_id', $data[0]->user_id)
        //   ->update([
        //     'flag_donation'    => 0,
        //     'flag_topup'     => 0,
        //   ]);

        }else{
          $flag_donation = $data[0]->flag_donation;
          $flag_topup = $data[0]->flag_topup;
        }
        /* end */


        $session_id = $data[0]->user_id.hash("sha256",Carbon::now()->isoFormat('YYYYMMDD').substr(str_shuffle('0123456789'), 0, 10));
        DB::table('tbl_user_session')->insert([
            'user_id'         => $data[0]->user_id,
            'session_id'    => $session_id,
            'created_at'    => Carbon::now()
        ]);

        /*===== LOG ===== */
        $log = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->get();

        DB::table('tbl_log')->insert([
                'user_id'       => $log[0]->user_id,
                'action'        => 'LOGIN',
                'notes'         => '',
                'module'        => 'UserController.php',
                'created_at'    => Carbon::now()
                ]);
        /*===== LOG ===== */

        /* get lucky draw */
        $lucky_draw_chance = DB::select(
            DB::raw("SELECT count(lucky_draw_id) as total FROM tbl_lucky_draw WHERE user_id = '".$data[0]->user_id)."'");

        $arr = array([
            'user_id'           => $data[0]->user_id.'',
            'email'             => $r->email,
            'session_id'        => $session_id,
            'first_name'        => $data[0]->first_name,
            'last_name'         => $data[0]->last_name,
            'mobile'            => $data[0]->phone,
            'postal_code'       => $data[0]->zip_code,
            'address'           => $data[0]->address1.' '.$data[0]->address2,
            'first_time_login'  => $first_time_login,
            'flag_donation'     => $flag_donation,
            'flag_topup'        => $flag_topup,
            'lucky_draw_chance' => $lucky_draw_chance[0]->total
        ]);

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => json_encode($r->all()),
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json(['data' => $arr], 200);
        //return response()->json(['data' => $arr, 'active_discount_voucher' => $datas->active_voucher_list, 'redeem_discount_voucher' => $datas->inactive_voucher_list], 200);
        //return response()->json($response, 200);
    }



    public function getSession(request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'email'         => ['email', 'required','max:100'],
            'session_id'    => ['string', 'required','max:255']
        ]);

        if($validate->fails())
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'validation failed',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'SSN';
        $email      = $r->input('email');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$email.$date);

        if($token <> $r->input('token'))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if email in user session */
        $check   = DB::table('tbl_user_session')
        ->where('email', '=', $r->email)
        ->where('session_id', '=', $r->session_id)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'data not found',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'data not found';
            return response()->json($response, 400);
        }
        /* end check if email in user session */

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);

    }


    public function updatePassword(request $r)
    {

        /* check input validation */
        $validate   = validator::make($r->all(), [
            'new_password'  => ['string', 'required','max:200'],
            'code'          => ['string', 'required','max:250'],
        ]);

        if($validate->fails())
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'validation failed',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'UPW';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$r->code.$date);

        if($token <> $r->input('token'))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if code is similar with tbl_user_password_token and not more than 30 minutes */
        $count = DB::table('tbl_user_password_token')
        ->where('token', $r->code)
        ->where('created_at', '>', Carbon::now()->subMinutes(5)->toDateTimeString())
        ->count();

        if($count == 0)
        {
          /* ====== LOG API ===== */
          DB::table('tbl_log_api')->insert([
              'function'      => (__FUNCTION__),
              'file_name'     => basename(__FILE__, '.php'),
              'message'       => 'invalid code',
              'value'         => json_encode($r->all()),
              'url'           => URL::current(),
              'created_at'    => Carbon::now()
              ]);
          /* ===== LOG API ===== */

          $response['status'] = 'error';
          $response['message'] = 'invalid code';
          return response()->json($response, 400);
        }
        /* end */

        /* check if user active status is 1 */
        $check1 = DB::table('tbl_user_password_token')
          ->where('token', '=', $r->code)
          ->get();

        $check   = DB::table('tbl_user')
        ->where('email', '=', $check1[0]->email)
        ->where('active_status', '=', 1)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'user inactive',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'user is inactive';
            return response()->json($response, 400);
        }
        /* end check if user active status is 1 */

        //password must have this
        /*$regex = preg_match('[@_!#$%^&*()<>?/|}{~:]', $r->new_password);
        if (!$regex) {
            $response['status'] = 'error';
            $response['message'] = 'password must have special character';
            return response()->json($response, 400);
        }*/

        if(strlen($r->new_password)<6) {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'minimum 6 digits',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'minimum 6 digits';
            return response()->json($response, 400);
        }

        /*  update new password */
        $new_password_to_db     = md5($r->new_password);

        $data = DB::table('tbl_user')
            ->where('email', $check1[0]->email)
            ->update(['password' => $new_password_to_db]);
        /* LOG */
        $log = DB::table('tbl_user')
        ->where('email', '=', $check1[0]->email)
        ->get();
        DB::table('tbl_log')->insert([
            'user_id'       => $log[0]->user_id,
            'action'        => 'UPDATE_PASSWORD',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /* LOG */

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => json_encode($r->all()),
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
    }

    public function updateProfile(request $r)
    {

        /* check input validation */
        $validate   = validator::make($r->all(), [
          'first_name'    => ['regex:/^[a-zA-Z0-9\s]+$/', 'required','max:200'],
          'last_name'    => ['nullable', 'regex:/^[a-zA-Z0-9\s]+$/','max:200'],
          'address'     => ['nullable', 'regex:/^[a-zA-Z0-9#-.\s]+$/','max:200'],
          'zip_code'    => ['nullable', 'digits_between:3,10']
        ]);

        if($validate->fails())
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'validation failed',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'UPR';
        $email      = $r->input('email');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$email.$date);

        if($token <> $r->input('token'))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if email is on db */
        $check   = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'account not found',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check if email is on db */

        /* check if user active status is 1 */
        $check   = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->where('active_status', '=', 1)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'user inactive',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'user is inactive';
            return response()->json($response, 400);
        }

        /*  update new password */
        $data = DB::table('tbl_user')
            ->where('email', $r->email)
            ->update([
                'first_name'    => $r->first_name,
                'last_name'     => $r->last_name,
                'address1'       => $r->address,
                'zip_code'      => $r->zip_code,

            ]);

        /* LOG */
        $log = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->get();
        DB::table('tbl_log')->insert([
            'user_id'       => $log[0]->user_id,
            'action'        => 'UPDATE_PROFILE',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /* LOG */

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => json_encode($r->all()),
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
    }

    public function forgotPassword($id, $token1)
    {
        /* check token */
        $app_id     = 'UPW';
        $email      = $id;
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$email.$date);

        if($token <> $token1)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => $id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if email is on db */
        $check   = DB::table('tbl_user')
        ->where('email', '=', $id)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'success',
                'value'         => $id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'success';
            $response['message'] = 'success';
            return response()->json($response, 200);
        }
        /* end check if email is on db */

        /* get first name and last name */
        $db = DB::table('tbl_user')
            ->where('email', $id)
            ->get();
        /* end */

        /* insert into tbl_user_password_token */
        DB::table('tbl_user_password_token')
        ->insert([
            'email'       => $id,
            'token'       => hash("sha256",$id),
            'created_at'    => Carbon::now()
            ]);
        /* end */

        $data = [
            //'url'       => ' https://demo.trinaxmind.com/assisi2021_main/reset-pwd.html?token='.$token1,
            'email'     => hash("sha256",$id),
            'full_name' => $db[0]->first_name.' '.$db[0]->last_name,
        ];
        Mail::to($id)->send(new EmailForgotPassword($data));
        /* end */

        /*============ LOG =============*/
        $log = DB::table('tbl_user')
        ->where('email', '=', $id)
        ->get();
        DB::table('tbl_log')->insert([
            'user_id'       => $log[0]->user_id,
            'action'        => 'REQUEST_FORGOT_PASSWORD',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /*============ LOG =============*/

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => $id.', '.$token1,
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);

    }

    public function logout(request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'email'     => ['email', 'required','max:100']
        ]);

        if($validate->fails())
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'validation failed',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400)->header("Access-Control-Allow-Origin", env('ACCESS_ORIGIN'));
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'LGT';
        $email      = $r->input('email');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$email.$date);

        if($token <> $r->input('token'))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400)->header("Access-Control-Allow-Origin", env('ACCESS_ORIGIN'));
        }
        /* end check token */

        /* check if session is active */
        $duplicate   = DB::table('tbl_user_session as a')
        ->join('tbl_user as b', 'a.user_id', 'b.user_id')
        ->where('email', '=', $r->email)
        ->count();

        if ($duplicate == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'session not found',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'session not found';
            return response()->json($response, 400)->header("Access-Control-Allow-Origin", env('ACCESS_ORIGIN'));
        }
        /* end */

        // DB::table('tbl_user_session as a')
        // ->join('tbl_user as b', 'a.user_id', 'b.user_id')
        // ->where('email', '=', $r->email)
        // ->delete();

        /*============ LOG =============*/
        $log = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->get();
        DB::table('tbl_log')->insert([
            'user_id'       => $log[0]->user_id,
            'action'        => 'LOGOUT',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /*============ LOG =============*/

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => json_encode($r->all()),
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200)->header("Access-Control-Allow-Origin", env('ACCESS_ORIGIN'));
    }

    public function _logout(request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'email'     => ['email', 'required','max:100']
        ]);

        if($validate->fails())
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'validation failed',
                'value'         => $id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'LGT';
        $email      = $r->input('email');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$email.$date);

        if($token <> $r->input('token'))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => $id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if session is active */
        $duplicate   = DB::table('tbl_user_session as a')
        ->join('tbl_user as b', 'a.user_id', 'b.user_id')
        ->where('email', '=', $r->email)
        ->count();

        if ($duplicate == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'session not found',
                'value'         => $id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'session not found';
            return response()->json($response, 400);
        }
        /* end */

        // DB::table('tbl_user_session as a')
        // ->join('tbl_user as b', 'a.user_id', 'b.user_id')
        // ->where('email', '=', $r->email)
        // ->delete();

        /*============ LOG =============*/
        $log = DB::table('tbl_user')
        ->where('email', '=', $r->email)
        ->get();
        DB::table('tbl_log')->insert([
            'user_id'       => $log[0]->user_id,
            'action'        => 'LOGOUT',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /*============ LOG =============*/

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => $id.', '.$token1,
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
    }


    public function flagDonation($user_id, $token1)
    {

        /* check token */
        $app_id     = 'FLD';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $token1)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => $user_id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if email is on db */
        $check   = DB::table('tbl_user')
        ->where('user_id', '=', $user_id)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'account not found',
                'value'         => $user_id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check if email is on db */

        /* check if user active status is 1 */
        $check   = DB::table('tbl_user')
        ->where('user_id', '=', $user_id)
        ->where('active_status', '=', 1)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'user inactive',
                'value'         => $user_id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'user is inactive';
            return response()->json($response, 400);
        }

        /*  update flag */
        $data = DB::table('tbl_user')
            ->where('user_id', $user_id)
            ->update([
                'flag_donation'    => 1
            ]);

        /* LOG */
        DB::table('tbl_log')->insert([
            'user_id'       => $user_id,
            'action'        => 'FLAG_DONATION_UPDATE',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /* LOG */

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => $user_id.', '.$token1,
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
    }

    public function flagTopup($user_id, $token1)
    {

        /* check token */
        $app_id     = 'FLT';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $token1)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => $user_id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if email is on db */
        $check   = DB::table('tbl_user')
        ->where('user_id', '=', $user_id)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'account not found',
                'value'         => $user_id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check if email is on db */

        /* check if user active status is 1 */
        $check   = DB::table('tbl_user')
        ->where('user_id', '=', $user_id)
        ->where('active_status', '=', 1)
        ->count();

        if ($check == 0)
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'user inactive',
                'value'         => $user_id.', '.$token1,
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'user is inactive';
            return response()->json($response, 400);
        }

        /*  update flag */
        $data = DB::table('tbl_user')
            ->where('user_id', $user_id)
            ->update([
                'flag_topup'    => 1
            ]);

        /* LOG */
        DB::table('tbl_log')->insert([
            'user_id'       => $user_id,
            'action'        => 'FLAG_TOPUP_UPDATE',
            'module'        => 'UserController.php',
            'notes'         => '',
            'created_at'    => Carbon::now()
            ]);
        /* LOG */

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => $user_id.', '.$token1,
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
    }


    public function signUpGuest(request $r)
    {

        /* check token */
        $app_id     = 'SGG';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $r->input('token'))
        {
            /* ====== LOG API ===== */
            DB::table('tbl_log_api')->insert([
                'function'      => (__FUNCTION__),
                'file_name'     => basename(__FILE__, '.php'),
                'message'       => 'invalid token',
                'value'         => json_encode($r->all()),
                'url'           => URL::current(),
                'created_at'    => Carbon::now()
                ]);
            /* ===== LOG API ===== */

            $response['status'] = 'error';
            $response['message'] = 'error';
            return response()->json($response, 400);
        }
        /* end check token */

        $permitted_chars = '0123456789abcdefghjkmnpqrstuvwxyz';
        $random = substr(str_shuffle($permitted_chars), 0, 10);
        $email = $random.Carbon::now()->isoFormat('YYYYMMDDHis').'@guest';

        DB::table('tbl_user')->insert([
            'email'              => $email,
            'first_name'         => 'guest',
            'last_name'          => $random,
            'phone'              => '999',
            'gender'             => 'Nothing',
            'password'           => md5($email),
            'age'                => '',
            'address1'           => '',
            'zip_code'           => '',
            'active_status'      => 1,
            'token'              => hash("sha256",$email),
            'created_at'         => now()
        ]);

        /* ====== LOG ===== */
        $log = DB::table('tbl_user')
        ->where('email', '=', $email)
        ->get();
        DB::table('tbl_log')->insert([
            'user_id'         => $log[0]->user_id,
            'action'        => 'SIGN_UP_GUEST',
            'notes'         => '',
            'module'        => 'UserController.php',
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG ===== */

        $data = ([
            'user_id'         => $log[0]->user_id.'',
          'email'              => $email,
          'first_name'         => 'guest',
          'last_name'          => $random,
          'phone'              => '999',
          'gender'             => 'Nothing',
          'password'           => $email,
          'age'                => '',
          'address1'           => '',
          'zip_code'           => '',
          'active_status'      => 'ACTIVE',
          'created_at'         => date('d-m-Y')
        ]);

        /* ====== LOG API ===== */
        DB::table('tbl_log_api')->insert([
            'function'      => (__FUNCTION__),
            'file_name'     => basename(__FILE__, '.php'),
            'message'       => 'success',
            'value'         => json_encode($r->all()),
            'url'           => URL::current(),
            'created_at'    => Carbon::now()
            ]);
        /* ===== LOG API ===== */

        $response['status'] = 'success';
        $response['message'] = $data;
        return response()->json($response, 200);

    }

}
