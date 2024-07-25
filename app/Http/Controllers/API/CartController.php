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

class CartController extends Controller
{
  public function add(request $r)
  {
      /* check input validation */
      $validate   = validator::make($r->all(), [
          'product_id'    => ['integer', 'required', 'max:10000'],
          'qty'           => ['integer', 'required', 'max:1000'],
          'user_id'       => ['integer', 'required', 'max:100000'],
      ]);

      if($validate->fails())
      {
          $response['status'] = 'error';
          $response['message'] = 'validation failed';
          return response()->json($response, 400);
      }
      /* end check input validation */

      /* check token */
      $app_id     = 'CAD';
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

      /* check if product is there */
      $data   = DB::table('tbl_product')
          ->where('product_id', '=', $r->product_id)
          ->count();

      if ($data == 0 )
      {
          $response['status'] = 'error';
          $response['message'] = 'product not found';
          return response()->json($response, 400);
      }
      /*end*/

      /* check if product max qty not reach remaining_qty */
      $qty_order = DB::table('tbl_order as a')
          ->join('tbl_order_detail as b', 'a.order_code', 'b.order_code')
          ->where('user_id', $r->user_id)
          ->where('product_id', $r->product_id)
          ->where('order_status', 'PENDING')
          ->get();

      if(count($qty_order) == 0)
      {
        $qty_order = 0;
      }else{
        $qty_order = $qty_order[0]->qty;
      }

      $qty_product   = DB::table('tbl_product')
          ->where('product_id', '=', $r->product_id)
          ->get();

      $qty_completed = DB::select(
          DB::raw("SELECT sum(qty) as total FROM tbl_order a INNER JOIN tbl_order_detail b USING (order_code) WHERE a.order_status = 'COMPLETED' AND b.product_id = '".$r->product_id."'")
      );

      $remaining_qty = $qty_product[0]->total_quantity - $qty_completed[0]->total;

      $new_qty = $r->qty + $qty_order;

      if($qty_order >= $remaining_qty || ($new_qty) > $remaining_qty)
      {
        $response['message'] = 'not enough quantity';
        $response['your_qty'] = $r->qty;
        $response['qty_product'] = $qty_product[0]->total_quantity;
        $response['qty_completed'] = $qty_completed[0]->total;
        $response['qty_pending'] = $qty_order;
        $response['remaining_qty'] = $remaining_qty;
        return response()->json($response, 400);
        //exit();
      }
      //exit();

      $data   = DB::table('tbl_product')
          ->where('product_id', '=', $r->product_id)
          ->get();

      if ($data[0]->delete_status == 1)
      {
          $response['status'] = 'error';
          $response['message'] = 'product unavailable';
          return response()->json($response, 400);
      }

      /* check if this user already have pending cart */
      $order = DB::table('tbl_order')
          ->where('user_id','=',$r->user_id)
          ->where('order_status','<>','COMPLETED')
          ->count();

      $order_code = 'C'.date('ymd').substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 8);
      $merchant_code = 'SHP'.date('ymd').substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 8);

      if($order == 0) { //if this is new cart then create an order_code

          $data1 = DB::table('tbl_order')->insert([
              'user_id'       => $r->user_id,
              'order_status'  => 'PENDING',
              'order_code'    => $order_code,
              'created_at'    => now()
          ]);

          $data2 = DB::table('tbl_order_shop')->insert([
              'shop_id'       => $data[0]->shop_id,
              'shop_name'     => $data[0]->shop_name,
              'order_code'    => $order_code,
              'merchant_code' => $merchant_code,
              'created_at'    => now()
          ]);

          $qty        = $r->qty;
          $sub_total  = $data[0]->price * $qty;
          $data3 = DB::table('tbl_order_detail')->insert([
              'shop_id' => $data[0]->shop_id,
              'order_code'    => $order_code,
              'product_id'    => $r->product_id,
              'product_name'  => $data[0]->product_name,
              'price'         => $data[0]->price,
              'qty'           => $qty,
              'sub_total'     => $sub_total,
              'created_at'    => now()
          ]);

      }else{ //already in cart, no need create order_code

          $data1 = DB::table('tbl_order')
              ->where('user_id','=', $r->user_id)
              ->where('order_status','<>','COMPLETED')
              ->get();

          //check if shop already on table shop
          $check_shop = DB::table('tbl_order_shop')
              ->where('order_code','=',$data1[0]->order_code)
              ->where('shop_id', '=', $data[0]->shop_id)
              ->count();

          if($check_shop == 0)
          {
              DB::table('tbl_order_shop')->insert([
                  'shop_id'       => $data[0]->shop_id,
                  'shop_name'     => $data[0]->shop_name,
                  'order_code'    => $data1[0]->order_code,
                  'merchant_code' => $merchant_code,
                  'created_at'    => now()
              ]);
          }

          //check if product already on table detail
          $check_product = DB::table('tbl_order_detail')
              ->where('order_code','=',$data1[0]->order_code)
              ->where('product_id', '=', $r->product_id)
              ->count();

          if($check_product == 0)
          { //no product_id for this order code, so create new row
              $qty        = $r->qty;
              $sub_total  = $data[0]->price * $qty;
              DB::table('tbl_order_detail')->insert([
                  'shop_id' => $data[0]->shop_id,
                  'order_code'    => $data1[0]->order_code,
                  'product_id'    => $r->product_id,
                  'product_name'  => $data[0]->product_name,
                  'price'         => $data[0]->price,
                  'qty'           => $qty,
                  'sub_total'     => $sub_total,
                  'created_at'    => now()
              ]);
          }else{ //already product_id with this order code, just updated it

              $data3 = DB::table('tbl_order_detail')
                  ->where('order_code', $data1[0]->order_code)
                  ->where('product_id', $r->product_id)
                  ->get();

              $qty        = $data3[0]->qty + $r->qty;
              $sub_total  = $data[0]->price * $qty;

              DB::table('tbl_order_detail')
                  ->where('order_code', $data1[0]->order_code)
                  ->where('product_id', $r->product_id)
                  ->update([
                      'qty'            => $qty,
                      'sub_total'      => $sub_total,
                      'updated_at'     => now()
                  ]);
          }


      }

      /*============ LOG =============*/
      DB::table('tbl_log')->insert([
          'user_id'       => $r->user_id,
          'action'        => 'ADD_CART',
          'notes'         => 'Add product_id: '.$r->product_id.' with qty: '.$r->qty,
          'module'        => 'CartController.php',
          'created_at'    => Carbon::now()
          ]);
      /*============ LOG =============*/

      $response['status'] = 'success';
      $response['message'] = 'success';
      $response['your_qty'] = $r->qty;
      $response['qty_product'] = $qty_product[0]->total_quantity;
      $response['qty_completed'] = $qty_completed[0]->total;
      $response['qty_pending'] = $qty_order;
      $response['remaining_qty'] = $remaining_qty;
      return response()->json($response, 200);

  }

    public function getQuantity($id,$token1)
    {

        /* check token */
        $app_id     = 'CRQ';
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

        $data = DB::table('tbl_order as c')
          ->join('tbl_order_detail as a', 'a.order_code', 'c.order_code')
          ->join('tbl_product as b', 'a.product_id', 'b.product_id')
          ->select(DB::raw('SUM(qty) As total'))
          ->where('c.user_id', $user_id)
          ->where('c.order_status', '<>', 'COMPLETED')
          ->get();

        if(count($data) == 0)
        {
            $response['status'] = 'failed';
            $response['message'] = 'user not found';
            return response()->json($response, 400);
        }


        $response['status'] = 'success';
        return response()->json(['data' => $data], 200);
    }

    public function view($id,$token1)
    {

        /* check token */
        $app_id     = 'CRV';
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

        $data = DB::table('tbl_order')
          ->select('order_code', 'full_name', 'phone', 'email', 'address', 'zip_code')
          ->where('user_id', $user_id)
          ->where('order_status', '<>', 'COMPLETED')
          ->get();

        if(count($data) == 0)
        {
            $response['status'] = 'failed';
            $response['message'] = 'user not found';
            return response()->json($response, 400);
        }

        foreach($data as $row)
        {

          DB::enableQueryLog();
          $data_shop = DB::table('tbl_order_shop')
            ->select('order_code', 'shop_id', 'shop_name', 'merchant_code', 'fufillment_type', 'fufillment_date', 'fufillment_time', 'fufillment_remarks', 'delivery_status', 'pickup_location')
            ->where('order_code', $row->order_code)
            ->get();
            //dd($data_shop);
          //dd(DB::getQueryLog());

            foreach($data_shop as $row2)
            {

              $data_order_detail = DB::table('tbl_order_detail as a')
                ->join('tbl_product as b', 'a.product_id', 'b.product_id')
                ->select('a.product_id', 'a.product_name', 'product_type', 'product_image', 'a.price', 'qty', 'sub_total', 'special_instruction', 'shop_notes', 'active_status', 'delete_status')
                ->where('order_code', $row->order_code)
                ->where('a.shop_id', $row2->shop_id)
                ->get();

              $row2->data_order_detail  = $data_order_detail;

              foreach($data_order_detail as $row3)
              {

                $qty_product   = DB::table('tbl_product')
                    ->where('product_id', '=', $row3->product_id)
                    ->get();

                $qty_completed = DB::select(
                    DB::raw("SELECT sum(qty) as total FROM tbl_order a INNER JOIN tbl_order_detail b USING (order_code) WHERE a.order_status = 'COMPLETED' AND b.product_id = '".$row3->product_id."'")
                );

                //$row3->total_quantity = $qty_product[0]->total_quantity;
                //$row3->total_sold = $qty_completed[0]->total;
                $row3->data_remaining = $qty_product[0]->total_quantity - $qty_completed[0]->total;

                /*$data_remaining = DB::select(
                      DB::raw("SELECT (a.total_quantity - sum(b.qty)) as total_qty FROM tbl_product a INNER JOIN tbl_order_detail b USING (product_id) WHERE product_id = '".$row3->product_id."' GROUP BY product_id")
                );*/

                //$row3->data_remaining  = $data_remaining[0]->total_qty;
              }
            }

          $row->data_shop               = $data_shop;
        }

        /*$data = DB::select(
            DB::raw("SELECT a.order_code, b.order_detail_id, b.product_id, c.product_type, d.fufillment_type, d.fufillment_date, d.fufillment_time, d.fufillment_remarks, d.pickup_location, b.special_instruction, a.order_status, b.product_id, b.product_name, c.product_image, b.price, qty, sub_total FROM tbl_order a INNER JOIN tbl_order_detail b using (order_code) INNER JOIN tbl_product c USING (product_id) INNER JOIN tbl_order_shop d ON c.shop_id = d.shop_id WHERE a.user_id ='".$user_id."' AND a.order_status <> 'COMPLETED'")
        );

        //foreach ($data as $datas) {
          $data2 = DB::select(
              DB::raw("SELECT (a.total_quantity - b.qty) as total_qty FROM tbl_product a INNER JOIN tbl_order_detail b USING (product_id) WHERE product_id = '".$data[0]->product_id."'")
          );
          $tot = $data2[0]->total_qty;*/
        //}

        /*$arr = array($data, $data2);
        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
          $arrNew[$incI]['order_id'] = $arrKey;
          $arrNew[$incI]['total'] = $tot;
          $incI++;
        }
        $encoded = json_encode($arrNew);*/

        $response['status'] = 'success';
        //$response['message'] = $arr;
        return response()->json(['data' => $data], 200);
        //return response()->json($data, 200);

      /* =============== */

    /*$categories = DB::select(
        DB::raw("SELECT a.order_code, b.order_detail_id, b.product_id, c.product_type, d.fufillment_type, d.fufillment_date, d.fufillment_time, d.fufillment_remarks, d.pickup_location, b.special_instruction, a.order_status, b.product_id, b.product_name, c.product_image, b.shop_id, b.price, qty, sub_total FROM tbl_order a INNER JOIN tbl_order_detail b using (order_code) INNER JOIN tbl_product c USING (product_id) INNER JOIN tbl_order_shop d ON c.shop_id = d.shop_id WHERE a.user_id ='".$user_id."' AND a.order_status <> 'COMPLETED' group by b.product_id")
    );

    foreach($categories as $category) {


        $datas = DB::select(
            DB::raw("SELECT (a.total_quantity - sum(b.qty)) as total_qty FROM tbl_product a INNER JOIN tbl_order_detail b USING (product_id) WHERE product_id = '".$category->product_id."'")
        );
        $category->remaining_quantity = $datas;
      }

      return response()->json(['cart' => $categories, 'total_quantity' => $datas], 200);*/


/* =============== */
    }

    public function history($id,$token1)
    {

        /* check token */
        $app_id     = 'HIS';
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

        $data = DB::table('tbl_order')
          ->select('order_code', 'full_name', 'phone', 'email', 'address', 'zip_code')
          ->where('user_id', $user_id)
          ->where('order_status', 'COMPLETED')
          ->get();

        if(count($data) == 0)
        {
            $response['status'] = 'failed';
            $response['message'] = 'user not found';
            return response()->json($response, 400);
        }

        /*$data = DB::select(
            DB::raw("SELECT x.order_code, a.shop_id, x.order_status, b.product_id, a.product_name, b.product_image, a.price, qty, sub_total FROM tbl_order x INNER JOIN tbl_order_detail a using (order_code) INNER JOIN tbl_product b USING (product_id) WHERE user_id ='".$user_id."' AND order_status = 'COMPLETED'")
        );*/

        foreach($data as $row)
        {

          DB::enableQueryLog();
          $data_shop = DB::table('tbl_order_shop as a')
            ->join('tbl_shop as b', 'a.shop_id', 'b.shop_id')
            ->select('order_code', 'a.shop_id', 'a.shop_name', 'merchant_code', 'fufillment_type', 'fufillment_date', 'fufillment_time', 'fufillment_remarks', 'delivery_status', 'a.pickup_location', 'shop_icon', 'cover_image')
            ->where('order_code', $row->order_code)
            ->get();
            //dd($data_shop);
          //dd(DB::getQueryLog());

            foreach($data_shop as $row2)
            {

              $data_order_detail = DB::table('tbl_order_detail')
                ->select('product_id', 'product_name', 'price', 'qty', 'sub_total', 'special_instruction', 'shop_notes')
                ->where('order_code', $row->order_code)
                ->where('shop_id', $row2->shop_id)
                ->get();

              $row2->data_order_detail  = $data_order_detail;
            }

          $row->data_shop               = $data_shop;
        }


        $arr = array($data);
        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['order_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        //return response()->json(['data' => $arr], 200);
        return response()->json(['data' => $data], 200);
        //return response()->json(['data' => $arr, 'shop_data' => $datas->data_shop, 'detail_data' => $datas_shop->data_order_detail], 200);
        //return response()->json($response, 200);

    }

    public function updateByShop(request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            // 'fufillment_type'     => ['string', 'required'],
            'order_code'          => ['string', 'required'],
            'user_id'             => ['string', 'required'],
            'shop_id'             => ['string', 'required'],
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'UBS';
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
        $duplicate   = DB::table('tbl_order_shop')
        ->join('tbl_order','tbl_order_shop.order_code','=','tbl_order.order_code')
        ->where('user_id', '=', $r->user_id)
        ->where('shop_id', '=', $r->shop_id)
        ->where('tbl_order_shop.order_code', '=', $r->order_code)
        ->where('order_status', '<>', 'SUCCESS')
        ->count();

        if ($duplicate == 0)
        {
            $response['status'] = 'error';
            $response['message'] = 'Data not found';
            return response()->json($response, 400);
        }
        /* end */

        DB::table('tbl_order_shop')
            ->where('order_code', $r->order_code)
            ->where('shop_id', $r->shop_id)
            ->update([
                'fufillment_type'      => $r->fufillment_type,
                'fufillment_date'      => $r->fufillment_date,
                'fufillment_time'      => $r->fufillment_time,
                'fufillment_remarks'   => $r->fufillment_remarks,
                'pickup_location'   => $r->pickup_location,
                'updated_at'           => now()
            ]);

        /*============ LOG =============*/
        DB::table('tbl_log')->insert([
            'user_id'       => $r->user_id,
            'action'        => 'UPDATE_CART_BY_SHOP',
            'notes'         => 'Update shop fufillment info',
            'module'        => 'CartController.php',
            'created_at'    => Carbon::now()
            ]);
        /*============ LOG =============*/

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);
    }

    public function deleteByProduct($id, $product_id, $order_code, $token1)
    {

        /* check token */
        $app_id     = 'DBP';
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

        $count = DB::table('tbl_order_detail')
        ->join('tbl_order','tbl_order_detail.order_code','=','tbl_order.order_code')
        ->where('product_id', $product_id)
        ->where('tbl_order.order_code', $order_code)
        ->where('order_status','<>','SUCCESS')
        ->where('user_id', $user_id)
        ->count();

        if($count == 0)
        {
            $response['status'] = 'failed';
            $response['message'] = 'data not found';
            return response()->json($response, 400);
        }

        //get product price to deduct the subtotal
        $data   = DB::table('tbl_product')
            ->where('product_id', '=', $product_id)
            ->get();

        //get current qty
        $data2 = DB::table('tbl_order_detail')
            ->join('tbl_order','tbl_order_detail.order_code','=','tbl_order.order_code')
            ->where('product_id', $product_id)
            ->where('tbl_order.order_code', $order_code)
            ->where('order_status','<>','SUCCESS')
            ->where('user_id', $user_id)
            ->get();

            //kalau ada > 1 produk dari 1 toko, maka harus cek apakah
            // produk A itu habis di kurangi tapi masih ada produk B
            //kalau produk B habis baru delete si shop nya
        if($data2[0]->qty > 1)
        {
            $datas = DB::select(
                DB::raw("UPDATE tbl_order_detail SET qty = qty-1, sub_total = sub_total - '".$data[0]->price."', updated_at = now() WHERE product_id ='".$product_id."' AND order_code ='".$order_code."'")
            );
        }else{
            DB::table('tbl_order_detail')
            ->join('tbl_order','tbl_order_detail.order_code','=','tbl_order.order_code')
            ->where('product_id', $product_id)
            ->where('tbl_order.order_code', $order_code)
            ->where('order_status','<>','SUCCESS')
            ->where('user_id', $user_id)
            ->delete();

            //check if shop_id is there on tbl_order_detail? if no then delete shop_id on tbl_order_shop
            $shop = DB::table('tbl_order_detail')
            ->where('shop_id', $data[0]->shop_id)
            ->where('order_code', $order_code)
            ->count();

            if($shop == 0)
            {
              DB::table('tbl_order_shop')
              ->where('shop_id', $data[0]->shop_id)
              ->where('order_code', $order_code)
              ->delete();
            }

        }

        /*============ LOG =============*/
        DB::table('tbl_log')->insert([
            'user_id'       => $user_id,
            'action'        => 'DELETE_CART_BY_PRODUCT',
            'notes'         => 'Delete qty for product_id: '.$product_id,
            'module'        => 'CartController.php',
            'created_at'    => Carbon::now()
            ]);
        /*============ LOG =============*/

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);

    }

    public function deleteByShop($id, $shop_id, $order_code, $token1)
    {

        /* check token */
        $app_id     = 'DBS';
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

        $count = DB::table('tbl_order_shop')
        ->join('tbl_order','tbl_order_shop.order_code','=','tbl_order.order_code')
        ->where('shop_id', $shop_id)
        ->where('tbl_order.order_code', $order_code)
        ->where('order_status','<>','SUCCESS')
        ->where('user_id', $user_id)
        ->count();

        if($count == 0)
        {
            $response['status'] = 'failed';
            $response['message'] = 'data not found';
            return response()->json($response, 400);
        }

        //get product price to deduct the subtotal
        DB::table('tbl_order_shop')
            ->where('shop_id', '=', $shop_id)
            ->where('order_code', '=', $order_code)
            ->delete();

            DB::table('tbl_order_detail')
            ->where('shop_id', '=', $shop_id)
            ->where('order_code', '=', $order_code)
            ->delete();

        /*============ LOG =============*/
        DB::table('tbl_log')->insert([
            'user_id'       => $user_id,
            'action'        => 'DELETE_CART_BY_SHOP',
            'notes'         => 'Delete shop_id: '.$shop_id,
            'module'        => 'CartController.php',
            'created_at'    => Carbon::now()
            ]);
        /*============ LOG =============*/

        $response['status'] = 'success';
        $response['message'] = 'success';
        return response()->json($response, 200);

    }



}
