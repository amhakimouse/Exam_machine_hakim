<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Assuming vendor/autoload.php is in the root
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env (safe to call multiple times — vlucas/phpdotenv is idempotent)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

/**
 * Send an HTML email via SMTP, with optional file attachments.
 *
 * @param string $to          Recipient email address.
 * @param string $subject     Email subject.
 * @param string $message     HTML body.
 * @param array  $attachments Array of ['path' => '/abs/path/file.pdf', 'name' => 'display.pdf']
 * @return bool               TRUE on success, FALSE on failure (error is logged).
 */
function sendEmail(string $to, string $subject, string $message, array $attachments = []): bool {
    $mail = new PHPMailer(true);

    try {
        // ── Server settings ───────────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST']   ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER']   ?? '';
        $mail->Password   = $_ENV['SMTP_PASS']   ?? '';
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? 'tls';
        $mail->Port       = (int) ($_ENV['SMTP_PORT'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        // ── Sender / recipient ────────────────────────────────────────────
        $mail->setFrom(
            $_ENV['FROM_EMAIL'] ?? $mail->Username,
            $_ENV['FROM_NAME']  ?? 'EventHub Pro'
        );
        $mail->addAddress($to);

        // ── Content ───────────────────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message); // Plain-text fallback

        // ── Attachments (NEW — backward-compatible: defaults to []) ───────
        foreach ($attachments as $att) {
            $path = $att['path'] ?? '';
            $name = $att['name'] ?? basename($path);
            if ($path && file_exists($path)) {
                $mail->addAttachment($path, $name);
            }
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("[mailer.php] PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
