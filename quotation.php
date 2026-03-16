<?php
require_once 'config.php';
require_once 'includes/functions.php';
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Request a Quotation &mdash; <?= SITE_NAME ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="nav-logo"><?= SITE_NAME ?><span>.</span></a>
    <ul class="nav-links">
      <li><a href="index.php#services">Services</a></li>
      <li><a href="index.php#gallery">Gallery</a></li>
      <li><a href="index.php#about">About</a></li>
      <li><a href="index.php#contact">Contact</a></li>
      <li><a href="quotation.php" class="nav-cta">Request Quote</a></li>
    </ul>
    <div class="nav-toggle"><span></span><span></span><span></span></div>
  </div>
</nav>

<div class="quotation-page">
  <div class="quotation-header">
    <h1>Request a Quotation</h1>
    <p>Select the services you need and we&rsquo;ll send you a detailed quote.</p>
  </div>
  <div class="quotation-body">
    <div class="container">
      <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:24px">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form id="quotation-form" method="POST" action="process_quotation.php" enctype="multipart/form-data">
        <input type="hidden" name="items_data" id="items-data" value="[]">
        <div class="quotation-layout">

          <!-- LEFT COLUMN -->
          <div>
            <!-- CLIENT INFO -->
            <div class="quotation-form-section" style="margin-bottom:24px">
              <div class="form-section-header"><h3>Your Information</h3></div>
              <div class="form-section-body">
                <div class="form-row">
                  <div class="form-group">
                    <label>Full Name / Company Name *</label>
                    <input type="text" id="cust-name" name="customer_name" class="form-control" placeholder="Your name or company" required>
                  </div>
                  <div class="form-group">
                    <label>Company Name <span style="font-weight:400;color:var(--gray-400)">(optional)</span></label>
                    <input type="text" name="company_name" class="form-control" placeholder="If applicable">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" id="cust-email" name="email" class="form-control" placeholder="your@email.com" required>
                  </div>
                  <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="tel" id="cust-phone" name="contact_number" class="form-control" placeholder="09XXXXXXXXX" required>
                  </div>
                </div>
                <div class="form-group">
                  <label>Message / Additional Notes</label>
                  <textarea name="message" class="form-control" rows="3" placeholder="Any special instructions, questions, or details about your order..."></textarea>
                </div>
              </div>
            </div>

            <!-- SERVICE SELECTION -->
            <div class="quotation-form-section" style="margin-bottom:24px">
              <div class="form-section-header"><h3>Select Services</h3></div>
              <div class="service-tabs">
                <div class="service-tab active" data-panel="printing"><i class="fas fa-print" style="margin-right:6px"></i>Printing</div>
                <div class="service-tab" data-panel="sublimation"><i class="fas fa-tshirt" style="margin-right:6px"></i>Sublimation</div>
                <div class="service-tab" data-panel="signage"><i class="fas fa-sign-hanging" style="margin-right:6px"></i>Signage</div>
              </div>

              <!-- PRINTING -->
              <div class="service-panel active" id="panel-printing">
                <div class="form-group">
                  <label>Service Type</label>
                  <select id="print-type" class="form-control">
                    <option value="tarpaulin">Tarpaulin Printing</option>
                    <option value="mug">Mug Printing</option>
                    <option value="keychain">Keychain</option>
                    <option value="keyholder">Keyholder</option>
                    <option value="ref_magnet">Ref Magnet</option>
                    <option value="wedding_souvenir">Wedding Souvenir</option>
                    <option value="birthday_souvenir">Birthday Souvenir</option>
                  </select>
                </div>
                <div id="tarp-fields">
                  <div class="form-row">
                    <div class="form-group">
                      <label>Width (ft)</label>
                      <input type="number" id="tarp-width" class="form-control" placeholder="e.g. 4" min="0.1" step="0.1">
                    </div>
                    <div class="form-group">
                      <label>Height (ft)</label>
                      <input type="number" id="tarp-height" class="form-control" placeholder="e.g. 8" min="0.1" step="0.1">
                    </div>
                  </div>
                </div>
                <div id="qty-fields" style="display:none">
                  <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" id="print-qty" class="form-control" placeholder="e.g. 10" min="1">
                  </div>
                </div>
                <div class="form-group">
                  <label>Design Option</label>
                  <select id="print-design" class="form-control">
                    <option value="With Design">With Design (we design it)</option>
                    <option value="Already Have Design">Already Have Design</option>
                  </select>
                </div>
                <div id="print-error" class="form-error" style="display:none"></div>
                <button type="button" id="add-printing" class="add-service-btn"><i class="fas fa-plus"></i> Add to Request</button>
              </div>

              <!-- SUBLIMATION -->
              <div class="service-panel" id="panel-sublimation">
                <div class="form-group">
                  <label>Item Type</label>
                  <select id="sub-type" class="form-control">
                    <option value="tshirt">T-Shirt</option>
                    <option value="polo">Polo Shirt</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Quantity</label>
                  <input type="number" id="sub-qty" class="form-control" placeholder="e.g. 20" min="1">
                </div>
                <div class="form-group">
                  <label>Design Option</label>
                  <select id="sub-design" class="form-control">
                    <option value="With Design">With Design (we design it)</option>
                    <option value="Already Have Design">Already Have Design</option>
                  </select>
                </div>
                <div id="sub-error" class="form-error" style="display:none"></div>
                <button type="button" id="add-sublimation" class="add-service-btn"><i class="fas fa-plus"></i> Add to Request</button>
              </div>

              <!-- SIGNAGE -->
              <div class="service-panel" id="panel-signage">
                <div class="steps-indicator">
                  <div class="step-dot active" id="dot-1"><div class="dot">1</div><div class="step-label">Type</div></div>
                  <div class="step-dot" id="dot-2"><div class="dot">2</div><div class="step-label">Light</div></div>
                  <div class="step-dot" id="dot-3"><div class="dot">3</div><div class="step-label">Size</div></div>
                  <div class="step-dot" id="dot-4"><div class="dot">4</div><div class="step-label">Location</div></div>
                  <div class="step-dot" id="dot-5"><div class="dot">5</div><div class="step-label">Design</div></div>
                </div>
                <div class="step-content active" id="signage-step-1">
                  <div class="form-group">
                    <label>Signage Type</label>
                    <select id="sign-type" class="form-control">
                      <option value="Double Face">Double Face</option>
                      <option value="Single Face">Single Face</option>
                      <option value="Single Frame">Single Frame</option>
                      <option value="Double Face Frame">Double Face Frame</option>
                      <option value="Special Design">Special Design</option>
                    </select>
                  </div>
                  <div class="step-nav">
                    <button type="button" class="btn btn-dark step-next">Next <i class="fas fa-arrow-right"></i></button>
                  </div>
                </div>
                <div class="step-content" id="signage-step-2">
                  <div class="form-group">
                    <label>Light Type</label>
                    <select id="sign-light" class="form-control">
                      <option value="Lighted">Lighted</option>
                      <option value="Non-lighted">Non-lighted</option>
                    </select>
                  </div>
                  <div class="step-nav">
                    <button type="button" class="btn btn-outline step-prev" style="color:var(--black);border-color:var(--gray-200)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button type="button" class="btn btn-dark step-next">Next <i class="fas fa-arrow-right"></i></button>
                  </div>
                </div>
                <div class="step-content" id="signage-step-3">
                  <div class="form-row">
                    <div class="form-group"><label>Width (ft)</label><input type="number" id="sign-width" class="form-control" placeholder="e.g. 4" min="0.1" step="0.1"></div>
                    <div class="form-group"><label>Height (ft)</label><input type="number" id="sign-height" class="form-control" placeholder="e.g. 8" min="0.1" step="0.1"></div>
                  </div>
                  <div class="step-nav">
                    <button type="button" class="btn btn-outline step-prev" style="color:var(--black);border-color:var(--gray-200)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button type="button" class="btn btn-dark step-next">Next <i class="fas fa-arrow-right"></i></button>
                  </div>
                </div>
                <div class="step-content" id="signage-step-4">
                  <div id="signage-map"></div>
                  <p class="map-coords" id="map-coords-display">Click the map to pin your location (optional)</p>
                  <input type="hidden" id="sign-lat" name="sign_lat">
                  <input type="hidden" id="sign-lng" name="sign_lng">
                  <input type="hidden" id="sign-address" name="sign_address">
                  <div class="step-nav">
                    <button type="button" class="btn btn-outline step-prev" style="color:var(--black);border-color:var(--gray-200)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button type="button" class="btn btn-dark step-next">Next <i class="fas fa-arrow-right"></i></button>
                  </div>
                </div>
                <div class="step-content" id="signage-step-5">
                  <div class="form-group">
                    <label>Upload Design <span style="font-weight:400;color:var(--gray-400)">(optional)</span></label>
                    <input type="file" name="design_file" class="form-control" accept="image/*,.pdf">
                  </div>
                  <div id="sign-error" class="form-error" style="display:none"></div>
                  <div class="step-nav">
                    <button type="button" class="btn btn-outline step-prev" style="color:var(--black);border-color:var(--gray-200)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button type="button" id="add-signage" class="btn btn-dark"><i class="fas fa-plus"></i> Add to Request</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- SUBMIT -->
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:18px;margin-top:8px">
              <i class="fas fa-paper-plane"></i>&nbsp; Submit Quotation Request
            </button>
            <p style="text-align:center;font-size:0.8rem;color:var(--gray-400);margin-top:10px">
              No prices shown &mdash; our team will review and send you a detailed quotation.
            </p>
          </div>

          <!-- RIGHT: SUMMARY -->
          <div class="order-summary">
            <div class="summary-header"><h3>Your Request</h3></div>
            <div class="summary-body">
              <div class="summary-items" id="summary-items">
                <div class="summary-empty">No services added yet.</div>
              </div>
              <p style="font-size:0.75rem;color:var(--gray-400);margin-top:16px;text-align:center;border-top:1px solid var(--gray-200);padding-top:12px">
                <i class="fas fa-info-circle"></i> Prices will be provided by our team after review.
              </p>
            </div>
          </div>

        </div><!-- end quotation-layout -->
      </form>
    </div>
  </div>
</div>

<footer>
  <div class="container">
    <div class="footer-logo"><?= SITE_NAME ?></div>
    <p style="margin-bottom:12px">Premium Printing Solutions &middot; Digos City, Davao del Sur</p>
    <div class="footer-social">
      <a href="tel:<?= CONTACT_PHONE ?>"><i class="fas fa-phone"></i></a>
      <a href="mailto:<?= CONTACT_EMAIL ?>"><i class="fas fa-envelope"></i></a>
      <a href="<?= CONTACT_FACEBOOK ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
    </div>
    <p style="margin-top:16px;font-size:0.78rem">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
  </div>
</footer>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap" async defer></script>
<script src="assets/js/maps.js"></script>
<script src="assets/js/quotation.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>