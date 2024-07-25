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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /*========== NOTIFY ===========*/
    public function notifyPaymentCart(request $r)
    {
      Log::error($r);
      if(count($r->all()) == 0) {
        $response['status'] = 'failed';
        $response['message'] = 'empty message';
        return response()->json($response, 200);
      }

      $data = DB::table('tbl_order')
        ->where('order_code', $r->order_id)
        ->get();

      DB::table('tbl_log_payment_result')->insert([
        'user_id'                   => $data[0]->user_id,
        'order_code'                => $r->order_id,
        'transaction_id'            => $r->transaction_id,
        'acquirer_transaction_id'   => $r->acquirer_transaction_id,
        'request_amount'            => $r->request_amount,
        'request_ccy'               => $r->request_ccy,
        'authorized_amount'         => $r->authorized_amount,
        'authorized_ccy'            => $r->authorized_ccy,
        'response_code'             => $r->response_code,
        'response_msg'              => $r->response_msg,
        'acquirer_response_code'    => $r->acquirer_response_code,
        'acquirer_response_msg'     => $r->acquirer_response_msg,
        'acquirer_mpi_eci'          => $r->acquirer_mpi_eci,
        'created_timestamp'         => $r->created_timestamp,
        'acquirer_created_timestamp'=> $r->acquirer_created_timestamp,
        'first_6'                   => $r->first_6,
        'last_4'                    => $r->last_4,
        'payer_name'                => $r->payer_name,
        'exp_date'                  => $r->exp_date,
        'request_mid'               => $r->request_mid,
        'merchant_reference'        => $r->merchant_reference,
        'payer_id'                  => $r->payer_id,
        'transaction_type'          => $r->transaction_type,
        'payment_mode'              => $r->payment_mode,
        'signature'                 => $r->signature,
        'created_timestamp'         => $r->created_timestamp,
        'acquirer_created_timestamp'=> $r->acquirer_created_timestamp,
        'request_timestamp'         => $r->request_timestamp,
        'notify_url'                => 1,
        'created_at'                => now()
      ]);

      if($r->response_code <> 0)
      {

        /* send back his ewallet (if any) */
        DB::table('tbl_ledger')
        ->where('tranId', $r->order_id)
        ->where('user_id', $data[0]->user_id)
        ->where('ledger_type', 'DEBET')
        ->delete();
        /* end */

        /* make DV re-ready*/
        DB::table('tbl_discount_voucher_log')
        ->where('order_code', $r->order_id)
        ->where('user_id', $data[0]->user_id)
        ->delete();

        DB::select(
            DB::raw("UPDATE tbl_discount_voucher SET status = 0, updated_at = '0000-00-00 00:00:00', used_qty = used_qty-1, order_code = '' WHERE order_code = '".$r->order_id."' LIMIT 1")
        );

        DB::SELECT(
          DB::raw("UPDATE tbl_order SET delivery_fee = 0.00, donation = 0.00, voucher_amount = 0.00, credit_used = 0.00 WHERE order_code = '".$r->order_id."' LIMIT 1")
        );
        /* end */

        $response['status'] = 'failed';
        $response['message'] = $r->response_msg;
        return response()->json($response, 200);

      }else{

        DB::table('tbl_order')
          ->where('order_code', $r->order_id)
          ->update([
          'order_status'      => 'COMPLETED',
          'updated_at'        => now(),
          'payment_at'        => now()
          ]);

          /* INSERT INTO lucky_draw */
          $pembagian      = round($data[0]->lucky_draw_temp/10);
          if($pembagian > 0)
          {
            for($i=1;$i<$pembagian; $i++)
            {
              DB::table('tbl_lucky_draw')->insert([
                'user_id'          => $data[0]->user_id,
                'order_code'       => $r->order_id,
                'via'              => 'Order',
                'created_at'       => now()
              ]);
            }
          }
          /* end */

        /* post to other PHP code to process voucher etc */
        //$url='https://assisifunday.trinaxmind.com/backend-main/public/cronjob/post_payment.php';
        $url= 'https://www.assisifunday.sg/backend-main/public/cronjob/post_payment.php';
        $data2=array("order_code"=>$r->order_id);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data2);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result2 = curl_exec($curl);

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
      }

    }

    public function notifyPaymentDeposit(request $r)
    {
      Log::error($r);
      if(count($r->all()) == 0) {
        $response['status'] = 'failed';
        $response['message'] = 'empty message';
        return response()->json($response, 400);
      }

      $data = DB::table('tbl_log_deposit_request')
        ->where('transaction_id', $r->transaction_id)
        ->get();

      DB::table('tbl_log_deposit_result')->insert([
        'user_id'                   => $data[0]->user_id,
        'order_code'                => $r->order_id,
        'transaction_id'            => $r->transaction_id,
        'acquirer_transaction_id'   => $r->acquirer_transaction_id,
        'request_amount'            => $r->request_amount,
        'request_ccy'               => $r->request_ccy,
        'authorized_amount'         => $r->authorized_amount,
        'authorized_ccy'            => $r->authorized_ccy,
        'response_code'             => $r->response_code,
        'response_msg'              => $r->response_msg,
        'acquirer_response_code'    => $r->acquirer_response_code,
        'acquirer_response_msg'     => $r->acquirer_response_msg,
        'acquirer_mpi_eci'          => $r->acquirer_mpi_eci,
        'created_timestamp'         => $r->created_timestamp,
        'acquirer_created_timestamp'=> $r->acquirer_created_timestamp,
        'first_6'                   => $r->first_6,
        'last_4'                    => $r->last_4,
        'payer_name'                => $r->payer_name,
        'exp_date'                  => $r->exp_date,
        'request_mid'               => $r->request_mid,
        'merchant_reference'        => $r->merchant_reference,
        'payer_id'                  => $r->payer_id,
        'transaction_type'          => $r->transaction_type,
        'payment_mode'              => $r->payment_mode,
        'signature'                 => $r->signature,
        'request_timestamp'         => $r->request_timestamp,
        'notify_url'                => 1,
        'created_at'                => now()
      ]);

      if($r->response_code <> 0)
      {

        $response['status'] = 'failed';
        $response['message'] = $r->acquirer_response_msg;
        return response()->json($response, 400);

      }else{

        $count = DB::table('tbl_ledger')
        ->where('tranId', $r->transaction_id)
        ->count();

        if($count == 0)
        {

          /* bonus */
          /*$data2 = DB::table('tbl_ledger')
            ->where('user_id', $data[0]->user_id)
            ->where('bonus2', '>', 1)
            ->get();

          if(date("Y-m-d") < date("2022-10-06"))
          {
            $bonus1 = ($r->request_amount*10/100);
          }else{
            $bonus1 = 0;
          }

          $total_amount = $bonus1+$r->request_amount;

          if($r->request_amount>=100)
          {
            if(count($data2) == 0)
            {
              $bonus2 = 5.00;
              $total_amount = $total_amount + $bonus2;
            }else{
              $bonus2 = 0;
            }
          }else{
            $bonus2 = 0;
          }*/
          /* bonus */

          $total_amount = $r->request_amount;
          DB::table('tbl_ledger')->insert([
              'ledger_type'           => 'CREDIT',
              'description'           => 'DEPOSIT',
              'credit'                => $r->request_amount,
              'cardHolderName'        => $r->payer_name,
              'last_four_digit'       => $r->last_4,
              'totalAmount'           => $total_amount,
              'currency'              => 'SGD',
              'user_id'               => $data[0]->user_id,
              'trx_status'            => 'Approved',
              'tranId'                => $r->transaction_id,
              'responseCode'          => '00',
              'responseMsg'           => 'SUCCESS',
              'order_code'            => $r->order_id,
              //'bonus1'                => $bonus1,
              //'bonus2'                => $bonus2,
              'created_at'            => now()
          ]);
        }

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
      }
    }
    /*========== END NOTIFY ===========*/




    public function createPayment(request $r)
    {

      /* check input validation */
      $validate   = validator::make($r->all(), [
          'order_code'    => ['string', 'required'],
          'user_id'       => ['string', 'required'],
          'full_name'     => ['string', 'required'],
          // 'address'       => ['string', 'required'],
          // 'zip_code'      => ['string', 'required'],
          'email'         => ['string', 'required'],
          'donation'      => ['string', 'required'],
          'phone'         => ['string', 'required']

      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end check input validation */

      /* check if tbl_ledger has this order_code (user pending the previous transaction) */
      $count = DB::table('tbl_ledger as a')
        ->join('tbl_order as b', 'a.order_code', 'b.order_code')
        ->where('a.order_code', $r->order_code)
        ->where('a.user_id', $r->user_id)
        ->where('b.order_status', 'PENDING')
        ->count();

      if($count > 0)
      {
        $response['status'] = 'error';
        $response['message'] = 'Another transaction is pending';
        return response()->json($response, 400);
      }
      /* end */

      /* check token */
      $app_id     = 'PGS';
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

      /* check if order code is paired with user_id */
      $count = DB::table('tbl_order')
        ->where('order_code', $r->order_code)
        ->where('user_id', $r->user_id)
        ->count();

      if($count == 0)
      {
        $response['status'] = 'error';
        $response['message'] = 'invalid order code. Please check if order code is matched with user ID';
        return response()->json($response, 400);
      }
      /* end */

      /* check if order code is PENDING */
      $count = DB::table('tbl_order')
        ->where('order_code', $r->order_code)
        ->where('order_status', 'PENDING')
        ->count();

      if($count == 0)
      {
        $response['status'] = 'error';
        $response['message'] = 'this order is not PENDING';
        return response()->json($response, 400);
      }
      /* end */

      /* check if product is not disable */
      $product = DB::table('tbl_product as a')
      ->join('tbl_order_detail as b', 'a.product_id', 'b.product_id')
      ->where('order_code', $r->order_code)
      ->where('active_status', 0)
      ->orwhere('delete_status', 1)
      ->count();

      if($product > 0)
      {
        $response['status'] = 'error';
        $response['message'] = 'there are product is in inactive or deleted by admin';
        return response()->json($response, 400);
      }
      /* end */

      /* check if shop is not disable */
      $shop = DB::table('tbl_shop as a')
      ->join('tbl_order_shop as b', 'a.shop_id', 'b.shop_id')
      ->where('order_code', $r->order_code)
      ->where('active_status', 0)
      ->count();

      if($shop > 0)
      {
        $response['status'] = 'error';
        $response['message'] = 'there are shop is in inactive';
        return response()->json($response, 400);
      }
      /* end */

      /* check if shop golive is today */
      $date = date('Y-m-d');
      $shop = DB::table('tbl_shop as a')
      ->join('tbl_order_shop as b', 'a.shop_id', 'b.shop_id')
      ->where('order_code', $r->order_code)
      ->where('golive', '<>', '0000-00-00')
      ->where('golive', '<>', $date)
      ->count();

      if($shop > 0)
      {
        $response['status'] = 'error';
        $response['message'] = 'there are shop is not in go live';
        return response()->json($response, 400);
      }
      /* end */

      /* check fufilment_type, if DELIVERY or POSTAGE then need to add $5 on every shop */
      $fufilment = DB::select(
          DB::raw("SELECT count(shop_id) as total_fufilment FROM tbl_order_shop WHERE order_code = '".$r->order_code."' AND fufillment_type IN ('POSTAGE', 'DELIVERY')")
      );
      $total_fufilment = $fufilment[0]->total_fufilment * 6;

      /* check the shopping amount */
      $data = DB::select(
          DB::raw("SELECT sum(sub_total) as total FROM tbl_order_detail WHERE order_code = '".$r->order_code."'")
      );
      $data2 = DB::table('tbl_order')
        ->where('order_code', $r->order_code)
        ->where('user_id', $r->user_id)
        ->get();


      $deduct_amount  = ($data[0]->total + $total_fufilment + $r->donation);

      /* if voucher code is use then we check */
      if ($r->voucher_code <> '')
      {
        $dv = DB::table('tbl_discount_voucher')
          ->where('code', $r->voucher_code)
          ->get();

        if(count($dv) == 0)
        {
          $response['status']   = 'error';
          $response['message']  = 'invalid voucher code';
          return response()->json($response, 400);
        }

        if($dv[0]->max_qty == 1) // if this voucher code is only for max_qty 1 ?
        {

          if($dv[0]->user_id <> $r->user_id)
          {
            $response['status']   = 'error';
            $response['message']  = 'user ID is not correct for this voucher';
            return response()->json($response, 400);
          }

          if($dv[0]->user_id == $r->user_id && $dv[0]->status == 1)
          {
            $response['status']   = 'error';
            $response['message']  = 'voucher has been used';
            return response()->json($response, 400);
          }

        }else{ // so this voucher code can be use more than 1 x

          if($dv[0]->used_qty >= $dv[0]->max_qty)
          {
            $response['status']   = 'error';
            $response['message']  = 'voucher has reached maximum usage';
            return response()->json($response, 400);
          }

        }

        if (($deduct_amount > $dv[0]->value)) //if deduct_amount still larger then we need to continue to RDP
        {
            $deduct_amount = $deduct_amount - $dv[0]->value;

            if($dv[0]->max_qty == 1) //update voucher code to be expired
            {
              DB::table('tbl_discount_voucher')
                 ->where('code', $r->voucher_code)
                 ->update([
                     'status'      => 1,
                     'updated_at'  => now(),
                     'used_qty'    => 1,
                     'order_code'  => $r->order_code
                 ]);

             DB::table('tbl_discount_voucher_log')->insert([
                 'order_code'    => $r->order_code,
                 'code'          => $r->voucher_code,
                 'value'         => $dv[0]->value,
                 'user_id'       => $r->user_id,
                 'created_at'    => now()
             ]);
           }else{
             DB::table('tbl_discount_voucher')
                 ->where('code', $r->voucher_code)
                 ->update([
                     'status'      => 1,
                     'updated_at'  => now(),
                     'used_qty'    => $dv[0]->used_qty + 1,
                     'order_code'  => $r->order_code
                 ]);

             DB::table('tbl_discount_voucher_log')->insert([
                 'order_code'    => $r->order_code,
                 'code'          => $r->voucher_code,
                 'value'         => $dv[0]->value,
                 'user_id'       => $r->user_id,
                 'created_at'    => now()
             ]);
           }

           DB::table('tbl_order')
             ->where('order_code', $r->order_code)
             ->where('user_id', $r->user_id)
             ->update([
             'voucher_amount'    => $dv[0]->value,
             'updated_at'        => now()
             ]);
             $dv_value = $dv[0]->value;

        }else{ //else, no need continue to RDP just stop here

          $deduct_amount = 0;
          if($dv[0]->max_qty == 1) //update voucher code to be expired
          {
            DB::table('tbl_discount_voucher')
             ->where('code', $r->voucher_code)
             ->update([
               'status'      => 1,
               'updated_at'  => now(),
               'used_qty'    => 1,
               'order_code'  => $r->order_code
             ]);
         }else{
           DB::table('tbl_discount_voucher')
             ->where('code', $r->voucher_code)
             ->update([
               'status'      => 1,
               'updated_at'  => now(),
               'used_qty'    => $dv[0]->used_qty + 1,
               'order_code'  => $r->order_code
             ]);
         }

         //write to DV log
         DB::table('tbl_discount_voucher_log')->insert([
             'order_code'    => $r->order_code,
             'code'          => $r->voucher_code,
             'value'         => $dv[0]->value,
             'user_id'       => $r->user_id,
             'created_at'    => now()
         ]);

         //update tbl_order to COMPLETED
         $lucky_draw     = $data[0]->total + $total_fufilment + $r->donation - $dv[0]->value;
         //$pembagian      = round($data2[0]->lucky_draw_temp/10);
         $pembagian      = round($lucky_draw/10);
         DB::table('tbl_order')
           ->where('order_code', $r->order_code)
           ->where('user_id', $r->user_id)
           ->update([
           'order_status'      => 'COMPLETED',
           'address'           => $r->address,
           'zip_code'          => $r->zip_code,
           'full_name'         => $r->full_name,
           'phone'             => $r->phone,
           'email'             => $r->email,
           'delivery_fee'      => $total_fufilment,
           'donation'          => $r->donation,
           'voucher_amount'    => $dv[0]->value,
           'updated_at'        => now(),
           'payment_at'        => now()
           ]);

          DB::table('tbl_order_payment_log')->insert([
            'order_code'       => $r->order_code,
            'order_code'       => $r->order_code,
            'created_at'       => now()
          ]);
         $dv_value = $dv[0]->value;

         /* INSERT INTO lucky_draw */
         if($pembagian > 0)
         {
           for($i=1;$i<=$pembagian; $i++)
           {
             DB::table('tbl_lucky_draw')->insert([
               'user_id'          => $r->user_id,
               'voucher_code'     => $r->voucher_code,
               'order_code'       => $r->order_code,
               'via'              => 'Order',
               'created_at'       => now()
             ]);
           }
         }
         /* end */

         /* post to other PHP code to process voucher etc */
         //$url='https://assisifunday.trinaxmind.com/backend-main/public/cronjob/post_payment.php';
         $url= 'https://www.assisifunday.sg/backend-main/public/cronjob/post_payment.php';
         $data=array("order_code"=>$r->order_code);

         $curl = curl_init();
         curl_setopt($curl, CURLOPT_URL, $url);
         curl_setopt($curl, CURLOPT_POST, 1);
         curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
         $result2 = curl_exec($curl);

         $response['status']   = 'success';
         $response['message']  = 'order completed via discount voucher';
         return response()->json($response, 200);

        }
      }

      if($r->credit_used == 1) //use wallet? check then
      {
        $balance = DB::select(
            DB::raw("SELECT ((SELECT sum(totalAmount) as credit FROM tbl_ledger WHERE ledger_type = 'CREDIT' AND user_id = '".$r->user_id."' AND responseCode =00) - ifnull((SELECT sum(totalAmount) as debet FROM tbl_ledger WHERE ledger_type = 'DEBET' AND user_id = '".$r->user_id."' AND responseCode =00),0)) as balance FROM tbl_ledger WHERE user_id = '".$r->user_id."' ORDER BY ledger_id DESC LIMIT 1")
        );

        if(count($balance)==0)
        {
          $response['status']   = 'failed';
          $response['message']  = 'zero balance';
          return response()->json($response, 400);
        }

        if ($deduct_amount > 0)
        {
          if ($deduct_amount >= $balance[0]->balance) //deduct_amount is bigger (or same) with balance
          {
            $deduct_amount = $deduct_amount - $balance[0]->balance;
            $balance = $balance[0]->balance;
            DB::table('tbl_ledger')->insert([
                'ledger_type'           => 'DEBET',
                'description'           => 'PURCHASE',
                'debet'                 => $balance,
                'totalAmount'           => $balance,
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
              ->where('user_id', $r->user_id)
              ->update([
              'credit_used'       => $balance,
              'updated_at'        => now()
              ]);
          }
          else
          {
            //balance is bigger than deduct_amount. transaction done.
            $balance = $balance[0]->balance - $deduct_amount;
            DB::table('tbl_ledger')->insert([
                'ledger_type'           => 'DEBET',
                'description'           => 'PURCHASE',
                'debet'                 => $deduct_amount,
                'totalAmount'           => $deduct_amount,
                'currency'              => 'SGD',
                'user_id'               => $r->user_id,
                'trx_status'            => 'Approved',
                'tranId'                => $r->order_code,
                'responseCode'          => '00',
                'responseMsg'           => 'SUCCESS',
                'order_code'            => $r->order_code,
                'created_at'            => now()
            ]);

            //update tbl_order to COMPLETED
            $lucky_draw     = $data[0]->total + $total_fufilment + $r->donation;
            $pembagian      = round($lucky_draw/10);
            DB::table('tbl_order')
              ->where('order_code', $r->order_code)
              ->where('user_id', $r->user_id)
              ->update([
              'order_status'      => 'COMPLETED',
              'address'           => $r->address,
              'zip_code'          => $r->zip_code,
              'full_name'         => $r->full_name,
              'phone'             => $r->phone,
              'email'             => $r->email,
              'delivery_fee'      => $total_fufilment,
              'donation'          => $r->donation,
              'voucher_amount'    => @$dv_value,
              'credit_used'       => $deduct_amount,
              'updated_at'        => now(),
              'payment_at'        => now()
              ]);

            DB::table('tbl_order_payment_log')->insert([
                'order_code'       => $r->order_code,
                'voucher_code'     => $r->voucher_code,
                'balance'          => $deduct_amount,
                'created_at'       => now()
              ]);

            $deduct_amount = 0;

            /* INSERT INTO lucky_draw */
            if($pembagian > 0)
            {
              for($i=1;$i<=$pembagian; $i++)
              {
                DB::table('tbl_lucky_draw')->insert([
                  'user_id'          => $r->user_id,
                  'order_code'       => $r->order_code,
                  'voucher_code'     => $r->voucher_code,
                  'via'              => 'Order',
                  'created_at'       => now()
                ]);
              }
            }
            /* end */

            /* post to other PHP code to process voucher etc */
            //$url='https://assisifunday.trinaxmind.com/backend-main/public/cronjob/post_payment.php';
            $url= 'https://www.assisifunday.sg/backend-main/public/cronjob/post_payment.php';
            $data=array("order_code"=>$r->order_code);

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $result2 = curl_exec($curl);

            $response['status']   = 'success';
            $response['message']  = 'order completed using wallet';
            return response()->json($response, 200);

          }
        }
      }

      if ($deduct_amount > 0)
      {
        // go to Reddot to ask user to pay money -
        $signature = hash('sha512', trim('0000028740'.$r->order_code.'S'.$deduct_amount.'SGD'.'B2SFFB05RDBznSX5tJB9U3itgKoZLLmEAp6bAImVEOnxPq4VPF4pPCopmZPqh6vUd6A4e9Z7AO8szENuQUBenxfG7sRXR31evJLyID2R5Z6RyC1sDg990QLtUKTQJRjy'));
        $url = 'https://secure.reddotpayment.com/service/payment-api';

        $post = array(
          "redirect_url"        => 'https://www.assisifunday.sg/main/checkoutend.html?transaction_id='.$r->query('transaction_id'),
          //"notify_url"          => env('ASSISI_URL').'/backend-main/api/v1/getPayment?transaction_id='.$r->query('transaction_id'),
          "notify_url"          => 'https://www.assisifunday.sg/backend-main/api/v1/notifyPaymentCart',
          "back_url"            => 'https://www.assisifunday.sg/backend-main/api/v1/void/'.$r->order_code,
          //"back_url"            => env('ASSISI_URL').'/cart.html',
          "mid"                 => '0000028740',
          "order_id"            => $r->order_code,
          "amount"              => $deduct_amount,
          "ccy"                 => "SGD",
          "api_mode"            => "redirection_hosted",
          "payment_type"        => "S",
          "merchant_reference"  => "the things to reference",
          "signature"           => $signature
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);

        /*============ LOG =============*/
        DB::table('tbl_log')->insert([
            'user_id'       => $r->user_id,
            'action'        => 'CREATE_PAYMENT',
            'notes'         => 'Create payment for order code: '.$r->order_code,
            'created_at'    => Carbon::now()
            ]);
        /*============ LOG =============*/

        $hasil      = json_decode($result);
        if($hasil->{'response_code'} == 0)
        {
          DB::table('tbl_log_payment_request')->insert([
            'user_id'         => $r->user_id,
            'order_code'      => $r->order_code,
            'amount'          => $deduct_amount,
            'signature'       => $signature,
            'post_url'        => $url,
            'transaction_id'  => $hasil->{'transaction_id'},
            'payment_url'     => $hasil->{'payment_url'},
            'response_code'   => $hasil->{'response_code'},
            'pg_signature'    => $hasil->{'signature'},
            'created_at'      => now()
          ]);

          /* update some data on tbl_order */
          DB::table('tbl_order')
            ->where('order_code', $r->order_code)
            ->where('user_id', $r->user_id)
            ->update([
            'address'           => $r->address,
            'zip_code'          => $r->zip_code,
            'full_name'         => $r->full_name,
            'phone'             => $r->phone,
            'email'             => $r->email,
            'delivery_fee'      => $total_fufilment,
            'donation'          => $r->donation,
            'voucher_amount'    => @$dv_value,
            'updated_at'        => now()
            ]);

          $response['status']   = 'success';
          $response['message']  = $hasil->{'payment_url'};
          return response()->json($response, 200);

        }else{
          DB::table('tbl_log_payment_request')->insert([
            'user_id'         => $r->user_id,
            'order_code'      => $r->order_code,
            'amount'          => $deduct_amount,
            'signature'       => $signature,
            'post_url'        => $url,
            'response_status' => $hasil->{'response_status'},
            'response_code'   => $hasil->{'response_code'},
            'response_msg'    => $hasil->{'response_msg'},
            'created_at'      => now()
          ]);

          $response['status']   = 'error';
          $response['message']  = $hasil->{'response_msg'};
          return response()->json($response, 400);

      }
    }

    }

    public function getPayment(Request $r)
    {
      $transaction_id = $r->query('transaction_id');

      function sign_generic($secret_key, $params)
      {
          unset($params['signature']);
          $data_to_sign = "";
          recursive_generic_array_sign($params, $data_to_sign);
          $data_to_sign .= $secret_key;
          return hash('sha512', $data_to_sign);
      }

      function recursive_generic_array_sign(&$params, &$data_to_sign)
      {
          ksort($params);

          foreach ($params as $v)
          {
            if (is_array($v))
            {
              recursive_generic_array_sign($v, $data_to_sign);
            }else{
              $data_to_sign .= $v;
            }
          }
      }

      $rp = array(
          'request_mid' => '0000028740',
          'transaction_id' => $transaction_id
      );

      $rp['signature'] = sign_generic('B2SFFB05RDBznSX5tJB9U3itgKoZLLmEAp6bAImVEOnxPq4VPF4pPCopmZPqh6vUd6A4e9Z7AO8szENuQUBenxfG7sRXR31evJLyID2R5Z6RyC1sDg990QLtUKTQJRjy', $rp);
      $json_rp = json_encode($rp);

      // target RDP development server
      $url = 'https://secure.reddotpayment.com/service/Merchant_processor/query_redirection';
      $curl = curl_init($url);
      curl_setopt_array($curl, array(
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_POST => 1,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_SSL_VERIFYHOST => false,
          CURLOPT_POSTFIELDS => $json_rp,
          CURLOPT_HTTPHEADER => array('Content-Type: application/json')
      ));

      $result     = curl_exec($curl);
      $hasil      = json_decode($result);

      $data = DB::table('tbl_order')
        ->where('order_code', $hasil->{'order_id'})
        ->get();

      $data2 = DB::select(
          DB::raw("SELECT sum(sub_total) as total FROM tbl_order_detail WHERE order_code = '".$hasil->{'order_id'}."'")
      );

      /* at July 19, 2022, response from rdp changed. now no parameter like '%aquirer%' so i must comment it out */
      DB::table('tbl_log_payment_result')->insert([
        'user_id'                   => $data[0]->user_id,
        'order_code'                => $hasil->{'order_id'},
        'transaction_id'            => $hasil->{'transaction_id'},
        //'acquirer_transaction_id'   => $hasil->{'acquirer_transaction_id'},
        'request_amount'            => $hasil->{'request_amount'},
        'request_ccy'               => $hasil->{'request_ccy'},
        'authorized_amount'         => $hasil->{'authorized_amount'},
        'authorized_ccy'            => $hasil->{'authorized_ccy'},
        'response_code'             => $hasil->{'response_code'},
        'response_msg'              => $hasil->{'response_msg'},
        //'acquirer_response_code'    => $hasil->{'acquirer_response_code'},
        //'acquirer_response_msg'     => $hasil->{'acquirer_response_msg'},
        //'acquirer_mpi_eci'          => $hasil->{'acquirer_mpi_eci'},
        'created_timestamp'         => $hasil->{'created_timestamp'},
        //'acquirer_created_timestamp'=> $hasil->{'acquirer_created_timestamp'},
        'first_6'                   => $hasil->{'first_6'},
        'last_4'                    => $hasil->{'last_4'},
        'payer_name'                => $hasil->{'payer_name'},
        'exp_date'                  => $hasil->{'exp_date'},
        'request_mid'               => $hasil->{'request_mid'},
        'merchant_reference'        => $hasil->{'merchant_reference'},
        //'payer_id'                  => $hasil->{'payer_id'},
        'transaction_type'          => $hasil->{'transaction_type'},
        'payment_mode'              => $hasil->{'payment_mode'},
        'signature'                 => $hasil->{'signature'},
        'created_timestamp'         => $hasil->{'created_timestamp'},
        //'acquirer_created_timestamp'=> $hasil->{'acquirer_created_timestamp'},
        'request_timestamp'         => $hasil->{'request_timestamp'},
        'created_at'                => now()
      ]);

      if($hasil->{'response_code'} <> 0)
      {

        /* send back his ewallet (if any) */
        DB::table('tbl_ledger')
        ->where('tranId', $hasil->{'order_id'})
        ->where('user_id', $data[0]->user_id)
        ->where('ledger_type', 'DEBET')
        ->delete();

        /* make DV re-ready*/
        DB::table('tbl_discount_voucher_log')
        ->where('order_code', $hasil->{'order_id'})
        ->where('user_id', $data[0]->user_id)
        ->delete();


        DB::select(
            DB::raw("UPDATE tbl_discount_voucher SET status = 0, updated_at = '0000-00-00 00:00:00', used_qty = used_qty-1, order_code = '' WHERE order_code = '".$hasil->{'order_id'}."' LIMIT 1")
        );

        DB::SELECT(
          DB::raw("UPDATE tbl_order SET delivery_fee = 0.00, donation = 0.00, voucher_amount = 0.00, credit_used = 0.00 WHERE order_code = '".$hasil->{'order_id'}."' LIMIT 1")
        );
        /* end */

        $response['status'] = 'failed';
        $response['message'] = $hasil->{'response_msg'};
        return response()->json($response, 400);

      }else{

        $lucky_draw     = $data2[0]->total + $data[0]->delivery_fee + $data[0]->donation;
        $pembagian      = round($lucky_draw/10);
        DB::table('tbl_order')
          ->where('order_code', $hasil->{'order_id'})
          ->update([
          'order_status'      => 'COMPLETED',
          'lucky_draw_final'  => $pembagian,
          'updated_at'        => now(),
          'payment_at'        => now()
          ]);

          /* INSERT INTO lucky_draw */
          if($pembagian > 0)
          {
            for($i=1;$i<=$pembagian; $i++)
            {
              DB::table('tbl_lucky_draw')->insert([
                'user_id'          => $data[0]->user_id,
                'order_code'       => $hasil->{'order_id'},
                'via'              => 'Order',
                'created_at'       => now()
              ]);
            }
          }
          /* end */

        /* post to other PHP code to process voucher etc */
        //$url='https://assisifunday.trinaxmind.com/backend-main/public/cronjob/post_payment.php';
        $url= 'https://www.assisifunday.sg/backend-main/public/cronjob/post_payment.php';
        $data=array("order_code"=>$hasil->{'order_id'});

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result2 = curl_exec($curl);

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
      }

    }







    /* ========= THIS IS FOR DEPOSIT ========== */
    public function createDeposit(request $r)
    {

      /* check input validation */
      $validate   = validator::make($r->all(), [
          'user_id'       => ['string', 'required'],
          'amount'        => ['string', 'required']

      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end check input validation */

      /* check token */
      $app_id     = 'PGS';
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

      /* check if orduser exist */
      $count = DB::table('tbl_user')
        ->where('active_status', 1)
        ->where('user_id', $r->user_id)
        ->count();

      if($count == 0)
      {
        $response['status'] = 'error';
        $response['message'] = 'user not found or inactive';
        return response()->json($response, 400);
      }
      /* end */

      $amount = $r->amount;
      /* hit to reddot */
      $order_id = 'D'.date('ymd').substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 8);
      $signature = hash('sha512', trim('0000028740'.$order_id.'S'.$amount.'SGD'.'B2SFFB05RDBznSX5tJB9U3itgKoZLLmEAp6bAImVEOnxPq4VPF4pPCopmZPqh6vUd6A4e9Z7AO8szENuQUBenxfG7sRXR31evJLyID2R5Z6RyC1sDg990QLtUKTQJRjy'));
      $url = 'https://secure.reddotpayment.com/service/payment-api';

      $post = array(
        "redirect_url"        => 'https://www.assisifunday.sg/main/account.html?transaction_id='.$r->query('transaction_id'),
        //"notify_url"          => env('ASSISI_URL').'/backend-main/api/v1/getDeposit?transaction_id='.$r->query('transaction_id'),
        "notify_url"          => 'https://www.assisifunday.sg/backend-main/api/v1/notifyPaymentDeposit',
        "back_url"            => 'https://www.assisifunday.sg/main/account.html',
        "mid"                 => '0000028740',
        "order_id"            => $order_id,
        "amount"              => $amount,
        "ccy"                 => "SGD",
        "api_mode"            => "redirection_hosted",
        "payment_type"        => "S",
        "merchant_reference"  => $r->user_id,
        "signature"           => $signature
      );


      // Log::debug("=============API Request===============");
      // Log::debug($post);

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      $result = curl_exec($ch);
      curl_close($ch);

      /*============ LOG =============*/
      DB::table('tbl_log')->insert([
          'user_id'       => $r->user_id,
          'action'        => 'CREATE_PAYMENT',
          'notes'         => 'Create payment for order code: '.$order_id,
          'created_at'    => Carbon::now()
          ]);
      /*============ LOG =============*/

      $hasil      = json_decode($result);



      // Log::debug("=============API Response (Payment URL)===============");
      // Log::debug($result);


      if($hasil->{'response_code'} == 0)
      {
        DB::table('tbl_log_deposit_request')->insert([
          'user_id'         => $r->user_id,
          'order_code'      => $order_id,
          'amount'          => $amount,
          'signature'       => $signature,
          'post_url'        => $url,
          'transaction_id'  => $hasil->{'transaction_id'},
          'payment_url'     => $hasil->{'payment_url'},
          'response_code'   => $hasil->{'response_code'},
          'pg_signature'    => $hasil->{'signature'},
          'created_at'      => now()
        ]);

        $response['status']   = 'success';
        $response['message']  = $hasil->{'payment_url'};
        return response()->json($response, 200);

      }else{
        DB::table('tbl_log_deposit_request')->insert([
          'user_id'         => $r->user_id,
          'order_code'      => $order_id,
          'amount'          => $amount,
          'signature'       => $signature,
          'post_url'        => $url,
          'response_status' => $hasil->{'response_status'},
          'response_code'   => $hasil->{'response_code'},
          'response_msg'    => $hasil->{'response_msg'},
          'created_at'      => now()
        ]);

        $response['status']   = 'error';
        $response['message']  = $hasil->{'response_msg'};
        return response()->json($response, 400);

      }

    }

    public function getDeposit(Request $r)
    {
      $transaction_id = $r->query('transaction_id');

      function sign_generic($secret_key, $params)
      {
          unset($params['signature']);
          $data_to_sign = "";
          recursive_generic_array_sign($params, $data_to_sign);
          $data_to_sign .= $secret_key;
          return hash('sha512', $data_to_sign);
      }

      function recursive_generic_array_sign(&$params, &$data_to_sign)
      {
          ksort($params);

          foreach ($params as $v)
          {
            if (is_array($v))
            {
              recursive_generic_array_sign($v, $data_to_sign);
            }else{
              $data_to_sign .= $v;
            }
          }
      }

      $rp = array(
          'request_mid' => '0000028740',
          'transaction_id' => $transaction_id
      );

      $rp['signature'] = sign_generic('B2SFFB05RDBznSX5tJB9U3itgKoZLLmEAp6bAImVEOnxPq4VPF4pPCopmZPqh6vUd6A4e9Z7AO8szENuQUBenxfG7sRXR31evJLyID2R5Z6RyC1sDg990QLtUKTQJRjy', $rp);
      $json_rp = json_encode($rp);


      // target RDP development server
      $url = 'https://secure.reddotpayment.com/service/Merchant_processor/query_redirection';
      $curl = curl_init($url);
      curl_setopt_array($curl, array(
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_POST => 1,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_SSL_VERIFYHOST => false,
          CURLOPT_POSTFIELDS => $json_rp,
          CURLOPT_HTTPHEADER => array('Content-Type: application/json')
      ));

      $result     = curl_exec($curl);
      $hasil      = json_decode($result);


      // Log::debug("=============Payment Response (Redirection)===============");
      // Log::debug($result);

      $data = DB::table('tbl_log_deposit_request')
        ->where('transaction_id', $transaction_id)
        ->get();

      DB::table('tbl_log_deposit_result')->insert([
        'user_id'                   => $data[0]->user_id,
        'order_code'                => $data[0]->order_code,
        'transaction_id'            => @$hasil->{'transaction_id'},
        //'acquirer_transaction_id'   => @$hasil->{'acquirer_transaction_id'},
        'request_amount'            => @$hasil->{'request_amount'},
        'request_ccy'               => @$hasil->{'request_ccy'},
        'authorized_amount'         => @$hasil->{'authorized_amount'},
        'authorized_ccy'            => @$hasil->{'authorized_ccy'},
        'response_code'             => @$hasil->{'response_code'},
        'response_msg'              => @$hasil->{'response_msg'},
        //'acquirer_response_code'    => @$hasil->{'acquirer_response_code'},
        //'acquirer_response_msg'     => @$hasil->{'acquirer_response_msg'},
        //'acquirer_mpi_eci'          => @$hasil->{'acquirer_mpi_eci'},
        'created_timestamp'         => @$hasil->{'created_timestamp'},
        'acquirer_created_timestamp'=> @$hasil->{'acquirer_created_timestamp'},
        'first_6'                   => @$hasil->{'first_6'},
        'last_4'                    => @$hasil->{'last_4'},
        'payer_name'                => @$hasil->{'payer_name'},
        'exp_date'                  => @$hasil->{'exp_date'},
        'request_mid'               => @$hasil->{'request_mid'},
        'merchant_reference'        => @$hasil->{'merchant_reference'},
        //'payer_id'                  => @$hasil->{'payer_id'},
        'transaction_type'          => @$hasil->{'transaction_type'},
        'payment_mode'              => @$hasil->{'payment_mode'},
        'signature'                 => @$hasil->{'signature'},
        'created_timestamp'         => @$hasil->{'created_timestamp'},
        //'acquirer_created_timestamp'=> @$hasil->{'acquirer_created_timestamp'},
        'request_timestamp'         => @$hasil->{'request_timestamp'},
        'created_at'                => now()
      ]);

      if($hasil->{'response_code'} <> 0)
      {

        $response['status'] = 'failed';
        $response['message'] = $hasil->{'response_msg'};
        return response()->json($response, 400);

      }else{

        $count = DB::table('tbl_ledger')
        ->where('tranId', $hasil->{'transaction_id'})
        ->count();

        if($count == 0)
        {

          /* bonus */
          /*$data2 = DB::table('tbl_ledger')
            ->where('user_id', $data[0]->user_id)
            ->where('bonus2', '>', 1)
            ->get();

          if(date("Y-m-d") < date("2022-10-06"))
          {
            $bonus1 = ($hasil->request_amount*10/100);
          }else{
            $bonus1 = 0;
          }

          $total_amount = $bonus1+$hasil->request_amount;

          if($hasil->request_amount>=100)
          {
            if(count($data2) == 0)
            {
              $bonus2 = 5.00;
              $total_amount = $total_amount + $bonus2;
            }else{
              $bonus2 = 0;
            }
          }else{
            $bonus2 = 0;
          }*/
          /* bonus */
          $total_amount = $r->request_amount;

          DB::table('tbl_ledger')->insert([
            'ledger_type'           => 'CREDIT',
            'description'           => 'DEPOSIT',
            'credit'                => $hasil->request_amount,
            'totalAmount'           => $total_amount,
            'currency'              => 'SGD',
            'cardHolderName'        => $hasil->payer_name,
            'last_four_digit'       => $hasil->last_4,
            'user_id'               => $data[0]->user_id,
            'trx_status'            => 'Approved',
            'tranId'                => $hasil->transaction_id,
            'responseCode'          => '00',
            'responseMsg'           => 'SUCCESS',
            'order_code'            => $hasil->order_id,
            //'bonus1'                => $bonus1,
            //'bonus2'                => $bonus2,
            'created_at'            => now()
          ]);
        }


        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
      }

    }

}
