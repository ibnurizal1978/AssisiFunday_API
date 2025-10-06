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
//$s1 = "SELECT order_code, full_name, delivery_fee, donation, voucher_amount, credit_used, address, phone, zip_code, email, date_format(updated_at, '%d %M %Y') as updated_at FROM tbl_order WHERE order_status = 'COMPLETED' AND order_code = 'C220911HA5Z46XF'"; //C220911HA5Z46XF //C220911PZ5HRK76
$h1 = mysqli_query($conn, $s1);
while($r1 = mysqli_fetch_assoc($h1)) {
    $email_header = $base_url."backend-main/public/images/edm/2022-header.png";
    $email_footer = $base_url."backend-main/public/images/edm/2022-footer.png";

    $content = "<table border=0 cellpadding=0 cellspacing=0 width=70% style='background: #fff'>
      <tr><td colspan=3><img src=$email_header /></td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><h3 style='color:#2FD4E1'>ORDER CONFIRMATION</h3><br/><h3>Hi ".$r1['full_name']."!</h3>Thank you for shopping for a good cause!<br/>Your order has been confirmed. Kindly find your purchase detail below.<br/><br/></td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><hr/></td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><h3 style='color:#2FD4E1'>ORDER SUMMARY</h3>";

    $s2 = "SELECT a.shop_id, b.shop_name, location, a.pickup_location, fufillment_type, fufillment_remarks, date_format(fufillment_date, '%d-%m-%Y') as fufillment_date, fufillment_time, merchant_code FROM tbl_order_shop a INNER JOIN tbl_shop b USING (shop_id) WHERE order_code = '".$r1['order_code']."'";
    $h2 = mysqli_query($conn, $s2);

    $content .= "<tr><td>&nbsp;</td><td>";

    while($r2 = mysqli_fetch_assoc($h2)) {
        $ff_type = $r2['fufillment_type'];
        $shop_id = $r2['shop_id'];

        $content .= "<table width=100% border=1 style=border:1px solid #ff0000>";
        $content .= "<tr><td width=50%><b>".$r2['shop_name']."</b></td><td><b>".$r2['fufillment_type']."</b></td></tr>";

        if($r2['fufillment_type'] == 'delivery') {
          $content .= "<tr><td colspan=2>Delivery date/time: ".$r2['fufillment_date'].' '.$r2['fufillment_time']."</td></tr>";
        }

        if($r2['fufillment_type'] == 'pick up' && $r2['pickup_location'] <> '') {
          $content .= "<tr><td colspan=2>Collection Date: ".$r2['fufillment_date']."<br/>Collection Address: ".$r2['pickup_location']."</td></tr>";
        }

        $content .= "<tr><td colspan=2>#".$r2['merchant_code']."</td></tr>";
        //$content .= "<tr><td width=20%>&nbsp;</td><td><b style='color:#000; font-weight:800; text-transform:uppercase'>".$r2['shop_name']."</b></td><td width=20%>&nbsp;</td></tr>";

        //======================================== TABLE SHOW  THE ITEMS ======================================//

        $s_products = "SELECT qty, sub_total, product_name, price, product_id, merchant_code, a.order_code FROM tbl_order_detail a INNER JOIN tbl_order_shop b USING (shop_id) WHERE a.order_code = '".$r1['order_code']."' AND shop_id = '".$shop_id."' GROUP BY product_id";
        $h_products = mysqli_query($conn, $s_products);
        while($r_products = mysqli_fetch_assoc($h_products)) {

            $content .= "<tr><td>".$r_products['product_name']." x ".$r_products['qty']."</td><td>".$r_products['sub_total']."</td></tr>";

            //$content .= "<tr><td width=20%>&nbsp;</td><td>".$r_products['product_name']." x ".$r_products['qty']." - $".$r_products['sub_total']."</td><td width=20%>&nbsp;</td></tr>";

        }
        $content .="</table><br/><br/>";
        //======================================== END TABLE SHOW  THE ITEMS ======================================//

    }

    $content .= "</td><td>&nbsp;</td></tr>";

    //===================== CHECK IF THERE IS EVOUCHER, WRITE HERE ==============================//
    $s = "SELECT evoucher_code FROM tbl_evoucher_list WHERE order_code = '".$r1['order_code']."' GROUP BY order_code";
    $h = mysqli_query($conn, $s);
    if(mysqli_num_rows($h) > 0)
    {
      $content .= "</td><td></td><td>Please see attached for the E-Voucher(s) for redemption.</td><td></td></tr>";
    }

    //===================== CHECK IF THERE IS HARD COPY VOUCHER, WRITE HERE ==============================//
    $s = "SELECT product_type FROM tbl_product a INNER JOIN tbl_order_detail b USING (product_id) WHERE order_code = '".$r1['order_code']."'";
    $h = mysqli_query($conn, $s);
    $r = mysqli_fetch_assoc($h);
    if($r['product_type'] == 'Cash Voucher')
    {
      $content .= "</td><td></td><td>We will mail the hardcopy voucher(s) to you within 5 working days.</td><td></td></tr>";
    }

    //======================================== TABLE SHOW  THE ORDER ======================================//
    $s3 = "SELECT sum(sub_total) as sub_total FROM tbl_order_detail WHERE order_code = '".$r1['order_code']."'";
    $h3 = mysqli_query($conn, $s3);
    $r3 = mysqli_fetch_assoc($h3);

    if($r1['voucher_amount']<1) { $discount = 0.00; }else{ $discount = $r1['voucher_amount']; }

    $total_price = ($r3['sub_total']+$r1['donation']+$r1['delivery_fee'])-($r1['voucher_amount']+$r1['credit_used']);
    $content .= "<tr><td width=20%>&nbsp;</td><td><hr/></td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><h3 style='color:#2FD4E1;'>ORDER TOTAL</h3>";
    $content .= "Subtotal amount: $".$r3['sub_total']."<br/>";
    $content .= "Donation: $".$r1['donation']."<br/>";
    $content .= "Voucher: ($".$r1['voucher_amount'].")<br/>";
    $content .= "Credits: $".$r1['credit_used']."<br/>";
    $content .= "Delivery Cost: $".$r1['delivery_fee'];
    $content .= "<h3>Total Amount: $".$total_price."</h3><br/>";
    $content .= "</td><td width=20%>&nbsp;</td></tr>";
    $content .= "<tr><td width=20%>&nbsp;</td><td><hr/></td><td width=20%>&nbsp;</td></tr>";

    $content .= "<tr><td></td><td><h3 style='color:#2FD4E1;'>CONTACT DETAILS</h3></td><td></td></tr>";
    $content .= "<tr><td></td><td width=45%>Name: ".$r1['full_name']."</td><td></td></tr>";
    if($r1['phone'] <> '') { $content .= "<tr><td></td><td>Contact: ".$r1['phone']."</td><td></td></tr>"; }
    if($r1['email'] <> '') { $content .= "<tr><td></td><td>Email: ".$r1['email']."</td><td></td></tr>"; }

    //===================== CHECK IF FUFILMENT TYPE IS DELIVERY, WRITE HERE ==============================//
    $s = "SELECT fufillment_type FROM tbl_order_shop WHERE order_code = '".$r1['order_code']."'";
    $h = mysqli_query($conn, $s);
    $r = mysqli_fetch_assoc($h);

    if($ff_type == 'delivery' || $ff_type == 'pick up' || $ff_type == 'dine in/pick up')

    {
      $content .= "<tr><td></td><td><h3 style='color:#2FD4E1;'><br/><Br/>DELIVERY DETAILS</h3></td><td></td></tr>";
      $content .= "<tr><td></td><td>Name: ".$r1['full_name']."</td><td></td></tr>";
      $content .= "<tr><td></td><td>Address: ".$r1['address']." ".$r1['zip_code']."</td><td></td></tr>";
    }

    $content .= "<tr><td></td><td><br/><br/><br/>Cheers,<br>Team Assisi</td><td></td></tr>";
    $content .= "</td><td colspan=3></td></tr>";
    $content .= "<tr style='height:137px; background:url($email_footer)'><td width=10%>&nbsp;</td><td width='80%' align='center' valign='bottom'><a href=https://www.assisihospice.org.sg/about-us/>Disclaimer and Intellectual Rights</a> | <a href=https://www.assisihospice.org.sg/about-us/privacy-policy/>Privacy Notice</a> | &copy;2022 Assisi Hospice. All Rights Reserved.<br/><br/></td><td width=10%>&nbsp;</td></tr></table>";
    $content .= "</table>";
    echo $content;

    //======================================== END TABLE SHOW  THE ORDER ======================================//

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Mailer     = "smtp";
    $mail->Host       = 'smtp.office365.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = '';
    $mail->Password   = '';
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
    $mail->Subject = 'Your Assisi Order';
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
