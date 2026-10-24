<?php
/*
 * ສ່ວນ: JS ຂອງຈໍລູກຄ້າ
 * ສະກຸນເງິນ, ເຕັມຈໍ, ໂມງ, ໝາຍເລກຄິວ, ຟັງຂໍ້ມູນຈາກໜ້າຂາຍ ແລະ renderDisplayData
 * (ແຕ້ມລາຍການ/ຍອດເງິນ/QR).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/customer_display.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
// ===== Display currency (cart data arrives in KIP; shown in the active currency) =====
<?php
  // $cd ກຳນົດໄວ້ແລ້ວໃນ pages/pos/customer_display.php (ກ່ອນ page_body)
  if (!isset($cd)) {
    $cd = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo)
        : ['code' => 'KIP', 'symbol' => '₭', 'decimals' => 0, 'rate' => 1];
  }
?>
window.CD_CUR = {
  code: <?php echo json_encode($cd['code']); ?>,
  symbol: <?php echo json_encode($cd['symbol'], JSON_UNESCAPED_UNICODE); ?>,
  decimals: <?php echo (int)$cd['decimals']; ?>,
  rate: <?php echo json_encode((float)$cd['rate']); ?>,
  isKip: <?php echo ($cd['code'] === 'KIP') ? 'true' : 'false'; ?>
};
function cdFmt(v) {
  v = parseFloat(v) || 0;
  return v.toLocaleString('en-US', { minimumFractionDigits: CD_CUR.decimals, maximumFractionDigits: CD_CUR.decimals }) + ' <span class="cur-sym">' + CD_CUR.symbol + '</span>';
}
// kip -> formatted display string
function cdMoney(kip) {
  kip = parseFloat(kip) || 0;
  return cdFmt(CD_CUR.isKip ? Math.round(kip) : (kip / CD_CUR.rate));
}

// Fullscreen: try automatically on load (works when opened via a user click), and
// always offer the manual toggle button since browsers can silently block the auto request.
function toggleFullscreen() {
  if (!document.fullscreenElement) {
    (document.documentElement.requestFullscreen && document.documentElement.requestFullscreen())?.catch(function(){});
  } else {
    document.exitFullscreen && document.exitFullscreen().catch(function(){});
  }
}
document.getElementById('fullscreenToggleBtn').addEventListener('click', toggleFullscreen);
document.addEventListener('fullscreenchange', function() {
  var icon = document.getElementById('fullscreenToggleIcon');
  icon.className = document.fullscreenElement ? 'fas fa-compress' : 'fas fa-expand';
});
function enterFullscreen() {
  if (document.fullscreenElement || !document.documentElement.requestFullscreen) return;
  try { document.documentElement.requestFullscreen({ navigationUI: 'hide' }).catch(function(){}); } catch(e) {}
}
// ລອງເຕັມຈໍທັນທີຕອນເປີດ — Chrome ອະນຸຍາດສະເພາະເມື່ອເປີດ "ເຕັມຈໍອັດຕະໂນມັດ" ໃຫ້ເວັບນີ້ແລ້ວ
// (ການກົດປຸ່ມຈໍລູກຄ້າໃນໜ້າຂາຍ ຖືກໃຊ້ເປີດໜ້າຕ່າງໄປແລ້ວ ໃຊ້ຊ້ຳສັ່ງເຕັມຈໍບໍ່ໄດ້).
enterFullscreen();
window.addEventListener('load', enterFullscreen);
// ບໍ່ດັ່ງນັ້ນ: ແຕະ/ຄລິກບ່ອນໃດກໍ່ໄດ້ ເທື່ອດຽວ → ເຕັມຈໍທັນທີ (ປຸ່ມ ⛶ ມຸມຂວາ ຍັງໃຊ້ສະຫຼັບໄດ້ຄືເກົ່າ)
['pointerdown', 'touchstart', 'keydown'].forEach(function(ev) {
  document.addEventListener(ev, function(e) {
    if (e.target && e.target.closest && e.target.closest('#fullscreenToggleBtn')) return;
    enterFullscreen();
  }, true);
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

// Live Clock
function updateClock() {
  var now = new Date();
  $('#liveClock').text(now.toLocaleTimeString('lo-LA'));
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
    if (e.data) renderDisplayData(e.data);
  };
} catch(e) {}

window.addEventListener('storage', function(e) {
  if (e.key === 'StoreMini.customer_display_data') {
    try {
      renderDisplayData(JSON.parse(e.newValue));
    } catch(err) {}
  }
});

function loadInitialData() {
  var raw = localStorage.getItem('StoreMini.customer_display_data');
  if (raw) {
    try {
      var d = JSON.parse(raw);
      // ຖ້າຂໍ້ມູນເກົ່າທີ່ບັນທຶກໄວ້ເປັນ payment_success ທີ່ຈົບໄປແລ້ວ: ໃຫ້ປັບເປັນ shopping (ລ້າງຄ່າເປັນ 0 ທັງໝົດ)
      // ເພື່ອບໍ່ໃຫ້ຕອນຣີເຟຣຊໜ້າຈໍແລ້ວມີຄ່າເກົ່າຄ້າງ ຫຼື ເດັ້ງປັອບອັບໃບບິນເກົ່າຂຶ້ນມາຊ້ຳ
      if (d && d.checkoutState === 'payment_success') {
        d.checkoutState = 'shopping';
        d.cart = [];
        d.cashReceived = 0;
        d.qrReceived = 0;
        d.changeAmount = 0;
        d.totalAmount = 0;
        d.paidAmount = 0;
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
  $('#bppAmount').html(cdMoney(data.transferDue || data.qrReceived || 0));
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

// Realistic Cash Register / Money Received Sound ("Cha-ching! 💰") via Web Audio API
function playMoneyReceivedSound() {
  try {
    var ctx = getAudioContext();
    if (!ctx) return;
    var now = ctx.currentTime;

    var masterGain = ctx.createGain();
    masterGain.gain.setValueAtTime(0.72, now);
    masterGain.connect(ctx.destination);

    // 1. Mechanical cash drawer "Ka-thunk / click"
    var clickOsc = ctx.createOscillator();
    var clickGain = ctx.createGain();
    clickOsc.type = 'triangle';
    clickOsc.frequency.setValueAtTime(320, now);
    clickOsc.frequency.exponentialRampToValueAtTime(140, now + 0.04);
    clickGain.gain.setValueAtTime(0.45, now);
    clickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);
    clickOsc.connect(clickGain);
    clickGain.connect(masterGain);
    clickOsc.start(now);
    clickOsc.stop(now + 0.05);

    // 2. Loud, resonant cash register bells ("Ching!")
    var bellStart = now + 0.035;
    var bellFreqs = [2093.0, 2793.8, 3135.9, 4186.0];
    bellFreqs.forEach(function(freq, idx) {
      var bellOsc = ctx.createOscillator();
      var bellGain = ctx.createGain();
      bellOsc.type = 'sine';
      bellOsc.frequency.setValueAtTime(freq, bellStart);
      var initGain = (idx === 0) ? 0.38 : (0.24 / idx);
      bellGain.gain.setValueAtTime(initGain, bellStart);
      bellGain.gain.exponentialRampToValueAtTime(0.0001, bellStart + 0.95);
      bellOsc.connect(bellGain);
      bellGain.connect(masterGain);
      bellOsc.start(bellStart);
      bellOsc.stop(bellStart + 1.0);
    });

    // 3. Coins dropping / clinking inside the drawer
    var coinDelays = [0.08, 0.16, 0.24, 0.32];
    var coinFreqs = [3500, 4200, 3900, 4800];
    coinDelays.forEach(function(delay, i) {
      var coinOsc = ctx.createOscillator();
      var coinGain = ctx.createGain();
      coinOsc.type = 'sine';
      coinOsc.frequency.setValueAtTime(coinFreqs[i], now + delay);
      coinGain.gain.setValueAtTime(0.20, now + delay);
      coinGain.gain.exponentialRampToValueAtTime(0.001, now + delay + 0.14);
      coinOsc.connect(coinGain);
      coinGain.connect(masterGain);
      coinOsc.start(now + delay);
      coinOsc.stop(now + delay + 0.15);
    });
  } catch(e) {}
}

var _paySuccessTimer = null;
function showPaymentSuccessModal(data) {
  var $overlay = $('#paymentSuccessOverlay');
  if (!$overlay.length) return;

  var total = parseFloat(data.totalAmount || data.paidAmount || 0);
  $('#paySuccessAmount').html(cdMoney(total));

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
    $('#paySuccessMethodText').text('ຊຳລະດ້ວຍ: ແຍກຈ່າຍ (ເງິນສົດ + ໂອນ)');
  } else if (isTransfer) {
    $('#paySuccessMethodIcon').html('<i class="fas fa-qrcode text-primary"></i>');
    $('#paySuccessMethodText').text('ຊຳລະດ້ວຍ: ໂອນຜ່ານທະນາຄານ');
  } else {
    $('#paySuccessMethodIcon').html('<i class="fas fa-money-bill-wave text-success"></i>');
    $('#paySuccessMethodText').text('ຊຳລະດ້ວຍ: ເງິນສົດ');
  }

  // Bank Info section in badge
  if ((isTransfer || isSplit) && (bankName || bankLogo)) {
    if (bankLogo) {
      $('#paySuccessBankLogo').attr('src', bankLogo).show();
    } else {
      $('#paySuccessBankLogo').hide();
    }
    $('#paySuccessBankName').text(bankName ? ('ທະນາຄານ ' + bankName) : '').show();
    $('#paySuccessBankWrap').css('display', 'flex');
  } else {
    $('#paySuccessBankWrap').hide();
  }

  // Detailed Breakdown rows
  // 1. Cash row
  if (cash > 0 || (!isTransfer && !isSplit)) {
    $('#paySuccessCashAmt').html(cdMoney(cash > 0 ? cash : total));
    $('#paySuccessCashRow').show();
  } else {
    $('#paySuccessCashRow').hide();
  }

  // 2. QR Transfer row
  if (qr > 0 || isTransfer) {
    var qrAmtVal = qr > 0 ? qr : total;
    $('#paySuccessQrAmt').html(cdMoney(qrAmtVal));
    $('#paySuccessQrBankInline').text(bankName ? ('(' + bankName + ')') : '');
    $('#paySuccessQrRow').show();
  } else {
    $('#paySuccessQrRow').hide();
  }

  // 3. Change row
  $('#paySuccessChange').html(cdMoney(change));
  $('#paySuccessChangeRow').show();

  // Invoice Number
  if (data.invoiceNumber) {
    $('#paySuccessInvoiceNo').text('ເລກທີໃບບິນ: ' + data.invoiceNumber).show();
  } else {
    $('#paySuccessInvoiceNo').text('ເລກທີໃບບິນ: -').show();
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

  if (_paySuccessTimer) clearTimeout(_paySuccessTimer);
  _paySuccessTimer = setTimeout(function() {
    $overlay.fadeOut(600, function() {
      resetDisplayTotals();
    });
  }, 6000);
}

function resetDisplayTotals() {
  $('#displayItemCount').text('0 ລາຍການ / 0 ອັນ');
  ['displaySubtotal', 'displayDiscount', 'displayGrandTotal', 'displayCashReceived', 'displayQrReceived', 'displayChangeAmount'].forEach(function(id) {
    $('#' + id).html(cdFmt(0));
  });
  highlightPayBank(null);
  $('#bankPayPopup').hide();
  $('#displayCartBody').empty();
  $('#displayCartEmpty').show();

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
  renderBankPopup(data);

  // Trigger payment success overlay with amount paid
  if (data.checkoutState === 'payment_success') {
    showPaymentSuccessModal(data);
  } else if (data.checkoutState === 'checkout_modal_open') {
    $('#paymentSuccessOverlay').hide();
  } else if (data.checkoutState === 'shopping' && data.cart && data.cart.length > 0) {
    if (_paySuccessTimer) clearTimeout(_paySuccessTimer);
    $('#paymentSuccessOverlay').hide();
  }

  // Queue number flash on payment success (if standalone queue flash is desired)
  if (data.checkoutState === 'payment_success' && data.queueNumber && !$('#paymentSuccessOverlay').length) {
    showQueueFlash(data.queueNumber);
  }

  // 1. Cart Table (Enlarged Image, Name, Unit, Qty, Price, Total)
  var tbody = $('#displayCartBody');
  tbody.empty();

  var cart = data.cart || [];
  $('#displayCartEmpty').toggle(cart.length === 0);
  if (cart.length === 0) {
    // Nothing to render — #displayCartEmpty (shown above) carries the message.
  } else {
    // Render newest first: the most recently scanned/tapped item shows at the top and
    // is highlighted. Prefer the "_touch" stamp set by the POS cart; fall back to the
    // cart's own order. The "ລຳດັບ" number still reflects the original add order.
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
      var unitNameStr = item.unit_name ? item.unit_name : 'ອັນ';
      var giftBadgeHtml = isGift ? `<span class="badge badge-success font-weight-bold ml-1" style="font-size:0.82rem; padding:2px 6px; border-radius:4px;"><i class="fas fa-gift mr-1"></i>ແຖມຟຣີ</span>` : '';
      var discBadgeHtml = hasLineDisc 
        ? `<span class="badge badge-danger font-weight-bold ml-1" style="font-size:0.80rem; padding:2px 6px; border-radius:4px;"><i class="fas fa-tag mr-1"></i>-${item.item_discount_text || cdMoney(origUnitPrice - curUnitPrice)}</span>` 
        : '';

      var rawImg = item.image || item.img_url || '';
      var imgName = rawImg ? rawImg.split('/').pop().split('\\').pop() : 'image.jpg';
      var imgSrc = (!imgName || imgName === 'image.jpg') ? '../../assets/img/image.jpg' : '../../assets/product_img/' + imgName;

      tbody.append(`
        <tr class="${isNewest ? 'display-row-newest' : ''}" style="${isGift ? 'background:#f0fdf4;' : ''}">
          <td class="text-center text-muted font-weight-bold">${idx + 1}</td>
          <td class="text-center">
            <img src="${imgSrc}" alt="${item.product_name}" class="display-pro-img" onerror="this.src='../../assets/img/image.jpg';">
          </td>
          <td class="font-weight-bold text-dark" style="font-size: 1.1rem;">
            ${item.product_name} ${giftBadgeHtml} ${discBadgeHtml}
            <small class="text-muted d-block font-weight-normal" style="font-size:0.80rem; ${item.barcode ? '' : 'visibility:hidden;'}">${item.barcode || ' '}</small>
          </td>
          <td class="text-center font-weight-bold text-primary">${unitNameStr}</td>
          <td class="text-center"><span class="badge-qty">x${item.quantity}</span></td>
          <td class="text-right ${isGift ? 'text-success' : 'text-primary'} font-weight-bold" style="white-space: nowrap;">${unitPriceStr}</td>
          <td class="text-right text-success font-weight-bold" style="font-size: 1.15rem; white-space: nowrap;">${itemTotal}</td>
        </tr>
      `);
    });
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

  // Animated number counter helper. targetKip is in KIP; displayed in CD_CUR.
  function animateNumber(elementId, targetKip) {
    var $el = $('#' + elementId);
    var target = CD_CUR.isKip ? Math.round(targetKip) : (parseFloat(targetKip) || 0) / CD_CUR.rate;
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

  $('#displayItemCount').text(lineCount + ' ລາຍການ / ' + totalQty + ' ອັນ');
  animateNumber('displaySubtotal', subtotal);
  animateNumber('displayDiscount', discountVal);
  animateNumber('displayGrandTotal', netGrandTotal);

  // 3. Cash, QR, and Change Breakdown
  // ຄ່າເຫຼົ່ານີ້ສະແດງສະເພາະຕອນພະນັກງານກຳລັງປ້ອນເງິນໃນໜ້າຕ່າງຊຳລະ (payment_live).
  // ເມື່ອຊຳລະສຳເລັດ (payment_success) ຫຼື ຢູ່ໜ້າຂາຍປົກກະຕິ (shopping) ຕ້ອງລ້າງເປັນ 0 ບໍ່ໃຫ້ຄ້າງ.
  var isPayLive = (data.checkoutState === 'payment_live');
  var cashRec   = isPayLive ? parseFloat(data.cashReceived || 0) : 0;
  var qrRec     = isPayLive ? parseFloat(data.qrReceived || 0) : 0;
  var changeAmt = isPayLive ? parseFloat(data.changeAmount || 0) : 0;

  animateNumber('displayCashReceived', cashRec);
  animateNumber('displayQrReceived', qrRec);
  animateNumber('displayChangeAmount', changeAmt);

}
</script>
