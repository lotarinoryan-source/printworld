<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function _buildMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
    $mail->isHTML(true);
    return $mail;
}

// Notify admin when a new request comes in
function sendAdminNotification(array $req, array $items, string $pdfPath): bool {
    try {
        $mail = _buildMailer();
        $mail->addAddress(ADMIN_EMAIL, 'Printworld Admin');
        $mail->Subject = '[New Request] ' . ($req['request_number'] ?? '') . ' — ' . ($req['customer_name'] ?? '');

        $name    = htmlspecialchars($req['customer_name'] ?? '');
        $company = htmlspecialchars($req['company_name'] ?? '—');
        $email   = htmlspecialchars($req['email'] ?? '');
        $phone   = htmlspecialchars($req['contact_number'] ?? '');
        $location = htmlspecialchars($req['location'] ?? '—');
        $rnum    = htmlspecialchars($req['request_number'] ?? '');
        $msg     = htmlspecialchars($req['message'] ?? '');
        $site    = SITE_NAME;
        $adminUrl = SITE_URL . '/admin/quotations.php';

        $itemRows = '';
        foreach ($items as $item) {
            $d = htmlspecialchars($item['description'] ?? '');
            $q = (int)($item['quantity'] ?? 1);
            $itemRows .= "<tr><td style='padding:8px 12px;border-bottom:1px solid #eee;'>{$d}</td><td style='padding:8px 12px;border-bottom:1px solid #eee;text-align:center;'>{$q}</td></tr>";
        }

        $mail->Body = <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
          <div style="background:#111;padding:24px 32px;">
            <h1 style="color:#fff;margin:0;font-size:22px;letter-spacing:3px;">{$site}</h1>
            <p style="color:#aaa;margin:4px 0 0;font-size:11px;letter-spacing:1px;">NEW QUOTATION REQUEST</p>
          </div>
          <div style="padding:28px 32px;background:#fff;border:1px solid #e0e0e0;">
            <p style="font-size:14px;margin-bottom:20px;">A new quotation request has been submitted.</p>
            <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
              <tr><td style="padding:6px 0;color:#888;width:130px;font-size:12px;">Request No.</td><td style="font-weight:700;">{$rnum}</td></tr>
              <tr><td style="padding:6px 0;color:#888;font-size:12px;">Name</td><td>{$name}</td></tr>
              <tr><td style="padding:6px 0;color:#888;font-size:12px;">Company</td><td>{$company}</td></tr>
              <tr><td style="padding:6px 0;color:#888;font-size:12px;">Email</td><td>{$email}</td></tr>
              <tr><td style="padding:6px 0;color:#888;font-size:12px;">Phone</td><td>{$phone}</td></tr>
              <tr><td style="padding:6px 0;color:#888;font-size:12px;">Location</td><td>{$location}</td></tr>
              <tr><td style="padding:6px 0;color:#888;font-size:12px;">Message</td><td>{$msg}</td></tr>
            </table>
            <table style="width:100%;border-collapse:collapse;">
              <thead><tr style="background:#111;color:#fff;"><th style="padding:10px 12px;text-align:left;font-size:11px;">Description</th><th style="padding:10px 12px;font-size:11px;">Qty</th></tr></thead>
              <tbody>{$itemRows}</tbody>
            </table>
            <div style="margin-top:24px;">
              <a href="{$adminUrl}" style="background:#111;color:#fff;padding:12px 24px;text-decoration:none;font-size:12px;letter-spacing:1px;font-weight:700;">VIEW IN DASHBOARD →</a>
            </div>
          </div>
          <div style="background:#111;padding:14px 32px;text-align:center;">
            <p style="color:#888;font-size:11px;margin:0;">© {$site} Admin Notification</p>
          </div>
        </div>
        HTML;

        if (file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath, 'Request_' . ($req['request_number'] ?? 'new') . '.pdf');
        }
        $mail->AltBody = "New quotation request from {$name} ({$phone}). Request: {$rnum}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Admin mail error: ' . $e->getMessage());
        return false;
    }
}

// Send final quotation PDF to client
function sendFinalQuotationToClient(array $quotation, string $pdfPath): bool {
    try {
        $mail = _buildMailer();
        $mail->addAddress($quotation['email'], $quotation['customer_name']);
        $mail->addReplyTo(ADMIN_EMAIL, SMTP_FROM_NAME);

        $name  = htmlspecialchars($quotation['customer_name']);
        $qnum  = htmlspecialchars($quotation['quotation_number']);
        $total = 'P' . number_format($quotation['total_amount'], 2);
        $site  = SITE_NAME;
        $phone = CONTACT_PHONE;
        $fb    = CONTACT_FACEBOOK;

        $mail->Subject = "Your Quotation from {$site} — {$qnum}";
        $mail->Body = <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
          <div style="background:#111;padding:24px 32px;">
            <h1 style="color:#fff;margin:0;font-size:22px;letter-spacing:3px;">{$site}</h1>
            <p style="color:#aaa;margin:4px 0 0;font-size:11px;letter-spacing:1px;">THE PROFESSIONAL SIGNAGE MAKER</p>
          </div>
          <div style="padding:32px;background:#fff;border:1px solid #e0e0e0;">
            <p style="font-size:15px;margin-bottom:16px;">Dear <strong>{$name}</strong>,</p>
            <p style="color:#555;line-height:1.7;margin-bottom:20px;">Thank you for your interest in our services. Please find your quotation attached to this email.</p>
            <div style="background:#f5f5f5;border-left:4px solid #111;padding:18px 20px;margin-bottom:24px;">
              <p style="margin:0;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:1px;">Quotation Number</p>
              <p style="margin:4px 0 12px;font-size:18px;font-weight:700;color:#111;">{$qnum}</p>
              <p style="margin:0;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:1px;">Total Amount</p>
              <p style="margin:4px 0 0;font-size:22px;font-weight:700;color:#111;">{$total}</p>
            </div>
            <p style="color:#555;line-height:1.7;margin-bottom:20px;">To proceed or for any questions, please contact us:</p>
            <p style="margin:4px 0;font-size:13px;">📞 <a href="tel:{$phone}" style="color:#111;">{$phone}</a></p>
            <p style="margin:4px 0;font-size:13px;">📘 <a href="{$fb}" style="color:#111;">Facebook: Digos Tarpaulin Printing</a></p>
            <p style="margin-top:24px;color:#555;">Best regards,<br><strong>{$site} Team</strong></p>
          </div>
          <div style="background:#111;padding:14px 32px;text-align:center;">
            <p style="color:#f5c842;font-size:11px;margin:0;font-weight:700;">Printworld &amp; Advertising Services · Roxas Ext., Digos City</p>
          </div>
        </div>
        HTML;

        if (file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath, 'Quotation_' . $qnum . '.pdf');
        }
        $mail->AltBody = "Dear {$name}, your quotation {$qnum} is attached. Total: {$total}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Client mail error: ' . $e->getMessage());
        return false;
    }
}
