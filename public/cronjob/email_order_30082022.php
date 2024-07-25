<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
chdir('/var/www/assisi/backend-main/public/cronjob/');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require '../plugins/PHPMailer/src/Exception.php';
require '../plugins/PHPMailer/src/PHPMailer.php';
require '../plugins/PHPMailer/src/SMTP.php';
require 'config.php';
?>

<link rel="stylesheet" href="<?php echo $base_url ?>backend-main/public/css/edm.css" />

<?php
$s1 = "SELECT order_code, full_name, delivery_fee, donation, voucher_amount, credit_used, address, zip_code, phone, email, date_format(updated_at, '%d %M %Y') as updated_at FROM tbl_order WHERE order_status = 'COMPLETED' AND email_sent_status = 0 and email<>''";
//$s1 = "SELECT order_code, full_name, delivery_fee, donation, voucher_amount, credit_used, address, zip_code, email, date_format(updated_at, '%d %M %Y') as updated_at FROM tbl_order WHERE order_status = 'COMPLETED' AND order_code = 'C220616XNU2ZS3H'";
$h1 = mysqli_query($conn, $s1);
while($r1 = mysqli_fetch_assoc($h1)) {
    $email_header = $base_url."backend-main/public/images/edm/2022-header.png";
    $email_footer = $base_url."backend-main/public/images/edm/2022-footer.png";

    $content = "<table border=0 cellpadding=0 cellspacing=0 width=70% style='background: #fff'>
      <tr><td colspan=3><img src=$email_header /></td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><h3 style='color:#2FD4E1'>ORDER CONFIRMATION</h3><br/><h3>Hello ".$r1['full_name']."!</h3>Thank you for shopping for a good cause!<br/>Your order has been processed. Kindly find your purchase detail below.<br/><br/></td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><hr/></td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><h3 style='color:#2FD4E1'>ORDER SUMMARY #".$r1['order_code']."</h3>".$r1['updated_at']."<br/><br/><br/></td><td width=20%>&nbsp;</td></tr>";

    $s2 = "SELECT a.shop_id, b.shop_name, location, a.pickup_location, fufillment_type, fufillment_remarks, date_format(fufillment_date, '%d-%m-%Y') as fufillment_date, fufillment_time, merchant_code FROM tbl_order_shop a INNER JOIN tbl_shop b USING (shop_id) WHERE order_code = '".$r1['order_code']."'";
    $h2 = mysqli_query($conn, $s2);
    while($r2 = mysqli_fetch_assoc($h2)) {
        $shop_id = $r2['shop_id'];

        $content .= "<tr><td width=20%>&nbsp;</td><td><b style='color:#000; font-weight:800; text-transform:uppercase'>".$r2['shop_name']."</b></td><td width=20%>&nbsp;</td></tr>";

        //======================================== TABLE SHOW  THE ITEMS ======================================//

        $s_products = "SELECT qty, sub_total, product_name, price, merchant_code, a.order_code FROM tbl_order_detail a INNER JOIN tbl_order_shop b USING (shop_id) WHERE a.order_code = '".$r1['order_code']."' AND shop_id = '".$shop_id."' GROUP BY product_id";
        $h_products = mysqli_query($conn, $s_products);
        while($r_products = mysqli_fetch_assoc($h_products)) {

            $content .= "<tr><td width=20%>&nbsp;</td><td>".$r_products['product_name']." x ".$r_products['qty']." - $".$r_products['sub_total']."</td><td width=20%>&nbsp;</td></tr>";

        }
        $content .= "<tr><td colspan=3>&nbsp;</td></tr>";
        //======================================== END TABLE SHOW  THE ITEMS ======================================//

    }

    //======================================== TABLE SHOW  THE ORDER ======================================//
    $s3 = "SELECT sum(sub_total) as sub_total FROM tbl_order_detail WHERE order_code = '".$r1['order_code']."'";
    $h3 = mysqli_query($conn, $s3);
    $r3 = mysqli_fetch_assoc($h3);

    if($r1['voucher_amount']<1) { $discount = 0.00; }else{ $discount = $r1['voucher_amount']; }

    $total_price = ($r3['sub_total']+$r1['donation']+$r1['delivery_fee'])-($r1['voucher_amount']+$r1['credit_used']);
    $content .= "<tr><td width=20%>&nbsp;</td><td><hr/></td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><h3 style='color:#2FD4E1;'>ORDER TOTAL</h3>";
    $content .= "Subtotal price - $".$r3['sub_total']."<br/>";
    $content .= "Discount - ($".$r1['voucher_amount'].")<br/>";
    $content .= "Donation - $".$r1['donation']."<br/>";
    $content .= "Credit - $".$r1['credit_used']."<br/>";
    $content .= "Delivery Service - $".$r1['delivery_fee'];
    $content .= "<br/><br/>";
    $content .= "<h3>Total Price - $".$total_price."</h3><br/>";
    $content .= "</td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><hr/></td><td width=20%>&nbsp;</td></tr>";

    $content .= "<tr><td width=20%>&nbsp;</td><td><h3 style='color:#2FD4E1;'>BILLING AND SHIPPING</h3>";
    $content .= "<table>";
    $content .= "<tr><td width=45%><b>Name</b></td><td width=90%>".$r1['full_name']."</td></tr>";
    if($r1['address'] <> '') { $content .= "<tr><td><b>Address</b></td><td>".$r1['address']."</td></tr>"; }
    if($r1['zip_code'] <> '') { $content .= "<tr><td><b>Zip Code</b></td><td>".$r1['zip_code']."</td></tr>"; }
    if($r1['address'] == '') {
      if($r1['zip_code'] == '') {
        $content .= "<tr><td><b>Contact Number</b></td><td>".$r1['phone']."</td></tr>";
      }
    }
    $content .= "<tr><td><b>Country</b></td><td>Singapore</td></tr>";
    $content .= "</table>";
    $content .= "</td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr style='height:137px; background:url($email_footer)'><td width=10%>&nbsp;</td><td width='80%' align='center' valign='bottom'><a href=https://www.assisihospice.org.sg/about-us/>Disclaimer and Intellectual Rights</a> | <a href=https://www.assisihospice.org.sg/about-us/privacy-policy/>Privacy Notice</a> | &copy;2022 Assisi Hospice. All Rights Reserved.<br/><br/></td><td width=10%>&nbsp;</td></tr></table>";
    $content .= "</table>";
    echo $content;

    //======================================== END TABLE SHOW  THE ORDER ======================================//

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Mailer     = "smtp";
    $mail->Host       = 'smtp.office365.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'afd@assisihospice.org.sg';
    $mail->Password   = 'Muc36340';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAutoTLS = false;
    $mail->SMTPKeepAlive = true;

    $s = "SELECT evoucher_code, product_id FROM tbl_evoucher_list WHERE order_code = '".$r1['order_code']."' GROUP BY order_code";
    $h = mysqli_query($conn, $s);
    if(mysqli_num_rows($h) > 0)
    {
      while($r = mysqli_fetch_assoc($h))
      {
        $path=dirname(__FILE__,2)."/images/qr/".$r1['order_code'].".pdf";
        if(file_exists($path))
        {
          $name=$r1['order_code'].".pdf";
          $mail->AddAttachment($path,$name,$encoding ='base64',$type = 'application/octet-stream');
        }
      }
    }

    $mail->setFrom('afd@assisihospice.org.sg', 'Assisi Funday (no reply)');
    $mail->addAddress($r1['email'], $r1['full_name']);
    $mail->isHTML(true);
    $mail->Subject = '[TEST] Your Assisi Order';
    $mail->Body    = $content;

    if(!$mail->send()) {
        echo 'Mailer error: ' . $mail->ErrorInfo;
        $sent_status = $mail->ErrorInfo;
    } else {
        echo 'Message has been sent: '.$r1['email'];
        $sent_status = 'Message has been sent to '.$r1['email'];
    }

    //update tbl_order to set email set status = 1
    $s5 = "UPDATE tbl_order SET email_sent_status = 1, email_sent_date = now() WHERE order_code = '".$r1['order_code']."'";
    mysqli_query($conn, $s5);

    $s6 = "INSERT INTO tbl_order_email_status SET order_code = '".$r1['order_code']."', email_address = '".$r1['email']."', sent_status = '".$sent_status."', created_at = now()";
    mysqli_query($conn, $s6);
}
?>
