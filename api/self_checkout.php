<?php

require_once '../config/database.php';
require_once '../includes/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
header('Content-Type: application/json');

$phone = trim($_GET['phone'] ?? '');

if (empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'Phone number required'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT

vl.id,

v.full_name,

v.email

FROM visit_logs vl

JOIN visitors v
ON vl.visitor_id=v.id

WHERE v.phone=?

AND vl.status='checked_in'

ORDER BY vl.check_in DESC

LIMIT 1
");

$stmt->execute([$phone]);

$visit = $stmt->fetch();

if (!$visit) {
    echo json_encode([
        'success' => false,
        'message' => 'No active visit found'
    ]);
    exit;
}

$update = $pdo->prepare("
    UPDATE visit_logs
    SET check_out = NOW(),
        status = 'checked_out'
    WHERE id = ?
");

$update->execute([$visit['id']]);
if(!empty($visit['email'])){

    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    try{

        $mail->isSMTP();



        $mail->SMTPAuth = true;

$mail->Host=$_ENV['MAIL_HOST'];

$mail->Port=$_ENV['MAIL_PORT'];

$mail->Username=$_ENV['MAIL_USER'];

$mail->Password=$_ENV['MAIL_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        
        $mail->SMTPDebug = 2;
        $mail->Debugoutput='html';
        $mail->SMTPOptions = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];

        $mail->setFrom(
            'kazinamir@gmail.com',
            'Ambitious Group'
        );

        $mail->addAddress(
            $visit['email'],
            $visit['full_name']
        );

        $mail->isHTML(true);

        $mail->Subject =
        'Thank You for Visiting Ambitious Group';

        $mail->Body = "

        <h2>Thank You for Visiting Ambitious Group</h2>

        <p>Dear {$visit['full_name']},</p>

        <p>

        Thank you for visiting Ambitious Group.

        We appreciate your time and hope your experience with us was pleasant.

        We look forward to welcoming you again in the future.

        </p>

        <br>

        Regards,<br>

        <b>Ambitious Group</b><br>

        Dubai, UAE

        ";

        $mail->send();

    }

    catch(Exception $e){

    echo json_encode([
        'success' => false,
        'message' => $mail->ErrorInfo
    ]);

    exit();

}

}
echo json_encode([
    'success' => true,
    'checkout_time' => date('Y-m-d H:i:s')
]);