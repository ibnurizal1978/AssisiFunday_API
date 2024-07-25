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

class ShopController extends Controller
{
    public function view($token1)
    {
        /* check token */
        $app_id     = 'SHP';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::table('tbl_shop')
            ->select('shop_id', 'shop_name', 'is_highlight', 'is_priority', 'is_halal', 'is_eco_merchant', 'shop_description', 'category_id', 'order_index', 'website_link', 'facebook_link', 'delivery_time', 'delivery_date', 'pickup_time', 'pickup_date', 'location', 'cover_image', 'shop_icon', 'shop_image', 'delivery_leadtime', 'pickup_leadtime', 'fufilment_type_option', 'fufilment_cash_option', 'fufilment_delivery', 'fufilment_pickup', 'fufilment_postage', 'fufilment_others', 'fufilment_cash', 'display_fufilment_delivery', 'display_fufilment_pickup', 'display_fufilment_postage', 'display_fufilment_dinein', 'display_fufilment_takeaway', 'display_fufilment_appointment', 'shop_type','pickup_location', 'golive', 'active_status', 'fb_live_url')
            ->where('active_status', 1)
            // ->orderby('order_index')
            ->orderby('shop_name')
            ->get();

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
            $arrNew[$incI]['shop_id'] = $arrKey;
            $incI++;
        }

        $encoded = json_encode($arrNew);*/
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function detail($id, $token1)
    {

        /* check token */
        $app_id     = 'SID';
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
        $data = DB::table('tbl_shop')
        ->select('shop_id', 'shop_name', 'is_highlight', 'is_priority', 'is_halal', 'is_eco_merchant', 'shop_description', 'category_id', 'order_index', 'website_link', 'facebook_link', 'delivery_time', 'delivery_date', 'pickup_time', 'pickup_date', 'location', 'cover_image', 'shop_icon', 'shop_image', 'delivery_leadtime', 'pickup_leadtime', 'fufilment_type_option', 'fufilment_cash_option', 'fufilment_delivery', 'fufilment_pickup', 'fufilment_postage', 'fufilment_others', 'fufilment_cash', 'display_fufilment_delivery', 'display_fufilment_pickup', 'display_fufilment_postage', 'display_fufilment_dinein', 'display_fufilment_takeaway', 'display_fufilment_appointment', 'shop_type','pickup_location', 'golive', 'active_status', 'fb_live_url')
                ->where('shop_id', '=', $id)
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
            $arrNew[$incI]['shop_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);*/

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
        $data = DB::table('tbl_shop')
        ->select('shop_id', 'shop_name', 'is_highlight', 'is_priority', 'is_halal', 'is_eco_merchant', 'shop_description', 'category_id', 'order_index', 'website_link', 'facebook_link', 'delivery_time', 'delivery_date', 'pickup_time', 'pickup_date', 'location', 'cover_image', 'shop_icon', 'shop_image', 'delivery_leadtime', 'pickup_leadtime', 'fufilment_type_option', 'fufilment_cash_option', 'fufilment_delivery', 'fufilment_pickup', 'fufilment_postage', 'fufilment_others', 'fufilment_cash', 'display_fufilment_delivery', 'display_fufilment_pickup', 'display_fufilment_postage', 'display_fufilment_dinein', 'display_fufilment_takeaway', 'display_fufilment_appointment', 'shop_type','pickup_location', 'golive', 'active_status', 'fb_live_url')
                ->where('shop_name', 'LIKE', "%" . $r->keywords . "%")
                ->where('active_status', 1)
                ->orWhere('category_id', 'LIKE', "%" . $r->keywords . "%")
                ->orWhere('shop_description', 'LIKE', "%" . $r->keywords . "%")
                ->orWhere('location', 'LIKE', "%" . $r->keywords . "%")
                ->orWhere('pickup_location', 'LIKE', "%" . $r->keywords . "%")
                ->orderby('order_index')
                ->orderby('shop_name')
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
            $arrNew[$incI]['shop_id'] = $arrKey;
            $incI++;
        }
        $encoded = json_encode($arrNew);*/
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function highlight($token1)
    {
        /* check token */
        $app_id     = 'SHL';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token';
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::table('tbl_shop')
            ->select('shop_id', 'shop_name', 'is_highlight', 'is_priority', 'is_halal', 'is_eco_merchant', 'shop_description', 'category_id', 'order_index', 'website_link', 'facebook_link', 'delivery_time', 'delivery_date', 'pickup_time', 'pickup_date', 'location', 'cover_image', 'shop_icon', 'shop_image', 'delivery_leadtime', 'pickup_leadtime', 'fufilment_type_option', 'fufilment_cash_option', 'fufilment_delivery', 'fufilment_pickup', 'fufilment_postage', 'fufilment_others', 'fufilment_cash', 'display_fufilment_delivery', 'display_fufilment_pickup', 'display_fufilment_postage', 'display_fufilment_dinein', 'display_fufilment_takeaway', 'display_fufilment_appointment', 'shop_type','pickup_location', 'golive', 'active_status', 'fb_live_url')
            ->where('is_highlight', 1)
            ->where('active_status', 1)
            ->orderby('is_priority')
            ->orderby('order_index')
            ->orderby('shop_name')
            ->get();

        // $arr = array($data);

        // $arrNew = array();
        // $incI = 0;
        // foreach($arr AS $arrKey => $arrData){
        //     $arrNew[$incI]['product_id'] = $arrKey;
        //     $incI++;
        // }

        // $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $data;
        return response()->json($response, 200);
    }

}
