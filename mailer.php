<?php
/**
 * mailer.php — centralized email sending via PHPMailer + Gmail SMTP
 */

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Try Composer autoload first, then fall back to manual paths
$composer = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer)) {
    require_once $composer;
} else {
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
}

function send_mail(
    string $to_email,
    string $to_name,
    string $subject,
    string $html_body,
    ?string $reply_to_email = null,
    ?string $reply_to_name = null,
    array $attachments = []
): array {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        if ($reply_to_email) {
            $mail->addReplyTo($reply_to_email, $reply_to_name ?? $reply_to_email);
        }

        foreach ($attachments as $att) {
            if (!empty($att['path']) && file_exists($att['path'])) {
                $mail->addAttachment($att['path'], $att['name'] ?? basename($att['path']));
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html_body));

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        file_put_contents(
            __DIR__ . '/email_errors.log',
            date('Y-m-d H:i:s') . " [FAIL] To=$to_email Subj=$subject Err=" . $mail->ErrorInfo . PHP_EOL,
            FILE_APPEND
        );
        return ['success' => false, 'message' => 'Mailer error: ' . $mail->ErrorInfo];
    }
}