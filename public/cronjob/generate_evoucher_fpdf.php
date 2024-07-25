<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

chdir('/var/www/assisi/backend-main/public/cronjob/');
include '../plugins/phpqrcode/qrlib.php';
//require_once 'plugins/dompdf/autoload.inc.php';
//use Dompdf\Dompdf;
require '../plugins/fpdf/fpdf.php';
$pdf = new FPDF();
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

  $pdf->AddPage();
  $pdf->SetFont('Arial','B',16);
  // Membuat string
  $pdf->Cell(185,7,'PT. Smart Cakrawala Aviation - Parts Data',0,1,'C');
  $pdf->SetFont('Arial','B',9);
  $pdf->Cell(185  ,7,'(As of '.date('d M Y').')',0,1,'C');
  // Setting spasi kebawah supaya tidak rapat
  $pdf->Cell(10,7,'',0,1);

  $pdf->SetFont('Arial','B',12);
  $pdf->Cell(10,6,'No.','B',0,'L');
  $pdf->Cell(70,6,'Part Name','B',0,'L');
  $pdf->Cell(20,6,'Qty','B',0,'C');
  $pdf->Cell(45,6,'Value ($)','B',0,'R');
  $pdf->Cell(45,6,'Total Value (IDR)','B',0,'R');
  $pdf->Cell(10,7,'',0,1);
  $pdf->SetFont('Arial','B',12);
  $pdf->SetTextColor(255, 0, 0);
  $pdf->Cell(10,6,'',0,0,'R');
  $pdf->Cell(70,6,'TOTAL',0,0,'R');
  $pdf->Output();
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
