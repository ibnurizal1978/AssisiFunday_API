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

class DailySpecialController extends Controller
{
    public function view($token1)
    {
        /* check token */
        $app_id     = 'DSP';
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
        $data = DB::table('tbl_product as a')
            ->join('tbl_shop as b','a.shop_id', 'b.shop_id')
            ->select('a.product_id', 'highlight_status', 'a.shop_id', 'a.shop_name', 'a.shop_description', 'product_name', 'product_type', 'a.order_index', 'price', 'usual_price', 'product_description', 'product_tc', 'product_image', 'product_keyword', 'total_quantity', 'accept_special_instruction', 'mandatory_pickup_location', 'product_similar', 'golive')
            ->where('shop_type', 'DS')
            ->where('a.active_status', 1)
            ->where('b.active_status', 1)
            ->where('a.delete_status', 0)
            ->where('b.golive', $now)
            ->orderby('a.order_index')
            ->orderby('a.product_name')
            ->get();
            //dd(DB::getQueryLog());

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        foreach($data as $row)
        {

          $outlet_detail = DB::table('tbl_shop_outlet')
            ->select('shop_outlet_id','shop_outlet_name', 'pickup_time', 'pickup_date')
            ->where('shop_id', $row->shop_id)
            ->get();
          if(count($outlet_detail)==0)
          {
            $row->outlet_detail  = '';
          }else{
            $row->outlet_detail  = $outlet_detail;
          }

          $qty_completed = DB::select(
              DB::raw("SELECT sum(qty) as total FROM tbl_order a INNER JOIN tbl_order_detail b USING (order_code) WHERE a.order_status = 'COMPLETED' AND b.product_id = '".$row->product_id."'")
          );

          $row->remaining_qty = $row->total_quantity - $qty_completed[0]->total;

        }

        /*$arr = array($data);

        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['product_id'] = $arrKey;
            $incI++;
        }

        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);*/
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function viewUpcoming($token1)
    {
        /* check token */
        $app_id     = 'DSP';
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
        $data = DB::table('tbl_product as a')
            ->join('tbl_shop as b','a.shop_id', 'b.shop_id')
            ->select('a.product_id', 'highlight_status', 'a.shop_id', 'a.shop_name', 'a.shop_description', 'product_name', 'product_type', 'a.order_index', 'price', 'usual_price', 'product_description', 'product_tc', 'product_image', 'product_keyword', 'total_quantity', 'accept_special_instruction', 'mandatory_pickup_location', 'product_similar', 'golive')
            ->where('shop_type', 'DS')
            ->where('a.active_status', 1)
            ->where('b.active_status', 1)
            ->where('a.delete_status', 0)
            ->where('b.golive', '>', $now)
            ->orderby('a.order_index')
            ->orderby('a.product_name')
            ->get();
            //dd(DB::getQueryLog());

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        foreach($data as $row)
        {

          $outlet_detail = DB::table('tbl_shop_outlet')
            ->select('shop_outlet_id','shop_outlet_name', 'pickup_time', 'pickup_date')
            ->where('shop_id', $row->shop_id)
            ->get();
          if(count($outlet_detail)==0)
          {
            $row->outlet_detail  = '';
          }else{
            $row->outlet_detail  = $outlet_detail;
          }

        }

        /*$arr = array($data);

        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['product_id'] = $arrKey;
            $incI++;
        }

        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);*/
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function detail($id, $token1)
    {

        /* check token */
        $app_id     = 'PID';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        //$id = Crypt::decrypt($id);
        $now = date('Y-m-d');
        $data = DB::table('tbl_product as a')
            ->join('tbl_shop as b','a.shop_id', 'b.shop_id')
            ->select('a.product_id', 'highlight_status', 'a.shop_id', 'a.shop_name', 'a.shop_description', 'product_name', 'product_type', 'a.order_index', 'price', 'usual_price', 'product_description', 'product_tc', 'product_image', 'product_keyword', 'total_quantity', 'accept_special_instruction', 'mandatory_pickup_location', 'product_similar')
            ->where('shop_type', 'DS')
            ->where('b.active_status', 1)
            ->where('b.golive', $now)
            ->where('a.product_id', '=', $id)
            ->limit(1)
            ->get();

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        foreach($data as $row)
        {

          $outlet_detail = DB::table('tbl_shop_outlet')
            ->select('shop_outlet_id','shop_outlet_name', 'pickup_time', 'pickup_date')
            ->where('shop_id', $row->shop_id)
            ->get();
          if(count($outlet_detail)==0)
          {
            $row->outlet_detail  = '';
          }else{
            $row->outlet_detail  = $outlet_detail;
          }

        }

        /*$arr = array($data);
        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['product_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr ;
        return response()->json($response, 200);*/
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function search(Request $r)
    {
        /* check input validation */
        $validate   = validator::make($r->all(), [
            'keywords'       => ['string', 'required','max:100']
        ]);

        if($validate->fails())
        {
            $response['status'] = 'error';
            $response['message'] = 'validation failed';
            return response()->json($response, 400);
        }
        /* end check input validation */

        /* check token */
        $app_id     = 'SRC';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $r->input('token'))
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        DB::enableQueryLog();
        $now = date('Y-m-d');
        $data = DB::table('tbl_product as a')
            ->join('tbl_shop as b','a.shop_id', 'b.shop_id')
            ->select('a.product_id', 'highlight_status', 'a.shop_id', 'a.shop_name', 'a.shop_description', 'product_name', 'product_type', 'a.order_index', 'price', 'usual_price', 'product_description', 'product_tc', 'product_image', 'product_keyword', 'total_quantity', 'accept_special_instruction', 'mandatory_pickup_location', 'product_similar')
            ->where('shop_type', 'DS')
            ->where('b.active_status', 1)
            ->where('b.golive', $now)
            ->where('product_name', 'LIKE', "%" . $r->keywords . "%")
            ->orWhere('product_keyword', 'LIKE', "%" . $r->keywords . "%")
            ->orWhere('product_description', 'LIKE', "%" . $r->keywords . "%")
            ->orWhere('a.shop_name', 'LIKE', "%" . $r->keywords . "%")
            ->orWhere('a.shop_description', 'LIKE', "%" . $r->keywords . "%")
            ->orderby('a.order_index')
            ->orderby('a.product_name')
            ->get();
                //dd(DB::getQueryLog());
                //dd($data);

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        foreach($data as $row)
        {

          $outlet_detail = DB::table('tbl_shop_outlet')
            ->select('shop_outlet_id','shop_outlet_name', 'pickup_time', 'pickup_date')
            ->where('shop_id', $row->shop_id)
            ->get();
          if(count($outlet_detail)==0)
          {
            $row->outlet_detail  = '';
          }else{
            $row->outlet_detail  = $outlet_detail;
          }

        }

        /*$arr = array($data);
        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['product_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);*/
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function similar($id, $token1)
    {

        /* check token */
        $app_id     = 'PRS';
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
            DB::raw('SELECT product_id, highlight_status, shop_id, shop_name, shop_description, product_name, product_type, order_index, price, usual_price, product_description, product_tc, product_image, product_keyword, total_quantity, accept_special_instruction, mandatory_pickup_location, product_similar FROM tbl_product_similar a LEFT JOIN tbl_product b ON a.similar_product_id = b.product_id WHERE a.original_product_id = '.$id)
        );

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        $arr = array($data);
        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['product_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr ;
        return response()->json($response, 200);
    }

    public function highlight($token1)
    {
        /* check token */
        $app_id     = 'HGH';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::table('tbl_product')
            ->select('product_id', 'highlight_status', 'shop_id', 'shop_name', 'shop_description', 'product_name', 'product_type', 'order_index', 'price', 'usual_price', 'product_description', 'product_tc', 'product_image', 'product_keyword', 'total_quantity', 'accept_special_instruction', 'mandatory_pickup_location', 'product_similar')
            ->where('highlight_status', 1)
            ->where('a.active_status', 1)
            ->orderby('order_index')
            ->orderby('product_name')
            ->get();

        if(count($data)==0) {
            $response['status'] = 'failed';
            $response['message'] = 'Data not found' ;
            return response()->json($response, 400);
        }

        foreach($data as $row)
        {

          $outlet_detail = DB::table('tbl_shop_outlet')
            ->select('shop_outlet_id','shop_outlet_name', 'pickup_time', 'pickup_date')
            ->where('shop_id', $row->shop_id)
            ->get();
          if(count($outlet_detail)==0)
          {
            $row->outlet_detail  = '';
          }else{
            $row->outlet_detail  = $outlet_detail;
          }

        }

        /*$arr = array($data);

        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['product_id'] = $arrKey;
            $incI++;
        }

        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);*/
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

}
