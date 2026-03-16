<?php
require_once 'config.php';
require_once 'includes/functions.php';

$heroTitle    = getSiteContent('hero_title', 'Premium Printing Solutions');
$heroSubtitle = getSiteContent('hero_subtitle', 'Quality prints that make your brand stand out.');
$aboutTitle   = getSiteContent('about_title', 'About Printworld');
$aboutText    = getSiteContent('about_text', '');
$address      = getSiteContent('contact_address', 'Digos City, Davao del Sur');
$phone        = getSiteContent('contact_phone', CONTACT_PHONE);
$email        = getSiteContent('contact_email', CONTACT_EMAIL);
$hours        = getSiteContent('contact_hours', 'Mon-Sat: 8AM - 6PM');
$fbUrl        = getSiteContent('facebook_url', CONTACT_FACEBOOK);
$gallery      = getGalleryImages(8);
$services     = getActiveServices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= SITE_NAME ?> — Premium Printing Solutions</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="nav-logo"><?= SITE_NAME ?><span>.</span></a>
    <ul class="nav-links">
      <li><a href="#services">Services</a></li>
      <li><a href="#gallery">Gallery</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#contact">Contact</a></li>
      <li><a href="quotation.php" class="nav-cta">Request Quote</a></li>
    </ul>
    <div class="nav-toggle" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero" id="home">
  <div class="hero-bg"></div>
  <div class="hero-pattern"></div>
  <div class="hero-content">
    <div class="hero-badge">Professional Printing Services</div>
    <h1><?= htmlspecialchars($heroTitle) ?></h1>
    <p><?= htmlspecialchars($heroSubtitle) ?></p>
    <div class="hero-actions">
      <a href="quotation.php" class="btn btn-primary"><i class="fas fa-file-invoice"></i> Request a Quotation</a>
      <a href="#services" class="btn btn-outline"><i class="fas fa-th-large"></i> Our Services</a>
    </div>
  </div>
  <div class="hero-scroll">Scroll</div>
</section>

<!-- SERVICES -->
<section class="section services" id="services">
  <div class="container">
    <div class="section-title">
      <h2>Our Services</h2>
      <p>From custom souvenirs to large-format signage, we deliver quality on every print.</p>
      <div class="line"></div>
    </div>

    <?php
    // Group services by category
    $groups = [
      'basic'       => ['label' => 'Basic Services',       'icon' => 'fa-box-open',   'items' => []],
      'sublimation' => ['label' => 'Sublimation Services', 'icon' => 'fa-tshirt',     'items' => []],
      'signage'     => ['label' => 'Signage Services',     'icon' => 'fa-sign-hanging','items' => []],
    ];
    foreach ($services as $svc) {
      $cat = $svc['category'] ?? 'basic';
      if (!isset($groups[$cat])) $cat = 'basic';
      $groups[$cat]['items'][] = $svc;
    }
    $iconMap = [
      'fa-key','fa-mug-hot','fa-heart','fa-magnet','fa-image','fa-tshirt',
      'fa-sign-hanging','fa-cake-candles','fa-print','fa-star','fa-box-open',
    ];
    ?>

    <div class="services-groups">
      <?php foreach ($groups as $gkey => $group): ?>
      <div class="service-group-card">
        <div class="service-group-header">
          <div class="service-group-icon"><i class="fas <?= $group['icon'] ?>"></i></div>
          <h3><?= $group['label'] ?></h3>
        </div>
        <ul class="service-group-list">
          <?php if (empty($group['items'])): ?>
          <li style="color:#aaa;font-style:italic">No services yet.</li>
          <?php else: foreach ($group['items'] as $svc): ?>
          <li>
            <a href="service.php?slug=<?= urlencode($svc['slug']) ?>" style="display:flex;align-items:center;width:100%;color:inherit;text-decoration:none" onmouseover="this.style.color='#000';this.style.fontWeight='700'" onmouseout="this.style.color='inherit';this.style.fontWeight='normal'">
              <i class="fas <?= htmlspecialchars($svc['icon'] ?? 'fa-print') ?>" style="margin-right:10px;color:#555;width:16px;text-align:center"></i>
              <?= htmlspecialchars($svc['name']) ?>
              <i class="fas fa-chevron-right" style="margin-left:auto;font-size:0.65rem;color:#ccc"></i>
            </a>
          </li>
          <?php endforeach; endif; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:48px">
      <a href="quotation.php" class="btn btn-dark">Request a Quotation <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- GALLERY -->
