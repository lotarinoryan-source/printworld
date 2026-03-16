<?php
function adminSidebar(string $active = ''): void {
    $siteName = SITE_NAME;
    $nav = [
        'dashboard'       => ['icon' => 'fa-gauge',         'label' => 'Dashboard'],
        'quotations'      => ['icon' => 'fa-file-invoice',  'label' => 'Quotation Requests'],
        'final_quotations'  => ['icon' => 'fa-file-circle-check', 'label' => 'Final Quotations'],
        'manual_quotation'  => ['icon' => 'fa-pen-to-square',    'label' => 'Manual Quotation'],
        'premium_clients'   => ['icon' => 'fa-star',             'label' => 'Premium Clients'],
        'prices'          => ['icon' => 'fa-tag',           'label' => 'Prices'],
        'services'        => ['icon' => 'fa-print',         'label' => 'Services'],
        'gallery'         => ['icon' => 'fa-images',        'label' => 'Gallery'],
        'content'         => ['icon' => 'fa-pen-to-square', 'label' => 'Site Content'],
    ];
    echo '<aside class="admin-sidebar" id="admin-sidebar">';
    echo '<div class="admin-logo"><h2>' . $siteName . '</h2><p>Admin Panel</p></div>';
    echo '<nav class="admin-nav">';
    foreach ($nav as $page => $info) {
        $cls = $active === $page ? ' active' : '';
        echo '<a href="' . $page . '.php" class="' . trim($cls) . '"><i class="fas ' . $info['icon'] . '"></i>' . $info['label'] . '</a>';
    }
    echo '</nav>';
    echo '<div style="padding:16px 0;border-top:1px solid rgba(255,255,255,0.08);margin-top:auto;">';
    echo '<a href="../index.php" target="_blank" style="display:flex;align-items:center;gap:12px;padding:10px 24px;color:rgba(255,255,255,0.4);font-size:0.82rem;"><i class="fas fa-external-link-alt"></i> View Site</a>';
    echo '<a href="logout.php" style="display:flex;align-items:center;gap:12px;padding:10px 24px;color:rgba(255,255,255,0.4);font-size:0.82rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>';
    echo '</div>';
    echo '</aside>';
    // Mobile toggle button
    echo '<button class="admin-mobile-toggle" onclick="document.getElementById(\'admin-sidebar\').classList.toggle(\'open\')" style="display:none;position:fixed;top:16px;left:16px;z-index:200;background:#111;color:#fff;border:none;padding:10px 14px;cursor:pointer;"><i class="fas fa-bars"></i></button>';
}
