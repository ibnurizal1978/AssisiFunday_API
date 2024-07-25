<?php
ini_set('display_errors',1);  error_reporting(E_ALL);

chdir('/var/www/assisi/backend/public/cronjob/');
require 'config.php';
date_default_timezone_set('Asia/Singapore');
$tgl = date("Y-m-d H:i:s");
$s = "DELETE FROM tbl_user where active_status = 'PENDING' AND created_at < '".$tgl."' - INTERVAL 30 minute";
$h = mysqli_query($conn, $s) or die (mysqli_error());
//mysqli_query($conn, $s2);
?>