<?php if (!empty($gallery)): ?>
<section class="section" id="gallery" style="background:var(--white)">
  <div class="container">
    <div class="section-title">
      <h2>Our Work</h2>
      <p>A glimpse of the quality we deliver to our clients.</p>
      <div class="line"></div>
    </div>
    <div class="gallery-grid">
      <?php foreach ($gallery as $img): ?>
      <div class="gallery-item">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['title'] ?? 'Gallery') ?>" loading="lazy">
        <div class="gallery-overlay"><span><?= htmlspecialchars($img['title'] ?? 'View') ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ABOUT -->
<section class="section about" id="about">
  <div class="container">
    <div class="about-grid">
      <div class="about-text">
        <h2><?= htmlspecialchars($aboutTitle) ?></h2>
        <p><?= nl2br(htmlspecialchars($aboutText)) ?></p>
        <a href="quotation.php" class="btn btn-primary" style="margin-top:24px">Get a Quote</a>
        <div class="about-stats">
          <div class="stat-item"><div class="num">500+</div><div class="label">Happy Clients</div></div>
          <div class="stat-item"><div class="num">10+</div><div class="label">Years Experience</div></div>
          <div class="stat-item"><div class="num">1000+</div><div class="label">Projects Done</div></div>
          <div class="stat-item"><div class="num">9</div><div class="label">Services Offered</div></div>
        </div>
      </div>
      <div class="about-image">
        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80" alt="Printworld Shop">
      </div>
    </div>
  </div>
</section>

<!-- CONTACT -->
<section class="section" id="contact">
  <div class="container">
    <div class="section-title">
      <h2>Get In Touch</h2>
      <p>Have questions? We'd love to hear from you.</p>
      <div class="line"></div>
    </div>
    <div class="contact-grid">
      <div class="contact-info">
        <h3>Contact Information</h3>
        <div class="contact-item">
          <div class="contact-item-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div class="contact-item-text"><h4>Address</h4><p><?= htmlspecialchars($address) ?></p></div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon"><i class="fas fa-phone"></i></div>
          <div class="contact-item-text"><h4>Phone</h4><p><a href="tel:<?= htmlspecialchars($phone) ?>"><?= htmlspecialchars($phone) ?></a></p></div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon"><i class="fas fa-envelope"></i></div>
          <div class="contact-item-text"><h4>Email</h4><p><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></p></div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon"><i class="fab fa-facebook-f"></i></div>
          <div class="contact-item-text"><h4>Facebook</h4><p><a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" rel="noopener">Digos Tarpaulin Printing</a></p></div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon"><i class="fas fa-clock"></i></div>
          <div class="contact-item-text"><h4>Hours</h4><p><?= htmlspecialchars($hours) ?></p></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap">
          <a href="quotation.php" class="btn btn-dark">Request Quotation <i class="fas fa-arrow-right"></i></a>
          <a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline" style="color:var(--black);border-color:var(--gray-200)">
            <i class="fab fa-facebook-f"></i> Facebook Page
          </a>
        </div>
      </div>
      <div class="contact-map">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31574.5!2d125.3573!3d6.7497!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f8c4e1b1b1b1b1%3A0x0!2sDigos+City%2C+Davao+del+Sur!5e0!3m2!1sen!2sph!4v1"
          allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="footer-logo"><?= SITE_NAME ?></div>
    <p style="margin-bottom:12px">Premium Printing Solutions · Digos City, Davao del Sur</p>
    <div class="footer-social">
      <a href="tel:<?= CONTACT_PHONE ?>" title="Call us"><i class="fas fa-phone"></i></a>
      <a href="mailto:<?= CONTACT_EMAIL ?>" title="Email us"><i class="fas fa-envelope"></i></a>
      <a href="<?= CONTACT_FACEBOOK ?>" target="_blank" rel="noopener" title="Facebook Page"><i class="fab fa-facebook-f"></i></a>
    </div>
    <p style="margin-top:16px;font-size:0.78rem">© <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
    <p style="margin-top:10px">
      <a href="#" onclick="document.getElementById('admin-login-modal').classList.add('open');return false;" style="font-size:0.7rem;color:rgba(255,255,255,0.2);text-decoration:none;letter-spacing:0.5px" onmouseover="this.style.color='rgba(255,255,255,0.5)'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
        <i class="fas fa-lock" style="font-size:0.65rem;margin-right:4px"></i>Admin
      </a>
    </p>
  </div>
