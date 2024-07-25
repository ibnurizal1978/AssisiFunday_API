<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

chdir('/var/www/assisi/backend/public/cronjob/');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require '../plugins/PHPMailer/src/Exception.php';
require '../plugins/PHPMailer/src/PHPMailer.php';
require '../plugins/PHPMailer/src/SMTP.php';
require 'config.php';

$s1 = "SELECT order_code, full_name, delivery_fee, donation, voucher_amount, credit_used, address, zip_code, email FROM tbl_order WHERE order_status = 'COMPLETED' AND email_sent_status = 0 and email<>''";
$h1 = mysqli_query($conn, $s1);
while($r1 = mysqli_fetch_assoc($h1)) {
    $email_header = $base_url."backend/public/images/edm/email_header.png";
    $email_footer = $base_url."backend/public/images/edm/email_footer.png";

    $content = "<table width=100%><tr><td align=center><img src=". $email_header."></td></tr><tr><td><h3 style='color:#3fb55f; text-align:center'>ORDER CONFIRMATION</h3><p style='color:#226C85'>Hello ".$r1['full_name'].",<br/><br/>Thank you for shopping for a good cause!<br/>Your order has been processed. Kindly find your purchase below.<br/><hr/><h3 style='color:#3fb55f;'>ORDER SUMMARY</h3>";

    $s2 = "SELECT a.shop_id, b.shop_name, location, a.pickup_location, fufillment_type, fufillment_remarks, date_format(fufillment_date, '%d-%m-%Y') as fufillment_date, fufillment_time, merchant_code FROM tbl_order_shop a INNER JOIN tbl_shop b USING (shop_id) WHERE order_code = '".$r1['order_code']."'";
    $h2 = mysqli_query($conn, $s2);
    while($r2 = mysqli_fetch_assoc($h2)) {
        $shop_id = $r2['shop_id'];

        //$ff_type = $r2['fulfilment_type'];
        if($r2['fufillment_type']=='delivery')
        {
          $lokasi = $r1['address'].' '.$r1['zip_code'];
        }
        elseif($r2['fufillment_type']=='dine in')
        {
          $lokasi = $r2['location'];
        }
        elseif($r2['fufillment_type']=='pick up')
        {
          if($r2['pickup_location']=='')  {
              $lokasi = $r2['location'];
          }else{
              $lokasi = $r2['pickup_location'];
          }
        }else{
          $lokasi = '';
        }


        if($r2['fufillment_remarks']=='')
        {
            $remarks = "";
        }else{
            $remarks = "<tr><td><b>Remarks:</b></td><td>".$r2['fufillment_remarks']."</td></tr>";
        }

        if($r2['fufillment_date'] <>'' || $r2['fufillment_time'] <> '')
        {
            $date =  "<tr><td><b>Date/Time:</b></td><td>".$r2['fufillment_date'].' / '.$r2['fufillment_time']."</td></tr>";
        }else{
            $date = '';
        }

        $content .= "<h3>".$r2['shop_name']." (".$r2['fufillment_type'].")</h3>";
        $content .= "<table style='color:#636363'>";
        $content .= $date;
        $content .= "<tr><td valign=top><b>Location:</b></td><td>".$lokasi."</td></tr>";
        $content .= $remarks;
        $content .= "</table>";
        $content .= "<br>";

        //======================================== TABLE SHOW  THE ITEMS ======================================//
        $content .= "<table width=60% style='color:#636363; padding:30px; border-collapse: collapse;'>";
        $content .= "<tr><td width=10% style='border: 1px solid #abb6b8; padding:10px'><b>ORDER NO.</b></td><td width=30% style='border: 1px solid #abb6b8; padding:10px'><b>PRODUCTS</b></td><td width=5% style='border: 1px solid #abb6b8; padding:10px'><b>PRICE</b></td></tr>";

        $s_products = "SELECT qty, sub_total, product_name, price, merchant_code, a.order_code FROM tbl_order_detail a INNER JOIN tbl_order_shop b USING (shop_id) WHERE a.order_code = '".$r1['order_code']."' AND shop_id = '".$shop_id."' GROUP BY a.order_code";
        $h_products = mysqli_query($conn, $s_products);
        while($r_products = mysqli_fetch_assoc($h_products)) {

            $content .= "<tr><td style='border: 1px solid #abb6b8; padding:10px'>".$r1['order_code']."</td><td style='border: 1px solid #abb6b8; padding:10px'>".$r_products['qty'].'x '.$r_products['product_name']."</td><td style='border: 1px solid #abb6b8; padding:10px'>$".$r_products['sub_total']."</td></tr>";

        }
        $content .= "</table>";
        //======================================== END TABLE SHOW  THE ITEMS ======================================//

    }

    //======================================== TABLE SHOW  THE ORDER ======================================//
    $s3 = "SELECT sum(sub_total) as sub_total FROM tbl_order_detail WHERE order_code = '".$r1['order_code']."'";
    $h3 = mysqli_query($conn, $s3);
    $r3 = mysqli_fetch_assoc($h3);
    $total_price = ($r3['sub_total']+$r1['donation']+$r1['delivery_fee'])-($r1['voucher_amount']+$r1['credit_used']);
    $content .= "<br/><hr/>";
    $content .= "<h3 style='color:#3fb55f;'>ORDER TOTAL</h3>";
    $content .= "Subtotal price - $".$r3['sub_total']."<br/>";
    $content .= "Discount - ($".$r1['voucher_amount'].")<br/>";
    $content .= "Donation - $".$r1['donation']."<br/>";
    $content .= "Credit - $".$r1['credit_used']."<br/>";
    $content .= "Delivery Service - $".$r1['delivery_fee'];
    $content .= "<br/><br/>";
    $content .= "<b>Total Price - $".$total_price."</b><br/>";
    $content .= "<hr/>";

    $content .= "<h3 style='color:#3fb55f;'>CONTACT DETAILS</h3>";
    $content .= "<table width=100% style='color:#636363;'>";
    $content .= "<tr><td width=5%><b>Name</b></td><td width=90%>".$r1['full_name']."</td></tr>";
    $content .= "<tr><td><b>Contact</b></td><td>".$r1['address']."</td></tr>";
    $content .= "<tr><td><b>Email</b></td><td>".$r1['email']."</td></tr>";
    $content .= "</table>";
    $content .= "<br/>";
    $content .= "<hr/>";

    $content .= "<h3 style='color:#3fb55f;'>DELIVERY DETAILS</h3>";
    $content .= "<table width=100% style='color:#636363;'>";
    $content .= "<tr><td width=5%><b>Name</b></td><td width=60%>".$r1['full_name']."</td></tr>";
    $content .= "<tr><td><b>Address</b></td><td>".$r1['address'].' '.$r1['zip_code']."</td></tr>";
    $content .= "<tr><td><b>Email</b></td><td>".$r1['email']."</td></tr>";
    $content .= "</table>";
    $content .= "<br/><br/>Cheers,<br/>Team Assisi<br/><br/></td></tr><tr><td align='center'><img src=".$email_footer."></td></tr></table>";
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

        //$path=$_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/".$r1['order_code']."-".$r['product_id']."-".$r['evoucher_code'].".pdf";
        //echo 'aa '.dirname(__FILE__, 2).' xx';
        $path=dirname(__FILE__,2)."/images/qr/".$r1['order_code'].".pdf";
        $name=$r1['order_code'].".pdf";
        //$name=$r1['order_code']."-".$r['product_id']."-".$r['evoucher_code'].".pdf";
        $mail->AddAttachment($path,$name,$encoding ='base64',$type = 'application/octet-stream');
        //echo 'ada'.$path;
          //$mail->AddAttachment($base_url."backend/public/images/qr/".$r1['order_code']."-".$r['product_id']."-".$r['evoucher_code'].".pdf");
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
