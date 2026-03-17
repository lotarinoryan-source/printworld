<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function _dompdf(): Dompdf {
    $opts = new Options();
    $opts->set('defaultFont', 'DejaVu Sans');
    $opts->set('isRemoteEnabled', true);
    $opts->set('isHtml5ParserEnabled', true);
    $d = new Dompdf($opts);
    $d->setPaper('A4', 'portrait');
    return $d;
}

function _saveDir(): string {
    $dir = UPLOAD_DIR . 'quotations/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

// =============================================
// FINAL QUOTATION PDF (with prices)
// =============================================
function generateQuotationPDF(array $q, array $items): string {
    $d = _dompdf();
    $d->loadHtml(buildPDFHtml($q, $items));
    $d->render();
    $path = _saveDir() . 'Quotation_' . $q['quotation_number'] . '.pdf';
    file_put_contents($path, $d->output());
    return $path;
}

function buildPDFHtml(array $q, array $items): string {
    $date      = date('F d, Y');
    $company   = htmlspecialchars($q['company_name'] ?? '');
    $address   = htmlspecialchars($q['prem_address'] ?? '');
    $branch    = htmlspecialchars($q['prem_branch'] ?? '');
    $dear      = htmlspecialchars($q['prem_dear'] ?? $q['customer_name'] ?? '');
    $prepBy    = htmlspecialchars($q['prem_prepared_by'] ?? 'Nino S. Del Rosario');
    $chkBy     = htmlspecialchars($q['prem_checked_by'] ?? 'Ryan Mark R. Lotarino');
    $discAmt   = (float)($q['discount_amount'] ?? 0);
    $subtotal  = (float)($q['subtotal'] ?? 0);
    $total     = (float)($q['total_amount'] ?? 0);
    $isPremium = !empty($q['is_premium']);

    if ($isPremium && $company) {
        $infoBlock = "<p style='margin:1px 0'><strong>Company Name:</strong> {$company}</p>";
        if ($address) $infoBlock .= "<p style='margin:1px 0'><strong>Address:</strong> {$address}</p>";
        if ($branch)  $infoBlock .= "<p style='margin:1px 0'><strong>Branch:</strong> {$branch}</p>";
    } else {
        $name     = htmlspecialchars($q['customer_name'] ?? '');
        $phone    = htmlspecialchars($q['contact_number'] ?? '');
        $email    = htmlspecialchars($q['email'] ?? '');
        $location = htmlspecialchars($q['location'] ?? '');
        $infoBlock = "<p style='margin:1px 0'><strong>Name:</strong> {$name}</p>";
        if ($phone)    $infoBlock .= "<p style='margin:1px 0'><strong>Contact:</strong> {$phone}</p>";
        if ($email)    $infoBlock .= "<p style='margin:1px 0'><strong>Email:</strong> {$email}</p>";
        if ($location) $infoBlock .= "<p style='margin:1px 0'><strong>Location:</strong> {$location}</p>";
    }
    $infoBlock .= "<p style='margin:1px 0'><strong>Date:</strong> {$date}</p>";

    $rows = '';
    foreach ($items as $item) {
        $rawDesc = preg_replace('/\s*[\x{2014}\-]+\s*Design:\s*(Yes|No)\s*$/iu', '', $item['description'] ?? '');
        $desc    = nl2br(htmlspecialchars($rawDesc));
        $qty     = (int)($item['quantity'] ?? 1);
        $up      = (float)($item['unit_price'] ?? 0);
        $sub     = (float)($item['subtotal'] ?? $qty * $up);
        $upStr   = $up > 0 ? 'P' . number_format($up, 2) : '';
        $subStr  = $sub > 0 ? 'P' . number_format($sub, 2) : '';
        $rows   .= "<tr>
          <td style='text-align:center;padding:10px 6px;border-bottom:1px solid #ddd;vertical-align:top'>{$qty}</td>
          <td style='padding:10px 8px;border-bottom:1px solid #ddd;font-weight:600;vertical-align:top'>{$desc}</td>
          <td style='text-align:center;padding:10px 6px;border-bottom:1px solid #ddd;vertical-align:top'>{$upStr}</td>
          <td style='text-align:right;padding:10px 8px;border-bottom:1px solid #ddd;font-weight:700;vertical-align:top'>{$subStr}</td>
        </tr>";
    }

    // Total block: full breakdown when discount applied, simple total otherwise
    if ($discAmt > 0) {
        $totalBlock =
            "<div style='text-align:right;padding:10px 8px 3px;font-size:11px;color:#333;border-top:1px solid #ddd'>" .
                "Original Price = P" . number_format($subtotal, 2) .
            "</div>" .
            "<div style='text-align:right;padding:3px 8px 3px;font-size:11px;color:#c00'>" .
                "Corporate Discount = P" . number_format($discAmt, 2) .
            "</div>" .
            "<div style='text-align:right;padding:6px 8px 6px;border-top:1px solid #ccc'>" .
                "<span style='font-size:18px;font-weight:900;letter-spacing:0.5px'>TOTAL PRICE P" . number_format($total, 2) . "</span>" .
            "</div>";
    } else {
        $totalBlock =
            "<div style='text-align:right;padding:14px 8px 6px'>" .
                "<span style='font-size:18px;font-weight:900;letter-spacing:0.5px'>TOTAL P" . number_format($total, 2) . "</span>" .
            "</div>";
    }

    return _pdfTemplate($infoBlock, $dear, $rows, $totalBlock, $prepBy, $chkBy);
}

function _pdfTemplate(string $infoBlock, string $dear, string $rows, string $totalBlock, string $prepBy, string $chkBy): string {
    // Load T&C from DB
    $tnc = '';
    try {
        $db = db();
        $r  = $db->query("SELECT content_value FROM site_content WHERE content_key='quotation_tnc' LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) $tnc = trim($row['content_value']);
    } catch (\Throwable $e) {}
    if (!$tnc) {
        $tnc = "Full payment must be made within 30 calendar days from project completion.\nPrintworld shall not be held liable for any acts of God that may occur before or during delivery, installation or execution of materials.\nSignages for this project will be installed before the store opening.\nPrintworld will tap to the nearest electricity supply up to 2 meters in excess to this provision will be charged to client.\n10% weekly interest will be charged as penalty for late payment.\nAny intentional scratches or damages on the product will void the warranty.\n(5) years of Avery Sticker warranty\n(6) months of LED warranty.\n(1) year of faulty workmanship.";
    }
    $tncRows = '';
    foreach (explode("\n", $tnc) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $tncRows .= "<p>&#8226;" . htmlspecialchars($line) . "</p>";
    }

    $css = '
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: Arial, Helvetica, sans-serif; font-size:11px; color:#1a1a1a; background:#fff; }
      .page { padding:30px 38px 20px; }
      .hdr { width:100%; border-collapse:collapse; margin-bottom:4px; }
      .hdr td { vertical-align:middle; padding:0; }
      .badge { display:inline-block; background:#1a1a1a; color:#fff; font-size:24px; font-weight:900; letter-spacing:2px; padding:8px 20px; border-radius:3px; }
      .brand-name { font-size:22px; font-weight:900; letter-spacing:3px; color:#1a1a1a; text-transform:uppercase; line-height:1; text-align:right; }
      .brand-tag { font-size:8px; letter-spacing:2px; color:#666; text-transform:uppercase; margin-top:3px; text-align:right; }
      .gold { height:5px; background:linear-gradient(90deg,#8B6914,#D4A017,#F5D060,#D4A017,#8B6914); margin:10px 0 14px; }
      .info { font-size:11px; line-height:1.9; margin-bottom:14px; }
      .dear { font-size:11px; margin:12px 0 4px; }
      .intro { font-size:11px; line-height:1.6; margin-bottom:16px; color:#222; }
      table.items { width:100%; border-collapse:collapse; }
      table.items thead tr { background:#888888; }
      table.items thead th { color:#fff; padding:10px 8px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; text-align:center; }
      table.items thead th.desc { text-align:left; }
      table.items tbody tr:nth-child(even) { background:#f7f7f7; }
      .terms { margin-top:20px; padding-top:12px; border-top:1px solid #ccc; }
      .powered { font-size:10.5px; margin-bottom:8px; font-weight:700; }
      .tc-title { font-size:11px; font-weight:700; text-align:center; text-decoration:underline; text-transform:uppercase; margin-bottom:6px; }
      .tc-list { font-size:9.5px; line-height:1.75; color:#333; }
      .tc-list p { margin:0; padding-left:4px; }
      .conforme { text-align:right; font-size:11px; margin-top:10px; }
      .conf-line { display:inline-block; border-bottom:1px solid #222; width:150px; margin-left:6px; }
      .contact-note { font-size:10px; color:#444; margin-top:14px; }
      .sigs { width:55%; border-collapse:collapse; margin-top:14px; }
      .sigs td { vertical-align:bottom; padding:0 30px 0 0; font-size:11px; width:50%; }
      .sig-name { font-weight:700; border-top:1px solid #222; padding-top:3px; margin-top:30px; font-size:11px; }
      .footer-bar { background:#1a1a1a; color:#fff; padding:9px 16px; margin-top:22px; font-size:9px; }
      .footer-bar span { color:#f5c842; font-weight:700; }
      .footer-addr { text-align:center; margin-top:8px; font-size:9.5px; color:#444; line-height:1.8; }
    ';

    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>{$css}</style></head><body><div class='page'>
      <table class='hdr'><tr>
        <td><div class='badge'>QUOTATION</div></td>
        <td><div class='brand-name'>PRINTWORLD</div><div class='brand-tag'>THE PROFESSIONAL SIGNAGE MAKER</div></td>
      </tr></table>
      <div class='gold'></div>
      <div class='info'>{$infoBlock}</div>
      <p class='dear'>Dear {$dear},</p>
      <p class='intro'>Thank you for your interest in our services. Anyhow, we are glad to present to you our quotation for the following materials:</p>
      <table class='items'>
        <thead><tr>
          <th style='width:12%'>QUANTITY</th>
          <th class='desc' style='width:46%'>DESCRIPTION</th>
          <th style='width:20%'>UNIT PRICE</th>
          <th style='width:22%;text-align:right;padding-right:10px'>TOTAL</th>
        </tr></thead>
        <tbody>{$rows}</tbody>
      </table>
      {$totalBlock}
      <div class='terms'>
        <p class='powered'>Powered By <em style='font-style:italic;color:#c00'>Mimaki</em> &amp; <em style='font-style:italic;color:#00a'>GRAPHTEC</em></p>
        <p class='tc-title'>TERMS AND CONDITIONS</p>
        <div class='tc-list'>{$tncRows}</div>
        <div class='conforme'>Conforme: <span class='conf-line'>&nbsp;</span></div>
      </div>
      <p class='contact-note'>If you need any additional information, please feel free to contact the undersigned.</p>
      <table class='sigs'><tr>
        <td><div>Prepared By:</div><div class='sig-name'>{$prepBy}</div></td>
        <td><div>Checked By:</div><div class='sig-name'>{$chkBy}</div></td>
      </tr></table>
      <div class='footer-bar'>Please make cheque payable to <span>Printworld &amp; Advertising Services.</span></div>
      <div class='footer-addr'><strong>Printworld &amp; Advertising Services</strong><br>Roxas Ext., Digos City<br>0910 772 8888 &nbsp;&nbsp;(082) 272 4561</div>
    </div></body></html>";
}

// =============================================
// CLIENT REQUEST PDF (no prices)
// =============================================
function generateRequestPDF(array $req, array $items): string {
    $d = _dompdf();
    $d->loadHtml(buildRequestPDFHtml($req, $items));
    $d->render();
    $path = _saveDir() . 'Request_' . $req['request_number'] . '.pdf';
    file_put_contents($path, $d->output());
    return $path;
}

function buildRequestPDFHtml(array $req, array $items): string {
    $date     = date('F d, Y');
    $rnum     = htmlspecialchars($req['request_number']);
    $name     = htmlspecialchars($req['customer_name']);
    $company  = htmlspecialchars($req['company_name'] ?? '');
    $email    = htmlspecialchars($req['email']);
    $phone    = htmlspecialchars($req['contact_number']);
    $location = htmlspecialchars($req['location'] ?? '');
    $msg      = htmlspecialchars($req['message'] ?? '');
    $coLine   = $company  ? "<p style='margin:1px 0'><strong>Company:</strong> {$company}</p>"   : '';
    $locLine  = $location ? "<p style='margin:1px 0'><strong>Location:</strong> {$location}</p>" : '';
    $msgBlock = $msg      ? "<div style='margin-top:14px;padding:10px 12px;background:#f5f5f5;border-left:3px solid #888;font-size:11px'><strong>Message:</strong><br>{$msg}</div>" : '';

    $rows = '';
    foreach ($items as $i => $item) {
        $n       = $i + 1;
        $rawDesc = preg_replace('/\s*[\x{2014}\-]+\s*Design:\s*(Yes|No)\s*$/iu', '', $item['description'] ?? '');
        $desc    = nl2br(htmlspecialchars($rawDesc));
        $qty     = (int)($item['quantity'] ?? 1);
        $bg      = ($i % 2 === 0) ? '#fff' : '#f7f7f7';
        $rows   .= "<tr style='background:{$bg}'>
          <td style='text-align:center;padding:10px 6px;border-bottom:1px solid #ddd'>{$n}</td>
          <td style='padding:10px 8px;border-bottom:1px solid #ddd;font-weight:600'>{$desc}</td>
          <td style='text-align:center;padding:10px 6px;border-bottom:1px solid #ddd'>{$qty}</td>
        </tr>";
    }

    $css = '* {margin:0;padding:0;box-sizing:border-box;} body{font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#1a1a1a;} .page{padding:30px 38px 20px;} .hdr{width:100%;border-collapse:collapse;margin-bottom:4px;} .hdr td{vertical-align:middle;padding:0;} .badge{display:inline-block;background:#1a1a1a;color:#fff;font-size:16px;font-weight:900;letter-spacing:1px;padding:7px 16px;border-radius:3px;} .gold{height:5px;background:linear-gradient(90deg,#8B6914,#D4A017,#F5D060,#D4A017,#8B6914);margin:10px 0 14px;} table.items{width:100%;border-collapse:collapse;} table.items thead tr{background:#888;} table.items thead th{color:#fff;padding:9px 8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:center;} table.items thead th.desc{text-align:left;} .footer-bar{background:#1a1a1a;color:#fff;padding:9px 16px;margin-top:22px;font-size:9px;} .footer-bar span{color:#f5c842;font-weight:700;} .footer-addr{text-align:center;margin-top:8px;font-size:9.5px;color:#444;line-height:1.8;}';

    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>{$css}</style></head><body><div class='page'>
      <table class='hdr'><tr>
        <td><div class='badge'>QUOTATION REQUEST</div></td>
        <td style='text-align:right'><div style='font-size:20px;font-weight:900;letter-spacing:3px;color:#1a1a1a;text-transform:uppercase'>PRINTWORLD</div><div style='font-size:8px;letter-spacing:2px;color:#666;text-transform:uppercase;margin-top:2px'>THE PROFESSIONAL SIGNAGE MAKER</div></td>
      </tr></table>
      <div class='gold'></div>
      <div style='font-size:11px;line-height:1.9;margin-bottom:14px'>
        <p style='margin:1px 0'><strong>Name:</strong> {$name}</p>
        {$coLine}
        <p style='margin:1px 0'><strong>Contact:</strong> {$phone}</p>
        <p style='margin:1px 0'><strong>Email:</strong> {$email}</p>
        {$locLine}
        <p style='margin:1px 0'><strong>Request No.:</strong> {$rnum}</p>
        <p style='margin:1px 0'><strong>Date:</strong> {$date}</p>
      </div>
      <p style='font-size:11px;margin-bottom:14px'>Thank you for your interest. We have received your quotation request for the following items:</p>
      <table class='items'>
        <thead><tr><th style='width:8%'>#</th><th class='desc' style='width:72%'>DESCRIPTION</th><th style='width:20%'>QUANTITY</th></tr></thead>
        <tbody>{$rows}</tbody>
      </table>
      {$msgBlock}
      <p style='font-size:10px;margin-top:18px;color:#555'>Our team will review your request and send you a detailed quotation shortly.<br>For inquiries: <strong>09107728888</strong> | <strong>digosprinting@gmail.com</strong></p>
      <div class='footer-bar'>Please make cheque payable to <span>Printworld &amp; Advertising Services.</span></div>
      <div class='footer-addr'><strong>Printworld &amp; Advertising Services</strong><br>Roxas Ext., Digos City<br>0910 772 8888 &nbsp;&nbsp;(082) 272 4561</div>
    </div></body></html>";
}