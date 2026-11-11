/**
 * ເອັບເຟັກພື້ນຫຼັງ hero ໜ້າຫຼັກ ([data-logo-net] ໃນ components/hero.php) — ຄື Vanta.js "NET" ແຕ່ຈຸດເປັນໂລໂກ້ບໍລິສັດນ້ອຍໆ:
 *   - ໂລໂກ້ລອຍຊ້າໆ ສະທ້ອນກັບຂອບ, ອຽງໄປມາເລັກນ້ອຍ
 *   - ໂລໂກ້ທີ່ຢູ່ໃກ້ກັນ ມີເສັ້ນບາງເຊື່ອມ (ຍິ່ງໃກ້ ຍິ່ງເຂັ້ມ), ເມົາກໍເປັນຈຸດເຊື່ອມນຳ
 *   - ໂລໂກ້ໃກ້ເມົາ ຖືກຍູ້ອອກເບົາໆ
 * Canvas 2D ລ້ວນ (ບໍ່ໃຊ້ three.js / Vanta). ຢຸດເມື່ອເລື່ອນພົ້ນຈໍ ຫຼື ແທັບບໍ່ເປີດ; prefers-reduced-motion = ວາງນິ່ງ.
 * ຈຳນວນໂລໂກ້ຕາມຂະໜາດພື້ນທີ່ (ຈໍໃຫຍ່ ~28, ມືຖື ~12).
 */
(function () {
  var canvas = document.querySelector('[data-logo-net]');
  if (!canvas || !canvas.getContext) return;
  var ctx = canvas.getContext('2d');
  var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var logo = new Image();
  var dpr = 1, W = 0, H = 0, nodes = [], mouse = { x: -9999, y: -9999 }, running = false, visible = true, raf = 0;
  var LINK = 150;            // ໄລຍະເຊື່ອມເສັ້ນ (px)
  var LOGO_RATIO = 268 / 404; // ສູງ / ກວ້າງ ຂອງ logo.png

  function size() {
    var r = canvas.getBoundingClientRect();
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = r.width; H = r.height;
    canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    var want = Math.max(10, Math.min(30, Math.round(W * H / 26000)));
    while (nodes.length < want) nodes.push(make());
    nodes.length = want;
    nodes.forEach(function (n) { n.x = Math.min(n.x, W); n.y = Math.min(n.y, H); });
  }
  function make() {
    var w = 22 + Math.random() * 22;              // ກວ້າງ 22–44px
    return {
      x: Math.random() * (W || 1), y: Math.random() * (H || 1),
      vx: (Math.random() - .5) * .35, vy: (Math.random() - .5) * .35,
      w: w, a: .28 + Math.random() * .32,           // ຄວາມເຂັ້ມ
      rot: (Math.random() - .5) * .5, vr: (Math.random() - .5) * .002
    };
  }

  function step() {
    for (var i = 0; i < nodes.length; i++) {
      var n = nodes[i];
      var dx = n.x - mouse.x, dy = n.y - mouse.y, d2 = dx * dx + dy * dy;
      if (d2 < 120 * 120 && d2 > 1) {               // ໜີເມົາເບົາໆ
        var f = (1 - Math.sqrt(d2) / 120) * .6;
        n.vx += dx / Math.sqrt(d2) * f * .08; n.vy += dy / Math.sqrt(d2) * f * .08;
      }
      n.vx *= .995; n.vy *= .995;                    // ຫຼຸດຄວາມໄວທີ່ໄດ້ຈາກເມົາຄືນຊ້າໆ
      var sp = Math.hypot(n.vx, n.vy);
      if (sp < .12) { n.vx *= 1.02; n.vy *= 1.02; }
      if (sp > 1.4) { n.vx *= .9; n.vy *= .9; }
      n.x += n.vx; n.y += n.vy; n.rot += n.vr;
      if (n.x < 0 || n.x > W) { n.vx *= -1; n.x = Math.max(0, Math.min(W, n.x)); }
      if (n.y < 0 || n.y > H) { n.vy *= -1; n.y = Math.max(0, Math.min(H, n.y)); }
    }
  }

  function draw() {
    ctx.clearRect(0, 0, W, H);
    // ເສັ້ນເຊື່ອມ (ລະຫວ່າງໂລໂກ້ ແລະ ກັບເມົາ)
    var pts = nodes.concat(mouse.x > -1000 ? [{ x: mouse.x, y: mouse.y, mouse: true }] : []);
    ctx.lineWidth = 1;
    for (var i = 0; i < pts.length; i++) {
      for (var j = i + 1; j < pts.length; j++) {
        var dx = pts[i].x - pts[j].x, dy = pts[i].y - pts[j].y, d = Math.sqrt(dx * dx + dy * dy);
        var max = (pts[i].mouse || pts[j].mouse) ? LINK * 1.3 : LINK;
        if (d < max) {
          ctx.strokeStyle = 'rgba(29, 78, 216,' + ((1 - d / max) * .28).toFixed(3) + ')';
          ctx.beginPath(); ctx.moveTo(pts[i].x, pts[i].y); ctx.lineTo(pts[j].x, pts[j].y); ctx.stroke();
        }
      }
    }
    // ໂລໂກ້
    if (logo.complete && logo.naturalWidth) {
      nodes.forEach(function (n) {
        var h = n.w * LOGO_RATIO;
        ctx.save();
        ctx.globalAlpha = n.a;
        ctx.translate(n.x, n.y); ctx.rotate(n.rot);
        ctx.drawImage(logo, -n.w / 2, -h / 2, n.w, h);
        ctx.restore();
      });
    }
  }

  function loop() {
    if (!running) return;
    step(); draw();
    raf = requestAnimationFrame(loop);
  }
  function start() { if (!running && visible && !document.hidden && !still) { running = true; raf = requestAnimationFrame(loop); } }
  function stop() { running = false; cancelAnimationFrame(raf); }

  var host = canvas.parentElement;
  host.addEventListener('pointermove', function (e) {
    var r = canvas.getBoundingClientRect();
    mouse.x = e.clientX - r.left; mouse.y = e.clientY - r.top;
  }, { passive: true });
  host.addEventListener('pointerleave', function () { mouse.x = mouse.y = -9999; });
  window.addEventListener('resize', function () { size(); if (still) draw(); });
  document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (en) { visible = en[0].isIntersecting; visible ? start() : stop(); }).observe(canvas);
  }

  logo.onload = function () { size(); draw(); start(); };
  logo.src = canvas.getAttribute('data-logo');
})();
