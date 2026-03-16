// ===== PRINTWORLD QUOTATION REQUEST (no prices shown) =====
const items = [];

// ===== SERVICE TABS =====
document.querySelectorAll('.service-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.service-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.service-panel').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('panel-' + tab.dataset.panel)?.classList.add('active');
  });
});

// ===== PRINTING TYPE TOGGLE =====
const printType  = document.getElementById('print-type');
const tarpFields = document.getElementById('tarp-fields');
const qtyFields  = document.getElementById('qty-fields');

function togglePrintFields() {
  const isTarp = printType?.value === 'tarpaulin';
  if (tarpFields) tarpFields.style.display = isTarp ? 'block' : 'none';
  if (qtyFields)  qtyFields.style.display  = isTarp ? 'none'  : 'block';
}
printType?.addEventListener('change', togglePrintFields);
togglePrintFields();

// ===== ADD PRINTING =====
document.getElementById('add-printing')?.addEventListener('click', () => {
  const type      = printType?.value || '';
  const design    = document.getElementById('print-design')?.value || 'With Design';
  const typeLabel = printType?.options[printType.selectedIndex]?.text || type;

  if (type === 'tarpaulin') {
    const w = parseFloat(document.getElementById('tarp-width')?.value) || 0;
    const h = parseFloat(document.getElementById('tarp-height')?.value) || 0;
    if (!w || !h) return showError('print-error', 'Please enter width and height.');
    addItem({ service_type: 'Tarpaulin Printing', item_name: 'Tarpaulin Printing', description: `Tarpaulin Printing - ${w}ft x ${h}ft (${design})`, quantity: 1, details: { width: w, height: h, design } });
  } else {
    const qty = parseInt(document.getElementById('print-qty')?.value) || 0;
    if (qty < 1) return showError('print-error', 'Please enter a valid quantity.');
    addItem({ service_type: typeLabel, item_name: typeLabel, description: `${typeLabel} - ${qty} pcs (${design})`, quantity: qty, details: { design } });
  }
  clearError('print-error');
  ['tarp-width', 'tarp-height', 'print-qty'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
});

// ===== ADD SUBLIMATION =====
document.getElementById('add-sublimation')?.addEventListener('click', () => {
  const subType = document.getElementById('sub-type');
  const qty     = parseInt(document.getElementById('sub-qty')?.value) || 0;
  const design  = document.getElementById('sub-design')?.value || 'With Design';
  const label   = subType?.options[subType.selectedIndex]?.text || subType?.value || '';
  if (qty < 1) return showError('sub-error', 'Please enter a valid quantity.');
  addItem({ service_type: 'Sublimation Printing', item_name: label, description: `${label} Sublimation - ${qty} pcs (${design})`, quantity: qty, details: { shirt_type: subType?.value, design } });
  clearError('sub-error');
  const sq = document.getElementById('sub-qty'); if (sq) sq.value = '';
});

// ===== SIGNAGE: LOCK LIGHT FOR FRAME TYPES =====
const FRAME_TYPES = ['Single Frame', 'Double Face Frame'];

function updateLightLock() {
  const signType = document.getElementById('sign-type');
  const lightSel = document.getElementById('sign-light');
  if (!signType || !lightSel) return;

  if (FRAME_TYPES.includes(signType.value)) {
    lightSel.value    = 'Non-lighted';
    lightSel.disabled = true;
    lightSel.title    = 'Frame types are Non-lighted only';
    lightSel.style.cssText = 'opacity:0.5;cursor:not-allowed;background:#f0f0f0';
  } else {
    lightSel.disabled = false;
    lightSel.title    = '';
    lightSel.style.cssText = '';
  }
}

document.getElementById('sign-type')?.addEventListener('change', updateLightLock);

// ===== SIGNAGE STEPS =====
let signageStep = 1;

function goToStep(step) {
  document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.step-dot').forEach((d, i) => {
    d.classList.remove('active', 'done');
    if (i + 1 < step) d.classList.add('done');
    if (i + 1 === step) d.classList.add('active');
  });
  document.getElementById('signage-step-' + step)?.classList.add('active');
  signageStep = step;

  // Run lock check whenever we land on step 2
  if (step === 2) updateLightLock();
}

