<?php
ini_set('display_errors',1);  error_reporting(E_ALL);

$db_server        = '127.0.0.1';
$db_user          = 'assisi';
$db_password      = 'Database@123';
$db_name          = 'db_assisi';
$conn 			  = new mysqli($db_server,$db_user,$db_password,$db_name) or die (mysqli_error($conn));

$s1 = "SELECT product_original_id, product_id FROM tbl_product_similar";
$h1 = mysqli_query($conn, $s1) or die (mysqli_error($conn));
while($r1 = mysqli_fetch_assoc($h1)) {

    $s2 = "SELECT product_id, productID FROM tbl_product WHERE productID = '".$r1['product_original_id']."'";
    $h2 = mysqli_query($conn, $s2);
    $r2 = mysqli_fetch_assoc($h2);

    $s3 = "SELECT product_id, productID FROM tbl_product WHERE productID = '".$r1['product_id']."'";
    $h3 = mysqli_query($conn, $s3);
    $r3 = mysqli_fetch_assoc($h3);

    echo 'product original ID dari tbl_similar: '.$r1['product_original_id'].'<br/>';
    echo 'product_id dari tbl_product: '.$r2['product_id'].'<br/><br/>';
    echo 'product ID dari tbl_similar: '.$r1['product_id'].'<br/>';
    echo 'product id dari tbl_product: '.$r3['product_id'].'<br/>';
    echo '<b>INSERT:</b><br/>';

    $s5 = "INSERT INTO tbl_product_similar2 (original_product_id, similar_product_id) VALUES ('".$r2['product_id']."', '".$r3['product_id']."')";
    //mysqli_query($conn, $s5);
    echo '<hr/>';
}
?>