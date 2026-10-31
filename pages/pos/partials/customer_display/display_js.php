<?php
/*
 * ສ່ວນ: JS ຂອງຈໍລູກຄ້າ
 * ສະກຸນເງິນ, ເຕັມຈໍ, ໂມງ, ໝາຍເລກຄິວ, ຟັງຂໍ້ມູນຈາກໜ້າຂາຍ ແລະ renderDisplayData
 * (ແຕ້ມລາຍການ/ຍອດເງິນ/QR).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/customer_display.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
// ຂໍ້ຄວາມຂອງຈໍລູກຄ້າ ທຸກພາສາ (lang/*/customer_display.php; ຄຳທີ່ຂາດ → ພາສາລາວ) — ປ່ຽນພາສາທັນທີໂດຍບໍ່ໂຫຼດໜ້າໃໝ່ (cdApplyLang)
var CD_ALL_LANG = <?php
  $__cdLo = loadLangDomain('lo', 'customer_display');
  $__cdAll = [];
  foreach (POS_SUPPORTED_LANGS as $__lc) { $__cdAll[$__lc] = array_merge($__cdLo, array_filter(loadLangDomain($__lc, 'customer_display'), 'strlen')); }
  echo json_encode($__cdAll, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG);
?>;
var CD_LANG = <?php echo json_encode(getCurrentLang()); ?>;
var CD_I18N = Object.assign({}, CD_ALL_LANG[CD_LANG] || CD_ALL_LANG.lo);
function cdT(key) { return CD_I18N[key] || key; }

// ===== Display currency (cart data arrives in KIP; shown in the active currency) =====
<?php
  // $cd ກຳນົດໄວ້ແລ້ວໃນ pages/pos/customer_display.php (ກ່ອນ page_body)
  if (!isset($cd)) {
    $cd = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo)
        : ['code' => 'KIP', 'symbol' => kipSymbol(), 'decimals' => 0, 'rate' => 1];
  }
?>
window.CD_CUR = {
  code: <?php echo json_encode($cd['code']); ?>,
  symbol: <?php echo json_encode($cd['symbol'], JSON_UNESCAPED_UNICODE); ?>,
  decimals: <?php echo (int)$cd['decimals']; ?>,
  rate: <?php echo json_encode((float)$cd['rate']); ?>,
  isKip: <?php echo ($cd['code'] === 'KIP') ? 'true' : 'false'; ?>
};
window.CD_DEFAULT_CUR = Object.assign({}, window.CD_CUR);

function cdFmt(v) {
  v = parseFloat(v) || 0;
  // ກີບ: ໃຊ້ຄຳຂອງພາສາປັດຈຸບັນສະເໝີ (ໜ້າຂາຍອາດສົ່ງສັນຍາລັກພາສາເກົ່າມາ ຫຼັງປ່ຽນພາສາ)
  var sym = CD_CUR.isKip ? cdT('cd.kip') : CD_CUR.symbol;
  return v.toLocaleString('en-US', { minimumFractionDigits: CD_CUR.decimals, maximumFractionDigits: CD_CUR.decimals }) + ' <span class="cur-sym">' + sym + '</span>';
}
function cdFmtKip(kip) {
  var v = Math.round(parseFloat(kip) || 0);
  return v.toLocaleString('en-US') + ' <span class="cur-sym">' + (cdT('cd.kip') || 'ກີບ') + '</span>';
}
// kip -> formatted display string
function cdMoney(kip) {
  kip = parseFloat(kip) || 0;
  return cdFmt(CD_CUR.isKip ? Math.round(kip) : cdCeil(kip / CD_CUR.rate));
}
// ສະກຸນຕ່າງປະເທດ: ກີບ ÷ ອັດຕາແລກປ່ຽນ ແລ້ວປັດເປັນຈຳນວນເຕັມ (ຄືກັບໜ້າຂາຍ POSCUR.fromKip):
// ເສດບໍ່ກາຍ .50 ປັດລົງ, ກາຍ .50 ປັດຂຶ້ນ
function cdCeil(v) {
  v = Math.round((parseFloat(v) || 0) * 1e6) / 1e6;
  var neg = v < 0; if (neg) v = -v;
  var f = Math.floor(v);
  var r = (v - f > 0.5) ? f + 1 : f;
  return neg ? -r : r;
}

// Toggle QR Dock at bottom of 3D minimart
window.toggleQrDock = function() {
  var dock = document.getElementById('payQrDock');
  var txt = document.getElementById('txtToggleQrDock');
  if (!dock) return;
  dock.classList.toggle('collapsed');
  if (txt) {
    txt.textContent = dock.classList.contains('collapsed') ? cdT('cd.qr_show') : cdT('cd.qr_pay');
  }
};

// Fullscreen: ຮອງຮັບທັງ HTML5 Fullscreen API ແລະ ການກົດປຸ່ມ F11 ອັດຕະໂນມັດແບບ Real-Time ໂດຍບໍ່ຕ້ອງເອົາເມົ້າໄປແຕະຈໍລູກຄ້າ
var _lastNativeF11Time = 0;
var _userExitedFullscreen = false;
var _autoFsTimers = [];

function getTargetFsDoc() {
  try {
    if (window.top && window.top !== window && window.top.document) {
      return window.top.document;
    }
  } catch(e) {}
  return document;
}

function isDomFullscreen() {
  var d = getTargetFsDoc();
  return !!(d.fullscreenElement || d.webkitFullscreenElement || document.fullscreenElement || document.webkitFullscreenElement);
}

function isNativeF11Fullscreen() {
  return (window.innerWidth >= (window.screen.width - 6) && window.innerHeight >= (window.screen.height - 6));
}

function isCurrentlyFullscreen() {
  return isDomFullscreen() || isNativeF11Fullscreen();
}

function syncFsIcon() {
  var icon = document.getElementById('fullscreenToggleIcon');
  if (icon) {
    icon.className = isCurrentlyFullscreen() ? 'fas fa-compress' : 'fas fa-expand';
  }
}

function triggerNativeF11Key(forceToggle) {
  var now = Date.now();
  if (!forceToggle && (now - _lastNativeF11Time < 340)) return;
  if (!forceToggle && isCurrentlyFullscreen()) { syncFsIcon(); return; }
  _lastNativeF11Time = now;
  try {
    window.focus();
    var url = 'customer_display.php?action=native_f11' + (forceToggle ? '&force=1' : '');
    fetch(url, { method: 'POST', cache: 'no-store', keepalive: true })
      .then(function() { setTimeout(syncFsIcon, 60); })
      .catch(function() {});
  } catch(e) {}
}

function enterFullscreen() {
  if (isCurrentlyFullscreen()) { syncFsIcon(); return; }
  var d = getTargetFsDoc();
  var el = d.documentElement || document.documentElement;
  var rfs = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
  if (rfs) {
    try {
      var p = rfs.call(el, { navigationUI: 'hide' });
      if (p && p.catch) {
        p.catch(function() {
          triggerNativeF11Key(false);
        });
      }
    } catch(e) {
      triggerNativeF11Key(false);
    }
  }
  if (!isCurrentlyFullscreen()) {
    triggerNativeF11Key(false);
  }
}

function ensureAutoFullscreen() {
  _userExitedFullscreen = false;
  for (var i = 0; i < _autoFsTimers.length; i++) {
    clearTimeout(_autoFsTimers[i]);
  }
  _autoFsTimers = [];

  enterFullscreen();

  var delays = [80, 460, 920, 1450, 2100];
  delays.forEach(function(ms) {
    var tid = setTimeout(function() {
      if (_userExitedFullscreen || isCurrentlyFullscreen()) {
        syncFsIcon();
        return;
      }
      enterFullscreen();
    }, ms);
    _autoFsTimers.push(tid);
  });
}

