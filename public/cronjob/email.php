<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require '../plugins/PHPMailer/src/Exception.php';
require '../plugins/PHPMailer/src/PHPMailer.php';
require '../plugins/PHPMailer/src/SMTP.php';


//======================================== TABLE SHOW  THE ORDER ======================================//
$content = "<br/><hr/>";
$content .= "
<script>
  function Hello() {
  var copyText = document.getElementById('myInput')
  copyText.select();
  document.execCommand('copy')
  console.log('Copied Text')
}

function showAlert() {
    var myText = 'This can be whatever text you like!';
    alert (myText);
  }
</script>";
$content .= "<a href=# onclick=showAlert()>aaa</a><br/>";
$content .= "<a href='https://www.cnn.com'>cnn</a><br/>";
$content .= "<h3 style='color:#3fb55f;'>MAGICLEAN</h3>";
$content .= "<input type='text' value='a 2' id='myInput'><button onclick='Hello()'>Copy Text</button>";


echo $content;
//======================================== END TABLE SHOW  THE ORDER ======================================//

//$to = 'ibnuriza@gmail.com'; 
$to = 'myself@localhost'; 
$from = 'noreply@localhost'; 
$fromName = 'ggg'; 
$subject = 'PHP Email';  
$htmlContent = $content;
$headers = "From: $fromName"." <".$from.">"; 
$semi_rand = md5(time());  
$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";  
$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";  
$message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" . 
"Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n";  

$message .= "--{$mime_boundary}--"; 
$returnpath = "-f" . $from; 
$mail = @mail($to, $subject, $message, $headers, $returnpath);  
echo $mail?"<h1>Email Sent Successfully!</h1>":"<h1>Email sending failed.</h1>"; 


/*
 $mail = new PHPMailer(true);
 $mail->isSMTP();    
 $mail->Mailer     = "smtp";   
 $mail->Host       = '';                      //'smtp.office365.com'
 $mail->SMTPAuth   = true;       
 $mail->Username   = 'afd@assisihospice.org.sg';                     //afd@assisihospice.org.sg
 $mail->Password   = 'Muc36340';                               //Muc36340
 $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  
 $mail->Port       = 587;            
 $mail->SMTPSecure = 'tls';
 $mail->SMTPAutoTLS = false;
 $mail->SMTPKeepAlive = true;
 $mail->setFrom('afd@assisihospice.org.sg', 'Assisi Funday (no reply)'); //'afd@assisihospice.org.sg', 'Assisi Funday (no reply)'
 $mail->addAddress($rowx['cart_email'], $rowx['cart_name']);
// if($ada == 1) { $mail->addAttachment($file); }
 $mail->isHTML(true); 
 $mail->Subject = 'Your Assisi Order';
 $mail->Body    = $content;

 if(!$mail->send()) {
     echo 'Message was not sent.';
     echo 'Mailer error: ' . $mail->ErrorInfo;
     $email_msg = 'Message was not sent.'.$mail->ErrorInfo;;
 } else {
     echo 'Message has been sent';
 }*/
 ?>