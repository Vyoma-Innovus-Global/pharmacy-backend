<?php
header('Content-Type: text/html;');
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
include('db.php');
 die('check email');
$application_table			=  'register_student';
$query = "select * 
			from  $application_table 
			where is_payment=0
			and s_first_name is not null";
$result = pg_query($dbconn, $query);
echo "<br/>test". $query;
#exit();
#$mail_id	=	'para2588banerjee@gmail.com';
$mail_id	=	'sachikanta@aranaxweb.com,para2588banerjee@gmail.com,s.samanta.how@gmail.com';	
send_mail('',$mail_id );
while ($transaction = pg_fetch_assoc($result)) { 
	$email	=	$transaction['s_email'];
	echo "<br/>".$email;
	send_mail('',$email );
	 
} 
send_mail('',$mail_id ); 
// Instantiation and passing `true` enables exceptions


//$name = $_POST['name'];
//$email = $_POST['email'];
//$sender = $_POST['sender'];
//$subject = $_POST['subject'];
//$message = $_POST['message'];
//$txtmessage = $_POST['txtmessage'];
//$passkey = $_POST['passkey'];

function send_mail($name, $email_id){
$name = $name;
$email = $email_id; #"para2588banerjee@gmail.com";
$sender = "";
$subject = "Reminder: Complete Payment for Diploma in Pharmacy Admission Application 2025-26";
$message = "This is to inform that you have successfully registered/submitted your application for admission to Diploma Course in Pharmacy during the academic session 2025-26 through the portal https://sctedved.wb.gov.in/polyadm/ but the application fee payment has not yet been completed.
<br/><br/>
As the last date for fee payment is approaching, you are requested to complete the payment process at the earliest to ensure that your application remains valid. Applications for which the payment remains pending after the closing date will be treated as incomplete and liable for cancellation.
<br/><br/>
After successful payment, verify your payment status to confirm submission.
<br/><br/>
For any technical assistance regarding payment issues, please contact the Helpdesk.
<br/><br/><br/>
Regards,
<br/>
<br/>
Admisison Cell<br/>
West Bengal State Council of Technical & Vocational Education and Skill Development (WBSCT&VE&SD)<br/>
Email: commonadmission2024@gmail.com<br/>
Helpline: 9147339495 <br/> ";
$txtmessage = "This is a code generated email for SMTP Authentication Bypass.";
$mail = new PHPMailer(true);
try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = '10.1.32.59';						// Set the SMTP server to send through
    $mail->SMTPAuth   = false;                                   // Enable SMTP authentication
    $mail->Username   = 'noreply-sctvesd@wb.gov.in';						// SMTP username
	$mail->Password   = '';									// SMTP password
    $mail->SMTPSecure = false;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->SMTPAutoTLS = false;
	$mail->Port       = 25;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
	$mail->isHTML(true);
    //Recipients
    $mail->setFrom('noreply-sctvesd@wb.gov.in', $sender);
    $mail->addAddress($email, $name);               			// Name is optional
     
    // Content
    $mail->isHTML(true);                                  		// Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $message;
    $mail->AltBody = $message;

    $mail->send();
	return true;
    //echo 'Message has been sent';
} catch (Exception $e) {
	return false;
    //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
} 
}
?>