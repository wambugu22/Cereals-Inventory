<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function send_2fa_email($to_email, $to_name, $code) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kevingachunji@gmail.com'; // YOUR GMAIL
        $mail->Password = 'itay jiay lgte gvbh'; // YOUR APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // From/To
        $mail->setFrom('kevingachunji@gmail.com', 'DevTech Partners');
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your DevTech Partners Login Code';
        $mail->Body = "
        <div style='font-family: Arial; padding: 40px; background: #f5f5f5;'>
            <div style='background: white; padding: 40px; border-radius: 10px; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #2c3e50; text-align: center;'>🌾 DevTech Partners</h1>
                <h2>Hello, $to_name!</h2>
                <p>Your login code is:</p>
                <div style='background: #667eea; color: white; font-size: 32px; font-weight: bold; padding: 20px; text-align: center; border-radius: 8px; letter-spacing: 10px; margin: 30px 0;'>
                    $code
                </div>
                <p><strong>⚠️ This code expires in 5 minutes</strong></p>
                <p style='color: #999; font-size: 12px; text-align: center; margin-top: 30px;'>DevTech Partners - Juja, Kenya</p>
            </div>
        </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>