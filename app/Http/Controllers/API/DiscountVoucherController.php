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

class DiscountVoucherController extends Controller
{

    public function check(Request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'voucher_code'  => ['string', 'required','max:100']
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'CDV';
        $user_id    = $r->input('user_id');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $r->input('token'))
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::table('tbl_discount_voucher')
                ->where('code', $r->voucher_code)
                ->get();

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        if($data[0]->max_qty == 1)
        {

          if($data[0]->user_id <> $r->user_id)
          {
            $response['status'] = 'failed';
            $response['message'] = 'wrong user ID' ;
            return response()->json($response, 400);
          }else{

            if($data[0]->used_qty == 1)
            {
              $response['status'] = 'failed';
              $response['message'] = 'voucher has been used' ;
              return response()->json($response, 400);
            }else{
              $response['status'] = 'success';
              $response['message'] = 'valid' ;
              return response()->json($response, 200);
            }

          }

        }else{
          if($data[0]->used_qty > $data[0]->max_qty)
          {
            $response['status'] = 'failed';
            $response['message'] = 'voucher has been used' ;
            return response()->json($response, 400);
          }else{
            $response['status'] = 'success';
            $response['message'] = 'valids' ;
            return response()->json($response, 200);
          }
        }

        $arr = array($data);
        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['shop_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);
    }

    public function deduct(Request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'user_id'       => ['string', 'required','max:100'],
            'order_code'    => ['string', 'required','max:100'],
            'voucher_code'  => ['string', 'required','max:100']
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'DDV';
        $user_id    = $r->input('user_id');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $r->input('token'))
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::table('tbl_discount_voucher')
            ->select('code', 'max_qty', 'value')
            ->where('code', $r->voucher_code)
            ->get();

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        if(count($data) < $data[0]->max_qty)
        {
            DB::table('tbl_discount_voucher_log')->insert([
                'order_code'    => $r->order_code,
                'code'          => $r->voucher_code,
                'value'         => $data[0]->value,
                'user_id'       => $r->user_id,
                'created_at'    => now()
            ]);

            $response['status'] = 'success';
            $response['message'] = 'success' ;
            return response()->json($response, 200);
        }else{
          $response['status'] = 'failed';
          $response['message'] = 'Voucher already used' ;
          return response()->json($response, 400);
        }

    }

    public function activeVoucher(Request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'user_id'  => ['string', 'required','max:100']
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'DVA';
        $user_id    = $r->input('user_id');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $r->input('token'))
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::table('tbl_discount_voucher')
        ->select('discount_voucher_id', 'code', 'value', 'max_qty')
                ->where('user_id', $r->user_id)
                ->where('max_qty', 1)
                ->where('used_qty', 0)
                ->get();

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        $arr = array($data);
        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['shop_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);
    }

    public function redeemVoucher(Request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'user_id'  => ['string', 'required','max:100']
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'DVR';
        $user_id    = $r->input('user_id');
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $r->input('token'))
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::table('tbl_discount_voucher')
        ->select('discount_voucher_id', 'code', 'value', 'max_qty')
                ->where('user_id', $r->user_id)
                ->where('max_qty', 1)
                ->where('used_qty', 1)
                ->get();

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        $arr = array($data);
        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['shop_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);
    }


}
