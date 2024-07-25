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
use App\Mail\EmailVoucher;
use URL;
use PDF;
use Illuminate\Support\Facades\Crypt;
use Storage;
use Redirect;

class DepositController extends Controller
{
    public function void(request $r)
    {

      $count = DB::table('tbl_order')
      ->where('order_code', $r->order_code)
      ->where('order_status', 'PENDING')
      ->count();
      if($count == 0)
      {
        $response['status'] = 'error';
        $response['message'] = 'invalid order code';
        return response()->json($response, 400);
      }

      if($count > 0)
      {

        $data = DB::table('tbl_order')
        ->where('order_code', $r->order_code)
        ->get();

        /* send back his ewallet (if any) */
        DB::table('tbl_ledger')
        ->where('tranId', $r->order_code)
        ->where('user_id', $data[0]->user_id)
        ->where('ledger_type', 'DEBET')
        ->delete();
        /* end */

        /* make DV re-ready*/
        DB::table('tbl_discount_voucher_log')
        ->where('order_code', $r->order_code)
        ->where('user_id', $data[0]->user_id)
        ->delete();

        DB::select(
            DB::raw("UPDATE tbl_discount_voucher SET status = 0, updated_at = '0000-00-00 00:00:00', used_qty = used_qty-1, order_code = '' WHERE order_code = '".$r->order_code."' LIMIT 1")
        );

        DB::SELECT(
          DB::raw("UPDATE tbl_order SET delivery_fee = 0.00, donation = 0.00, voucher_amount = 0.00, credit_used = 0.00 WHERE order_code = '".$r->order_code."' LIMIT 1")
        );
        /* end */

        //$response['status'] = 'success';
        //$response['message'] = env('ASSISI_URL').'/cart.html';
        return Redirect::to(env('ASSISI_URL').'/cart.html');
        //return response()->json($response, 200);

      }
    }

    public function view($id,$token1)
    {

        /* check token */
        $app_id     = 'DPV';
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

        $count = DB::table('tbl_ledger')
        ->where('user_id', $user_id)
        ->count();

        if($count == 0)
        {
            $response['status'] = 'failed';
            $response['message'] = 'user not found';
            return response()->json($response, 400);
        }

        $data = DB::select(
            DB::raw("SELECT ((SELECT sum(totalAmount) as credit FROM `tbl_ledger` WHERE ledger_type = 'CREDIT' AND user_id = '".$user_id."' AND responseCode =00) - ifnull((SELECT sum(totalAmount) as debet FROM `tbl_ledger` WHERE ledger_type = 'DEBET' AND user_id = '".$user_id."' AND responseCode =00),0)) as balance FROM tbl_ledger WHERE user_id = '".$user_id."' ORDER BY ledger_id DESC LIMIT 1")
        );

        $response['status'] = 'success';
        $response['message'] = $data[0]->balance;
        return response()->json($response, 200);

    }

    public function deduct(request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'user_id'       => ['string', 'required'],
            'amount'        => ['string', 'required'],
            'order_code'    => ['string', 'required'],
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'DDC';
        $user_id    = $r->user_id;
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$user_id.$date);

        if($token <> $r->input('token'))
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        /* check if data is on db */
        $data = DB::select(
            DB::raw("SELECT COALESCE(((SELECT SUM(credit) FROM tbl_ledger b WHERE b.ledger_id <= a.ledger_id AND user_id = '".$user_id."' AND trx_status IN ('Approved', 'completed', 'successfully') AND responseCode = 00 AND responseMsg IN ('Approved', 'completed', 'successfully')) - (SELECT SUM(debet) FROM tbl_ledger b WHERE b.ledger_id <= a.ledger_id AND user_id = '".$user_id."' AND trx_status IN ('Approved', 'completed', 'successfully'))), 0) as balance FROM tbl_ledger a WHERE user_id = '".$user_id."' ORDER BY ledger_id DESC LIMIT 1")
        );

        if ($data[0]->balance < $r->amount)
        {
            $response['status'] = 'failed';
            $response['message'] = 'Insufficient Balance';
            return response()->json($response, 400);
        }
        /* end */

        /* check if order code exist */
        $data = DB::table('tbl_order')
            ->where('order_code', '=', $r->order_code)
            ->where('order_status', '<>', 'COMPLETED')
            ->where('user_id', '=', $r->user_id)
            ->count();

        if($data == 0) {
            $response['status'] = 'failed';
            $response['message'] = 'order code not found';
            return response()->json($response, 400);
        }

        /* success */
        $data = DB::select(
            DB::raw("SELECT sum(sub_total) as total FROM tbl_order_detail WHERE order_code = '".$r->order_code."'")
        );