</footer>

<!-- Admin Login Modal -->
<div id="admin-login-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:10px;width:100%;max-width:380px;padding:36px 32px;box-shadow:0 12px 48px rgba(0,0,0,0.25);position:relative">
    <button onclick="closeAdminModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.3rem;cursor:pointer;color:#aaa;line-height:1">&times;</button>
    <div style="text-align:center;margin-bottom:24px">
      <div style="background:#111;color:#fff;display:inline-block;padding:8px 18px;border-radius:4px;font-weight:900;letter-spacing:2px;font-size:1rem;margin-bottom:8px"><?= SITE_NAME ?></div>
      <p style="font-size:0.75rem;letter-spacing:2px;color:#888;text-transform:uppercase">Admin Panel</p>
    </div>
    <div id="admin-modal-error" style="display:none;background:#fee;border:1px solid #fcc;color:#c00;padding:10px 14px;border-radius:6px;font-size:0.85rem;margin-bottom:16px"></div>
    <form id="admin-login-form">
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:6px;color:#333">Username</label>
        <input type="text" id="modal-username" name="username" placeholder="admin"
          style="width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:6px;font-size:0.95rem;outline:none;box-sizing:border-box"
          onfocus="this.style.borderColor='#111'" onblur="this.style.borderColor='#ddd'" required>
      </div>
      <div style="margin-bottom:20px">
        <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:6px;color:#333">Password</label>
        <input type="password" id="modal-password" name="password" placeholder="••••••••"
          style="width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:6px;font-size:0.95rem;outline:none;box-sizing:border-box"
          onfocus="this.style.borderColor='#111'" onblur="this.style.borderColor='#ddd'" required>
      </div>
      <button type="submit" id="admin-login-btn"
        style="width:100%;background:#111;color:#fff;border:none;padding:12px;border-radius:6px;font-size:0.95rem;font-weight:700;cursor:pointer;letter-spacing:1px">
        SIGN IN
      </button>
    </form>
  </div>
</div>

<script src="assets/js/main.js"></script>
<script>
var adminModal = document.getElementById('admin-login-modal');

function closeAdminModal() {
  adminModal.style.display = 'none';
  document.getElementById('admin-modal-error').style.display = 'none';
  document.getElementById('modal-username').value = '';
  document.getElementById('modal-password').value = '';
}

adminModal.addEventListener('click', function(e) {
  if (e.target === this) closeAdminModal();
});

// Show modal with flex when opened
var origAdd = DOMTokenList.prototype.add;
document.getElementById('admin-login-modal').classList.add = function(cls) {
  if (cls === 'open') this.el.style.display = 'flex';
  else origAdd.call(this, cls);
};

document.querySelector('[onclick*="admin-login-modal"]').addEventListener('click', function(e) {
  e.preventDefault();
  adminModal.style.display = 'flex';
});

document.getElementById('admin-login-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var btn = document.getElementById('admin-login-btn');
  var errBox = document.getElementById('admin-modal-error');
  btn.textContent = 'Signing in...';
  btn.disabled = true;
  errBox.style.display = 'none';

  var fd = new FormData();
  fd.append('username', document.getElementById('modal-username').value);
  fd.append('password', document.getElementById('modal-password').value);

  fetch('admin/ajax_login.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        window.location.href = 'admin/dashboard.php';
      } else {
        errBox.textContent = data.message || 'Invalid username or password.';
        errBox.style.display = 'block';
        btn.textContent = 'SIGN IN';
        btn.disabled = false;
      }
    })
    .catch(function() {
      errBox.textContent = 'Connection error. Please try again.';
      errBox.style.display = 'block';
      btn.textContent = 'SIGN IN';
      btn.disabled = false;
    });
});
</script>
</body>
</html>
