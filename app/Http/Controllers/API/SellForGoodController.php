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

class SellForGoodController extends Controller
{
    public function view($token1)
    {
        /* check token */
        $app_id     = 'SFG';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token'.$token;
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::select(
            DB::raw("SELECT * FROM tbl_product WHERE sell_for_good = 1 AND delete_status = 0")
        );

        $response['status'] = 'success';
        $response['message'] = $data;
        return response()->json($response, 200);
    }

    public function sales($token1)
    {
        /* check token */
        $app_id     = 'SFG';
        $date       = Carbon::now()->isoFormat('YYYYMMDD');
        $token      = hash("sha256",$app_id.$date);

        if($token <> $token1)
        {
            $response['status'] = 'error';
            $response['message'] = 'invalid token'.$token;
            return response()->json($response, 400);
        }
        /* end check token */

        $data = DB::select(
            DB::raw("SELECT sell_for_good, product_id, b.product_description, a.product_name, product_image, sum(qty) as total_qty, sum(sub_total) as total_amount FROM tbl_order_detail a INNER JOIN tbl_product b USING (product_id) WHERE b.sell_for_good = 1 AND b.delete_status = 0 GROUP BY a.product_id")
        );

        $arr = array($data);

        $arrNew = array();
        $incI = 0;
        foreach($arr AS $arrKey => $arrData){
            $arrNew[$incI]['product_id'] = $arrKey;
            $incI++;
        }

        $encoded = json_encode($arrNew);

        $response['status'] = 'success';
        $response['message'] = $arr;
        return response()->json($response, 200);
    }

}
