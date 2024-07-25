<?php
ini_set('display_errors',1);  error_reporting(E_ALL);
chdir('/var/www/assisi/backend-main/public/cronjob/');
require 'config.php';

$s1 = "SELECT product_id, user_id, shop_id, qty, order_code, product_name FROM tbl_order_detail a INNER JOIN tbl_order b USING (order_code) WHERE b.order_code = '".$_REQUEST['order_code']."' AND b.evoucher_check = 0 AND order_status = 'COMPLETED'";
$h1 = mysqli_query($conn, $s1);
if(mysqli_num_rows($h1) > 0)
{
  while($r1 = mysqli_fetch_assoc($h1))
  {
    $s_check = "SELECT evoucher_code FROM tbl_evoucher_list WHERE product_id = '".$r1['product_id']."' AND user_id ='".$r1['user_id']."' AND order_code = '".$r1['order_code']."'";
    $h_check = mysqli_query($conn, $s_check);
    if(mysqli_num_rows($h_check) > 0)
    {
      echo 'udah ada';
    }else{

      $s2 = "SELECT evoucher_id, evoucher_code FROM tbl_evoucher_list WHERE product_id = '".$r1['product_id']."' AND user_id IS NULL";
      $h2 = mysqli_query($conn, $s2);
      if(mysqli_num_rows($h2)>0)
      {
        for ($i=0; $i<$r1['qty']; $i++)
        {
          $r2 = mysqli_fetch_assoc($h2);

              $s3 = "UPDATE tbl_evoucher_list SET user_id = '".$r1['user_id']."', order_code = '".$r1['order_code']."', checked_at = now() WHERE evoucher_code = '".$r2['evoucher_code']."' LIMIT 1";
              mysqli_query($conn, $s3);
        }
      }
    }
  }


  $url  = $base_url.'backend-main/public/cronjob/generate_evoucher.php';
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
