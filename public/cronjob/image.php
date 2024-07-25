<?php
  //header("Content-type: image/png");
	$output = "../images/qr/godeg.jpg";
  /*$img_width = 800;
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

  imagestring($img, 5, $img_width/20, $img_height/20, 'This sentence was written using imagestring()!', $red);


  $patua_one = dirname(__FILE__) . '/Arialn.ttf';
  //imagettftext($img, 32, 0, $img_width/20, $img_height*3/10 + 2, $black, $patua_one, 'This is Patua One Font!');
  imagettftext($img, 16, 0, 10, $img_height*3/10, $black, $patua_one, 'This is Patua One Font!');

  $monoton = dirname(__FILE__) . '/Arialn.ttf';
  imagettftext($img, 16, 0, 200, $img_height*3/10, $blue, $monoton, 'MONOTON');

  $kaushan = dirname(__FILE__) . '/Arialn.ttf';
  imagettftext($img, 84, 0, $img_width/20, $img_height*8/10 - 2, $brown, $kaushan, 'Good Night!');
  imagettftext($img, 84, 0, $img_width/20, $img_height*8/10 + 2, $black, $kaushan, 'Good Night!');
  imagettftext($img, 84, 0, $img_width/20 - 2, $img_height*8/10, $brown, $kaushan, 'Good Night!');
  imagettftext($img, 84, 0, $img_width/20 + 2, $img_height*8/10, $black, $kaushan, 'Good Night!');
  imagettftext($img, 84, 0, $img_width/20, $img_height*8/10, $white, $kaushan, 'Good Night!');

  imagepng($img);
  //imagepng($img, $output);
*/
$html = <<<EOD
<div id="image"><table cellpadding=10 border="0" width="100%" style="background-color:#E8FBFD">
<tr>
<td width="15%" valign="top" align="center"><br/><br/><br/><img width="50" src="" /></td>
<td width="60%" style="color:#226C85;" valign="top">
<b style="color:#226C85; font-size:14pt"><br/><span class=yellow></span></b><br/>
<h3 style="color:#226C85"></h3><br/></td>
<td width="25%" valign="middle" align="center">
    <p>&nbsp;</p>
    <img width="70" src="" /><p>&nbsp;</p>
    <img width="70" src="" /><p>&nbsp;</p><p>&nbsp;</p>
</td>
</tr>
</table>
<br/><br/>
</div>
EOD;

echo $html;
?>
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

      var imgageData = getCanvas.toDataURL("image/png",1);
      var newData = imgageData.replace(
      /^data:image\/png/, "data:application/octet-stream");

      $("#btn-Convert-Html2Image").attr(
      "download", "SistemITImage.png").attr(
      "href", newData);



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
</script>
