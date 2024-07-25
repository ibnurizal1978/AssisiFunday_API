<?php
$db_server        = '127.0.0.1';
$db_user          = 'assisi';
$db_password      = 'Database@123';
$db_name          = 'assisifunday';
$conn 			      = new mysqli($db_server,$db_user,$db_password,$db_name) or die (mysqli_error($conn));

/* ini kalau yang pengertian gue:
SELECT a.cartID, a.created_date as payment_date, b.created_date as order_date, TIMEDIFF(a.created_date, b.created_date) as different FROM `tbl_salespayment` a INNER JOIN tbl_salestransaction b USING (cartID) where b.cart_status = 'COMPLETED' AND a.trx_status = 'Approved' AND TIMEDIFF(a.created_date, b.created_date) > '00:16:00'

SELECT a.cartID, a.created_date as payment_date, b.created_date as order_date, TIMEDIFF(a.created_date, b.created_date) as different FROM `tbl_salespayment` a INNER JOIN tbl_salestransaction b USING (cartID) where b.cart_status = 'COMPLETED' AND a.trx_status = 'Approved'
*/

$s = "SELECT a.user_id, cartID, a.created_date as order_date, b.created_date as payment_date, count(a.cartID) as total, TIMEDIFF(b.created_date, a.created_date) as different FROM tbl_salescart a INNER JOIN tbl_salespayment b USING (cartID) WHERE trx_status = 'Approved' AND TIMEDIFF(b.created_date, a.created_date) < '00:16:00' GROUP BY a.user_id HAVING COUNT(a.cartID) > 1";
$h = mysqli_query($conn, $s);
while($r = mysqli_fetch_assoc($h))
{
  echo '<b>'.$r['user_id'].' -> '.$r['total'].'</b><br/>';
  $s2 = "SELECT created_date as payment_date FROM tbl_salespayment WHERE user_id = '".$r['user_id']."' AND trx_status = 'Approved'";
  $h2 = mysqli_query($conn, $s2);
  if(mysqli_num_rows($h2)>0)
  {
    while($r2 = mysqli_fetch_assoc($h2))
    {
      echo 'order_date: '.$r['order_date'].' > payment_date: '.$r2['payment_date'].'<br/>';
    }
  }
  echo '<br/><br/>';

}
 ?>