function toggleFullscreen() {
  var d = getTargetFsDoc();
  if (!isCurrentlyFullscreen()) {
    _userExitedFullscreen = false;
    enterFullscreen();
  } else {
    _userExitedFullscreen = true;
    for (var i = 0; i < _autoFsTimers.length; i++) {
      clearTimeout(_autoFsTimers[i]);
    }
    _autoFsTimers = [];
    if (isDomFullscreen()) {
      var exitDoc = (d.fullscreenElement || d.webkitFullscreenElement) ? d : document;
      var efs = exitDoc.exitFullscreen || exitDoc.webkitExitFullscreen || exitDoc.mozCancelFullScreen || exitDoc.msExitFullscreen;
      if (efs) {
        try { efs.call(exitDoc).catch(function(){}); } catch(e) {}
      }
    } else {
      triggerNativeF11Key(true);
    }
  }
}

var _fsBtn = document.getElementById('fullscreenToggleBtn');
if (_fsBtn) _fsBtn.addEventListener('click', toggleFullscreen);
document.addEventListener('fullscreenchange', syncFsIcon);
window.addEventListener('resize', syncFsIcon);
try {
  if (window.top && window.top !== window && window.top.document) {
    window.top.document.addEventListener('fullscreenchange', syncFsIcon);
  }
} catch(e) {}
syncFsIcon();

// ບັນທຶກຕຳແໜ່ງໜ້າຈໍປັດຈຸບັນ ໂດຍໃຊ້ຂະໜາດເຕັມຈໍສະເໝີ (screen.width x screen.height)
function reportScreenPosition() {
  try {
    var sx = (typeof window.screenX === 'number') ? window.screenX : (window.screenLeft || 0);
    var sy = (typeof window.screenY === 'number') ? window.screenY : (window.screenTop || 0);
    var sw = window.screen.width || window.outerWidth || 1920;
    var sh = window.screen.height || window.outerHeight || 1080;
    if (sx !== 0 || sy !== 0 || sw > 500) {
      localStorage.setItem('StoreMini.customer_display_screen', JSON.stringify({
        left: sx,
        top: sy,
        width: sw,
        height: sh
      }));
    }
  } catch(e) {}
}
window.addEventListener('resize', reportScreenPosition);
window.addEventListener('beforeunload', reportScreenPosition);
setTimeout(reportScreenPosition, 800);

// ເຂົ້າ F11 Fullscreen ອັດຕະໂນມັດທັນທີຕອນເປີດໜ້າຈໍລູກຄ້າ ໂດຍບໍ່ຕ້ອງເອົາເມົ້າໄປແຕະຈໍລູກຄ້າ
ensureAutoFullscreen();
window.addEventListener('load', function() {
  if (!_userExitedFullscreen && !isCurrentlyFullscreen()) {
    ensureAutoFullscreen();
  }
});

// ຮັບຄຳສັ່ງ Fullscreen ຈາກໜ້າຂາຍເມື່ອກົດປຸ່ມຈໍລູກຄ້າ
window.addEventListener('storage', function(e) {
  if (e.key === 'StoreMini.customer_display_trigger_fs') {
    ensureAutoFullscreen();
  }
});

// ກວດສອບສິດ Automatic Fullscreen (ສຳລັບ Chrome / Edge ຮຸ່ນໃໝ່)
if (navigator.permissions && navigator.permissions.query) {
  try {
    navigator.permissions.query({ name: 'fullscreen', allowWithoutGesture: true }).then(function(p) {
      if (p.state === 'granted') enterFullscreen();
      p.onchange = function() { if (p.state === 'granted') enterFullscreen(); };
    }).catch(function(){});
  } catch(e) {}
}

// ແຕະ, ຄລິກ, ເລື່ອນເມົ້າ ຫຼື ກົດປຸ່ມໃດກໍ່ໄດ້ → ເຕັມຈໍທັນທີ (F11 fullscreen)
var _fsHandled = false;
['pointerdown', 'touchstart', 'click', 'keydown', 'mousemove', 'wheel'].forEach(function(ev) {
  document.addEventListener(ev, function(e) {
    if (document.fullscreenElement) return;
    if (e.target && e.target.closest && e.target.closest('#fullscreenToggleBtn')) return;
    enterFullscreen();
  }, { passive: true, capture: true });
});

// Particle Background — soft drifting dots behind the store logo/info panel
(function() {
  var canvas = document.getElementById('storeCardParticles');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var card = canvas.closest('.store-card');
  var particles = [];
  var colors = ['rgba(2,99,255,0.55)', 'rgba(109,40,217,0.50)', 'rgba(5,150,105,0.50)', 'rgba(255,212,90,0.55)'];
  var dpr = window.devicePixelRatio || 1;
  var w = 0, h = 0;

  function resize() {
    w = card.clientWidth;
    h = card.clientHeight;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function makeParticle() {
    var r = 1.5 + Math.random() * 3;
    return {
      x: Math.random() * w,
      y: Math.random() * h,
      r: r,
      vx: (Math.random() - 0.5) * 0.25,
      vy: (Math.random() - 0.5) * 0.25,
      color: colors[Math.floor(Math.random() * colors.length)],
      twinkle: Math.random() * Math.PI * 2
    };
  }

  function init() {
    resize();
    var count = Math.max(24, Math.round((w * h) / 9000));
    particles = [];
    for (var i = 0; i < count; i++) particles.push(makeParticle());
  }

  function step() {
    ctx.clearRect(0, 0, w, h);
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      p.x += p.vx;
      p.y += p.vy;
      p.twinkle += 0.02;

      if (p.x < -10) p.x = w + 10;
      if (p.x > w + 10) p.x = -10;
      if (p.y < -10) p.y = h + 10;
      if (p.y > h + 10) p.y = -10;

      var pulse = 0.55 + Math.sin(p.twinkle) * 0.35;
      ctx.beginPath();
      ctx.fillStyle = p.color;
      ctx.globalAlpha = pulse;
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.globalAlpha = 1;

    // Faint connecting lines between nearby particles for a "network" feel
    ctx.strokeStyle = 'rgba(100,116,139,0.12)';
    ctx.lineWidth = 1;
    for (var a = 0; a < particles.length; a++) {
      for (var b = a + 1; b < particles.length; b++) {
        var dx = particles[a].x - particles[b].x;
        var dy = particles[a].y - particles[b].y;
        var dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 90) {
          ctx.beginPath();
          ctx.moveTo(particles[a].x, particles[a].y);
          ctx.lineTo(particles[b].x, particles[b].y);
          ctx.stroke();
        }
      }
    }

    requestAnimationFrame(step);
  }

  init();
  requestAnimationFrame(step);
  window.addEventListener('resize', init);
})();

// Live Clock (24 ຊົ່ວໂມງ: ເຊັ່ນ 15:26:42)
function formatTime24(d) {
  var hh = String(d.getHours()).padStart(2, '0');
  var mm = String(d.getMinutes()).padStart(2, '0');
  var ss = String(d.getSeconds()).padStart(2, '0');
  return hh + ':' + mm + ':' + ss;
}
function updateClock() {
  var now = new Date();
  $('#liveClock').text(formatTime24(now));
  $('#liveDate').text(now.toLocaleDateString('lo-LA'));
}
setInterval(updateClock, 1000);

// Big queue-number takeover for a few seconds after a sale completes.
var _queueFlashTimer = null;
function showQueueFlash(num) {
  var el = document.getElementById('queueFlash');
  if (!el) return;
  document.getElementById('queueFlashNo').textContent = num;
  el.style.display = 'flex';
  if (_queueFlashTimer) clearTimeout(_queueFlashTimer);
  _queueFlashTimer = setTimeout(function () { el.style.display = 'none'; }, 6000);
}
updateClock();

// Broadcast Channel & Storage Sync
var channel = null;
try {
  channel = new BroadcastChannel('StoreMini.customer_display_channel');
  channel.onmessage = function(e) {
    if (e.data) {
      if (e.data.action === 'cashier_enter') {
        if (typeof window.cdTriggerCashierWalkIn === 'function') {
          window.cdTriggerCashierWalkIn(e.data.name, e.data);
        }
        return;
      }
      if (e.data.action === 'cashier_exit') {
        if (typeof window.cdTriggerCashierWalkOut === 'function') {
          window.cdTriggerCashierWalkOut(e.data.name, e.data);
        }
        return;
      }
      renderDisplayData(e.data);
    }
  };
} catch(e) {}

