<?php
chdir('/var/www/assisi/backend/public/cronjob/');
require 'config.php';

$s = "SELECT product_id, shop_id, total_quantity, evoucher_id FROM tbl_product a INNER JOIN tbl_evoucher b USING (product_id)";
$h = mysqli_query($conn, $s);
while($r = mysqli_fetch_assoc($h))
{
  $s1 = "SELECT evoucher_code FROM tbl_evoucher_list WHERE product_id = '".$r['product_id']."'";
  echo 'cek apakah product_id ini 0: '.$s1.'<br/>';
  $h1 = mysqli_query($conn, $s1);
  if(mysqli_num_rows($h1) == 0)
  {
    for($i=1;$i<=$r['total_quantity'];$i++)
    {
      $evoucher_code = $r['product_id'].substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 10);
      $s2 = "INSERT INTO tbl_evoucher_list SET evoucher_code = '".$evoucher_code."', evoucher_id = '".$r['evoucher_id']."', shop_id = '".$r['shop_id']."', product_id = '".$r['product_id']."', created_at = now(), staff_id = '53'";
      echo $s2.'<br/>';
      mysqli_query($conn, $s2);
    }
  }

  echo $r['product_id'].' : '.$r['total_quantity'].'<br/>';
}
?>
