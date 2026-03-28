// ===== NAVBAR =====
const navbar = document.querySelector('.navbar');
const navToggle = document.querySelector('.nav-toggle');
const navLinks = document.querySelector('.nav-links');

window.addEventListener('scroll', () => {
  if (window.scrollY > 50) {
    navbar?.classList.add('scrolled');
  } else {
    navbar?.classList.remove('scrolled');
  }
});

navToggle?.addEventListener('click', (e) => {
  e.stopPropagation();
  navLinks?.classList.toggle('open');
});

// Close nav on link click
document.querySelectorAll('.nav-links a').forEach(link => {
  link.addEventListener('click', () => navLinks?.classList.remove('open'));
});

// Close nav when clicking outside
document.addEventListener('click', (e) => {
  if (navLinks?.classList.contains('open') && !navLinks.contains(e.target) && e.target !== navToggle) {
    navLinks.classList.remove('open');
  }
});

// ===== SMOOTH SCROLL =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const offset = 80;
      const top = target.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  });
});

// ===== SCROLL REVEAL =====
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('revealed');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.service-card, .gallery-item, .stat-item').forEach(el => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(20px)';
  el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
  revealObserver.observe(el);
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.revealed').forEach(el => {
    el.style.opacity = '1';
    el.style.transform = 'translateY(0)';
  });
});

// Patch for IntersectionObserver callback
const style = document.createElement('style');
style.textContent = '.revealed { opacity: 1 !important; transform: translateY(0) !important; }';
document.head.appendChild(style);

