<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    private $config;
    
    public function __construct() {
        $this->config = include __DIR__ . '/../../config/email.php';
    }
    
    public function sendPasswordReset($email, $resetLink) {
        // If SMTP is not configured, save email to file for development
        if (!$this->config['use_smtp']) {
            return $this->saveEmailToFile($email, $resetLink);
        }
        
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->config['smtp_port'];
            
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset - SmartSHOP';
            $mail->Body = $this->getEmailTemplate($resetLink);
            
            $mail->send();
            return ['success' => true, 'message' => 'Password reset email sent successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Email could not be sent: ' . $mail->ErrorInfo];
        }
    }
    
    private function saveEmailToFile($email, $resetLink) {
        $emailContent = $this->getEmailTemplate($resetLink);
        $filename = __DIR__ . '/../../storage/emails/reset_' . time() . '.html';
        
        // Create directory if it doesn't exist
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($filename, $emailContent);
        
        return [
            'success' => true, 
            'message' => 'Email saved to file (SMTP not configured). Check: ' . $filename
        ];
    }
    
    public function sendEmail($email, $subject, $body) {
        if (!$this->config['use_smtp']) {
            $filename = __DIR__ . '/../../storage/emails/email_' . time() . '.html';
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($filename, $body);
            return true;
        }
        
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->config['smtp_port'];
            
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getEmailTemplate($resetLink) {
        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #1a237e;'>Password Reset Request</h2>
                <p>You requested a password reset for your SmartSHOP account.</p>
                <p>Click the button below to reset your password:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetLink' style='background: #1a237e; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                </div>
                <p><strong>This link will expire in 1 hour.</strong></p>
                <p>If you didn't request this, please ignore this email.</p>
                <hr style='margin: 30px 0;'>
                <p style='color: #666; font-size: 12px;'>SmartSHOP - Your Smart Shopping Solution</p>
            </div>
        ";
    }
}
?>