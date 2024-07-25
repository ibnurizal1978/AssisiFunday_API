<?php
/*---------------- ASSISI 2022 -----------------------------*/
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Validator;
use URL;
use Illuminate\Support\Facades\Crypt;
use App\Mail\EmailSpinAndWin;
use Illuminate\Support\Facades\Mail;

class GamesController extends Controller
{
    public function ninjaJumpCheck(request $r)
    {

        /* check input validation */
        $validate   = validator::make($r->all(), [
            'user_id'       => ['string', 'required', 'max:10000'],
            'session_id'    => ['string', 'required']
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end */

        /* check token */
        $app_id     = 'NJC';
        $user_id    = $r->input('user_id');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $r->input('token'))
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end */

        /* check session */
        $count = DB::table('tbl_user_session')
            ->where('user_id', '=', $r->user_id)
            ->where('session_id', '=', $r->session_id)
            ->count();

        if($count == 0)
        {
            $response['status'] = 'failed';
            $response['message'] = 'no active session for this user';
            return response()->json($response, 400);
        }

        /* check if user has play more than 2 today */
        $now = date('Y-m-d');
        $count = DB::table('tbl_game_ninja_game')
            ->where('user_id','=', $r->user_id)
            ->whereDate('created_at', '=', $now)
            ->count();

        if($count > 2)
        {
            $response['status'] = 'failed';
            $response['message'] = 'false';
            return response()->json($response, 400);
        }
        /* end */

        $response['status'] = 'success';
        $response['message'] = 'true';
        return response()->json($response, 200);
    }

    public function ninjaJumpResult(request $r)
    {

        /*
        - lihat master prize di tbl_ninjagameprize and package game
        - lihat termsnya utk daily win
        function random() {
        return (float)rand()/(float)getrandmax();
        }

        /* check input validation */
        $validate   = validator::make($r->all(), [
            'user_id'    => ['string', 'required', 'max:10000'],
            'session_id' => ['string', 'required'],
            'score'      => ['string', 'required', 'max:1000']
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end */

        /* check token */
        $app_id     = 'NJR';
        $user_id    = $r->input('user_id');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $r->input('token'))
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end */

        /* check if user has play more than 2 */
        $now = date('Y-m-d');
        $count = DB::table('tbl_game_ninja_game')
            ->where('user_id','=', $r->user_id)
            ->whereDate('created_at', '=', $now)
            ->count();

        if($count > 2)
        {
            $response['status'] = 'failed';
            $response['message'] = 'user has reach maximum play on today';
            return response()->json($response, 400);
        }
        /* end */

        /* SUPRISEEEEEEE :D */
        /* check if daily has 60 winners */
        $now = date('Y-m-d');
        $count = DB::table('tbl_game_ninja_game')
            ->where('prize_code','<>', 'no_prize')
            ->whereDate('created_at', '=', $now)
            ->count();

        if($count > 59)
        {
            DB::table('tbl_game_ninja_game')->insert([
              'user_id'       => $r->user_id,
              'voucher_code'  => '',
              'score'         => $r->score,
              'prize_code'    => 'no_prize',
              'created_at'    => now()
            ]);

            $response['status'] = 'success';
            $response['message'] = 'no_prize';
            return response()->json($response, 200);
        }
        /* end */

        /*=== if user hit >= 100 then this condition berlaku ===*/
        if($r->score >= 100)
        {

          /* check the prize, is there any available prize? */
          $count = DB::table('tbl_ninja_game_master')
            ->where('used_status','=',0)
            ->count();

          if($count == 0) // no available prize
          {
            $response['status'] = 'failed';
            $response['message'] = 'no prize available';
            return response()->json($response, 400);
          }
          /* end */

          /* create a random probability. 0 = no prize, 1 = voucher, 2 = ticket */
          $possi = array(1, 2);
          $decide = rand(0,2);

          if($decide ==0)
          {
            DB::table('tbl_game_ninja_game')->insert([
              'user_id'       => $r->user_id,
              'voucher_code'  => '',
              'score'         => $r->score,
              'prize_code'    => 'no_prize',
              'created_at'    => now()
            ]);

            $response['status'] = 'success';
            $response['message'] = 'no_prize';
            return response()->json($response, 200);

          }elseif($decide==1){

            /* check if today reached maximum winner: ticket: 2, voucher, 3 */
            $count = DB::table('tbl_game_ninja_game')
              ->where('prize_code','=','2Voucher')
              ->whereDate('created_at','=', $now)
              ->count();

            if($count > 2)
            {
              DB::table('tbl_game_ninja_game')->insert([
                'user_id'       => $r->user_id,
                'voucher_code'  => '',
                'score'         => $r->score,
                'prize_code'    => 'no_prize1',
                'created_at'    => now()
              ]);

              $response['status'] = 'failed';
              $response['message'] = 'no_prize1';
              return response()->json($response, 400);
            }

            //generate discount voucher code
            $voucher_code = 'DV'.date('hs').substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 5);

            DB::table('tbl_discount_voucher')->insert([
                'code'          => $voucher_code,
                'name'          => '$2 voucher',
                'value'         => 2,
                'user_id'       => $r->user_id,
                'status'        => 0,
                'max_qty'       => 1,
                'created_at'    => now()
            ]);

            DB::table('tbl_game_ninja_game')->insert([
                'user_id'       => $r->user_id,
                'voucher_code'  => $voucher_code,
                'score'         => $r->score,
                'prize_code'    => '2Voucher',
                'created_at'    => now()
            ]);


            DB::table('tbl_ninja_game_master')
              ->where('used_status','=',0)
              ->where('prize_code','=','2Voucher')
              ->take(1)
              ->update([
                'used_status'   => 1,
                'updated_at'  => Carbon::now()
              ]);

            $response['status'] = 'success';
            $response['message'] = 'voucher';
            $response['voucherCode'] = $voucher_code;
            return response()->json($response, 200);

          }else{

            DB::table('tbl_game_ninja_game')->insert([
                'user_id'       => $r->user_id,
                'voucher_code'  => '',
                'score'         => $r->score,
                'prize_code'    => 'Golden Ticket',
                'created_at'    => now()
            ]);

            DB::table('tbl_lucky_draw')->insert([
              'user_id'          => $r->user_id,
              'via'              => 'Ninja Jump Golden Ticket',
              'created_at'       => now()
            ]);

            /*DB::table('tbl_ninja_game_master')
              ->where('used_status','=',0)
              ->where('prize_code','=','goldern ticket')
              ->take(1)
              ->update([
               'used_status'   => 1,
               'updated_at'    => Carbon::now()
             ]);*/

            $response['status'] = 'success';
            $response['message'] = 'goldern ticket';
            return response()->json($response, 200);

          }

        }

      DB::table('tbl_game_ninja_game')->insert([
        'user_id'       => $r->user_id,
        'voucher_code'  => '',
        'score'         => $r->score,
        'prize_code'    => 'no_prize',
        'created_at'    => now()
      ]);

      $response['status'] = 'success';
      $response['message'] = 'no_prize';
      return response()->json($response, 200);
  }


  /* ======= NUN ======= */
  public function NunCheck(request $r)
  {

      /* check input validation */
      $validate   = validator::make($r->all(), [
          'user_id'       => ['string', 'required', 'max:10000'],
          'session_id'    => ['string', 'required']
      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end */

      /* check token */
      $app_id     = 'SWC';
      $user_id    = $r->input('user_id');
      $date       = Carbon::now()->isoFormat('YYYYMMDD');
      $token      = hash("sha256",$app_id.$user_id.$date);

      if($token <> $r->input('token'))
      {
          $response['status'] = 'error';
          $response['message'] = 'invalid token';
          return response()->json($response, 400);
      }
      /* end */

      /* check session */
      $count = DB::table('tbl_user_session')
          ->where('user_id', '=', $r->user_id)
          ->where('session_id', '=', $r->session_id)
          ->count();

      if($count == 0)
      {
          $response['status'] = 'failed';
          $response['message'] = 'no active session for this user';
          return response()->json($response, 400);
      }

      $now = date('Y-m-d');
      /*$count = DB::table('tbl_game_nun')
          ->where('user_id','=', $r->user_id)
          ->whereDate('created_at', '=', $now)
          ->count();*/
      $data = DB::table('tbl_game_nun')
          ->select('location')
          ->where('user_id','=', $r->user_id)
          ->whereDate('created_at', '=', $now)
          ->get();

      $response['status'] = 'success';
      $response['message'] = $data;
      return response()->json($response, 200);
  }

  public function nunResult(request $r)
  {

      /* check input validation */
      $validate   = validator::make($r->all(), [
          'user_id'    => ['string', 'required', 'max:10000'],
          'session_id' => ['string', 'required'],
          'location'      => ['string', 'required', 'max:1000']
      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end */

      /* check token */
      $app_id     = 'NJR';
      $user_id    = $r->input('user_id');
      $date       = Carbon::now()->isoFormat('YYYYMMDD');
      $token      = hash("sha256",$app_id.$user_id.$date);

      if($token <> $r->input('token'))
      {
          $response['status'] = 'error';
          $response['message'] = 'invalid token';
          return response()->json($response, 400);
      }
      /* end */

          /* check if user has play more than 2 */
      $now = date('Y-m-d');
      $count = DB::table('tbl_game_nun')
              ->where('user_id','=', $r->user_id)
              ->whereDate('created_at', '=', $now)
              ->count();

    if($count > 9)
    {
        $response['status'] = 'failed';
        $response['message'] = 'user has reach maximum play on today';
        return response()->json($response, 400);
    }

    DB::table('tbl_game_nun')->insert([
        'user_id'       => $r->user_id,
        'location'    => $r->location,
        'created_at'    => now()
    ]);

    $response['status'] = 'success';
    $response['message'] = 'data inserted';
    return response()->json($response, 200);
  }
  /* ======= END NUN ======== */


  /* ===== SPIN AND WIN ===== */
  public function spinAndWinCheck(request $r)
  {

      /* check input validation */
      $validate   = validator::make($r->all(), [
          'user_id'       => ['string', 'required', 'max:10000'],
          'session_id'    => ['string', 'required']
      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end */

      /* check token */
      $app_id     = 'SWC';
      $user_id    = $r->input('user_id');
      $date       = Carbon::now()->isoFormat('YYYYMMDD');
      $token      = hash("sha256",$app_id.$user_id.$date);

      if($token <> $r->input('token'))
      {
          $response['status'] = 'error';
          $response['message'] = 'invalid token';
          return response()->json($response, 400);
      }
      /* end */

      /* check session */
      $count = DB::table('tbl_user_session')
          ->where('user_id', '=', $r->user_id)
          ->where('session_id', '=', $r->session_id)
          ->count();

      if($count == 0)
      {
          $response['status'] = 'failed';
          $response['message'] = 'no active session for this user';
          return response()->json($response, 400);
      }

      /* check if user has got 10 nun today */
      $now = date('Y-m-d');
      $count = DB::table('tbl_game_nun')
          ->where('user_id','=', $r->user_id)
          ->whereDate('created_at', '=', $now)
          ->count();

      if($count <> 10)
      {
          $response['status'] = 'failed';
          $response['message'] = 'nun less than 10';
          return response()->json($response, 400);
      }
      /* end */

      /* check if user has play more than 2 today */
      $now = date('Y-m-d');
      $count = DB::table('tbl_game_spin_and_win')
          ->where('user_id','=', $r->user_id)
          ->whereDate('created_at', '=', $now)
          ->count();

      if($count > 0)
      {
          $response['status'] = 'failed';
          $response['message'] = 'false';
          return response()->json($response, 400);
      }
      /* end */

      $response['status'] = 'success';
      $response['message'] = 'true';
      return response()->json($response, 200);
  }



  public function spinAndWinResult(request $r)
  {

      /* check input validation */
      $validate   = validator::make($r->all(), [
          'user_id'    => ['string', 'required', 'max:10000'],
          'session_id' => ['string', 'required'],
          'score'      => ['string', 'required', 'max:1000']
      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end */

      /* check token */
      $app_id     = 'SWR';
      $user_id    = $r->input('user_id');
      $date       = Carbon::now()->isoFormat('YYYYMMDD');
      $token      = hash("sha256",$app_id.$user_id.$date);

      if($token <> $r->input('token'))
      {
          $response['status'] = 'error';
          $response['message'] = 'invalid token';
          return response()->json($response, 400);
      }
      /* end */

      /* check if user has play more than 1 */
      $now = date('Y-m-d');
      $count = DB::table('tbl_game_spin_and_win')
              ->where('user_id','=', $r->user_id)
              ->whereDate('created_at', '=', $now)
              ->count();

      if($count > 0)
      {
          $response['status'] = 'failed';
          $response['message'] = 'user has reach maximum play on today';
          return response()->json($response, 400);
      }
      /* end */

      /* check if user has session */
      $now = date('Y-m-d');
      $count = DB::table('tbl_user_session')
              ->where('user_id','=', $r->user_id)
              ->count();

      if($count == 0)
      {
          $response['status'] = 'failed';
          $response['message'] = 'no session id for this user';
          return response()->json($response, 400);
      }
      /* end */

      /*get first name data */
      $user = DB::table('tbl_user')
        ->where('user_id', $r->user_id)
        ->get();

      /* check if session correct */
      $now = date('Y-m-d');
      $count = DB::table('tbl_user_session')
              ->where('user_id','=', $r->user_id)
              ->where('session_id','=', $r->session_id)
              ->count();

      if($count == 0)
      {
          $response['status'] = 'failed';
          $response['message'] = 'invalid session id';
          return response()->json($response, 400);
      }
      /* end */

      /* check if daily has 50 winners */
      $now = date('Y-m-d');
      $count = DB::table('tbl_game_spin_and_win')
          ->where('prize','<>', 'no_prize')
          ->whereDate('created_at', '=', $now)
          ->count();

      if($count > 49)
      {
          DB::table('tbl_game_spin_and_win')->insert([
            'user_id'       => $r->user_id,
            'voucher_code'  => '',
            'score'         => $r->score,
            'prize'         => 'no_prize',
            'created_at'    => now()
          ]);

          $response['status'] = 'success';
          $response['message'] = 'no_prize';
          return response()->json($response, 200);
      }
      /* end */


      /* create a random probability.
      0 = no prize (oh no), 1 = no prize (dnt give up), 2 = no prize (try again)
      3 = health supplement, 4 = golden ticket, 5 = bluetooth speaker, 6 = hand cream, 7 = eco friendly utiliez
      */
      $possi = array(1, 2);
      $decide = rand(0,7);

      if($decide == 0 || $decide == 1 || $decide == 2)
      {
       DB::table('tbl_game_spin_and_win')->insert([
            'user_id'       => $r->user_id,
            'prize' => 'no_prize',
            'created_at'    => now()
        ]);

        $response['status'] = 'success';
        $response['message'] = 'no_prize';
        return response()->json($response, 200);

      }elseif($decide == 3) {
        DB::table('tbl_game_spin_and_win')->insert([
            'user_id'       => $r->user_id,
            'prize' => 'Ocean Health Buff C1000',
            'created_at'    => now()
        ]);

        /* email */
        $data = [
            'email'     => hash("sha256",$user[0]->email),
            'full_name' => $user[0]->first_name.' '.$user[0]->last_name,
            'prize'     => 'Ocean Health Buff C1000',
        ];
        Mail::to($user[0]->email)->send(new EmailSpinAndWin($data));
        /* end email */

        $response['status'] = 'success';
        $response['message'] = 'Ocean Health Buff C1000';
        return response()->json($response, 200);

      }elseif($decide == 4) {
        DB::table('tbl_game_spin_and_win')->insert([
            'user_id'       => $r->user_id,
            'prize' => '1 Lucky Draw Chance',
            'created_at'    => now()
        ]);

        DB::table('tbl_lucky_draw')->insert([
          'user_id'          => $r->user_id,
          'via'              => 'Spin and Win Golden Ticket',
          'created_at'       => now()
        ]);

        /* email */
        /* $data = [
            'email'     => hash("sha256",$user[0]->email),
            'full_name' => $user[0]->first_name.' '.$user[0]->last_name,
            'prize'     => 'Lucky Draw Chance',
        ];
        Mail::to($user[0]->email)->send(new EmailSpinAndWin($data)); */
        /* end email */

        $response['status'] = 'success';
        $response['message'] = '1 Lucky Draw Chance';
        return response()->json($response, 200);

      }elseif($decide == 5) {
        DB::table('tbl_game_spin_and_win')->insert([
            'user_id'       => $r->user_id,
            'prize' => 'Bluetooth Speakers',
            'created_at'    => now()
        ]);

        /* email */
        $data = [
            'email'     => hash("sha256",$user[0]->email),
            'full_name' => $user[0]->first_name.' '.$user[0]->last_name,
            'prize'     => 'Bluetooth Speakers',
        ];
        Mail::to($user[0]->email)->send(new EmailSpinAndWin($data));
        /* end email */

        $response['status'] = 'success';
        $response['message'] = 'Bluetooth Speakers';
        return response()->json($response, 200);

      }elseif($decide == 6) {
        DB::table('tbl_game_spin_and_win')->insert([
            'user_id'       => $r->user_id,
            'prize' => 'Ceradan Hand Lotion Sanitiser',
            'created_at'    => now()
        ]);

        /* email */
        $data = [
            'email'     => hash("sha256",$user[0]->email),
            'full_name' => $user[0]->first_name.' '.$user[0]->last_name,
            'prize'     => 'Ceradan Hand Lotion Sanitiser',
        ];
        Mail::to($user[0]->email)->send(new EmailSpinAndWin($data));
        /* end email */

        $response['status'] = 'success';
        $response['message'] = 'Ceradan Hand Lotion Sanitiser';
        return response()->json($response, 200);

      }else{
        DB::table('tbl_game_spin_and_win')->insert([
            'user_id'       => $r->user_id,
            'prize' => 'Eco-friendly Utensil & Carrier Set',
            'created_at'    => now()
        ]);

        /* email */
        $data = [
            'email'     => hash("sha256",$user[0]->email),
            'full_name' => $user[0]->first_name.' '.$user[0]->last_name,
            'prize'     => 'Eco-friendly Utensil & Carrier Set',
        ];
        Mail::to($user[0]->email)->send(new EmailSpinAndWin($data));
        /* end email */

        $response['status'] = 'success';
        $response['message'] = 'Eco-friendly Utensil & Carrier Set';
        return response()->json($response, 200);
      }


  }

  /* ===== END SPIN AND WIN ===== */

  public function whackAMoleCheck(request $r)
  {

      /* check input validation */
      $validate   = validator::make($r->all(), [
          'user_id'       => ['string', 'required', 'max:10000'],
          'session_id'    => ['string', 'required']
      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end */

      /* check token */
      $app_id     = 'WAMC';
      $user_id    = $r->input('user_id');
      $date       = Carbon::now()->isoFormat('YYYYMMDD');
      $token      = hash("sha256",$app_id.$user_id.$date);

      if($token <> $r->input('token'))
      {
          $response['status'] = 'error';
          $response['message'] = 'invalid token'.$token;
          return response()->json($response, 400);
      }
      /* end */

      /* check session */
      $count = DB::table('tbl_user_session')
          ->where('user_id', '=', $r->user_id)
          ->where('session_id', '=', $r->session_id)
          ->count();

      if($count == 0)
      {
          $response['status'] = 'failed';
          $response['message'] = 'no active session for this user';
          return response()->json($response, 400);
      }

      /* check if user has play more than 2 today */
      $now = date('Y-m-d');
      $count = DB::table('tbl_game_whack_a_mole')
          ->where('user_id','=', $r->user_id)
          ->whereDate('created_at', '=', $now)
          ->count();

      if($count > 2)
      {
          $response['status'] = 'failed';
          $response['message'] = 'user has reach maximum play on today';
          return response()->json($response, 400);
      }
      /* end */

      $response['status'] = 'success';
      $response['message'] = 'true';
      return response()->json($response, 200);
  }

//   public function whackAMoleReset(request $r)
//   {
//       /* check input validation */
//       $validate   = validator::make($r->all(), [
//         'user_id'       => ['string', 'required', 'max:10000'],
//     ]);

//     if($validate->fails())
//       {
//           $response['status'] = 'error';
//           $response['message'] = 'validation failed';
//           return response()->json($response, 400);
//       }
//       /* end */

//       /* check token */
//       $app_id     = 'WMR';
//       $user_id    = $r->input('user_id');
//       $date       = Carbon::now()->isoFormat('YYYYMMDD');
//       $token      = hash("sha256",$app_id.$user_id.$date);

//       if($token <> $r->input('token'))
//       {
//           $response['status'] = 'error';
//           $response['message'] = 'invalid token'.$token;
//           return response()->json($response, 400);
//       }
//       /* end */

//       $count = DB::table('tbl_game_whack_a_mole')
//             ->where('user_id',$user_id)
//             ->delete();

//       if ($count <= 0) {
//         $response['status'] = 'error';
//         $response['message'] = 'no user_id in db';
//         return response()->json($response, 200);
//       }

//       $response['status'] = 'success';
//       $response['message'] = 'success';
//       return response()->json($response, 200);
//   }

  public function whackAMoleResult(request $r)
  {

      /* check input validation */
      $validate   = validator::make($r->all(), [
          'user_id'    => ['string', 'required', 'max:10000'],
          'session_id' => ['string', 'required'],
          'score'      => ['string', 'required', 'max:1000']
      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end */

      /* check token */
      $app_id     = 'WAMR';
      $user_id    = $r->input('user_id');
      $date       = Carbon::now()->isoFormat('YYYYMMDD');
      $token      = hash("sha256",$app_id.$user_id.$date);

      if($token <> $r->input('token'))
      {
          $response['status'] = 'error';
          $response['message'] = 'invalid token'.$token;
          return response()->json($response, 400);
      }
      /* end */

      /* check if user has play more than 2 */
      $now = date('Y-m-d');
      $count = DB::table('tbl_game_whack_a_mole')
          ->where('user_id','=', $r->user_id)
          ->whereDate('created_at', '=', $now)
          ->count();

      if($count > 2)
      {
          $response['status'] = 'failed';
          $response['message'] = 'user has reach maximum play on today';
          return response()->json($response, 400);
      }
      /* end */

        /* check if daily has 60 winners */
        $now = date('Y-m-d');
        $count = DB::table('tbl_game_whack_a_mole')
            ->where('prize_code','<>', 'no_prize')
            ->whereDate('created_at', '=', $now)
            ->count();

        if($count > 59)
        {
            DB::table('tbl_game_whack_a_mole')->insert([
                'user_id'       => $r->user_id,
                'voucher_code'  => '',
                'score'         => $r->score,
                'prize_code'    => 'no_prize',
                'created_at'    => now()
            ]);

            $response['status'] = 'success';
            $response['message'] = 'no_prize';
            return response()->json($response, 200);
        }
        /* end */

      /*=== if user hit >= 100 then this condition berlaku ===*/
      if($r->score >= 100)
      {
          /* create a random probability. 0 = no prize, 1 = voucher, 2 = ticket */
          $decide = rand(0,2);

          if($decide ==0)
          {
              DB::table('tbl_game_whack_a_mole')->insert([
                  'user_id'       => $r->user_id,
                  'voucher_code'  => '',
                  'score'         => $r->score,
                  'prize_code'    => 'no_prize',
                  'created_at'    => now()
              ]);

              $response['status'] = 'success';
              $response['message'] = 'no_prize';
              return response()->json($response, 200);

          }elseif($decide==1){

              /* check if today reached maximum winner: ticket: 40, voucher, 20 */
              $count = DB::table('tbl_game_whack_a_mole')
                  ->where('prize_code','=','2Voucher')
                  ->whereDate('created_at','=', $now)
                  ->count();

              if($count > 20)
              {
                  DB::table('tbl_game_whack_a_mole')->insert([
                  'user_id'       => $r->user_id,
                  'voucher_code'  => '',
                  'score'         => $r->score,
                  'prize_code'    => 'no_prize1',
                  'created_at'    => now()
                  ]);

                  $response['status'] = 'failed';
                  $response['message'] = 'no_prize1';
                  return response()->json($response, 400);
              }

              //generate discount voucher code
              $voucher_code = 'DV'.date('hs').substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 5);

              DB::table('tbl_discount_voucher')->insert([
                  'code'          => $voucher_code,
                  'name'          => '$2 voucher',
                  'value'         => 2,
                  'user_id'       => $r->user_id,
                  'status'        => 0,
                  'max_qty'       => 1,
                  'created_at'    => now()
              ]);

              DB::table('tbl_game_whack_a_mole')->insert([
                  'user_id'       => $r->user_id,
                  'voucher_code'  => $voucher_code,
                  'score'         => $r->score,
                  'prize_code'    => '2Voucher',
                  'created_at'    => now()
              ]);


              $response['status'] = 'success';
              $response['message'] = 'voucher';
              $response['voucherCode'] = $voucher_code;
              return response()->json($response, 200);

          }else{

            /* check if today reached maximum winner: ticket: 40, voucher, 20 */
            $count = DB::table('tbl_game_whack_a_mole')
            ->where('prize_code','=','Golden Ticket')
            ->whereDate('created_at','=', $now)
            ->count();

            if($count > 40)
            {
                DB::table('tbl_game_whack_a_mole')->insert([
                'user_id'       => $r->user_id,
                'voucher_code'  => '',
                'score'         => $r->score,
                'prize_code'    => 'no_prize1',
                'created_at'    => now()
                ]);

                $response['status'] = 'failed';
                $response['message'] = 'no_prize2';
                return response()->json($response, 400);
            }

              DB::table('tbl_game_whack_a_mole')->insert([
                  'user_id'       => $r->user_id,
                  'voucher_code'  => '',
                  'score'         => $r->score,
                  'prize_code'    => 'Golden Ticket',
                  'created_at'    => now()
              ]);

              DB::table('tbl_lucky_draw')->insert([
                'user_id'          => $r->user_id,
                'via'              => 'Whack A Mole Golden Ticket',
                'created_at'       => now()
              ]);

              $response['status'] = 'success';
              $response['message'] = 'golden ticket';
              return response()->json($response, 200);

          }

      }

      DB::table('tbl_game_whack_a_mole')->insert([
          'user_id'       => $r->user_id,
          'voucher_code'  => '',
          'score'         => $r->score,
          'prize_code'    => 'no_prize3',
          'created_at'    => now()
      ]);

      $response['status'] = 'success';
      $response['message'] = 'true';
      return response()->json($response, 200);
    }
}