try {
  var cashierChannel = new BroadcastChannel('StoreMini.cashier_channel');
  cashierChannel.onmessage = function(e) {
    if (e.data) {
      if (e.data.action === 'cashier_enter') {
        if (typeof window.cdTriggerCashierWalkIn === 'function') {
          window.cdTriggerCashierWalkIn(e.data.name, e.data);
        }
      } else if (e.data.action === 'cashier_exit') {
        if (typeof window.cdTriggerCashierWalkOut === 'function') {
          window.cdTriggerCashierWalkOut(e.data.name, e.data);
        }
      }
    }
  };
} catch(e) {}

window.addEventListener('storage', function(e) {
  if (e.key === 'StoreMini.customer_display_data') {
    try {
      renderDisplayData(JSON.parse(e.newValue));
    } catch(err) {}
  }
  if (e.key === 'StoreMini.cashier_entered' && e.newValue) {
    try {
      var cData = JSON.parse(e.newValue);
      if (cData && (cData.name || cData.employee_name)) {
        if (typeof window.cdTriggerCashierWalkIn === 'function') {
          window.cdTriggerCashierWalkIn(cData.name || cData.employee_name, cData);
        }
      }
    } catch(err) {}
  }
  if (e.key === 'StoreMini.cashier_exited' && e.newValue) {
    try {
      var exData = JSON.parse(e.newValue);
      if (exData && (exData.name || exData.employee_name)) {
        if (typeof window.cdTriggerCashierWalkOut === 'function') {
          window.cdTriggerCashierWalkOut(exData.name || exData.employee_name, exData);
        }
      }
    } catch(err) {}
  }
  if (e.key === 'StoreMini.ui_lang' && e.newValue) {
    try { cdApplyLang(JSON.parse(e.newValue).lang); } catch(err) {}
  }
});

// ປ່ຽນພາສາຢູ່ໜ້າຫຼັກ (ທຸງໃນ navbar → home/partials/dashboard/js_language.php) → ຈໍລູກຄ້າປ່ຽນນຳທັນທີ ບໍ່ໂຫຼດໜ້າໃໝ່:
//  1. ແປຂໍ້ຄວາມທີ່ແຕ້ມໄວ້ແລ້ວ: ຂໍ້ຄວາມໃດກົງກັບຄຳຂອງພາສາເກົ່າ → ຄຳດຽວກັນຂອງພາສາໃໝ່ (CD_ALL_LANG)
//  2. ແຕ້ມລາຍການ/ຍອດເງິນຄືນ ດ້ວຍຂໍ້ມູນລ່າສຸດ (localStorage) — ຫົວໜ່ວຍ, ກີບ, "N ລາຍການ / N ອັນ"
//  3. ສາກ 3D: ປ້າຍຮ້ານ/ໃບບິນ (minimart3dSetLang ໃນ minimart_3d.php)
// ລະຫວ່າງລູກຄ້າກຳລັງຊຳລະ (QR / ຊຳລະສຳເລັດ ເປີດຢູ່) ລໍໃຫ້ປິດກ່ອນ ຈຶ່ງປ່ຽນ.
var _cdLangTimer = null;
function cdTranslateDom(fromLang, toLang) {
  var from = CD_ALL_LANG[fromLang] || {}, to = CD_ALL_LANG[toLang] || {}, rev = {};
  Object.keys(from).forEach(function (k) { var v = String(from[k] || '').trim(); if (v && to[k]) rev[v] = to[k]; });
  var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
    acceptNode: function (n) { return /^(SCRIPT|STYLE)$/.test(n.parentNode && n.parentNode.nodeName) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT; }
  });
  var nodes = [];
  while (walker.nextNode()) nodes.push(walker.currentNode);
  nodes.forEach(function (n) { var t = n.nodeValue.trim(); if (t && rev[t]) n.nodeValue = n.nodeValue.replace(t, rev[t]); });
  document.querySelectorAll('[title]').forEach(function (el) { var t = el.getAttribute('title').trim(); if (rev[t]) el.setAttribute('title', rev[t]); });
}
function cdApplyLang(lang) {
  if (!lang || lang === CD_LANG || !CD_ALL_LANG[lang]) return;
  if ($('#bankPayPopup').is(':visible') || $('#paymentSuccessOverlay').is(':visible') || $('#queueFlash').is(':visible')) {
    clearTimeout(_cdLangTimer);
    _cdLangTimer = setTimeout(function () { cdApplyLang(lang); }, 1500);
    return;
  }
  var fromLang = CD_LANG;
  cdTranslateDom(fromLang, lang);
  CD_LANG = lang;
  CD_I18N = Object.assign({}, CD_ALL_LANG[lang]);
  document.documentElement.lang = lang;
  // ແຕ້ມຄືນດ້ວຍຂໍ້ມູນລ່າສຸດ (ບໍ່ໃຫ້ປັອບອັບຊຳລະສຳເລັດເກົ່າເດັ້ງຄືນ — ຄືກັບ loadInitialData)
  var d = null;
  try { d = JSON.parse(localStorage.getItem('StoreMini.customer_display_data') || 'null'); } catch (e) {}
  if (!d) d = { cart: [], discount: 0, checkoutState: 'shopping' };
  if (d.checkoutState === 'payment_success') {
    d.cart = []; d.cashReceived = 0; d.qrReceived = 0; d.changeAmount = 0; d.totalAmount = 0; d.paidAmount = 0;
    d.checkoutState = 'shopping';
  }
  window._currentTbodyFingerprint = null;   // ບັງຄັບແຕ້ມຕາຕະລາງໃໝ່ (ຫົວໜ່ວຍ/ຄຳ ຕາມພາສາໃໝ່)
  renderDisplayData(d);
  if (typeof window.minimart3dSetLang === 'function') {
    try { window.minimart3dSetLang(CD_ALL_LANG[fromLang], CD_ALL_LANG[lang]); } catch (e) {}
  }
}
try {
  new BroadcastChannel('StoreMini.ui_lang_channel').onmessage = function(e) {
    if (e.data && e.data.lang) cdApplyLang(e.data.lang);
  };
} catch(e) {}

function loadInitialData() {
  var raw = localStorage.getItem('StoreMini.customer_display_data');
  if (raw) {
    try {
      var d = JSON.parse(raw);
      if (d) {
        // ເມື່ອເປີດ ຫຼື ຣີເຟຣຊຈໍລູກຄ້າ: ໃຫ້ເລີ່ມຕົ້ນຢູ່ໂໝດ shopping ປົກກະຕິສະເໝີ
        // ບໍ່ໃຫ້ເດັ້ງປັອບອັບ QR ທະນາຄານ (payment_live) ຫຼື ປັອບອັບໃບບິນເກົ່າ (payment_success) ຂຶ້ນມາທັບ
        if (d.checkoutState === 'payment_success') {
          d.cart = [];
          d.cashReceived = 0;
          d.qrReceived = 0;
          d.changeAmount = 0;
          d.totalAmount = 0;
          d.paidAmount = 0;
        }
        d.checkoutState = 'shopping';
        d.payBank = null;
        $('#bankPayPopup').hide();
        $('#paymentSuccessOverlay').hide();
        try { localStorage.setItem('StoreMini.customer_display_data', JSON.stringify(d)); } catch(err) {}
      }
      renderDisplayData(d);
    } catch(e) {}
  }
}
loadInitialData();

