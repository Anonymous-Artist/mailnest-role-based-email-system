<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Send a single email and log the result.
 *
 * @param int    $adminId      ID of the admin sending
 * @param string $toEmail      Recipient email
 * @param string $toName       Recipient name
 * @param string $subject      Email subject (already placeholder-replaced)
 * @param string $body         HTML body (already placeholder-replaced)
 * @return array{success: bool, error: string}
 */
function sendEmail(int $adminId, string $toEmail, string $toName, string $subject, string $body): array {
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST']       ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME']   ?? '';
        $mail->Password   = $_ENV['SMTP_PASSWORD']   ?? '';
        $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);

        // Sender
        $mail->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? $mail->Username,
            $_ENV['SMTP_FROM_NAME']  ?? 'MailNest'
        );

        // Recipient
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();

        logEmail($adminId, $toEmail, $subject, 'sent', null);
        return ['success' => true, 'error' => ''];

    } catch (MailException $e) {
        $errorMsg = $mail->ErrorInfo;
        logEmail($adminId, $toEmail, $subject, 'failed', $errorMsg);
        return ['success' => false, 'error' => $errorMsg];
    } finally {
        $mail->clearAddresses();
        $mail->clearAttachments();
    }
}

/**
 * Replace {{placeholders}} in subject/body.
 *
 * @param string $template  Raw template string
 * @param array  $vars      ['name' => '...', 'email' => '...', ...]
 */
function replacePlaceholders(string $template, array $vars): string {
    $search  = [];
    $replace = [];
    foreach ($vars as $key => $value) {
        $search[]  = '{{' . $key . '}}';
        $replace[] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
    return str_replace($search, $replace, $template);
}
function logEmail(int $adminId, string $recipientEmail, string $subject, string $status, ?string $errorMessage): void {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO email_logs (admin_id, recipient_email, subject, status, error_message)
        VALUES (:admin_id, :recipient_email, :subject, :status, :error_message)
    ");
    $stmt->execute([
        'admin_id'        => $adminId,
        'recipient_email' => $recipientEmail,
        'subject'         => $subject,
        'status'          => $status,
        'error_message'   => $errorMessage,
    ]);
}
