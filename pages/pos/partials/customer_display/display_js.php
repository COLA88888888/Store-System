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
      renderDisplayData(JSON.parse(raw));
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
function renderBankPopup(data) {
  var $pop = $('#bankPayPopup');
  var b = (data && data.checkoutState === 'payment_live') ? data.payBank : null;
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

function renderDisplayData(data) {
  if (!data) return;
  renderBankPopup(data);

  // Queue number flash on payment success (coffee-shop style)
  if (data.checkoutState === 'payment_success' && data.queueNumber) {
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
      var itemTotal = isGift ? cdMoney(0) : cdMoney(item.quantity * item.unit_price);
      var unitPriceStr = isGift ? cdMoney(0) : cdMoney(item.unit_price);
      var unitNameStr = item.unit_name ? item.unit_name : 'ອັນ';
      var giftBadgeHtml = isGift ? `<span class="badge badge-success font-weight-bold ml-1" style="font-size:0.82rem; padding:2px 6px; border-radius:4px;"><i class="fas fa-gift mr-1"></i>ແຖມຟຣີ</span>` : '';

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
            ${item.product_name} ${giftBadgeHtml}
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
  var cashRec = parseFloat(data.cashReceived || 0);
  var qrRec = parseFloat(data.qrReceived || 0);
  var changeAmt = parseFloat(data.changeAmount || 0);

  animateNumber('displayCashReceived', cashRec);
  animateNumber('displayQrReceived', qrRec);
  animateNumber('displayChangeAmount', changeAmt);
}
</script>