// ທະນາຄານຂອງສາຂານີ້ { id: { name, logo, qr } } — QR/ໂລໂກ້ ຈາກຝັ່ງຈໍລູກຄ້າເອງ (path ຖືກຕ້ອງສະເໝີ)
var CD_BANKS = <?php
  $__cdBanks = [];
  foreach ($cd_bank_accounts as $__b) {
    if (!empty($__b['id'])) $__cdBanks[(int)$__b['id']] = ['name' => $__b['bank_name'], 'logo' => $__b['logo'], 'qr' => $__b['qr']];
  }
  echo json_encode((object)$__cdBanks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>;

// ເດັ້ງ QR ທະນາຄານທີ່ພະນັກງານເລືອກ ຕອນລູກຄ້າຊຳລະດ້ວຍການໂອນ (payment_live ຈາກ pos_checkout_js.php)
// ໄຮໄລ້ບັດ QR ຂອງທະນາຄານທີ່ເລືອກໃນແຜງ (ອັນອື່ນຈາງລົງ) — ຄຽງຄູ່ກັບ popup QR ໃຫຍ່
function highlightPayBank(bankId) {
  var $wrap = $('#payQrWrap');
  if (!$wrap.length) return;
  $wrap.find('.pay-qr-card').removeClass('is-selected');
  var $hit = bankId ? $wrap.find('.pay-qr-card[data-bank-id="' + parseInt(bankId, 10) + '"]') : $();
  $hit.addClass('is-selected');
  $wrap.toggleClass('has-selection', $hit.length > 0);
}

function renderBankPopup(data) {
  var $pop = $('#bankPayPopup');
  var b = (data && data.checkoutState === 'payment_live') ? data.payBank : null;
  highlightPayBank(b ? b.id : null);
  if (!b) { $pop.hide(); return; }
  var local = CD_BANKS[b.id] || {};
  var qr = local.qr || b.qr || '';
  if (!qr) { $pop.hide(); return; }
  $('#bppBankName').text(local.name || b.name || '');
  if (local.logo) $('#bppLogo').attr('src', local.logo).show(); else $('#bppLogo').hide();
  if ($('#bppQr').attr('src') !== qr) $('#bppQr').css('visibility', 'visible').attr('src', qr);
  var transferKip = parseFloat(data.transferDue || data.qrReceived || 0);
  $('#bppAmount').html(cdMoney(transferKip));
  if (!CD_CUR.isKip && transferKip > 0) {
    $('#bppAmountKip').html('≈ ' + cdFmtKip(transferKip)).show();
  } else {
    $('#bppAmountKip').hide().empty();
  }

  // ຊຳລະຫຼາຍຊ່ອງທາງ (Split Payment): ສະແດງເງິນສົດທີ່ຕ້ອງຈ່າຍ ຫຼື ເງິນທອນ (ກໍລະນີປ້ອນກາຍ)
  var isSplit = (data && data.payMode === 'split');
  if (isSplit) {
    var totalKip   = parseFloat(data.totalAmount || 0);
    var cashRec    = parseFloat(data.cashReceived || 0);
    var changeKip  = parseFloat(data.changeAmount || 0);
    var targetCash = Math.max(0, totalKip - transferKip);

    var $box  = $('#bppSplitCashBox');
    var $icon = $('#bppSplitCashIcon');
    var $lbl  = $('#bppSplitCashLabel');
    var $amt  = $('#bppSplitCashAmount');
    var $kip  = $('#bppSplitCashKip');
    var $sub  = $('#bppSplitCashSub');

    // 1. ຖ້າປ້ອນກາຍ (Change > 0): ສະແດງເງິນທອນຂຶ້ນຊັດເຈນທັນທີ!
    if (changeKip > 0) {
      $box.addClass('is-change');
      $icon.html('<i class="fas fa-hand-holding-usd"></i>');
      $lbl.text((cdT('cd.change') || '<?php echo tlj('ເງິນທອນ'); ?>') + ':');
      $amt.html(cdMoney(changeKip));
      if (!CD_CUR.isKip && changeKip > 0) {
        $kip.html('≈ ' + cdFmtKip(changeKip)).show();
      } else {
        $kip.hide().empty();
      }
      if (cashRec > 0) {
        $sub.html((cdT('cd.cash_received') || '<?php echo tlj('ຮັບເງິນສົດ:'); ?>') + ' ' + cdMoney(cashRec)).show();
      } else {
        $sub.hide().empty();
      }
      $('#bppSplitSection').show();
    } else if (targetCash > 0 || cashRec > 0) {
      // 2. ຍັງບໍ່ກາຍ: ສະແດງເງິນສົດທີ່ຕ້ອງຈ່າຍ
      $box.removeClass('is-change');
      $icon.html('<i class="fas fa-money-bill-wave"></i>');

      if (cashRec >= targetCash && targetCash > 0) {
        $lbl.text((cdT('cd.cash_to_pay') || '<?php echo tlj('ເງິນສົດທີ່ຕ້ອງຈ່າຍ'); ?>') + ':');
        $amt.html(cdMoney(targetCash));
        $sub.html('<span class="text-success font-weight-bold">✓ <?php echo tlj('ພໍດີ'); ?></span>').show();
      } else if (cashRec > 0 && cashRec < targetCash) {
        var remCash = targetCash - cashRec;
        $lbl.text('<?php echo tlj('ຍັງຄ້າງເງິນສົດ:'); ?>');
        $amt.html(cdMoney(remCash));
        $sub.html((cdT('cd.cash_received') || '<?php echo tlj('ຮັບແລ້ວ:'); ?>') + ' ' + cdMoney(cashRec)).show();
      } else {
        $lbl.text((cdT('cd.cash_to_pay') || '<?php echo tlj('ເງິນສົດທີ່ຕ້ອງຈ່າຍ'); ?>') + ':');
        $amt.html(cdMoney(targetCash));
        $sub.hide().empty();
      }

      if (!CD_CUR.isKip && targetCash > 0) {
        $kip.html('≈ ' + cdFmtKip(targetCash)).show();
      } else {
        $kip.hide().empty();
      }
      $('#bppSplitSection').show();
    } else {
      $('#bppSplitSection').hide();
    }
  } else {
    $('#bppSplitSection').hide();
  }

  $pop.css('display', 'flex');
}

// Audio Context unlock for browser autoplay policy
var _audioUnlockedCtx = null;
function getAudioContext() {
  try {
    var AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return null;
    if (!_audioUnlockedCtx) {
      _audioUnlockedCtx = new AudioCtx();
    }
    if (_audioUnlockedCtx.state === 'suspended') {
      _audioUnlockedCtx.resume();
    }
    return _audioUnlockedCtx;
  } catch(e) {
    return null;
  }
}
['pointerdown', 'touchstart', 'click', 'keydown'].forEach(function(ev) {
  document.addEventListener(ev, function() { getAudioContext(); }, { passive: true });
});

// Friendly welcoming chime for cashier arrival ("Ding-Dong! 👋") via Web Audio API
function playCashierWelcomeChime() {
  if (window.MiniposSounds) { MiniposSounds.doorChime(); return; }   // ກະດິ່ງປະຕູ "ຕິງ-ຕ່ອງ"
  try {
    var ctx = getAudioContext();
    if (!ctx) return;
    var now = ctx.currentTime;

    var osc1 = ctx.createOscillator();
    var gain1 = ctx.createGain();
    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(659.25, now); // E5
    gain1.gain.setValueAtTime(0.14, now);
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
    osc1.connect(gain1);
    gain1.connect(ctx.destination);
    osc1.start(now);
    osc1.stop(now + 0.36);

    var osc2 = ctx.createOscillator();
    var gain2 = ctx.createGain();
    osc2.type = 'sine';
    osc2.frequency.setValueAtTime(880.0, now + 0.12); // A5
    gain2.gain.setValueAtTime(0.16, now + 0.12);
    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.65);
    osc2.connect(gain2);
    gain2.connect(ctx.destination);
    osc2.start(now + 0.12);
    osc2.stop(now + 0.66);
  } catch(e) {}
}

// ບໍ່ໃຫ້ຂຶ້ນເຕືອນໜ້າຈໍພະນັກງານໃດເຂົ້າ (Toast disabled per user directive)
window.showCashierArrivalToast = function(name) {};
window.showCashierExitToast = function(name) {};