document.querySelectorAll('.step-next').forEach(btn => btn.addEventListener('click', () => { if (signageStep < 5) goToStep(signageStep + 1); }));
document.querySelectorAll('.step-prev').forEach(btn => btn.addEventListener('click', () => { if (signageStep > 1) goToStep(signageStep - 1); }));

// ===== ADD SIGNAGE =====
document.getElementById('add-signage')?.addEventListener('click', () => {
  const stype = document.getElementById('sign-type')?.value || '';
  const light = document.getElementById('sign-light')?.value || '';
  const w     = parseFloat(document.getElementById('sign-width')?.value) || 0;
  const h     = parseFloat(document.getElementById('sign-height')?.value) || 0;
  const lat   = document.getElementById('sign-lat')?.value || '';
  const lng   = document.getElementById('sign-lng')?.value || '';
  const addr  = document.getElementById('sign-address')?.value || '';
  if (!w || !h) return showError('sign-error', 'Please enter width and height in Step 3.');
  addItem({ service_type: 'Signage', item_name: 'Signage', description: `${w}ft x ${h}ft ${stype} - ${light}${addr ? ' | ' + addr : ''}`, quantity: 1, details: { signage_type: stype, light_type: light, width: w, height: h, lat, lng, address: addr } });
  clearError('sign-error');
  goToStep(1);
  ['sign-width', 'sign-height'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
});

// ===== ITEM MANAGEMENT =====
function addItem(item) {
  item.id = 'item_' + Date.now();
  items.push(item);
  renderSummary();
  showToast(item.service_type + ' added to request');
}

function removeItem(id) {
  const idx = items.findIndex(i => i.id === id);
  if (idx > -1) items.splice(idx, 1);
  renderSummary();
}

function renderSummary() {
  const container = document.getElementById('summary-items');
  if (!container) return;
  if (items.length === 0) {
    container.innerHTML = '<div class="summary-empty">No services added yet.</div>';
  } else {
    container.innerHTML = items.map(item => `
      <div class="summary-item">
        <div style="flex:1">
          <div class="summary-item-name">${escHtml(item.service_type)}</div>
          <div class="summary-item-desc">${escHtml(item.description)}</div>
        </div>
        <span class="summary-item-remove" onclick="removeItem('${item.id}')">&#x2715; Remove</span>
      </div>`).join('');
  }
  const input = document.getElementById('items-data');
  if (input) input.value = JSON.stringify(items);
}

// ===== FORM SUBMIT =====
document.getElementById('quotation-form')?.addEventListener('submit', function(e) {
  e.preventDefault();
  if (items.length === 0) return showToast('Please add at least one service.', 'error');
  if (!validateForm()) return;
  this.submit();
});

function validateForm() {
  let valid = true;
  ['cust-name', 'cust-email', 'cust-phone'].forEach(id => {
    const el = document.getElementById(id);
    if (!el?.value.trim()) { el?.classList.add('error'); valid = false; }
    else el?.classList.remove('error');
  });
  const emailEl = document.getElementById('cust-email');
  if (emailEl?.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)) {
    emailEl.classList.add('error'); valid = false;
  }
  if (!valid) showToast('Please fill in all required fields.', 'error');
  return valid;
}

// ===== HELPERS =====
function escHtml(str) { return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
function showError(id, msg) { const el = document.getElementById(id); if (el) { el.textContent = msg; el.style.display = 'block'; } }
function clearError(id) { const el = document.getElementById(id); if (el) { el.textContent = ''; el.style.display = 'none'; } }
function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = `position:fixed;bottom:24px;right:24px;background:${type === 'error' ? '#c00' : '#111'};color:#fff;padding:12px 20px;font-size:0.85rem;z-index:9999;border-radius:2px;`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3000);
}

// ===== INIT =====
renderSummary();
goToStep(1);