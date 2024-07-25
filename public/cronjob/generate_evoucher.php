<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

chdir('/var/www/assisi/backend/public/cronjob/');
include '../plugins/phpqrcode/qrlib.php';
require_once '../plugins/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
require 'config.php';

$arr = array();
$s_v  = "SELECT cover_image, a.product_id, product_name, shop_icon, evoucher_code, voucher_info FROM tbl_evoucher_list a INNER JOIN tbl_evoucher b USING (evoucher_id) INNER JOIN tbl_product c ON b.product_id = c.product_id INNER JOIN tbl_shop d ON c.shop_id = d.shop_id WHERE order_code = '".$_REQUEST['order_code']."'";
$h_v = mysqli_query($conn, $s_v);
while($r_v = mysqli_fetch_assoc($h_v)) {

  $ecc = 'H';
  $pixel_size = 5;
  $frame_size = 2;

  $file_name = $_REQUEST['order_code'].'-'.$r_v['product_id'].'-'.$r_v['evoucher_code'];
  $file_image = $_SERVER['DOCUMENT_ROOT']."/backend-main/public/images/qr/".$file_name.".png";
  QRcode::png($r_v['evoucher_code'], $file_image, $ecc, $pixel_size, $frame_size);

  $assisi_logo = $base_url."src/img/logo.png";
  $qrcode_file = $base_url."backend-main/public/images/qr/".$file_name.".png";


    if($r_v['cover_image']<>'')
    {
      $cover_image   = $base_url.'cms/assets/img/cover/'.$r_v['cover_image'];
    }else{
      $cover_image   = $assisi_logo;
    }

    if($r_v['shop_icon']<>'')
    {
      $shop_icon   = $base_url.'cms/assets/img/icon/'.$r_v['shop_icon'];
    }else{
      $shop_icon   = $assisi_logo;
    }

    $evoucher_code = $r_v['evoucher_code'];
    $product_name  = $r_v['product_name'];
    $evoucher_info = str_replace('&lt;p&gt;', '&lt;p style="font-family:helvetica;"&gt;', $r_v['voucher_info']);
    $evoucher_info = str_replace('&lt;li&gt;', '&lt;li style="font-family:helvetica;"&gt;', $evoucher_info);
    $evoucher_info = str_replace('&lt;strong&gt;', '&lt;strong style="font-family:helvetica;"&gt;', $evoucher_info);
    $godeg = htmlspecialchars_decode($evoucher_info);

  $evoucher_layout = '<table cellpadding=10 border=0 width=800 style="background:#E8FBFD; margin-left:-18px">
  <tr>
  <td width=100 valign="top" align="center"><br/><br/><br/><img width="100%" src='.$shop_icon.'></td>
  <td width=500 style="color:#226C85;" valign="top">
  <br/>
  <b style="color:#226C85; font-size:12pt; font-family:helvetica;"><span class=yellow>'.$r_v['evoucher_code'].'</span></b>
  <h3 style="color:#226C85; font-family:helvetica;">'.$r_v['product_name'].'</h3>'.$godeg.'</td>
  <td valign="top" width=100 align="center">
      <img src='.$assisi_logo.' width="150" /><br/><br/><br/>
      <img src="'.$qrcode_file.'" width="150" /><br/><br/>
  </td>
  </tr>
  </table><br/><br/>';
  $arr[] = $evoucher_layout;
  //$content .=  '<br/><br/>';//.$evoucher_layout;
  /*$isi = htmlspecialchars_decode($r_v['voucher_info']);


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
  */



  $html = <<<EOD
  <table cellpadding=10 border="0" width="100%" style="background-color:#E8FBFD; table-layout:fixed;">
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
  //$arr[] = $html;
  /*$pdf->SetCompression(true);
  $pdf->setCellPaddings( 0, 0, 0, 0);
  $pdf->writeHTMLCell(200,0,5,10,$html,0,0,true,true,'L',true);
  //$pdf->Output('example_009.pdf', 'I');
  $pdf->Output($_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/".$file_name.".pdf", 'F');*/

  //$filename = "images/qr/".$file_name.".pdf";
  //$pdf->Output($filename,'F');*/


  /*$file = "images/qr/".$file_name.".pdf";
  echo $file.'<br/>';
  $files = array($file);
  for($i=1;$i<count($files);$i++)
  {
  echo $files[$i];
  }
  $order_code = $r1['order_code'];*/

}
$output = implode("<br/>", $arr);
//echo $output;

/*$pdf->SetCompression(true);
$pdf->setCellPaddings( 0, 0, 0, 0);
$pdf->writeHTMLCell(200,0,5,10,$html,0,0,true,true,'L',true);
$pdf->Output('example_009.pdf', 'I');
//$pdf->Output($_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/".$file_name.".pdf", 'F');*/


$dompdf = new Dompdf(array('enable_remote' => true));
$dompdf->setPaper('A4', 'landscape');
//$placeholders = array_fill(0, count($arr), "?");
/*foreach($arr as $parent) {
    echo $parent;
}*/
$dompdf->loadHtml($output);
$dompdf->render();
$pdf = $dompdf->output();
//$dompdf->stream("", array("Attachment" => false)); /* ini buat tampil di layar tp nggak generate file */
//$dompdf->output($_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/godeg.pdf", 'I');
//$dompdf->output($_SERVER['DOCUMENT_ROOT']."backend/public/images/qr/".$_REQUEST['order_code'].".pdf", 'I');
file_put_contents($_SERVER['DOCUMENT_ROOT']."/backend-main/public/images/qr/".$_REQUEST['order_code'].".pdf", $pdf);
/* generate PDF */

$update = "UPDATE tbl_order SET generate_evoucher_check = 1 WHERE order_code = '".$_REQUEST['order_code']."'";
mysqli_query($conn, $update);





/*sleep(8);
$url  = $base_url.'backend/public/cronjob/email_order.php';
$data =array("order_code"=>$_REQUEST['order_code']);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$result2 = curl_exec($curl);*/
?>