        DB::table('tbl_ledger')->insert([
            'ledger_type'           => 'DEBET',
            'description'           => 'PURCHASE',
            'amount'                => $data[0]->total,
            'debet'                 => $data[0]->total,
            'currency'              => 'SGD',
            'user_id'               => $r->user_id,
            'trx_status'            => 'Approved',
            'tranId'                => $r->order_code,
            'responseCode'          => '00',
            'responseMsg'           => 'SUCCESS',
            'order_code'            => $r->order_code,
            'created_at'            => now()
        ]);

        DB::table('tbl_order')
          ->where('order_code', $r->order_code)
          ->update([
          'order_status'  => 'COMPLETED',
          'updated_at'    => now()
          ]);

        $url=env('ASSISI_URL').'/backend/public/cronjob/post_payment.php';
        $data=array("order_code"=>$r->order_code);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result2 = curl_exec($curl);
        /* end */

        /*$data1 = DB::table('tbl_ledger')->insert([
            'ledger_type'           => 'DEBET',
            'description'           => 'PURCHASE',
            'amount'                => $r->amount,
            'debet'                 => $r->amount,
            'currency'              => 'SGD',
            'user_id'               => $r->user_id,
            'trx_status'            => 'Approved',
            'tranId'                => $r->order_code,
            'responseCode'          => '00',
            'responseMsg'           => 'Success',
            'order_code'            => $r->order_code,
            'created_at'            => now()
        ]);

        DB::table('tbl_order')
            ->where('order_code', $r->order_code)
            ->where('user_id', $r->user_id)
            ->update([
                'order_status'            => 'SUCCESS'
            ]);*/

        //check if this product get evoucher?
        /*$data_evoucher = DB::table('tbl_evoucher')
        ->join('tbl_order_detail','tbl_order_detail.product_id','=','tbl_evoucher.product_id')
        ->join('tbl_order_shop','tbl_order_detail.shop_id','=','tbl_order_shop.shop_id')
        ->where('tbl_order_detail.order_code', '=', $r->order_code)
        ->count();

        if($data_evoucher > 0)
        {
            $data = DB::table('tbl_evoucher')
            ->join('tbl_order_detail','tbl_order_detail.product_id','=','tbl_evoucher.product_id')
            ->join('tbl_order_shop','tbl_order_detail.shop_id','=','tbl_order_shop.shop_id')
            ->where('tbl_order_detail.order_code', '=', $r->order_code)
            ->get();

            foreach($data as $datas)
            {
                //get how many qty to get evoucher
                $data2 = DB::table('tbl_order_detail')
                ->where('order_code', '=', $r->order_code)
                ->where('product_id', '=', $data[0]->product_id)
                ->get();

                $data_evoucher_detail = DB::select(
                    DB::raw("SELECT count(order_code) as total FROM tbl_evoucher_detail WHERE order_code = '".$r->order_code."' AND user_id = '".$r->user_id."'")
                );

                if($data_evoucher_detail[0]->total == $data[0]->qty)
                {

                }else{
                    for($i=0; $i<$data2[0]->qty; $i++)
                    {
                        $evoucher_code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 8);
                        DB::table('tbl_evoucher_detail')->insert([
                            'evoucher_master_id'    => $data[0]->evoucher_master_id,
                            'evoucher_code'         => $evoucher_code,
                            'shop_id'               => $data[0]->shop_id,
                            'product_id'            => $data[0]->product_id,
                            'user_id'               => $r->user_id,
                            'order_code'            => $r->order_code,
                            'created_at'            => now()
                        ]);
                    }
                }

                $data_evoucher_detail = DB::table('tbl_evoucher_detail')
                ->where('order_code', '=', $r->order_code)
                ->where('email_sent', '=', 0)
                ->get();

                $arr = array($data_evoucher_detail);
                $arrNew = array();
                $incI = 0;
                foreach($arr AS $arrKey => $arrData){
                    $arrNew[$incI]['evoucher_code'] = $arrKey;

                    $data_echo = [
                        'evoucher_code' => $data_evoucher_detail[0]->evoucher_code,
                        'title' => 'godeg',
                        'date' => date('m/d/Y')
                    ];
                    $pdf = PDF::loadView('email/emailVoucher', $data_echo);

                    $incI++;
                }


                return $pdf->download('itsolutionstuff.pdf');
                //file_put_contents('public/evoucher/'.$r->order_code.'-'.$data[0]->shop_id.'.pdf', $pdf->output());
                $response['status'] = 'success';
                $response['message'] = $arr;
                return response()->json($response, 200);

            }
        }*/


       // $data = '';
        //Mail::to($r->email)->send(new EmailVoucher($data));

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
    }


}