// ກົດປຸ່ມ 'w' ເພື່ອທົດສອບພະນັກງານຍ່າງເຂົ້າ / 'e' ເພື່ອທົດສອບຍ່າງອອກ
window.addEventListener('keydown', function(e) {
  if (!e.ctrlKey && !e.altKey && !e.metaKey) {
    if (e.key === 'w' || e.key === 'W') {
      var testNames = ['ສົມສັກ', 'ນາງ ມະນີ', 'ຄຳຫຼ້າ', 'ວິໄລສັກ'];
      var randomName = testNames[Math.floor(Math.random() * testNames.length)];
      if (typeof window.cdTriggerCashierWalkIn === 'function') {
        window.cdTriggerCashierWalkIn(randomName);
      }
    } else if (e.key === 'e' || e.key === 'E') {
      if (typeof window.cdTriggerCashierWalkOut === 'function') {
        window.cdTriggerCashierWalkOut();
      }
    }
  }
});

// ສຽງຕອນກົດຂາຍສຳເລັດແບບໃໝ່ (Modern Melodic POS Sale Confirmation Chime)
function playMoneyReceivedSound() {
  if (window.MiniposSounds) { MiniposSounds.saleChime(); return; }   // ສຽງໃໝ່ (assets/js/pos-sounds.js)
  try {
    var ctx = getAudioContext();
    if (!ctx) return;
    var now = ctx.currentTime;

    var masterGain = ctx.createGain();
    masterGain.gain.setValueAtTime(0.42, now);
    masterGain.connect(ctx.destination);

    // ສຽງ Chime ອ່ອນນຸ່ມ ສົດໃສ ລະດັບສຽງ C5 -> E5 -> G5 -> C6
    var chord = [
      { f: 523.25,  t: 0.00, d: 0.38, g: 0.28 }, // C5
      { f: 659.25,  t: 0.08, d: 0.44, g: 0.32 }, // E5
      { f: 783.99,  t: 0.16, d: 0.54, g: 0.36 }, // G5
      { f: 1046.50, t: 0.24, d: 0.88, g: 0.45 }  // C6 (sparkling crystal top)
    ];

    chord.forEach(function(n) {
      var start = now + n.t;
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(n.f, start);
      gain.gain.setValueAtTime(0.001, start);
      gain.gain.exponentialRampToValueAtTime(n.g, start + 0.014);
      gain.gain.exponentialRampToValueAtTime(0.0001, start + n.d);
      osc.connect(gain);
      gain.connect(masterGain);
      osc.start(start);
      osc.stop(start + n.d + 0.02);

      // Warm harmonic overtone
      var oscH = ctx.createOscillator();
      var gainH = ctx.createGain();
      oscH.type = 'triangle';
      oscH.frequency.setValueAtTime(n.f * 2, start);
      gainH.gain.setValueAtTime(0.001, start);
      gainH.gain.exponentialRampToValueAtTime(n.g * 0.18, start + 0.014);
      gainH.gain.exponentialRampToValueAtTime(0.0001, start + n.d * 0.55);
      oscH.connect(gainH);
      gainH.connect(masterGain);
      oscH.start(start);
      oscH.stop(start + n.d * 0.55 + 0.02);
    });
  } catch(e) {}
}

var _paySuccessTimer = null;
function showPaymentSuccessModal(data) {
  var $overlay = $('#paymentSuccessOverlay');
  if (typeof window.minimart3dCelebrate === 'function') window.minimart3dCelebrate();
  if (!$overlay.length) return;

  var total = parseFloat(data.totalAmount || data.paidAmount || 0);
  $('#paySuccessAmount').html(cdMoney(total));
  if (!CD_CUR.isKip && total > 0) {
    $('#paySuccessAmountKip').html('≈ ' + cdFmtKip(total)).show();
  } else {
    $('#paySuccessAmountKip').hide().empty();
  }

  var cash = parseFloat(data.cashReceived || 0);
  var qr = parseFloat(data.qrReceived || 0);
  var change = parseFloat(data.changeAmount || 0);
  var payType = data.paymentType || '';
  var payMode = data.payMode || '';

  // Determine Bank: lookup from CD_BANKS if id/name available
  var bankId = data.bankId ? parseInt(data.bankId, 10) : null;
  var bank = (bankId && CD_BANKS && CD_BANKS[bankId]) ? CD_BANKS[bankId] : null;
  if (!bank && data.bankName && typeof CD_BANKS === 'object') {
    for (var bid in CD_BANKS) {
      if (CD_BANKS[bid].name === data.bankName || (data.bankName && data.bankName.includes(CD_BANKS[bid].name))) {
        bank = CD_BANKS[bid];
        break;
      }
    }
  }
  var bankName = bank ? bank.name : (data.bankName || '');
  var bankLogo = bank ? bank.logo : '';

  // Determine payment method display
  var isSplit = (payMode === 'split') || (cash > 0 && qr > 0);
  var isTransfer = (qr > 0) || (payType && (payType.includes('ໂອນ') || payType.includes('QR')));

  if (isSplit) {
    $('#paySuccessMethodIcon').html('<i class="fas fa-coins text-warning"></i>');
    $('#paySuccessMethodText').text(cdT('cd.paid_by_split'));
  } else if (isTransfer) {
    $('#paySuccessMethodIcon').html('<i class="fas fa-qrcode text-primary"></i>');
    $('#paySuccessMethodText').text(cdT('cd.paid_by_transfer'));
  } else {
    $('#paySuccessMethodIcon').html('<i class="fas fa-money-bill-wave text-success"></i>');
    $('#paySuccessMethodText').text(cdT('cd.paid_by_cash'));
  }

  // Bank Info section in badge
  if ((isTransfer || isSplit) && (bankName || bankLogo)) {
    if (bankLogo) {
      $('#paySuccessBankLogo').attr('src', bankLogo).show();
    } else {
      $('#paySuccessBankLogo').hide();
    }
    $('#paySuccessBankName').text(bankName ? (cdT('cd.bank') + ' ' + bankName) : '').show();
    $('#paySuccessBankWrap').css('display', 'flex');
  } else {
    $('#paySuccessBankWrap').hide();
  }

  // Detailed Breakdown rows
  // 1. Cash row
  if (cash > 0 || (!isTransfer && !isSplit)) {
    $('#paySuccessCashAmt').html(cdMoney(cash > 0 ? cash : total));
    $('#paySuccessCashRow').css('display', 'flex');
  } else {
    $('#paySuccessCashRow').hide();
  }

  // 2. QR Transfer row
  if (qr > 0 || isTransfer) {
    var qrAmtVal = qr > 0 ? qr : total;
    $('#paySuccessQrAmt').html(cdMoney(qrAmtVal));
    $('#paySuccessQrBankInline').text(bankName ? ('(' + bankName + ')') : '');
    $('#paySuccessQrRow').css('display', 'flex');
  } else {
    $('#paySuccessQrRow').hide();
  }

  // 3. Change row (ສະແດງສະເພາະເມື່ອມີເງິນທອນ > 0 ເທົ່ານັ້ນ)
  if (change > 0) {
    $('#paySuccessChange').html(cdMoney(change));
    $('#paySuccessChangeRow').css('display', 'flex');
  } else {
    $('#paySuccessChangeRow').hide();
  }

  // Invoice Number & Time (24 ຊົ່ວໂມງ)
  var nowTime = new Date();
  $('#paySuccessTimeText').text(formatTime24(nowTime));
  if (data.invoiceNumber) {
    $('#paySuccessInvoiceNo').text(cdT('cd.invoice_no') + ' ' + data.invoiceNumber).show();
  } else {
    $('#paySuccessInvoiceNo').text(cdT('cd.invoice_no') + ' -').show();
  }

  // Queue Number
  if (data.queueNumber) {
    $('#paySuccessQueueNo').text(data.queueNumber);
    $('#paySuccessQueueBadge').show();
  } else {
    $('#paySuccessQueueBadge').hide();
  }

  $('#bankPayPopup').hide();

  // Reset and animate countdown progress bar (6 seconds)
  var $bar = $('#paySuccessProgressBar');
  if ($bar.length) {
    $bar.stop().css({ width: '100%', transition: 'none' });
    void $bar[0].offsetWidth; // force DOM reflow
    $bar.css({ transition: 'width 6s linear', width: '0%' });
  }

  $overlay.stop(true, true).css('display', 'flex').hide().fadeIn(250);
  playMoneyReceivedSound();

  // Lao TTS Voice: ເວົ້າແຈ້ງຍອດເງິນ, ຮັບເງິນ, ເງິນທອນ ຫຼື ເລກຄິວລູກຄ້າ
  try {
    if (window.LaoTTS) {
      var lastSpokenInvoice = null;
      try { lastSpokenInvoice = localStorage.getItem('StoreMini.tts_last_spoken_invoice'); } catch (e) {}
      // ຖ້າ POS ຝັ່ງແຄັດເຊຍເວົ້າໄປແລ້ວໃນເຄື່ອງດຽວກັນ, ບໍ່ຕ້ອງເວົ້າຊ້ຳ
      if (!lastSpokenInvoice || !data.invoiceNumber || lastSpokenInvoice !== data.invoiceNumber) {
        if (data.queueNumber) {
          setTimeout(function() { LaoTTS.speakQueue(data.queueNumber); }, 80);
        } else if (data.cashReceived || data.qrReceived || data.paidAmount || data.totalAmount) {
          setTimeout(function() {
            var tenderCode = (data.tenderCurrency && data.tenderCurrency.code) || 'KIP';
            var tenderRate = parseFloat(data.tenderCurrency && data.tenderCurrency.rate) || 1;
            var isKipCur = (tenderCode === 'KIP' || tenderRate <= 1);
            var totalVal = parseFloat(data.totalAmount || data.paidAmount || data.total || 0);
            var cashVal = parseFloat(data.cashReceived || 0);
            var qrVal = parseFloat(data.qrReceived || 0);
            var changeVal = parseFloat(data.changeAmount || data.change || 0);
            var isSplit = (data.payMode === 'split') || (cashVal > 0 && qrVal > 0);
            var isQr = isSplit ? 'split' : (qrVal > 0 || (data.paymentType && (data.paymentType.includes('QR') || data.paymentType.includes('ໂອນ'))));

            var spTotal = isKipCur ? totalVal : Math.round(totalVal / tenderRate);
            var spCash = isKipCur ? cashVal : Math.round(cashVal / tenderRate);
            var spQr = isKipCur ? qrVal : Math.round(qrVal / tenderRate);
            var spChange = isKipCur ? changeVal : Math.max(0, Math.round(changeVal / tenderRate));

            var bankId = data.bankId ? parseInt(data.bankId, 10) : null;
            var bankObj = (bankId && CD_BANKS && CD_BANKS[bankId]) ? CD_BANKS[bankId] : null;
            var bName = bankObj ? bankObj.name : (data.bankName || '');

            if (data.payMode === 'credit' || data.paymentType === 'ຕິດໜີ້') {
              LaoTTS.speakPaymentAndChange(spTotal, spTotal, 0, 'credit', tenderCode);
            } else if (isSplit) {
              LaoTTS.speakPaymentAndChange(spTotal, spCash + spQr, spChange, 'split', tenderCode, { cash: spCash, qr: spQr, bank: bName }, bName);
            } else if (isQr) {
              LaoTTS.speakPaymentAndChange(spTotal, spQr || spTotal, 0, true, tenderCode, null, bName);
            } else {
              LaoTTS.speakPaymentAndChange(spTotal, spCash || spTotal, spChange, false, tenderCode);
            }
          }, 80);
        } else if (data.total || data.totalAmount) {
          setTimeout(function() { LaoTTS.speakSale(data.total || data.totalAmount); }, 80);
        }
      }
    }
  } catch(e) {}

  if (_paySuccessTimer) clearTimeout(_paySuccessTimer);
  _paySuccessTimer = setTimeout(function() {
    $overlay.fadeOut(600);
    // ໝາຍເຫດ: ບໍ່ລ້າງ resetDisplayTotals() ທີ່ນີ້, ໃຫ້ຂໍ້ມູນບິນ ແລະ ເງິນທອນຄ້າງໄວ້ເທິງຈໍລູກຄ້າ ຈົນກວ່າແຄັດເຊຍຈະເລີ່ມບິນໃໝ່
  }, 10000);
}

