<?php
require __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../util/loadEnv.php';
require_once __DIR__ . '/../../../../util/getEmails.php';
loadEnv(__DIR__ . '/../../../../api.env');

use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

$email = filter_var($_REQUEST['email'] ?? '', FILTER_VALIDATE_EMAIL);

try {
    $known = $email && in_array(
        strtolower($email),
        array_map('strtolower', getAllEmails()),
        true
    );
} catch (Throwable $e) {
    error_log($e->getMessage());
    $known = false;
}

if (!$known) {
    exit(json_encode(['status' => 'sent']));
}

$mail = new PHPMailer();   

$mail->isSMTP();
$mail->Host       = getenv('EMAIL_HOST') ?: 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = getenv('EMAIL_HOST_USER');
$mail->Password   = getenv('EMAIL_HOST_PASSWORD');
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;
$mail->CharSet    = 'UTF-8';

$mail->setFrom(getenv('EMAIL_HOST_USER'));
$mail->addAddress($email);

$mail->isHTML(true);
$mail->Subject = 'Autentificare firme.peviitor.ro';
$mail->Body    = '<h2>Salutare!</h2>';

$mail->send();

echo json_encode(['status' => 'sent']);