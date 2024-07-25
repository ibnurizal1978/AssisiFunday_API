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
$base_url         = 'https://assisifunday.trinaxmind.com/';
?>
<script src="<?php echo $base_url ?>backend/public/plugins/html_to_image/jquery.min.js"></script>
<script src="<?php echo $base_url ?>backend/public/plugins/html_to_image/html2canvas.js"></script>
<!DOCTYPE html>
<html>
<head>
    <title>
       Cara Convert HTML ke Image
    </title>
    <script src="jquery.min.js"></script>
    <script src="html2canvas.js"></script>
</head>
<body>

    <center>
    <h2 style="color:purple">
        Convert Bagian HTML ke IMAGE
    </h2>

    <div id="html-content-holder" style="background-color: #e2c6c6;
                color: brown; width: 500px;
                padding: 10px;">
        <strong>
            Tutorial Convert HTML ke Image
        </strong>
        <hr style='color:black'/>
        <h3 style="color: #3e4b51;">
            Tentang Kami
        </h3>
        <p style="color: #3e4b51;">
            <b>SistemIT.com</b> adalah website software development, sharing informasi, tutorial dan info teknologi lainnya
        </p>
        <p style="color: #3e4b51;">
            Anda juga dapat melakukan pemesanan web atau pemesanan pembuatan sistem informasi secara custom. Banyak dari klien atau pelanggan
            kami sangat terbantu dalam project mereka. Tuggu Apalagi silahkan kontak admin web SistemIT.com
            <br>
        </p>
    </div>

    <input id="btn-Preview-Image" type="button" value="Preview" />

    <a id="btn-Convert-Html2Image" href="#">Download</a>

    <h3>Preview Berikut adalah Gambar:</h3>

    <div id="previewImage"></div>

    <script>
        $(document).ready(function() {

            // Global variable
            var element = $("#html-content-holder");

            // Global variable
            var getCanvas;
            $("#btn-Preview-Image").on('click', function() {
                html2canvas(element, {
                onrendered: function(canvas) {
                        $("#previewImage").append(canvas);
                        getCanvas = canvas;
                    }
                });
            });
            $("#btn-Convert-Html2Image").on('click', function() {
                var imgageData =
                    getCanvas.toDataURL("image/png",1);

                // Now browser starts downloading
                // it instead of just showing it
                var newData = imgageData.replace(
                /^data:image\/png/, "data:application/octet-stream");

                $("#btn-Convert-Html2Image").attr(
                "download", "SistemITImage.png").attr(
                "href", newData);
            });
        });
    </script>
    </center>
</body>
</html>

<?php

$db_server        = '127.0.0.1';
$db_user          = 'assisi';
$db_password      = 'Database@123';
$db_name          = 'db_assisi';
$conn 			  = new mysqli($db_server,$db_user,$db_password,$db_name) or die (mysqli_error($conn));

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

      $content .=  '<br/><br/>';//.$evoucher_layout;
      $isi = htmlspecialchars_decode($r_v['voucher_info']);


      $evoucher_code = $r_v['evoucher_code'];
      $product_name  = $r_v['product_name'];
      $evoucher_info = htmlspecialchars_decode($r_v['voucher_info']);
      if($r_v['cover_image']<>'')
      {
        $cover_image   = $base_url.'cms/assets/img/cover/'.$r_v['cover_image'];
      }else{
        $cover_image   = $assisi_logo;
      }

      ?>
      <div id="html-content-holder<?php echo $file_name ?>">
      <table cellpadding=10 border="0" width="100%" style="background-color:#E8FBFD">
      <tr>
      <td width="15%" valign="top" align="center"><br/><br/><br/><img width="50" src="<?php echo $cover_image ?>" /></td>
      <td width="60%" style="color:#226C85;" valign="top">
      <b style="color:#226C85; font-size:14pt"><br/><span class=yellow><?php echo $evoucher_code ?></span></b><br/>
      <h3 style="color:#226C85"><?php echo $product_name ?></h3><?php echo $evoucher_info ?><br/></td>
      <td width="25%" valign="middle" align="center">
          <p>&nbsp;</p>
          <img width="70" src="<?php echo $assisi_logo ?>" /><p>&nbsp;</p>
          <img width="70" src="<?php echo $qrcode_file ?>" /><p>&nbsp;</p><p>&nbsp;</p>
      </td>
      </tr>
      </table>
      </div>


      <input id="btn-Preview-Image" type="button" value="Preview" />

        <a id="btn-Convert-Html2Image" href="#">Download</a>

        <h3>Preview Berikut adalah Gambar:</h3>

        <div id="previewImage"></div>

          <script>
              $(document).ready(function() {

                  // Global variable
                  var element = $("#html-content-holder<?php echo $file_name ?>");

                  // Global variable
                  var getCanvas;
                  $("#btn-Preview-Image").on('click', function() {
                      html2canvas(element, {
                      onrendered: function(canvas) {
                              $("#previewImage").append(canvas);
                              getCanvas = canvas;
                          }
                      });
                  });
                  $("#btn-Convert-Html2Image").on('click', function() {
                      var imgageData =
                          getCanvas.toDataURL("image/png",1);

                      // Now browser starts downloading
                      // it instead of just showing it
                      var newData = imgageData.replace(
                      /^data:image\/png/, "data:application/octet-stream");

                      $("#btn-Convert-Html2Image").attr(
                      "download", "SistemITImage.png").attr(
                      "href", newData);
                  });
              });
          </script>
          </center>
      </body>
      </html>
  <?php
      echo $file_name.'<br/>';
      $files[] = $file_name;
    }


    echo '<h2>kedua</h2>'.implode(",",$files).'<br/>';
    foreach($files as $item) {
        $mail->AddAttachment($_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/".$item.".pdf");
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

    /*foreach($files as $item) {
        $mail->AddAttachment($_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/".$item.".pdf");
    }*/

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
</body>
