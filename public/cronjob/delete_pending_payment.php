<?php
ini_set('display_errors',1);  error_reporting(E_ALL);

chdir('/var/www/assisi/backend-main/public/');
require 'config.php';
/*
for($i=1;$i<70;$i++)
{
  $voucher_code = 'DVT'.date('hs').substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 5);
  $s = "INSERT INTO tbl_discount_voucher SET code = '".$voucher_code."', name ='test', user_id = '15102', status = 0, created_at = now(), updated_at = now(), max_qty = 1, used_qty = 0, source = 'Whack a Mole'";
  echo $voucher_code.'<br/>';
  mysqli_query($conn, $s);
}
*/

date_default_timezone_set('Asia/Singapore');
$tgl = date("Y-m-d H:i:s");
$s = "SELECT order_code, updated_at FROM tbl_order WHERE order_status = 'PENDING' AND updated_at < '".$tgl."' - interval 16 minute";
$h =mysqli_query($conn, $s) or die (mysqli_error($conn));
while($r = mysqli_fetch_assoc($h))
{
  //check if any deduction on tbl_ledger based on order_code?
  $s2 = "SELECT order_code, ledger_id FROM tbl_ledger WHERE tranId = '".$r['order_code']."'";
  $h2 = mysqli_query($conn, $s2);
  while($r2 = mysqli_fetch_assoc($h2))
  {
    $s3 = "DELETE from tbl_ledger WHERE ledger_id = '".$r2['ledger_id']."'";
    mysqli_query($conn, $s3);
    echo $r['order_code'].' - '.$r['updated_at'].' - '.$r2['ledger_id'].'<br/>';
  }

  $s4 = "UPDATE tbl_discount_voucher SET status = 0, updated_at = '0000-00-00 00:00:00', used_qty = used_qty-1, order_code = '' WHERE order_code = '".$r['order_code']."'";
  mysqli_query($conn, $s4);

  $s5 = "DELETE FROM tbl_discount_voucher_log WHERE order_code = '".$r['order_code']."'";
  mysqli_query($conn, $s5);

}
?>