function resetDisplayTotals() {
  $('#displayItemCount').text('0 ' + cdT('cd.lines') + ' / 0 ' + cdT('cd.pieces'));
  ['displaySubtotal', 'displayDiscount', 'displayGrandTotal', 'displayCashReceived', 'displayQrReceived', 'displayChangeAmount'].forEach(function(id) {
    $('#' + id).html(cdFmt(0));
  });
  highlightPayBank(null);
  $('#bankPayPopup').hide();
  $('#displayCartBody').empty();
  $('#displayCartEmpty').show();
  window._currentTbodyFingerprint = '';
  if (window.cdAutoScroll) {
    window.cdAutoScroll._lastFingerprint = '';
    window.cdAutoScroll.resetToTop();
  }

  try {
    var cleanPayload = {
      checkoutState: 'shopping',
      cart: [],
      customer: null,
      discount: '0',
      cashReceived: 0,
      qrReceived: 0,
      changeAmount: 0,
      totalAmount: 0,
      paidAmount: 0
    };
    localStorage.setItem('StoreMini.customer_display_data', JSON.stringify(cleanPayload));
  } catch(e) {}
}

function renderDisplayData(data) {
  if (!data) return;

  // Real-time currency switching from POS cashier selection
  if (data.tenderCurrency && data.tenderCurrency.code) {
    window.CD_CUR = {
      code: data.tenderCurrency.code,
      symbol: data.tenderCurrency.symbol || data.tenderCurrency.code,
      decimals: typeof data.tenderCurrency.decimals === 'number' ? data.tenderCurrency.decimals : 0,
      rate: parseFloat(data.tenderCurrency.rate) || 1,
      isKip: data.tenderCurrency.code === 'KIP'
    };
  } else if (data.checkoutState === 'shopping') {
    window.CD_CUR = Object.assign({}, window.CD_DEFAULT_CUR);
  }

  // ຖ້າມີການສັ່ງລ້າງຈໍລູກຄ້າໂດຍກົງ (ຕົວຢ່າງ: ແຄັດເຊຍກົດປຸ່ມ ລ້າງກະຕ່າ)
  if (data.action === 'clear_cart' || data.action === 'reset_customer_display') {
    window.__currentCdState = 'shopping';
    if (_paySuccessTimer) clearTimeout(_paySuccessTimer);
    $('#paymentSuccessOverlay').hide();
    resetDisplayTotals();
    return;
  }

  // ປ້ອງກັນບໍ່ໃຫ້ broadcast 'payment_live' ຫຼື 'shopping' (ທີ່ກະຕ່າວ່າງ) ມາລ້າງຍອດບິນ ແລະ ເງິນທອນຕອນຊຳລະເງິນສຳເລັດ
  if (window.__currentCdState === 'payment_success') {
    if (data.checkoutState === 'payment_live') {
      return;
    }
    if (data.checkoutState === 'shopping' && (!data.cart || data.cart.length === 0)) {
      return; // ຮັກສາລາຍການທີ່ຂາຍ ແລະ ເງິນທອນໄວ້ເທິງຈໍລູກຄ້າ ຈົນກວ່າຈະມີການຍິງສິນຄ້າບິນໃໝ່
    }
  }

  renderBankPopup(data);

  // Trigger payment success overlay with amount paid
  if (data.checkoutState === 'payment_success') {
    window.__currentCdState = 'payment_success';
    showPaymentSuccessModal(data);
  } else if (data.checkoutState === 'checkout_modal_open' || (data.checkoutState === 'shopping' && data.cart && data.cart.length > 0)) {
    window.__currentCdState = data.checkoutState;
    if (_paySuccessTimer) clearTimeout(_paySuccessTimer);
    $('#paymentSuccessOverlay').hide();
    $('#bankPayPopup').hide();
  } else if (data.checkoutState === 'shopping') {
    window.__currentCdState = 'shopping';
    if (_paySuccessTimer) clearTimeout(_paySuccessTimer);
    $('#paymentSuccessOverlay').hide();
    $('#bankPayPopup').hide();
  }

  // Queue number flash on payment success (if standalone queue flash is desired)
  if (data.checkoutState === 'payment_success' && data.queueNumber && !$('#paymentSuccessOverlay').length) {
    showQueueFlash(data.queueNumber);
  }

  // 1. Cart Table: ອັບເດດສະເພາະເມື່ອລາຍການສິນຄ້າປ່ຽນແປງແທ້ໆ
  // ຖ້າລາຍການຄືເກົ່າ (ເຊັ່ນ: ຕອນກົດໂອນເງິນ, ເລືອກທະນາຄານ ຫຼື ເດັ້ງ QR) ຈະບໍ່ empty() ຕາຕະລາງ
  // ເພື່ອໃຫ້ລາຍການສິນຄ້າສາມາດ Auto-Scroll ເລື່ອນສະໂລຂຶ້ນ-ລົງໄດ້ຢ່າງຕໍ່ເນື່ອງ ບໍ່ສະດຸດ ແລະ ບໍ່ຖືກດຶງກັບໄປເທິງສຸດ
  var tbody = $('#displayCartBody');
  var cart = data.cart || [];
  $('#displayCartEmpty').toggle(cart.length === 0);

  var cartFingerprint = CD_CUR.code + '@' + (cart || []).map(function(it){
    return (it.product_id || it.id || '') + ':' + (it.quantity || 0) + ':' + (it.unit_price || 0) + ':' + (it.item_discount || 0) + ':' + (it.is_free_gift ? 1 : 0);
  }).join('|');

  if (window._currentTbodyFingerprint !== cartFingerprint) {
    window._currentTbodyFingerprint = cartFingerprint;
    tbody.empty();

    if (cart.length > 0) {
      var origIndex = new Map();
      cart.forEach(function(it, n) { origIndex.set(it, n); });
      var orderedCart = cart.slice().sort(function(a, b) {
        var ta = (typeof a._touch === 'number') ? a._touch : origIndex.get(a);
        var tb = (typeof b._touch === 'number') ? b._touch : origIndex.get(b);
        return tb - ta;
      });
      orderedCart.forEach(function(item, i) {
        var idx = origIndex.get(item);
        var isNewest = (i === 0);
        var isGift = !!item.is_free_gift;
        var origUnitPrice = parseFloat(item.original_price || item.unit_price) || 0;
        var curUnitPrice = parseFloat(item.unit_price) || 0;
        var itemDisc = parseFloat(item.item_discount) || 0;
        var hasLineDisc = (itemDisc > 0 || origUnitPrice > curUnitPrice) && !isGift;

        var itemTotal = isGift ? cdMoney(0) : cdMoney(item.quantity * curUnitPrice);
        var unitPriceStr = isGift 
          ? cdMoney(0) 
          : (hasLineDisc && origUnitPrice > curUnitPrice
              ? `<del style="color:#94a3b8; font-size:0.85em; text-decoration:line-through; font-weight:normal;" class="mr-1">${cdMoney(origUnitPrice)}</del><span class="text-danger font-weight-bold">${cdMoney(curUnitPrice)}</span>`
              : cdMoney(curUnitPrice));
        var unitNameStr = item.unit_name ? item.unit_name : cdT('cd.pieces');
        var giftBadgeHtml = isGift ? `<span class="badge badge-success font-weight-bold ml-1" style="font-size:0.82rem; padding:2px 6px; border-radius:4px;"><i class="fas fa-gift mr-1"></i><?php echo tlj('ແຖມຟຣີ'); ?></span>` : '';
        var discBadgeHtml = hasLineDisc 
          ? `<span class="text-danger font-weight-bold ml-1" style="font-size:0.85rem; white-space:nowrap;">-${item.item_discount_text || cdMoney(origUnitPrice - curUnitPrice)}</span>` 
          : '';

        var rawImg = item.image || item.img_url || '';
        var imgName = rawImg ? rawImg.split('/').pop().split('\\').pop() : 'image.jpg';
        var imgSrc = (!imgName || imgName === 'image.jpg') ? '../../assets/img/image.jpg' : '../../assets/product_img/' + imgName;

        tbody.append(`
          <tr class="${isNewest ? 'display-row-newest' : ''}" style="${isGift ? 'background:#f0fdf4;' : ''}">
            <td class="text-center text-muted font-weight-bold" style="text-align: center !important;">${idx + 1}</td>
            <td class="text-center" style="text-align: center !important;">
              <img src="${imgSrc}" alt="${item.product_name}" class="display-pro-img" onerror="this.src='../../assets/img/image.jpg';">
            </td>
            <td class="text-left font-weight-bold text-dark" style="font-size: 1.05rem; text-align: left !important; padding-left: 10px !important;">
              <span>${item.product_name}</span>
              <span class="badge badge-light border text-primary font-weight-bold ml-1" style="font-size:0.82rem; padding:2px 7px; border-radius:4px; vertical-align:middle;">${unitNameStr}</span>
              ${giftBadgeHtml}
              ${discBadgeHtml}
              <small class="text-muted d-block font-weight-normal" style="font-size:0.80rem; ${item.barcode ? '' : 'visibility:hidden;'}">${item.barcode || ' '}</small>
            </td>
            <td class="text-center" style="text-align: center !important;"><span class="badge-qty">x${item.quantity}</span></td>
            <td class="text-center ${isGift ? 'text-success' : 'text-primary'} font-weight-bold" style="white-space: nowrap; text-align: center !important;">${unitPriceStr}</td>
            <td class="text-center text-success font-weight-bold" style="font-size: 1.15rem; white-space: nowrap; text-align: center !important;">${itemTotal}</td>
          </tr>
        `);
      });
    }

    if (window.cdAutoScroll) {
      window.cdAutoScroll._lastFingerprint = cartFingerprint;
      window.cdAutoScroll.resetToTop();
    }
  }

  // 2. Summary Totals Calculation
  var lineCount = cart.length;
  var totalQty = 0;
  var subtotal = 0;
  cart.forEach(function(i) {
    totalQty += i.quantity;
    subtotal += i.quantity * i.unit_price;
  });

  var discountVal = parseFloat((data.discount || '0').toString().replace(/[^\d]/g, '')) || 0;
  var netGrandTotal = Math.max(0, subtotal - discountVal);
  // ຊຳລະສຳເລັດ → ລ້າງຕົວເລກແຖບລຸ່ມທັງໝົດເປັນ 0 (ຍອດລວມ, ສ່ວນຫຼຸດ, ຮັບເງິນ, ເງິນທອນ, ຍອດທີ່ຕ້ອງຊຳລະ).
  // ຍອດທີ່ຈ່າຍ ແລະ ເງິນທອນ ລູກຄ້າຍັງເຫັນໃນໜ້າຕ່າງ "ຊຳລະສຳເລັດ" (showPaymentSuccessModal ອ່ານຈາກ data ເອງ)
  if (data.checkoutState === 'payment_success') {
    lineCount = 0;
    totalQty = 0;
    subtotal = 0;
    discountVal = 0;
    netGrandTotal = 0;
  }

  // Animated number counter helper. targetKip is in KIP; displayed in CD_CUR.
  function animateNumber(elementId, targetKip) {
    var $el = $('#' + elementId);
    var target = CD_CUR.isKip ? Math.round(targetKip) : cdCeil((parseFloat(targetKip) || 0) / CD_CUR.rate);
    var startValue = parseFloat(($el.text() || '').replace(/[^0-9.]/g, '')) || 0;
    if (startValue === target) { $el.html(cdFmt(target)); return; }
    var duration = 400;
    var startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var easeProgress = 1 - Math.pow(1 - progress, 3); // easeOutCubic
      var val = startValue + (target - startValue) * easeProgress;
      $el.html(cdFmt(val));
      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        $el.html(cdFmt(target));
      }
    }
    window.requestAnimationFrame(step);
  }

  $('#displayItemCount').text(lineCount + ' ' + cdT('cd.lines') + ' / ' + totalQty + ' ' + cdT('cd.pieces'));
  animateNumber('displaySubtotal', subtotal);
  animateNumber('displayDiscount', discountVal);
  animateNumber('displayGrandTotal', netGrandTotal);
  if (!CD_CUR.isKip && netGrandTotal > 0) {
    $('#displayGrandTotalKip').html('≈ ' + cdFmtKip(netGrandTotal)).show();
  } else {
    $('#displayGrandTotalKip').hide().empty();
  }

  // 3. Cash, QR, and Change Breakdown
  // ສະແດງຕອນປ້ອນເງິນ (payment_live) ເທົ່ານັ້ນ — ຂາຍສຳເລັດແລ້ວ ລ້າງເປັນ 0 (ເງິນທອນຢູ່ໃນໜ້າຕ່າງ "ຊຳລະສຳເລັດ")
  var isPayActive = (data.checkoutState === 'payment_live');
  var cashRec   = isPayActive ? parseFloat(data.cashReceived || 0) : 0;
  var qrRec     = isPayActive ? parseFloat(data.qrReceived || 0) : 0;
  var changeAmt = isPayActive ? parseFloat(data.changeAmount || 0) : 0;

  animateNumber('displayCashReceived', cashRec);
  animateNumber('displayQrReceived', qrRec);
  animateNumber('displayChangeAmount', changeAmt);

}

