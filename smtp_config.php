<?php
// Load Composer autoload
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send contact form email using Gmail SMTP
 */
function sendContactMail($data)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'manojkumar.work1@gmail.com';
        $mail->Password   = 'iptx veck fhhp bhui';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // EMAIL HEADERS
        $mail->setFrom('manojkumar.work1@gmail.com', 'Website Contact');
        $mail->addAddress('manojkumar.work1@gmail.com'); // where you receive mail
        $mail->addReplyTo($data['email'], $data['name']);

        // EMAIL CONTENT
        $mail->isHTML(true);
        $mail->Subject = 'New Contact Inquiry - Website';

        $mail->Body = '
            <h3>New Contact Message</h3>
            <table cellpadding="8" cellspacing="0" border="1" width="100%">
                <tr><td><b>Name</b></td><td>' . htmlspecialchars($data['name']) . '</td></tr>
                <tr><td><b>Email</b></td><td>' . htmlspecialchars($data['email']) . '</td></tr>
                <tr><td><b>Mobile</b></td><td>' . htmlspecialchars($data['mob']) . '</td></tr>
                <tr><td><b>Subject</b></td><td>' . htmlspecialchars($data['sub']) . '</td></tr>
                <tr><td><b>Message</b></td><td>' . nl2br(htmlspecialchars($data['msg'])) . '</td></tr>
            </table>
        ';

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Optional: log error
        // error_log($mail->ErrorInfo);
        return false;
    }
}