// ===== GALLERY LIGHTBOX with zoom + drag + pinch =====
document.addEventListener('DOMContentLoaded', function () {
  var galleryItems = document.querySelectorAll('.gallery-item');
  if (!galleryItems.length) return;

  var imgs = [];
  galleryItems.forEach(function(item) {
    var img = item.querySelector('img');
    if (img) imgs.push(img.src);
  });

  var lb = document.createElement('div');
  lb.id = 'gallery-lb';
  lb.style.cssText = 'display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.95);flex-direction:column;';
  lb.innerHTML =
    '<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;flex-shrink:0">'
    + '<span id="glb-counter" style="color:rgba(255,255,255,.6);font-size:.85rem"></span>'
    + '<div style="display:flex;gap:8px">'
    + '<button onclick="glbZoom(0.25)" style="background:rgba(255,255,255,.12);color:#fff;border:none;border-radius:5px;padding:7px 12px;cursor:pointer;font-size:.82rem">+ Zoom In</button>'
    + '<button onclick="glbZoom(-0.25)" style="background:rgba(255,255,255,.12);color:#fff;border:none;border-radius:5px;padding:7px 12px;cursor:pointer;font-size:.82rem">- Zoom Out</button>'
    + '<button onclick="glbReset()" style="background:rgba(255,255,255,.12);color:#fff;border:none;border-radius:5px;padding:7px 12px;cursor:pointer;font-size:.82rem">Reset</button>'
    + '<button onclick="glbClose()" style="background:rgba(255,255,255,.12);color:#fff;border:none;border-radius:5px;padding:7px 12px;cursor:pointer;font-size:.82rem">X Close</button>'
    + '</div></div>'
    + '<div id="glb-viewport" style="flex:1;overflow:hidden;position:relative;display:flex;align-items:center;justify-content:center;">'
    + '<div id="glb-wrap" style="position:absolute;cursor:grab;user-select:none;touch-action:none;">'
    + '<img id="glb-img" src="" alt="" style="display:block;max-width:90vw;max-height:80vh;object-fit:contain;pointer-events:none;border-radius:3px;">'
    + '</div>'
    + '<button onclick="glbNav(-1)" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.12);color:#fff;border:none;border-radius:5px;padding:14px 16px;cursor:pointer;font-size:1.4rem;z-index:2;">&lsaquo;</button>'
    + '<button onclick="glbNav(1)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.12);color:#fff;border:none;border-radius:5px;padding:14px 16px;cursor:pointer;font-size:1.4rem;z-index:2;">&rsaquo;</button>'
    + '</div>';
  document.body.appendChild(lb);

  var wrap = document.getElementById('glb-wrap');
  var img  = document.getElementById('glb-img');
  var counter = document.getElementById('glb-counter');
  var viewport = document.getElementById('glb-viewport');
  var curIdx = 0, scale = 1, tx = 0, ty = 0;
  var dragging = false, dragSX, dragSY, dragTx, dragTy, pinchDist = null;

  function applyT() { wrap.style.transform = 'translate('+tx+'px,'+ty+'px) scale('+scale+')'; }

  function glbResetFn() {
    scale = 1; tx = 0; ty = 0;
    wrap.style.transition = 'transform .25s ease';
    applyT();
    setTimeout(function(){ wrap.style.transition = ''; }, 260);
  }
  window.glbReset = glbResetFn;

  function showImg() {
    img.src = imgs[curIdx];
    counter.textContent = (curIdx + 1) + ' / ' + imgs.length;
    glbResetFn();
  }

  window.glbClose = function() { lb.style.display = 'none'; };
  window.glbNav   = function(d) { curIdx = (curIdx + d + imgs.length) % imgs.length; showImg(); };
  window.glbZoom  = function(d) {
    scale = Math.min(5, Math.max(0.5, scale + d));
    wrap.style.transition = 'transform .15s ease'; applyT();
    setTimeout(function(){ wrap.style.transition = ''; }, 160);
  };

  galleryItems.forEach(function(item, i) {
    item.addEventListener('click', function(e) {
      e.stopPropagation();
      curIdx = i; showImg();
      lb.style.display = 'flex';
    });
  });

  viewport.addEventListener('click', function(e) { if (e.target === viewport) glbClose(); });

  document.addEventListener('keydown', function(e) {
    if (lb.style.display === 'none') return;
    if (e.key === 'ArrowRight') glbNav(1);
    if (e.key === 'ArrowLeft')  glbNav(-1);
    if (e.key === 'Escape')     glbClose();
    if (e.key === '+' || e.key === '=') glbZoom(0.25);
    if (e.key === '-') glbZoom(-0.25);
    if (e.key === '0') glbResetFn();
  });

  viewport.addEventListener('wheel', function(e) {
    e.preventDefault();
    var d = e.deltaY < 0 ? 0.15 : -0.15;
    var ns = Math.min(5, Math.max(0.5, scale + d));
    var rect = wrap.getBoundingClientRect();
    tx += (e.clientX - rect.left - rect.width / 2)  * (1 - ns / scale);
    ty += (e.clientY - rect.top  - rect.height / 2) * (1 - ns / scale);
    scale = ns; applyT();
  }, { passive: false });

  wrap.addEventListener('mousedown', function(e) {
    if (e.button !== 0) return;
    dragging = true; dragSX = e.clientX; dragSY = e.clientY; dragTx = tx; dragTy = ty;
    wrap.style.cursor = 'grabbing'; e.preventDefault();
  });
  document.addEventListener('mousemove', function(e) {
    if (!dragging) return;
    tx = dragTx + (e.clientX - dragSX); ty = dragTy + (e.clientY - dragSY); applyT();
  });
  document.addEventListener('mouseup', function() { dragging = false; wrap.style.cursor = 'grab'; });

  wrap.addEventListener('touchstart', function(e) {
    if (e.touches.length === 1) {
      dragging = true; dragSX = e.touches[0].clientX; dragSY = e.touches[0].clientY;
      dragTx = tx; dragTy = ty; pinchDist = null;
    } else if (e.touches.length === 2) {
      dragging = false;
      pinchDist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
    }
    e.preventDefault();
  }, { passive: false });

  wrap.addEventListener('touchmove', function(e) {
    if (e.touches.length === 1 && dragging) {
      tx = dragTx + (e.touches[0].clientX - dragSX); ty = dragTy + (e.touches[0].clientY - dragSY); applyT();
    } else if (e.touches.length === 2 && pinchDist) {
      var nd = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
      scale = Math.min(5, Math.max(0.5, scale * (nd / pinchDist))); pinchDist = nd; applyT();
    }
    e.preventDefault();
  }, { passive: false });

  wrap.addEventListener('touchend', function() { dragging = false; pinchDist = null; });

  var lastTap = 0;
  wrap.addEventListener('touchend', function() { var n = Date.now(); if (n - lastTap < 300) glbResetFn(); lastTap = n; });
  wrap.addEventListener('dblclick', glbResetFn);
});

// ===== CLICK RIPPLE on buttons, links, service cards =====
(function () {
  var SELECTORS = 'a, button, .btn, .service-card, .service-group-card, .gallery-item, .footer-social a';

  function addRipple(e) {
    var el = e.currentTarget;
    el.classList.add('ripple-host');

    var rect   = el.getBoundingClientRect();
    var size   = Math.max(rect.width, rect.height) * 1.5;
    var x      = e.clientX - rect.left - size / 2;
    var y      = e.clientY - rect.top  - size / 2;

    var wave = document.createElement('span');
    wave.className = 'ripple-wave';
    wave.style.cssText = 'width:' + size + 'px;height:' + size + 'px;left:' + x + 'px;top:' + y + 'px;';
    el.appendChild(wave);

    wave.addEventListener('animationend', function () { wave.remove(); });
  }

  document.querySelectorAll(SELECTORS).forEach(function (el) {
    el.addEventListener('click', addRipple);
  });
})();
