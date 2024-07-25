<?php
$base_url         = 'https://assisifunday.sg/';
$db_server        = '10.148.0.5';
$db_user          = 'assisi';
$db_password      = 'Database@123';
$db_name          = 'db_assisi';
$conn 			  = new mysqli($db_server,$db_user,$db_password,$db_name) or die (mysqli_error($conn));

?>
