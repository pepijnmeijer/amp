<?php
/*
Credits: Bit Repository
URL: http://www.bitrepository.com/
*/
include dirname(dirname(__FILE__)).'/config-booking.php';
error_reporting (E_ALL ^ E_NOTICE);
$post = (!empty($_POST)) ? true : false;
if($post)
{
include 'functions-booking.php';
$company = stripslashes($_POST['company']);
$name = stripslashes($_POST['name']);
$city = stripslashes($_POST['city']);
$state = stripslashes($_POST['state']);
$zip = stripslashes($_POST['zip']);
$country = stripslashes($_POST['country']);
$email = stripslashes($_POST['email']);
$subject = stripslashes($_POST['subject']);
$venuename = stripslashes($_POST['venuename']);
$venuecity = stripslashes($_POST['venuecity']);
$venuestate = stripslashes($_POST['venuestate']);
$venuezip = stripslashes($_POST['venuezip']);
$venuecountry = stripslashes($_POST['venuecountry']);
$venuetelephone = stripslashes($_POST['venuetelephone']);
$venuewebsite = stripslashes($_POST['venuewebsite']);
$venuecapacity = stripslashes($_POST['venuecapacity']);
$eventhours = stripslashes($_POST['eventhours']);
$settime = stripslashes($_POST['settime']);
$ticketprice = stripslashes($_POST['ticketprice']);
$otherartists = stripslashes($_POST['otherartists']);
$message = stripslashes($_POST['message']);
$error = '';
// Check company name
if(!$company)
{
$error .= 'Please enter your company information.<br />';
}
// Check name
if(!$name)
{
$error .= 'Please enter your name.<br />';
}
// Check telephone
if(!$email)
{
$error .= 'Please enter your email adress.<br />';
}
// Check subject
if(!$subject)
{
$error .= 'Please enter a subject.<br />';
}
// Check message (length)
if(!$message || strlen($message) < 15)
{
$error .= "Please enter your message. It should have at least 15 characters.<br />";
}

$msg = "$company\n";
$msg .= "$name\n";
$msg .= "$city\n";
$msg .= "$state\n";
$msg .= "$zip\n";
$msg .= "$country\n";
$msg .= "$email\n";
$msg .= "$subject\n";
$msg .= "$venuename\n";
$msg .= "$venuecity\n";
$msg .= "$venuestate\n";
$msg .= "$venuezip\n";
$msg .= "$venuecountry\n";
$msg .= "$venuetelephone\n";
$msg .= "$venuewebsite\n";
$msg .= "$venuecapacity\n";
$msg .= "$eventhours\n";
$msg .= "$settime\n";
$msg .= "$ticketprice\n";
$msg .= "$otherartists\n";

if(!$error)
{
$mail = mail(WEBMASTER_EMAIL, $subject, $message, $msg,
     "From: ".$name." <".$email.">\r\n"
    ."Reply-To: ".$email."\r\n"
    ."X-Mailer: PHP/" . phpversion());
if($mail)
{
echo 'OK';
}
}
else
{
echo '<div class="notification_error">'.$error.'</div>';
}
}
?>