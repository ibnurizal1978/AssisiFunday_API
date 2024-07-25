<?php
//header("Content-type: image/png");
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
ob_start();

chdir('/var/www/assisi/backend/public/cronjob/');
include '../plugins/phpqrcode/qrlib.php';
//require_once '../plugins/tcpdf/tcpdf_include.php';
require 'config.php';

$s_v  = "SELECT cover_image, a.product_id, product_name, evoucher_code, voucher_info FROM tbl_evoucher_list a INNER JOIN tbl_evoucher b USING (evoucher_id) INNER JOIN tbl_product c ON b.product_id = c.product_id INNER JOIN tbl_shop d ON c.shop_id = d.shop_id WHERE a.order_code = '".$_REQUEST['order_code']."'";
$h_v = mysqli_query($conn, $s_v);
while($r_v = mysqli_fetch_assoc($h_v)) {

  $ecc = 'H';
  $pixel_size = 5;
  $frame_size = 2;

  $file_name = $_REQUEST['order_code'].'-'.$r_v['product_id'].'-'.$r_v['evoucher_code'];
  $file_image = "../images/qr/".$file_name.".png";
  $qrcode = QRcode::png($r_v['evoucher_code'], $file_image, $ecc, $pixel_size, $frame_size);

  $assisi_logo    = $base_url."backend/public/images/logo/assisi_logo.png";
  $qrcode_file    = $base_url."backend/public/images/qr/".$file_name.".png";
  $evoucher_code  = $r_v['evoucher_code'];
  $product_name   = $r_v['product_name'];
  $evoucher_info  = htmlspecialchars_decode($r_v['voucher_info']);

  if($r_v['cover_image']<>'')
  {
    $cover_image   = $base_url.'cms/assets/img/cover/'.$r_v['cover_image'];
  }else{
    $cover_image   = $assisi_logo;
  }

  $html = <<<EOD
  <div id="image$evoucher_code"><table cellpadding=10 border="0" width="100%" style="background-color:#E8FBFD">
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
  <br/><br/>
  </div>
  EOD;

  echo $html;
file_put_contents('../images/qr/'.$_REQUEST['order_code'].'.html', ob_get_contents());
/*
  $output = "../images/qr/godeg.jpg";
  $img_width = 800;
  $img_height = 300;

  $img = imagecreatetruecolor($img_width, $img_height);

  $black = imagecolorallocate($img, 0, 0, 0);
  $white = imagecolorallocate($img, 205, 255, 255);
  $red   = imagecolorallocate($img, 255, 0, 0);
  $green = imagecolorallocate($img, 0, 255, 0);
  $blue  = imagecolorallocate($img, 0, 200, 250);
  $orange = imagecolorallocate($img, 255, 200, 0);
  $brown = imagecolorallocate($img, 220, 110, 0);

  imagefill($img, 0, 0, $white);

  imagestring($img, 5, $img_width/20, $img_height/20, $evoucher_code , $red);


  $patua_one = dirname(__FILE__) . '/Arialn.ttf';
  //imagettftext($img, 32, 0, $img_width/20, $img_height*3/10 + 2, $black, $patua_one, 'This is Patua One Font!');
  imagettftext($img, 16, 0, 20, $img_height*3/10, $black, $patua_one, 'This is Patua One Font!');

  $monoton = dirname(__FILE__) . '/Arialn.ttf';
  imagettftext($img, 16, 0, 200, $img_height*3/10, $blue, $monoton, $evoucher_info);

  $kaushan = dirname(__FILE__) . '/Arialn.ttf';
  imagettftext($img, 84, 0, $img_width/20, $img_height*8/10 - 2, $brown, $kaushan, 'Good Night!');
  imagettftext($img, 84, 0, $img_width/20, $img_height*8/10 + 2, $black, $kaushan, 'Good Night!');
  imagettftext($img, 84, 0, $img_width/20 - 2, $img_height*8/10, $brown, $kaushan, 'Good Night!');
  imagettftext($img, 84, 0, $img_width/20 + 2, $img_height*8/10, $black, $kaushan, 'Good Night!');
  imagettftext($img, 84, 0, $img_width/20, $img_height*8/10, $white, $kaushan, 'Good Night!');

  //imagepng($img);
  //imagepng($img, $output);*/

}

$url  = $base_url.'backend/public/cronjob/email_order.php';
$data =array("order_code"=>$_REQUEST['order_code']);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$result2 = curl_exec($curl);

$update = "UPDATE tbl_order SET generate_evoucher_check = 1 WHERE order_code = '".$_REQUEST['order_code']."'";
mysqli_query($conn, $update);
?>
<!--
<div id="previewImage"></div>
<script src="../js/jquery.min.js"></script>
<script src="../js/html2canvas.js"></script>
<script>
    $(document).ready(function() {

      html2canvas(element, {
      onrendered: function(canvas) {
              $("#previewImage").append(canvas);
              getCanvas = canvas;
          }
      });

        // Global variable
        var element = $("#image<?php echo $evoucher_code ?>");

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
            var imgageData = getCanvas.toDataURL("image/png",1);
            var newData = imgageData.replace(
            /^data:image\/png/, "data:application/octet-stream");

            $("#btn-Convert-Html2Image").attr(
            "download", "SistemITImage.png").attr(
            "href", newData);
        });
    });
</script>-->
