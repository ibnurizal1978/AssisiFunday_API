<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

chdir('/var/www/assisi/backend/public/');
include 'plugins/phpqrcode/qrlib.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require 'plugins/PHPMailer/src/Exception.php';
require 'plugins/PHPMailer/src/PHPMailer.php';
require 'plugins/PHPMailer/src/SMTP.php';
//require_once 'plugins/dompdf/autoload.inc.php';
//use Dompdf\Dompdf;
//require 'plugins/fpdf/fpdf.php';
//require 'plugins/fpdf/WriteHTML.php';
//$pdf=new PDF_HTML();
require_once 'plugins/tcpdf/tcpdf_include.php';

$files = array();
$s1 = "SELECT order_code, full_name, delivery_fee, donation, voucher_amount, credit_used, address, zip_code, email FROM tbl_order WHERE order_status = 'COMPLETED' AND email_sent_status = 0";
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

    /*================= E VOUCHER TAGGING ===============================*/
    //get info is this productID deserve to get evoucher?
    $s_v  = "SELECT cover_image, product_name, evoucher_code, voucher_info FROM tbl_evoucher_detail a INNER JOIN tbl_evoucher b USING (evoucher_id) INNER JOIN tbl_product c ON b.product_id = c.product_id INNER JOIN tbl_shop d ON c.shop_id = d.shop_id WHERE order_code = '".$r1['order_code']."'";
    $h_v = mysqli_query($conn, $s_v);
    while($r_v = mysqli_fetch_assoc($h_v)) {

      $ecc = 'H';
      $pixel_size = 5;
      $frame_size = 2;

      $file_name = $r1['order_code'].'-'.$r_v['evoucher_code'];
      $file_image = "images/qr/".$file_name.".png";
      QRcode::png($r_v['evoucher_code'], $file_image, $ecc, $pixel_size, $frame_size);

      $assisi_logo = $base_url."backend/public/images/logo/assisi_logo.png";
      $qrcode_file = $base_url."backend/public/images/qr/".$file_name.".png";
      /*$evoucher_layout = '<table cellpadding=20 width=100% style="background:#E8FBFD">
      <tr>
      <td width=15% valign="top" align="center"><br/><br/><br/>'.$icon.'</td>
      <td width=70% style="color:#226C85;" valign="top">
      <b style="color:#226C85; font-size:14pt"><span class=yellow>'.$r_v['evoucher_code'].'</span></b><br/>
      <h3 style="color:#226C85">'.$r_v['product_name'].'</h3>'.htmlspecialchars_decode($r_v['voucher_info']).'</td>
      <td valign="top" align="center">
          <img src='.$assisi_logo.' width="100" /><br/><br/><br/>
          <img src="'.$qrcode_file.'" width="100" /><br/><br/>''
      </td>
      </tr>
      </table><br/><br/>';*/
      $content .=  '<br/><br/>';//.$evoucher_layout;
      $isi = htmlspecialchars_decode($r_v['voucher_info']);


      $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
      $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
      $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
      $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);
      $pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
      $pdf->setHeaderMargin(PDF_MARGIN_HEADER);
      $pdf->setFooterMargin(PDF_MARGIN_FOOTER);
      $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
      $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
      $pdf->setFont('helvetica', '', 6, '', true);
      $pdf->AddPage();

      $evoucher_code = $r_v['evoucher_code'];
      $product_name  = $r_v['product_name'];
      $evoucher_info = htmlspecialchars_decode($r_v['voucher_info']);

      if($r_v['cover_image']<>'')
      {
        $cover_image   = $base_url.'cms/assets/img/cover/'.$r_v['cover_image'];
      }else{
        $cover_image   = $assisi_logo;
      }


      $html = <<<EOD
      <table cellpadding=10 border="0" width="100%" style="background-color:#E8FBFD">
      <tr>
      <td width="15%" valign="top" align="center"><br/><br/><br/><img width="50" src="$cover_image" /></td>
      <td width="60%" style="color:#226C85;" valign="top">
      <b style="color:#226C85; font-size:14pt"><br/><span class=yellow>$evoucher_code</span></b><br/>
      <h3 style="color:#226C85">$product_name</h3>$evoucher_info<br/></td>
      <td width="25%" valign="middle" align="center">
          <p>&nbsp;</p>
          <img width="70" src="$assisi_logo" /><p>&nbsp;</p>
          <img width="70" src="$qrcode_file" /><p>&nbsp;</p><p>&nbsp;</p>
      </td>
      </tr>
      </table>
      EOD;

      $pdf->SetCompression(true);
      $pdf->setCellPaddings( 0, 0, 0, 0);
      $pdf->writeHTMLCell(200,0,5,10,$html,0,0,true,true,'L',true);
      //$pdf->Output('example_009.pdf', 'I');
      $pdf->Output($_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/".$file_name.".pdf", 'F');

    //  echo $file_name.'<br/>';
      $files[] = $file_name;
      //$filename = "images/qr/".$file_name.".pdf";
      //$pdf->Output($filename,'F');


      /* generate PDF */
      /*$dompdf = new Dompdf(array('enable_remote' => true));
      $dompdf->setPaper('A4', 'landscape');
      $dompdf->loadHtml($evoucher_layout);
      $dompdf->render();
      $pdf = $dompdf->output();
      file_put_contents("images/qr/".$file_name.".pdf", $pdf);*/
      /*$file = "images/qr/".$file_name.".pdf";
      echo $file.'<br/>';
      $files = array($file);
      for($i=1;$i<count($files);$i++)
      {
      echo $files[$i];
      }
      $order_code = $r1['order_code'];*/
    }

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

    foreach($files as $item) {
        $mail->AddAttachment($_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/".$item.".pdf");
    }

    $mail->setFrom('afd@assisihospice.org.sg', 'Assisi Funday (no reply)');
    $mail->addAddress($r1['email'], $r1['full_name']);
    $mail->isHTML(true);
    $mail->Subject = '[TEST] Your Assisi Order';
    $mail->Body    = $content;

    if(!$mail->send()) {
        echo 'Mailer error: ' . $mail->ErrorInfo;
    } else {
        echo 'Message has been sent: '.$r1['email'];
    }

    //update tbl_order to set email set status = 1
    $s5 = "UPDATE tbl_order SET email_sent_status = 1, email_sent_date = now() WHERE order_code = '".$r1['order_code']."'";
    //mysqli_query($conn, $s5);

}
?>
