/**
 * ເບິ່ງຮູບເຕັມຈໍ (components/gallery_viewer.php): window.galleryViewerOpen(index)
 *   ຮູບ = .thumb-btn[data-img] ໃນ #solutionGallery (ຫຼື #galleryMainImg ຖ້າມີຮູບດຽວ)
 *   ປິດແລ້ວ ແກເລີຣີໃນໜ້າໄປຮູບດຽວກັນ (window.galleryViewerOnClose ຕັ້ງໂດຍ assets/js/main.js)
 */
(function () {
  var gv = document.getElementById('galleryViewer');
  if (!gv) return;
  var img = gv.querySelector('[data-gv-img]'), stage = gv.querySelector('[data-gv-stage]');
  var countEl = gv.querySelector('[data-gv-count]'), capEl = gv.querySelector('[data-gv-cap]'), strip = gv.querySelector('[data-gv-thumbs]');
  var items = [], idx = 0, scale = 1, tx = 0, ty = 0, timer = null;
  var MIN = 1, MAX = 4, STEP = 0.5;

  function collect() {
    var g = document.getElementById('solutionGallery');
    var btns = g ? g.querySelectorAll('.thumb-btn[data-img]') : [];
    items = Array.prototype.map.call(btns, function (b) {
      var t = b.querySelector('img');
      return { src: b.getAttribute('data-img'), thumb: t ? t.src : b.getAttribute('data-img'), cap: b.getAttribute('data-caption') || '' };
    });
    if (!items.length) {
      var m = document.getElementById('galleryMainImg');
      if (m) items = [{ src: m.src, thumb: m.src, cap: '' }];
    }
    strip.innerHTML = '';
    items.forEach(function (it, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', (i + 1) + ' / ' + items.length);
      var t = document.createElement('img');
      t.src = it.thumb; t.alt = ''; t.loading = 'lazy';
      b.appendChild(t);
      b.addEventListener('click', function () { show(i); });
      strip.appendChild(b);
    });
    gv.classList.toggle('is-single', items.length < 2);
  }

  function apply() {
    img.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')';
    gv.classList.toggle('is-zoomed', scale > 1);
  }
  function resetZoom() { scale = 1; tx = 0; ty = 0; apply(); }
  function zoom(to) {
    scale = Math.max(MIN, Math.min(MAX, to));
    if (scale === 1) { tx = 0; ty = 0; }
    apply();
  }

  function show(i) {
    if (!items.length) return;
    idx = (i + items.length) % items.length;
    var it = items[idx];
    resetZoom();
    img.classList.add('is-fading');
    var next = new Image();
    next.onload = next.onerror = function () {
      img.src = it.src;
      img.classList.remove('is-fading');
    };
    next.src = it.src;
    countEl.textContent = (idx + 1) + ' / ' + items.length;
    capEl.textContent = it.cap;
    capEl.hidden = !it.cap;
    Array.prototype.forEach.call(strip.children, function (b, k) { b.classList.toggle('is-active', k === idx); });
    var cur = strip.children[idx];
    if (cur) cur.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }

  function play(on) {
    clearInterval(timer);
    timer = null;
    gv.classList.toggle('is-playing', on);
    if (on) timer = setInterval(function () { show(idx + 1); }, 3000);
  }

  function open(i) {
    collect();
    if (!items.length) return;
    gv.hidden = false;
    document.documentElement.classList.add('gv-open');
    show(i || 0);
  }
  function close() {
    play(false);
    if (document.fullscreenElement) document.exitFullscreen().catch(function () {});
    gv.hidden = true;
    document.documentElement.classList.remove('gv-open');
    if (typeof window.galleryViewerOnClose === 'function') window.galleryViewerOnClose(idx);
  }
  window.galleryViewerOpen = open;

  // ປຸ່ມເຄື່ອງມື
  gv.addEventListener('click', function (e) {
    var b = e.target.closest('[data-gv]');
    if (!b) return;
    var a = b.getAttribute('data-gv');
    if (a === 'close') close();
    else if (a === 'prev') { play(false); show(idx - 1); }
    else if (a === 'next') { play(false); show(idx + 1); }
    else if (a === 'zin') zoom(scale + STEP);
    else if (a === 'zout') zoom(scale - STEP);
    else if (a === 'play') play(!timer);
    else if (a === 'thumbs') { gv.classList.toggle('is-thumbs-off'); b.classList.toggle('is-on', !gv.classList.contains('is-thumbs-off')); }
    else if (a === 'full') {
      if (document.fullscreenElement) document.exitFullscreen().catch(function () {});
      else if (gv.requestFullscreen) gv.requestFullscreen().catch(function () {});
    }
  });
  gv.querySelector('[data-gv="thumbs"]').classList.add('is-on');

  // ກົດພື້ນວ່າງ (ບໍ່ແມ່ນຮູບ, ບໍ່ໄດ້ປັດ) = ປິດ, ກົດສອງເທື່ອທີ່ຮູບ = ຊູມ 2× / ຄືນ
  var justDragged = false;
  stage.addEventListener('click', function (e) { if (justDragged) { justDragged = false; return; } if (e.target === stage && scale === 1) close(); });
  img.addEventListener('dblclick', function () { zoom(scale > 1 ? 1 : 2); });
  stage.addEventListener('wheel', function (e) { e.preventDefault(); zoom(scale + (e.deltaY < 0 ? 0.25 : -0.25)); }, { passive: false });

  // ລາກ: ຊູມຢູ່ = ເລື່ອນເບິ່ງ, ບໍ່ຊູມ = ປັດຊ້າຍ/ຂວາ ປ່ຽນຮູບ; ແຕະສອງເທື່ອ (ໂທລະສັບ) = ຊູມ
  var down = null, lastTap = 0;
  stage.addEventListener('pointerdown', function (e) {
    if (e.target.closest('button')) return;
    down = { x: e.clientX, y: e.clientY, tx: tx, ty: ty, moved: false };
    gv.classList.add('is-dragging');
    try { stage.setPointerCapture(e.pointerId); } catch (err) {}
  });
  stage.addEventListener('pointermove', function (e) {
    if (!down) return;
    var dx = e.clientX - down.x, dy = e.clientY - down.y;
    if (Math.abs(dx) > 5 || Math.abs(dy) > 5) down.moved = true;
    if (scale > 1) { tx = down.tx + dx; ty = down.ty + dy; apply(); }
    else if (items.length > 1) img.style.transform = 'translateX(' + dx + 'px)';   // ຮູບເລື່ອນຕາມນິ້ວ
  });
  stage.addEventListener('pointerup', function (e) {
    if (!down) return;
    var dx = e.clientX - down.x;
    justDragged = down.moved;
    gv.classList.remove('is-dragging');
    if (scale === 1 && Math.abs(dx) > 50 && items.length > 1) { play(false); show(idx + (dx < 0 ? 1 : -1)); }
    else if (scale === 1) apply();   // ປັດບໍ່ພໍ → ຮູບກັບທີ່ເດີມ
    else if (!down.moved && e.pointerType === 'touch' && e.target === img) {
      var now = Date.now();
      if (now - lastTap < 300) { zoom(scale > 1 ? 1 : 2); lastTap = 0; } else lastTap = now;
    }
    down = null;
  });
  stage.addEventListener('pointercancel', function () { down = null; gv.classList.remove('is-dragging'); apply(); });

  document.addEventListener('keydown', function (e) {
    if (gv.hidden) return;
    if (e.key === 'Escape' && !document.fullscreenElement) close();
    else if (e.key === 'ArrowLeft') { play(false); show(idx - 1); }
    else if (e.key === 'ArrowRight') { play(false); show(idx + 1); }
    else if (e.key === '+' || e.key === '=') zoom(scale + STEP);
    else if (e.key === '-') zoom(scale - STEP);
  });
})();
