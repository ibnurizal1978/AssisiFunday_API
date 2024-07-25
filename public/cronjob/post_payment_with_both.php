<?php
ini_set('display_errors',1);  error_reporting(E_ALL);
chdir('/var/www/assisi/backend/public/cronjob/');
require 'config.php';

$s1 = "SELECT product_id, user_id, shop_id, qty FROM tbl_order_detail a INNER JOIN tbl_order b USING (order_code) WHERE b.order_code = '".$_REQUEST['order_code']."' AND b.evoucher_check = 0 AND order_status = 'COMPLETED'";
$h1 = mysqli_query($conn, $s1);
if(mysqli_num_rows($h1) > 0)
{
  while($r1 = mysqli_fetch_assoc($h1))
    for ($i=0; $i<$r1['qty']; $i++) {
    {

      /* check is the product_id can get evoucher? */
      $s2 = "SELECT product_id, evoucher_id FROM tbl_evoucher WHERE product_id = '".$r1['product_id']."'";
      $h2 = mysqli_query($conn, $s2);
      while($r2 = mysqli_fetch_assoc($h2))
      {

        if($r2['product_id'] <> 1513) {
          $foodpanda_status = 0;
          $evoucher_code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 8);
        }else{
          $s3 = "SELECT evoucher_code FROM tbl_foodpanda WHERE used_status = 0 LIMIT 1";
          $h3 = mysqli_query($conn, $s3);
          $r3 = mysqli_fetch_assoc($h3);

          $evoucher_code    = $r3['evoucher_code'];
          $foodpanda_status = 1;

          mysqli_query($conn, "UPDATE tbl_foodpanda SET used_status = 1, used_date = now(), user_id = '".$r1['user_id']."' WHERE evoucher_code = '".$evoucher_code."' LIMIT 1");
        }

        $s4 = "INSERT INTO tbl_evoucher_detail SET evoucher_id = '".$r2['evoucher_id']."', evoucher_code ='".$evoucher_code."', shop_id = '".$r1['shop_id']."', product_id = '".$r1['product_id']."', user_id = '".$r1['user_id']."', foodpanda = '".$foodpanda_status."', order_code = '".$_REQUEST['order_code']."', created_at = now()";
        mysqli_query($conn, $s4);
          //echo $s4;
      }
    }

  }


  $url  = $base_url.'backend/public/cronjob/generate_evoucher.php';
  $data =array("order_code"=>$_REQUEST['order_code']);

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $result2 = curl_exec($curl);


  $update = "UPDATE tbl_order SET evoucher_check = 1 WHERE order_code = '".$_REQUEST['order_code']."'";
  mysqli_query($conn, $update);
}
?>