// ===== Auto-scroll Controller ສໍາລັບລາຍການສິນຄ້າໃນຈໍລູກຄ້າ (Non-touch Auto Pendulum Scroll) =====
// ເນື່ອງຈາກຈໍລູກຄ້າບໍ່ມີລະບົບສຳພັດ (Non-touch display), ເມື່ອມີສິນຄ້າຫຼາຍເກີນຂອບເຂດໜ້າຈໍ
// ລະບົບຈະຄ່ອຍໆເລື່ອນລົງ ແລະ ເລື່ອນຂຶ້ນຊ້າໆ (Slow Loop) ແບບສະໂລ ເພື່ອໃຫ້ລູກຄ້າສາມາດເຫັນໄດ້ຄົບທຸກລາຍການ
window.cdAutoScroll = {
  rafId: null,
  container: null,
  lastTime: null,
  currentY: 0,
  direction: 1, // 1 = scroll down, -1 = scroll up
  speed: 38, // pixels per second (ຄວາມໄວສະໂລກຳລັງດີ ເຫັນການເລື່ອນຊັດເຈນ)
  pauseUntil: 0,
  userPauseUntil: 0,
  topPauseMs: 1800, // ພັກຢູ່ເທິງສຸດ 1.8 ວິນາທີ ແລ້ວເລີ່ມເລື່ອນລົງທັນທີ
  bottomPauseMs: 2200, // ພັກຢູ່ລຸ່ມສຸດ 2.2 ວິນາທີ ເພື່ອໃຫ້ລູກຄ້າເຫັນລາຍການລຸ່ມສຸດ
  userInteractedPauseMs: 3500, // ຖ້າມີການແຕະ ຫຼື ເລື່ອນເມົ້າ ໃຫ້ຢຸດຊົ່ວຄາວ
  _lastFingerprint: '',

  init: function() {
    this.container = document.querySelector('.display-table-body');
    if (!this.container) return;
    var self = this;

    // ດັກຈັບຖ້າມີການເລື່ອນດ້ວຍເມົ້າ ຫຼື ສໍາພັດ
    var handleUserInteraction = function() {
      self.userPauseUntil = performance.now() + self.userInteractedPauseMs;
      if (self.container) {
        self.currentY = self.container.scrollTop;
      }
    };
    this.container.addEventListener('wheel', handleUserInteraction, { passive: true });
    this.container.addEventListener('pointerdown', handleUserInteraction, { passive: true });
    this.container.addEventListener('touchstart', handleUserInteraction, { passive: true });

    this.start();
  },

  resetToTop: function() {
    if (!this.container) {
      this.container = document.querySelector('.display-table-body');
      if (!this.container) return;
    }
    this.container.scrollTop = 0;
    this.currentY = 0;
    this.direction = 1;
    this.pauseUntil = performance.now() + this.topPauseMs;
  },

  start: function() {
    if (this.rafId) cancelAnimationFrame(this.rafId);
    this.lastTime = performance.now();
    this.pauseUntil = performance.now() + this.topPauseMs;
    var self = this;

    var step = function(timestamp) {
      if (!self.container) {
        self.container = document.querySelector('.display-table-body');
      }
      if (!self.container) {
        self.rafId = requestAnimationFrame(step);
        return;
      }

      var dt = (timestamp - (self.lastTime || timestamp)) / 1000;
      self.lastTime = timestamp;
      if (dt > 0.1) dt = 0.1; // ປ້ອງກັນການກະໂດດເມື່ອສະຫຼັບແທັບ

      var maxScroll = self.container.scrollHeight - self.container.clientHeight;

      // ຖ້າລາຍການບໍ່ເກີນໜ້າຈໍ ບໍ່ຕ້ອງເລື່ອນ (ຢູ່ເທິງສຸດ)
      if (maxScroll <= 10) {
        if (self.container.scrollTop !== 0) {
          self.container.scrollTop = 0;
          self.currentY = 0;
        }
        self.direction = 1;
        self.rafId = requestAnimationFrame(step);
        return;
      }

      // ຖ້າກຳລັງຢູ່ໃນຊ່ວງເວລາພັກ (ເທິງ/ລຸ່ມ) ຫຼື ຜູ້ໃຊ້ກຳລັງແຕະເລື່ອນ
      if (timestamp < self.pauseUntil || timestamp < self.userPauseUntil) {
        self.rafId = requestAnimationFrame(step);
        return;
      }

      // Sync sub-pixel position if manually scrolled
      if (Math.abs(self.container.scrollTop - Math.round(self.currentY)) > 8) {
        self.currentY = self.container.scrollTop;
      }

      // ເລື່ອນຕາມທິດທາງ (ລົງ ຫຼື ຂຶ້ນ) ໂດຍເກັບຄ່າທົດສະນິຍົມໄວ້ໃນ currentY ເພື່ອບໍ່ໃຫ້ scrollTop ປັດເສດເປັນ 0
      var moveBy = self.speed * dt;

      if (self.direction === 1) {
        // ກຳລັງຄ່ອຍໆເລື່ອນລົງ
        self.currentY += moveBy;
        if (self.currentY >= maxScroll - 0.5) {
          self.currentY = maxScroll;
          self.container.scrollTop = maxScroll;
          self.direction = -1; // ປ່ຽນທິດທາງເປັນເລື່ອນຂຶ້ນ
          self.pauseUntil = timestamp + self.bottomPauseMs;
        } else {
          self.container.scrollTop = Math.round(self.currentY);
        }
      } else {
        // ກຳລັງຄ່ອຍໆເລື່ອນຂຶ້ນ
        self.currentY -= moveBy;
        if (self.currentY <= 0.5) {
          self.currentY = 0;
          self.container.scrollTop = 0;
          self.direction = 1; // ປ່ຽນທິດທາງເປັນເລື່ອນລົງ
          self.pauseUntil = timestamp + self.topPauseMs;
        } else {
          self.container.scrollTop = Math.round(self.currentY);
        }
      }

      self.rafId = requestAnimationFrame(step);
    };

    this.rafId = requestAnimationFrame(step);
  }
};

window.cdAutoScroll.init();
$(function() {
  if (!window.cdAutoScroll.rafId) window.cdAutoScroll.init();
});
</script>
