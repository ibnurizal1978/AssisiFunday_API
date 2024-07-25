<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

chdir('/var/www/assisi/backend-main/public/cronjob/');
include '../plugins/phpqrcode/qrlib.php';
//require_once 'plugins/dompdf/autoload.inc.php';
//use Dompdf\Dompdf;
//require 'plugins/fpdf/fpdf.php';
//require 'plugins/fpdf/WriteHTML.php';
//$pdf=new PDF_HTML();
require_once '../plugins/tcpdf/tcpdf_include.php';
require 'config.php';

$s_v  = "SELECT cover_image, a.product_id, product_name, evoucher_code, voucher_info FROM tbl_evoucher_detail a INNER JOIN tbl_evoucher b USING (evoucher_id) INNER JOIN tbl_product c ON b.product_id = c.product_id INNER JOIN tbl_shop d ON c.shop_id = d.shop_id WHERE order_code = '".$_REQUEST['order_code']."'";
$h_v = mysqli_query($conn, $s_v);
while($r_v = mysqli_fetch_assoc($h_v)) {

  $ecc = 'H';
  $pixel_size = 5;
  $frame_size = 2;

  $file_name = $_REQUEST['order_code'].'-'.$r_v['product_id'].'-'.$r_v['evoucher_code'];
  $file_image = "../images/qr/".$file_name.".png";
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
  //$content .=  '<br/><br/>';//.$evoucher_layout;
  $isi = htmlspecialchars_decode($r_v['voucher_info']);

$tai = $base_url.'backend-main/public/plugins/tcpdf/fonts/avenir.php';
echo $tai;
  $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


    // $font = new TCPDF_FONTS();
      //$regularFont = $font->addTTFfont($tai);
  $fontname = TCPDF_FONTS::addTTFfont($tai, 'TrueTypeUnicode', '', 96);
  $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
  $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
  $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);
  $pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
  $pdf->setHeaderMargin(PDF_MARGIN_HEADER);
  $pdf->setFooterMargin(PDF_MARGIN_FOOTER);
  $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
  $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
  $pdf->SetFont($fontname, '', 14, '', false);
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

sleep(8);
$url  = $base_url.'backend/public/cronjob/email_order.php';
$data =array("order_code"=>$_REQUEST['order_code']);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$result2 = curl_exec($curl);
?>
