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

class OtherController extends Controller
{
    public function time($token1)
    {
        /* check token */
        $app_id     = 'TIM';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */
        date_default_timezone_set("Etc/GMT-8");
        $time = date('d-m-Y H:i:s');

        $response['status'] = 'success';
        $response['message'] = $time;
        return response()->json($response, 200);
    }

    public function newsTicker($token1)
    {
        /* check token */
        $app_id     = 'NTC';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */
        $now = date('Y-m-d');

        DB::enableQueryLog();
        $data = DB::table('tbl_news_ticker')
            ->where('live_date', $now)
            ->orderby('created_at')
            ->get();
        //dd(DB::getQueryLog());

        $arr = array($data);

        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['news_ticker_id'] = $arrKey;
            $incI++;
        }

        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);
    }

    /*=== LUCKY DRAW === */
    public function luckyDrawChance($id, $token1)
    {


        /* check token */
        $app_id     = 'LDC';
        $user_id    = $id;
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::select(
            DB::raw("SELECT count(lucky_draw_id) as total FROM tbl_lucky_draw WHERE user_id = '".$user_id)."'");

        $response['status'] = 'success';
        $response['message'] = $data;
        return response()->json($response, 200);
    }

    public function fbLive($token1)
    {
        /* check token */
        $app_id     = 'FBL';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::table('tbl_fb_live')
            ->select('url', 'is_live')
            ->get();

        $response['status'] = 'success';
        $response['message'] = $data;
        return response()->json($response, 200);
    }

    public function luckyDrawWinner($token1)
    {
        /* check token */
        $app_id     = 'LDW';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::select(
          //DB::raw("SELECT first_name, last_name, email, phone, winner_date FROM tbl_lucky_draw a INNER JOIN tbl_user b USING (user_id) WHERE approval_status = 1 AND date(winner_date) = SUBDATE(CURRENT_DATE, 1)"));
          DB::raw("SELECT first_name, last_name, concat(repeat('*', char_length(phone) - 4), substr(phone, 5, 10))AS phone, winner_date FROM tbl_lucky_draw a INNER JOIN tbl_user b USING (user_id) WHERE approval_status = 1"));

        $response['status'] = 'success';
        $response['message'] = $data;
        return response()->json($response, 200);
    }





}
