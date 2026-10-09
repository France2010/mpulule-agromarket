<?php
require_once __DIR__ . '/mailer.php';

$result = send_mail(
    MAIL_TO_EMAIL,
    MAIL_TO_NAME,
    'Test email from Mpulule AgroMarket — ' . date('H:i:s'),
    '<h2 style="color:#f59e0b;">Hello!</h2>
     <p>If you receive this, PHPMailer is working correctly.</p>
     <p>Sent at: <strong>' . date('Y-m-d H:i:s') . '</strong></p>',
    MAIL_TO_EMAIL,
    'Test'
);

echo '<pre style="font-size:14px;background:#f5f5f5;padding:20px;border-radius:8px;">';
print_r($result);
echo '</pre>';

if ($result['success']) {
    echo '<p style="color:green;font-size:18px;font-weight:bold;">✅ Success! Check your Gmail inbox (and Spam folder).</p>';
} else {
    echo '<p style="color:red;font-size:18px;font-weight:bold;">❌ Failed. Check <code>email_errors.log</code> in your project folder.</p>';
}