<?php
/**
 * includes/notify.php  — PHP 7.4 compatible (no match expressions)
 */
if (!isset($conn)) require_once __DIR__ . '/../config.php';

function save_notification($conn, $user_id, $email, $phone, $subject, $body, $type = 'order') {
    $uid  = $user_id ? (int)$user_id : 'NULL';
    $e    = mysqli_real_escape_string($conn, $email);
    $ph   = mysqli_real_escape_string($conn, $phone);
    $sub  = mysqli_real_escape_string($conn, $subject);
    $bod  = mysqli_real_escape_string($conn, $body);
    $typ  = mysqli_real_escape_string($conn, $type);
    mysqli_query($conn,
        "INSERT INTO notifications (user_id,email,phone,subject,body,type)
         VALUES($uid,'$e','$ph','$sub','$bod','$typ')");
    return (int)mysqli_insert_id($conn);
}

function mark_notification_sent($conn, $nid, $channel) {
    $c = mysqli_real_escape_string($conn, $channel);
    mysqli_query($conn, "UPDATE notifications SET sent_$c=1,sent_at=NOW() WHERE id=$nid");
}

function send_email($conn, $to_email, $to_name, $subject, $html_body) {
    $from_email = get_setting('contact_email');
    $site_name  = get_setting('site_name');
    if (!$from_email || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) return false;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $site_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";

    $gold = '#c9a84c';
    $dark = '#2c200a';
    $full = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='margin:0;padding:0;background:#faf6e8;font-family:Segoe UI,sans-serif'>
        <div style='max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e8d9a0'>
          <div style='background:{$dark};padding:24px 28px;text-align:center'>
            <span style='color:{$gold};font-size:22px;font-weight:800'>&#127807; " . htmlspecialchars($site_name) . "</span>
          </div>
          <div style='padding:28px 32px;color:#2c200a;line-height:1.7;font-size:15px'>{$html_body}</div>
          <div style='background:#fdf6e3;padding:16px 28px;text-align:center;font-size:12px;color:#7a6840;border-top:1px solid #e8d9a0'>
            &copy; " . date('Y') . " " . htmlspecialchars($site_name) . " &mdash; " . htmlspecialchars(get_setting('contact_address')) . "
          </div>
        </div></body></html>";

    return @mail($to_email, $subject, $full, $headers);
}

function notify_user($conn, $user_id, $to_email, $to_phone, $to_name, $subject, $html_body, $type = 'order') {
    $nid        = save_notification($conn, $user_id, $to_email, $to_phone, $subject, $html_body, $type);
    $sent_email = send_email($conn, $to_email, $to_name, $subject, $html_body);
    if ($sent_email) mark_notification_sent($conn, $nid, 'email');

    $wa_clean = preg_replace('/\D/', '', $to_phone);
    if ($wa_clean) {
        $site       = get_setting('site_name');
        $plain_text = strip_tags(html_entity_decode($html_body));
        $plain_text = preg_replace('/\s+/', ' ', trim($plain_text));
        $wa_msg     = urlencode($site . ': ' . $subject . "\n\n" . $plain_text);
        $wa_link    = 'https://wa.me/' . $wa_clean . '?text=' . $wa_msg;
        $wa_e       = mysqli_real_escape_string($conn, $wa_link);
        mysqli_query($conn, "UPDATE notifications SET whatsapp_link='$wa_e' WHERE id=$nid");
    }
}

// ── PHP 7 compatible status colour helpers ─────────────────
function _order_status_color($status) {
    if ($status === 'approved' || $status === 'delivered') return '#166534';
    if ($status === 'rejected') return '#991b1b';
    if ($status === 'shipped' || $status === 'processing') return '#1e40af';
    return '#92400e';
}
function _order_status_bg($status) {
    if ($status === 'approved' || $status === 'delivered') return '#dcfce7';
    if ($status === 'rejected') return '#fee2e2';
    if ($status === 'shipped' || $status === 'processing') return '#eff6ff';
    return '#fef3c7';
}
function _loan_status_color($status) {
    if ($status === 'approved' || $status === 'disbursed') return '#166534';
    if ($status === 'rejected') return '#991b1b';
    return '#92400e';
}
function _loan_status_bg($status) {
    if ($status === 'approved' || $status === 'disbursed') return '#dcfce7';
    if ($status === 'rejected') return '#fee2e2';
    return '#fef3c7';
}

function order_notification_body($order, $new_status, $admin_note) {
    $site      = get_setting('site_name');
    $gold      = '#c9a84c';
    $status_c  = _order_status_color($new_status);
    $status_bg = _order_status_bg($new_status);
    $note_html = $admin_note
        ? "<div style='margin-top:16px;padding:12px 16px;background:#fdf6e3;border-left:4px solid {$gold};border-radius:6px;font-size:14px'><strong>Note from our team:</strong><br>" . htmlspecialchars($admin_note) . "</div>"
        : '';

    return "
        <p>Dear <strong>" . htmlspecialchars($order['customer_name']) . "</strong>,</p>
        <p>Your order <strong style='color:{$gold}'>" . htmlspecialchars($order['order_ref']) . "</strong> has been updated.</p>
        <div style='text-align:center;margin:24px 0'>
            <span style='display:inline-block;padding:10px 28px;background:{$status_bg};color:{$status_c};border-radius:20px;font-size:18px;font-weight:800;border:1px solid {$status_c}'>"
            . strtoupper($new_status) .
            "</span>
        </div>
        <table style='width:100%;border-collapse:collapse;font-size:14px'>
            <tr><td style='padding:8px 0;color:#7a6840;border-bottom:1px solid #e8d9a0'>Order ref</td><td style='padding:8px 0;font-weight:600;border-bottom:1px solid #e8d9a0'>" . htmlspecialchars($order['order_ref']) . "</td></tr>
            <tr><td style='padding:8px 0;color:#7a6840;border-bottom:1px solid #e8d9a0'>Total</td><td style='padding:8px 0;font-weight:600;border-bottom:1px solid #e8d9a0'>" . fmt((float)$order['total_amount']) . "</td></tr>
            <tr><td style='padding:8px 0;color:#7a6840'>Status</td><td style='padding:8px 0;font-weight:600;color:{$status_c}'>" . htmlspecialchars(ucfirst($new_status)) . "</td></tr>
        </table>
        {$note_html}
        <p style='margin-top:20px;font-size:14px;color:#7a6840'>Questions? Reply to this email or WhatsApp: <strong>" . htmlspecialchars(get_setting('whatsapp_number')) . "</strong>.</p>
        <p style='font-size:13px;color:#9ca3af'>— The {$site} team</p>
    ";
}

function loan_notification_body($loan, $new_status, $admin_note) {
    $site      = get_setting('site_name');
    $gold      = '#c9a84c';
    $status_c  = _loan_status_color($new_status);
    $status_bg = _loan_status_bg($new_status);
    $note_html = $admin_note
        ? "<div style='margin-top:16px;padding:12px 16px;background:#fdf6e3;border-left:4px solid {$gold};border-radius:6px;font-size:14px'><strong>Note from our team:</strong><br>" . htmlspecialchars($admin_note) . "</div>"
        : '';

    return "
        <p>Dear <strong>" . htmlspecialchars($loan['applicant_name']) . "</strong>,</p>
        <p>Your loan application <strong style='color:{$gold}'>" . htmlspecialchars($loan['loan_ref']) . "</strong> has been updated.</p>
        <div style='text-align:center;margin:24px 0'>
            <span style='display:inline-block;padding:10px 28px;background:{$status_bg};color:{$status_c};border-radius:20px;font-size:18px;font-weight:800;border:1px solid {$status_c}'>"
            . strtoupper($new_status) .
            "</span>
        </div>
        <table style='width:100%;border-collapse:collapse;font-size:14px'>
            <tr><td style='padding:8px 0;color:#7a6840;border-bottom:1px solid #e8d9a0'>Loan ref</td><td style='padding:8px 0;font-weight:600;border-bottom:1px solid #e8d9a0'>" . htmlspecialchars($loan['loan_ref']) . "</td></tr>
            <tr><td style='padding:8px 0;color:#7a6840;border-bottom:1px solid #e8d9a0'>Amount</td><td style='padding:8px 0;font-weight:600;border-bottom:1px solid #e8d9a0'>" . fmt((float)$loan['requested_amount']) . "</td></tr>
            <tr><td style='padding:8px 0;color:#7a6840;border-bottom:1px solid #e8d9a0'>Farm</td><td style='padding:8px 0;border-bottom:1px solid #e8d9a0'>" . htmlspecialchars($loan['farm_size_acres']) . " acres — " . htmlspecialchars($loan['primary_crop']) . "</td></tr>
            <tr><td style='padding:8px 0;color:#7a6840'>Status</td><td style='padding:8px 0;font-weight:600;color:{$status_c}'>" . htmlspecialchars(ucfirst($new_status)) . "</td></tr>
        </table>
        {$note_html}
        <p style='margin-top:20px;font-size:14px;color:#7a6840'>Questions? Reply to this email or WhatsApp: <strong>" . htmlspecialchars(get_setting('whatsapp_number')) . "</strong>.</p>
        <p style='font-size:13px;color:#9ca3af'>— The {$site} team</p>
    ";
}
