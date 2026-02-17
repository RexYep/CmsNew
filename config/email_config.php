<?php
// ============================================
// EMAIL CONFIGURATION
// config/email_config.php
// ============================================

// Email configuration using PHPMailer
// Install: composer require phpmailer/phpmailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer is loaded via composer autoload in config.php
// No need to require vendor/autoload.php again here

function sendEmailSMTP($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = getenv('MAIL_HOST')     ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('MAIL_USERNAME')  ?: 'cmsproperty@gmail.com';
        $mail->Password   = getenv('MAIL_PASSWORD')  ?: 'abcd eeee eee efrr'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)(getenv('MAIL_PORT') ?: 587); 

        // Recipients
        $mail->setFrom(
            getenv('MAIL_USERNAME') ?: 'cmsproperty@gmail.com',
            defined('SITE_NAME') ? SITE_NAME : 'Complaint Management System'
        );
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>