<?php
ini_set('display_errors',1);  error_reporting(E_ALL);

chdir('/var/www/assisi/backend-main/public/');
require 'config.php';

for($i=1;$i<1001;$i++)
{
  $voucher_code = 'DVT'.date('d').substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 5);
  $s = "INSERT INTO tbl_discount_voucher SET code = '".$voucher_code."', name ='test', user_id = '15102', status = 0, created_at = now(), value=2, updated_at = now(), max_qty = 1, used_qty = 0, source = 'Whack a Mole'";
  echo $voucher_code.'<br/>';
  mysqli_query($conn, $s);
}

?>
