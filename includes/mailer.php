<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/autoload.php';

// Mail credentials — injected by GitHub Actions on deploy
// For local development: set these to your own test values
define('MAIL_FROM', 'MAIL_USER_PLACEHOLDER');
define('MAIL_PASS_VAL', 'MAIL_PASS_PLACEHOLDER');
define('MAIL_HOST', 'mail.blocknet.online');
define('MAIL_PORT', 465);

function _createMailer()
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->Port       = MAIL_PORT;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // port 465 SSL
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_FROM;
    $mail->Password   = MAIL_PASS_VAL;
    $mail->setFrom(MAIL_FROM, 'BLOCKNET Platform');
    return $mail;
}

function sendWelcomeEmail($toEmail, $username)
{
    $mail = _createMailer();
    try {
        $mail->addAddress($toEmail, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to BLOCKNET, ' . $username . '!';

        $protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host        = $_SERVER['HTTP_HOST'] ?? 'blocknet.online';
        $logoUrl     = $protocol . '://' . $host . '/public/Block.png';
        $termsLink   = $protocol . '://' . $host . '/views/terms.php';
        $discordLink    = 'https://discord.gg/Cphf8Uchnp';
        $discordLogoUrl = 'https://cdn-icons-png.flaticon.com/512/3670/3670157.png';

        $mail->Body = "
            <div style='background-color: #080808; color: #ffffff; padding: 40px; font-family: \"Inter\", sans-serif; max-width: 600px; margin: 0 auto; border-radius: 12px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <img src='{$logoUrl}' alt='BLOCKNET Logo' style='width: 80px; height: 80px; margin-bottom: 10px;'>
                    <h1 style='color: #ffffff; font-size: 24px; font-weight: 800; letter-spacing: 2px;'>BLOCKNET</h1>
                </div>
                <h2 style='color: #ffffff; margin-bottom: 20px;'>Welcome to the community, {$username}!</h2>
                <p style='color: rgba(255,255,255,0.7); line-height: 1.6;'>We're thrilled to have you join BLOCKNET — where interests bring people together. Your account is now active and ready to explore.</p>
                <div style='margin: 30px 0; padding: 20px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;'>
                    <h3 style='margin-top: 0; color: #ffffff; font-size: 18px;'>Join our Discord Server</h3>
                    <p style='color: rgba(255,255,255,0.7);'>Connect with other members in real-time, get the latest updates, and participate in community events.</p>
                    <a href='{$discordLink}' style='display: inline-flex; align-items: center; background-color: #5865F2; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 10px;'>
                        <img src='{$discordLogoUrl}' width='20' style='margin-right: 8px; vertical-align: middle;'>
                        Join Discord Server
                    </a>
                </div>
                <div style='margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; font-size: 14px; text-align: center; color: rgba(255,255,255,0.5);'>
                    <p>Questions? <a href='mailto:support@blocknet.online' style='color: #ffffff; text-decoration: none; font-weight: 600;'>support@blocknet.online</a></p>
                    <p style='margin-top: 10px;'><a href='{$termsLink}' style='color: rgba(255,255,255,0.7); text-decoration: underline;'>Terms and Conditions</a></p>
                    <p style='margin-top: 20px;'>&copy; " . date('Y') . " BLOCKNET Platform. All rights reserved.</p>
                </div>
            </div>
        ";
        $mail->AltBody = "Welcome to BLOCKNET, {$username}!\n\nJoin our Discord: {$discordLink}\n\nQuestions? support@blocknet.online\nTerms: {$termsLink}\n\nThanks,\nThe BLOCKNET Team";

        $mail->send();
        return true;
    }
    catch (Exception $e) {
        error_log('BLOCKNET welcome email error: ' . $mail->ErrorInfo);
        return false;
    }
}

function sendPasswordResetEmail($toEmail, $username, $resetLink)
{
    $mail = _createMailer();
    try {
        $mail->addAddress($toEmail, $username);
        $mail->isHTML(true);
        $protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host        = $_SERVER['HTTP_HOST'] ?? 'blocknet.online';
        $logoUrl     = $protocol . '://' . $host . '/public/Block.png';

        $mail->Body = "
            <div style='background-color:#080808;color:#ffffff;padding:40px;font-family:sans-serif;max-width:600px;margin:0 auto;border-radius:12px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <img src='{$logoUrl}' alt='BLOCKNET Logo' style='width: 80px; height: 80px; margin-bottom: 10px;'>
                    <h1 style='color: #ffffff; font-size: 24px; font-weight: 800; letter-spacing: 2px;'>BLOCKNET</h1>
                </div>
                <h2 style='color:#ffffff;'>Password Reset Request</h2>
                <p style='color:rgba(255,255,255,0.7);'>Hello {$username},</p>
                <p style='color:rgba(255,255,255,0.7);'>You requested a password reset. Click the button below — this link is valid for <strong>60 minutes</strong>.</p>
                <a href='{$resetLink}' style='display:inline-block;background:#ffffff;color:#080808;padding:12px 28px;border-radius:6px;font-weight:700;text-decoration:none;margin:20px 0;'>Reset My Password</a>
                <p style='color:rgba(255,255,255,0.5);font-size:13px;'>Or copy: {$resetLink}</p>
                <p style='color:rgba(255,255,255,0.5);font-size:13px;'>If you didn't request this, ignore this email.</p>
                <p style='color:rgba(255,255,255,0.5);font-size:13px;'>Thanks,<br>The BLOCKNET Team</p>
            </div>
        ";
        $mail->AltBody = "Hello {$username},\n\nReset link (valid 60 min):\n{$resetLink}\n\nIgnore if you didn't request this.\n\nThanks,\nThe BLOCKNET Team";

        $mail->send();
        return true;
    }
    catch (Exception $e) {
        error_log('BLOCKNET reset email error: ' . $mail->ErrorInfo);
        return false;
    }
}