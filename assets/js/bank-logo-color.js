/*
 * bank-logo-color.js — ສີກາດທະນາຄານ ຈັບຈາກສີໂລໂກ້ຂອງທະນາຄານນັ້ນເອງ
 * (pages/bank/partials/bank_accounts_tab.php). ອ່ານຈຸດສີຂອງໂລໂກ້ຜ່ານ canvas, ຂ້າມສີຂາວ/ດຳ/ເທົາ,
 * ເລືອກໂທນສີທີ່ມີຫຼາຍທີ່ສຸດ ແລ້ວປັບໃຫ້ເຂັ້ມພໍ ໃຫ້ໂຕໜັງສືສີຂາວອ່ານອອກ.
 * ໂລໂກ້ບໍ່ມີສີ (ຂາວ-ດຳລ້ວນ) ຫຼື ອ່ານບໍ່ໄດ້ → ໃຊ້ສີເດີມທີ່ PHP ຕັ້ງໄວ້ (getBankBrandStyle).
 *
 * Markup: .bank-brand-card ທີ່ມີ img.bank-brand-logo; ສ່ວນທີ່ປ່ຽນສີນຳ: .bank-brand-acc (ເລກບັນຊີ),
 * .bank-brand-badge (ຈຳນວນບິນ).
 */
(function () {
  'use strict';

  function rgbToHsl(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, h = 0, s = 0, d = max - min;
    if (d) {
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
      h *= 60;
    }
    return [h, s * 100, l * 100];
  }
  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

  // ສີຫຼັກຂອງໂລໂກ້ → [h, s, l] ຫຼື null
  function logoColor(img) {
    var N = 64, c = document.createElement('canvas');
    c.width = N; c.height = N;
    var ctx = c.getContext('2d');
    var k = Math.min(N / img.naturalWidth, N / img.naturalHeight);
    var w = Math.max(1, Math.round(img.naturalWidth * k)), h = Math.max(1, Math.round(img.naturalHeight * k));
    ctx.drawImage(img, (N - w) / 2, (N - h) / 2, w, h);
    var px;
    try { px = ctx.getImageData(0, 0, N, N).data; } catch (e) { return null; }   // ຮູບຈາກເວັບອື່ນ → ອ່ານບໍ່ໄດ້

    var buckets = [];   // 36 ໂທນ (10° ຕໍ່ໂທນ)
    for (var i = 0; i < px.length; i += 4) {
      if (px[i + 3] < 200) continue;                    // ໂປ່ງໃສ
      var hsl = rgbToHsl(px[i], px[i + 1], px[i + 2]);
      if (hsl[2] > 92 || hsl[2] < 8 || hsl[1] < 25) continue;   // ຂາວ / ດຳ / ເທົາ
      var bi = Math.floor(hsl[0] / 10) % 36, wgt = hsl[1] / 100;
      var bk = buckets[bi] || (buckets[bi] = { w: 0, r: 0, g: 0, b: 0, n: 0 });
      bk.w += wgt; bk.r += px[i] * wgt; bk.g += px[i + 1] * wgt; bk.b += px[i + 2] * wgt; bk.n++;
    }
    // ລວມໂທນທີ່ຕິດກັນ (ສີແດງທີ່ຢູ່ຮອຍຕໍ່ 350°/0° ກໍນັບເປັນກຸ່ມດຽວ)
    var best = null, bestW = 0;
    for (var j = 0; j < 36; j++) {
      var a = buckets[(j + 35) % 36], m = buckets[j], z = buckets[(j + 1) % 36];
      if (!m) continue;
      var sw = m.w + (a ? a.w * 0.5 : 0) + (z ? z.w * 0.5 : 0);
      if (sw > bestW) { bestW = sw; best = m; }
    }
    if (!best || best.n < 12) return null;               // ສີໜ້ອຍເກີນ (ໂລໂກ້ເກືອບບໍ່ມີສີ)
    return rgbToHsl(best.r / best.w, best.g / best.w, best.b / best.w);
  }

  function paint(card, img) {
    var hsl = logoColor(img);
    if (!hsl) return;
    var h = Math.round(hsl[0]), s = Math.round(clamp(hsl[1], 45, 85));
    var l = Math.round(clamp(hsl[2], 30, 44));           // ເຂັ້ມພໍໃຫ້ໂຕໜັງສືຂາວອ່ານອອກ (ສີເຫຼືອງ/ຟ້າອ່ອນ ຖືກເຮັດໃຫ້ເຂັ້ມລົງ)
    var dark = Math.max(12, l - 16);
    card.style.background = 'linear-gradient(135deg, hsl(' + h + ',' + s + '%,' + l + '%) 0%, hsl(' + h + ',' + s + '%,' + dark + '%) 100%)';
    card.querySelectorAll('.bank-brand-badge').forEach(function (el) { el.style.backgroundColor = 'hsl(' + h + ',' + s + '%,' + Math.max(10, l - 12) + '%)'; });
    card.querySelectorAll('.bank-brand-acc').forEach(function (el) { el.style.color = 'hsl(' + h + ',90%,86%)'; });
  }

  function run() {
    document.querySelectorAll('.bank-brand-card').forEach(function (card) {
      var img = card.querySelector('img.bank-brand-logo');
      if (!img || !img.getAttribute('src')) return;
      if (img.complete && img.naturalWidth) paint(card, img);
      else img.addEventListener('load', function () { paint(card, img); }, { once: true });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();
