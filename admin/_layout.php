<?php
function adminSidebar(string $active = ''): void {
    $db = db();

    // Auto-lock GCash when navigating away — only if enabled in settings
    if ($active !== 'gcash') {
        $r        = $db->query("SELECT `value` FROM site_settings WHERE `key`='gcash_autolock_enabled' LIMIT 1");
        $autolock = ($r && ($row = $r->fetch_assoc())) ? $row['value'] : '1';
        if ($autolock === '1') {
            unset($_SESSION['gcash_unlocked']);
        }
    }
    $siteName = SITE_NAME;
    $nav = [
        'dashboard'         => ['icon' => 'fa-gauge',              'label' => 'Dashboard'],
        'quotations'        => ['icon' => 'fa-file-invoice',       'label' => 'Quotation Requests', 'notify' => true],
        'final_quotations'  => ['icon' => 'fa-file-circle-check',  'label' => 'Final Quotations'],
        'manual_quotation'  => ['icon' => 'fa-pen-to-square',      'label' => 'Manual Quotation'],
        'premium_clients'   => ['icon' => 'fa-star',               'label' => 'Premium Clients'],
        'prices'            => ['icon' => 'fa-tag',                'label' => 'Prices'],
        'services'          => ['icon' => 'fa-print',              'label' => 'Services'],
        'color_codes'       => ['icon' => 'fa-palette',            'label' => 'Color Sticker Codes'],
        'gallery'           => ['icon' => 'fa-images',             'label' => 'Gallery'],
        'content'           => ['icon' => 'fa-pen-to-square',      'label' => 'Site Content'],
        'notes'             => ['icon' => 'fa-sticky-note',         'label' => 'Notes'],
        'gcash'             => ['icon' => 'fa-mobile-screen-button', 'label' => 'GCash Transactions'],
        'settings'          => ['icon' => 'fa-gear',                 'label' => 'Settings'],
    ];

    // Get pending count for initial render
    $pendingCount = (int)$db->query("SELECT COUNT(*) AS c FROM quotation_requests WHERE status='pending'")->fetch_assoc()['c'];

    echo '<aside class="admin-sidebar" id="admin-sidebar">';
    echo '<div class="admin-logo">';
    echo '<div style="display:flex;align-items:center;gap:10px;">';
    echo '<img src="../assets/pw.png" alt="Printworld" style="height:32px;width:32px;object-fit:contain;border-radius:4px;">';
    echo '<div><h2 style="margin:0">' . $siteName . '</h2><p style="margin:0">Admin Panel</p></div>';
    echo '</div>';
    echo '</div>';
    echo '<nav class="admin-nav">';
    foreach ($nav as $page => $info) {
        $cls = $active === $page ? ' active' : '';
        $notifyHtml = '';
        if (!empty($info['notify'])) {
            $display = $pendingCount > 0 ? '' : ' style="display:none"';
            $notifyHtml = '<span class="nav-notif-dot" id="nav-notif-dot"' . $display . '>'
                        . ($pendingCount > 0 ? $pendingCount : '') . '</span>';
        }
        echo '<a href="' . $page . '.php" class="' . trim($cls) . '" onclick="closeSidebar()">'
           . '<i class="fas ' . $info['icon'] . '"></i>'
           . '<span class="nav-label">' . $info['label'] . '</span>'
           . $notifyHtml
           . '</a>';
    }
    echo '</nav>';
    echo '<div style="padding:16px 0;border-top:1px solid rgba(255,255,255,0.08);margin-top:auto;">';
    echo '<a href="../index.php" target="_blank" style="display:flex;align-items:center;gap:12px;padding:10px 24px;color:rgba(255,255,255,0.4);font-size:0.82rem;"><i class="fas fa-external-link-alt"></i> View Site</a>';
    echo '<a href="logout.php" style="display:flex;align-items:center;gap:12px;padding:10px 24px;color:rgba(255,255,255,0.4);font-size:0.82rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>';
    echo '</div>';
    echo '</aside>';

    // Overlay for mobile
    echo '<div class="admin-sidebar-overlay" id="admin-sidebar-overlay" onclick="closeSidebar()"></div>';

    // Hamburger toggle button
    echo '<button class="admin-mobile-toggle" id="admin-mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>';

    // Poll for new requests every 30s + sidebar JS
    echo '<script>
(function(){
  function checkNotif(){
    fetch("ajax_notifications.php")
      .then(function(r){ return r.json(); })
      .then(function(d){
        var dot = document.getElementById("nav-notif-dot");
        if(!dot) return;
        if(d.pending > 0){
          dot.textContent = d.pending;
          dot.style.display = "";
        } else {
          dot.style.display = "none";
        }
      }).catch(function(){});
  }
  setInterval(checkNotif, 30000);
})();

function toggleSidebar() {
  var sidebar  = document.getElementById("admin-sidebar");
  var overlay  = document.getElementById("admin-sidebar-overlay");
  var isOpen   = sidebar.classList.contains("open");
  if (isOpen) {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
  } else {
    sidebar.classList.add("open");
    overlay.classList.add("show");
  }
}

function closeSidebar() {
  document.getElementById("admin-sidebar").classList.remove("open");
  document.getElementById("admin-sidebar-overlay").classList.remove("show");
}
</script>';
}
