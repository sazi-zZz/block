<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendWelcomeEmail($toEmail, $username)
{
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sazedurrahman707@gmail.com'; // SMTP username
        $mail->Password = 'dgkv xsoq xxea twmq'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('sazedurrahman707@gmail.com', 'BLOCKNET Platform');
        $mail->addAddress($toEmail, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to BLOCKNET, ' . $username . '!';

        $logoUrl = 'http://' . $_SERVER['HTTP_HOST'] . 'public/Block.png';
        $discordLogoUrl = 'https://cdn-icons-png.flaticon.com/512/3670/3670157.png'; // Improved Discord Icon
        $termsLink = 'http://' . $_SERVER['HTTP_HOST'] . 'views/terms.php';
        $discordLink = 'https://discord.gg/Cphf8Uchnp'; // Placeholder discord link

        $mail->Body = "
            <div style='background-color: #080808; color: #ffffff; padding: 40px; font-family: \"Inter\", sans-serif; max-width: 600px; margin: 0 auto; border-radius: 12px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <img src='{$logoUrl}' alt='BLOCKNET Logo' style='width: 80px; height: 80px; margin-bottom: 10px;'>
                    <h1 style='color: #ffffff; font-size: 24px; font-weight: 800; letter-spacing: 2px;'>BLOCKNET</h1>
                </div>
                
                <h2 style='color: #ffffff; margin-bottom: 20px;'>Welcome to the community, {$username}!</h2>
                <p style='color: rgba(255,255,255,0.7); line-height: 1.6;'>We're thrilled to have you join BLOCKNET—where interests bring people together. Your account is now active and ready for you to explore.</p>
                
                <div style='margin: 30px 0; padding: 20px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;'>
                    <h3 style='margin-top: 0; color: #ffffff; font-size: 18px;'>Join our Discord Server</h3>
                    <p style='color: rgba(255,255,255,0.7);'>Connect with other members in real-time, get the latest updates, and participate in community events.</p>
                    <a href='{$discordLink}' style='display: inline-flex; align-items: center; background-color: #5865F2; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 10px;'>
                        <img src='{$discordLogoUrl}' width='20' style='margin-right: 8px; vertical-align: middle;'>
                        Join Discord Server
                    </a>
                </div>

                <div style='margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; font-size: 14px; text-align: center; color: rgba(255,255,255,0.5);'>
                    <p>Have any questions? Contact us at <a href='mailto:support@blocknet.com' style='color: #ffffff; text-decoration: none; font-weight: 600;'>support@blocknet.com</a></p>
                    <p style='margin-top: 10px;'>
                        <a href='{$termsLink}' style='color: rgba(255,255,255,0.7); text-decoration: underline;'>Terms and Conditions</a>
                    </p>
                    <p style='margin-top: 20px;'>&copy; " . date('Y') . " BLOCKNET Platform. All rights reserved.</p>
                </div>
            </div>
        ";

        $mail->AltBody = "Welcome to BLOCKNET, {$username}!\n\nWe're thrilled to have you join our interest-based community.\n\nJoin our Discord Server: {$discordLink}\n\nHave questions? Contact us at support@blocknet.com\nTerms and Conditions: {$termsLink}\n\nThanks,\nThe BLOCKNET Team";

        $mail->send();
        return true;
    }
    catch (Exception $e) {
        // Log error if needed: error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}