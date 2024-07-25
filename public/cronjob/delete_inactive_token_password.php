<?php
ini_set('display_errors',1);  error_reporting(E_ALL);

chdir('/var/www/assisi/backend-main/public/');
require 'config.php';

date_default_timezone_set('Asia/Singapore');
$tgl = date("Y-m-d H:i:s");
$s1 = "DELETE FROM tbl_user_password_token WHERE created_at < '".$tgl."' - interval 30 minute";
mysqli_query($conn, $s1) or die (mysqli_error($conn));
?>
