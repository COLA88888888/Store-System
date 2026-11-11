/**
 * JS ທົ່ວໄປຂອງທຸກໜ້າ (ບໍ່ໃຊ້ jQuery):
 *   - ລິ້ງນອກເມນູເທິງ ເປີດແທັບໃໝ່ (ເມນູເທິງ = ແທັບເດີມ)
 *   - ປຸ່ມເປີດ/ປິດເມນູໃນມືຖື ([data-nav-toggle])
 *   - ທຸງພາສາ: ກົດເປີດ/ປິດ ລາຍການພາສາ ([data-lang-menu]); ກົດບ່ອນອື່ນ ຫຼື Esc = ປິດ
 *   - toast ແຈ້ງເຕືອນ: window.showToast(message, type)
 *   - window.apiPost(path, data): POST ໄປ API ພ້ອມ CSRF token
 *   - ຂໍ້ຄວາມໃນ JS ທີ່ແປແລ້ວ ມາຈາກ window.APP.i18n (layouts/header.php)
 *   - select ທີ່ມີ [data-auto-submit] ສົ່ງຟອມທັນທີ
 *   - ຫົວຂໍ້ພິມເອງ ([data-typing]) ແລະ ຄົ້ນຫາ real-time ([data-live-search]) ໃນໜ້າຫຼັກ (components/hero.php)
 *   - ແຖວບັດເລື່ອນທາງຂ້າງ ພ້ອມປຸ່ມ ‹ › ([data-strip] ໃນ pages/equipment.php)
 *   - ບັດຊ້ອນບັດ ([data-stack-under] + .stack-over) ແລະ ລອຍຂຶ້ນເມື່ອເລື່ອນເຖິງ ([data-reveal]) ໃນໜ້າຫຼັກ
 *   - ໂລໂກ້ລູກຄ້າເລື່ອນຕາມເສັ້ນໂຄ້ງ ([data-arc] ໃນ components/clients.php)
 *   - Lightbox ຮູບຊູມຊ້າໆ ([data-lightbox] ໃນ pages/work.php)
 *   - ບັດສິນຄ້າ skeleton ຕອນໂຫຼດ + ປ່ຽນໜ້າ/ໝວດບໍ່ refresh ([data-ajax-list] ໃນ pages/equipment_list.php)
 *   - ປຸ່ມກັ່ນຕອງ ([data-filter-group] + [data-filter-item]) ໃນໜ້າຜົນງານ
 */
// ໜ້າແຊັດເຕັມຈໍ (body.page-contact ໃນມືຖື): ຄວາມສູງ = ພື້ນທີ່ທີ່ເຫັນແທ້ (visualViewport) — 100dvh ໃນ Chrome/Safari iPhone
// ບາງເທື່ອນັບລວມແຖບເຄື່ອງມືລຸ່ມ ເຮັດໃຫ້ບ່ອນພິມ + ປຸ່ມສົ່ງໄຟລ໌ຖືກບັງ. ແປ້ນພິມເປີດ → ສັ້ນລົງ ບ່ອນພິມຢູ່ເທິງແປ້ນພິມ (chat_room.css --app-h / --app-top)
(function () {
  if (!document.body || !document.body.classList.contains('page-contact')) return;
  var root = document.documentElement, vv = window.visualViewport;
  // 100svh = ຄວາມສູງຕອນແຖບ browser ສະແດງເຕັມ — iPhone ແຖບເຄື່ອງມືລຸ່ມ (Chrome/Safari) ທັບເທິງໜ້າເວັບ ແລະ innerHeight/visualViewport
  // ນັບລວມພື້ນທີ່ໃຕ້ແຖບນັ້ນ → ໃຊ້ຄ່ານ້ອຍກວ່າ ລະຫວ່າງ visualViewport (ແປ້ນພິມເປີດ = ນ້ອຍລົງ) ແລະ 100svh
  var probe = document.createElement('div');
  probe.style.cssText = 'position:fixed;left:0;top:0;width:0;height:100vh;height:100svh;visibility:hidden;pointer-events:none';
  document.body.appendChild(probe);
  function fit() {
    var svh = probe.offsetHeight || window.innerHeight;
    var h = Math.min(vv ? vv.height : window.innerHeight, svh), top = vv ? vv.offsetTop : 0;
    root.style.setProperty('--app-h', Math.round(h) + 'px');
    root.style.setProperty('--app-top', Math.round(top) + 'px');
    var hd = document.querySelector('.site-header');   // ກ່ອງແຊັດ fixed ເລີ່ມຕໍ່ຈາກລຸ່ມເມນູເທິງ
    if (hd) root.style.setProperty('--cr-top', Math.max(0, Math.round(hd.getBoundingClientRect().bottom)) + 'px');
    // ແປ້ນພິມ (iPhone): ພື້ນທີ່ທີ່ຖືກບັງລຸ່ມ = innerHeight − (ຄວາມສູງທີ່ເຫັນ + offsetTop)
    root.style.setProperty('--kb', (vv ? Math.max(0, Math.round(window.innerHeight - vv.height - vv.offsetTop)) : 0) + 'px');
  }
  fit();
  window.addEventListener('resize', fit);
  window.addEventListener('orientationchange', function () { setTimeout(fit, 350); });
  if (vv) { vv.addEventListener('resize', fit); vv.addEventListener('scroll', fit); }
})();

// ທຸກ <dialog> ທີ່ເປີດແບບ modal (showModal): ລັອກໜ້າຫຼັງໃຫ້ຢູ່ນິ່ງ — iPhone (Safari) ບໍ່ສົນ overflow:hidden ຂອງ body
// ເລື່ອນ/ລາກແລ້ວໜ້າຫຼັງເຄື່ອນ ຈຶ່ງຕັ້ງ body ເປັນ position:fixed ໄວ້ທີ່ຕຳແໜ່ງເດີມ, ປິດ modal → ຄືນຕຳແໜ່ງເລື່ອນ. ນັບຊ້ອນກັນໄດ້ (ສະຫຼັບ modal)
(function () {
  if (!window.HTMLDialogElement || !HTMLDialogElement.prototype.showModal) return;
  var P = HTMLDialogElement.prototype, orig = P.showModal, n = 0, y = 0;
  function lock() {
    if (n++) return;
    y = window.scrollY || window.pageYOffset || 0;
    var st = document.body.style;
    st.position = 'fixed'; st.top = (-y) + 'px'; st.left = '0'; st.right = '0'; st.width = '100%';
  }
  function unlock() {
    if (n === 0 || --n) return;
    var st = document.body.style, h = document.documentElement.style, sb = h.scrollBehavior;
    st.position = st.top = st.left = st.right = st.width = '';
    h.scrollBehavior = 'auto'; window.scrollTo(0, y); h.scrollBehavior = sb;   // ກັບຄືນບ່ອນເກົ່າທັນທີ (ບໍ່ເລື່ອນ smooth)
  }
  P.showModal = function () {
    var was = this.open;
    orig.apply(this, arguments);
    if (was || this.__wlLock) return;
    this.__wlLock = true;
    lock();
    var d = this;
    d.addEventListener('close', function h() { d.removeEventListener('close', h); d.__wlLock = false; unlock(); });
  };
})();

(function () {
  'use strict';

  // ລິ້ງເປີດແທັບໃໝ່: ເມນູເທິງ (.site-header: ເມນູ, ໂລໂກ້, ກະຕ່າ, ພາສາ, ສະມາຊິກ, ເຂົ້າສູ່ລະບົບ) = ແທັບເດີມ;
  // ລິ້ງອື່ນທັງໝົດໃນໜ້າ = ແທັບໃໝ່. ຍົກເວັ້ນ: ລິ້ງທີ່ຢູ່ໜ້າເດີມ (ປ່ຽນໜ້າ ?page= / ໝວດ ?cat= / #), tel:, mailto:,
  // download, ລິ້ງທີ່ JS ອື່ນຈັດການແລ້ວ (preventDefault — lightbox, ສະໄລດ໌, popup ສະມາຊິກ…), ກົດພ້ອມ Ctrl/Shift/ປຸ່ມກາງ.
  // ຟັງຢູ່ window (ແລ່ນຫຼັງ listener ອື່ນທັງໝົດ) ຈຶ່ງຮູ້ວ່າ JS ອື່ນ preventDefault ແລ້ວບໍ
  // ມືຖື / ແທັບເລັດ (ຈໍສຳຜັດ ຫຼື ຈໍແຄບ): ທຸກລິ້ງຢູ່ແທັບເດີມ — ບໍ່ເປີດແທັບໃໝ່
  var touchDevice = window.matchMedia('(hover: none), (pointer: coarse), (max-width: 767.98px)');
  if (touchDevice.matches) {   // ລິ້ງພາຍໃນເວັບທີ່ຂຽນ target="_blank" ໄວ້ (ເຊັ່ນ ປຸ່ມເຂົ້າສູ່ລະບົບ) → ແທັບເດີມ; WhatsApp/Facebook ຍັງເປີດແອັບຕາມເດີມ
    document.querySelectorAll('a[target="_blank"][href]').forEach(function (a) {
      try { if (new URL(a.href, location.href).origin === location.origin) a.removeAttribute('target'); } catch (err) {}
    });
  }
  // ແທັບໃໝ່ສູງສຸດ 3 ແທັບ: ບໍ່ໃຊ້ '_blank' (ເປີດແທັບໃໝ່ທຸກຄັ້ງ) ແຕ່ໃຊ້ແທັບມີຊື່ 3 ອັນ ທີ່ຖືກໃຊ້ຊ້ຳ —
  //   wlao_site    = ໜ້າອື່ນໃນເວັບນີ້ (ສິນຄ້າ, ລະບົບ, ຜົນງານ…)
  //   wlao_contact = WhatsApp / Facebook / Messenger
  //   wlao_other   = ອື່ນໆ (ແຜນທີ່, ລະບົບຫຼັງບ້ານ, ລິ້ງນອກ)
  // ກົດລິ້ງໝວດດຽວກັນອີກ → ໂຫຼດໃນແທັບເກົ່າ ແລະ ດຶງແທັບນັ້ນຂຶ້ນມາ. ໃຊ້ຢູ່ທຸກບ່ອນທີ່ເປີດແທັບ (window.wlaoOpen).
  function tabNameFor(url) {
    if (url.origin === location.origin && url.pathname.indexOf('/backend/') === -1) return 'wlao_site';
    if (/(^|\.)(wa\.me|whatsapp\.com|facebook\.com|fb\.com|m\.me|messenger\.com)$/i.test(url.hostname)) return 'wlao_contact';
    return 'wlao_other';
  }
  function wlaoOpen(href) {
    var url;
    try { url = new URL(href, location.href); } catch (err) { return null; }
    var w = window.open(url.href, tabNameFor(url));
    if (w) { try { w.opener = null; } catch (err) {} try { w.focus(); } catch (err) {} }   // ຕັດ opener ແທນ rel=noopener (noopener ເຮັດໃຫ້ຊອກແທັບຕາມຊື່ບໍ່ໄດ້)
    return w;
  }
  window.wlaoOpen = wlaoOpen;

  // ນັບການກົດປຸ່ມຕິດຕໍ່ (api/track_click.php → w_web_contact_clicks; ເບິ່ງທີ່ backend/pages/web_chats/):
  // tel: = ໂທ, wa.me = WhatsApp, facebook/m.me = Facebook, Google Maps = ແຜນທີ່, ປຸ່ມແຊັດລອຍ / ແຖບມືຖື = ແຊັດ.
  // ຟັງແບບ capture ຈຶ່ງສົ່ງ beacon ກ່ອນໜ້າປ່ຽນ; sendBeacon ສົ່ງຕໍ່ໄດ້ເຖິງວ່າຈະອອກຈາກໜ້າແລ້ວ.
  function contactChannel(el) {
    if (el.closest('#fcTrigger, .mbar-btn.is-chat')) return 'chat';
    var a = el.closest('a[href]');
    if (!a) return '';
    var href = a.getAttribute('href') || '';
    if (/^tel:/i.test(href)) return 'tel';
    if (/wa\.me|whatsapp\.com/i.test(href)) return 'whatsapp';
    if (/facebook\.com|fb\.com|m\.me|messenger\.com/i.test(href)) return 'facebook';
    if (/google\.[a-z.]+\/maps|maps\.google|goo\.gl\/maps|maps\.app\.goo\.gl/i.test(href)) return 'map';
    return '';
  }
  document.addEventListener('click', function (e) {
    var ch = e.target && e.target.closest ? contactChannel(e.target) : '';
    if (!ch || !navigator.sendBeacon) return;
    try {
      var fd = new FormData();
      fd.append('channel', ch);
      fd.append('page', location.pathname + location.search);
      navigator.sendBeacon((window.WLAO_TRACK_URL || 'api/track_click.php'), fd);
    } catch (err) {}
  }, true);

  window.addEventListener('click', function (e) {
    if (touchDevice.matches) return;
    if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    var a = e.target.closest ? e.target.closest('a[href]') : null;
    if (!a || a.hasAttribute('download')) return;
    if (a.target && a.target !== '_blank') return;                      // target ອື່ນ (iframe, _self…) ປ່ອຍຕາມເດີມ
    if (!a.target && a.closest('.site-header')) return;                 // ເມນູເທິງ = ແທັບເດີມ
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || /^(tel|mailto|javascript|sms):/i.test(href)) return;
    var url;
    try { url = new URL(a.href, location.href); } catch (err) { return; }
    if (!a.target && url.origin === location.origin && url.pathname === location.pathname) return;   // ໜ້າເດີມ (ແບ່ງໜ້າ, ກັ່ນຕອງ)
    e.preventDefault();
    wlaoOpen(url.href);
  });

  // ປ້າຍໂລໂກ້ຕາມເມົາ ([data-cursor-badge] ໃນ layouts/footer.php): ສະແດງ ແລະ ໄລ່ຕາມເມົາແບບນຸ້ມ (lerp) ສະເພາະເມື່ອເມົາຢູ່ໃນ
  // ພາກ [data-cursor-zone] (ໜ້າຫຼັກ: hero, ກາດເສັ້ນແສງ, CTA). ບໍ່ສະແດງໃນຈໍສຳຜັດ ແລະ prefers-reduced-motion
  (function () {
    var badge = document.querySelector('[data-cursor-badge]');
    var zones = document.querySelectorAll('[data-cursor-zone]');
    if (!badge || !zones.length || !window.matchMedia('(hover: hover) and (pointer: fine)').matches
        || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var x = 0, y = 0, tx = 0, ty = 0, on = false, raf = 0;
    function frame() {
      x += (tx - x) * .18; y += (ty - y) * .18;   // ຕາມຊ້າກວ່າເມົາເລັກນ້ອຍ (ເບິ່ງນຸ້ມ)
      badge.style.transform = 'translate3d(' + (x + 12).toFixed(1) + 'px,' + (y + 14).toFixed(1) + 'px,0)';
      raf = (on || Math.abs(tx - x) > .5 || Math.abs(ty - y) > .5) ? requestAnimationFrame(frame) : 0;
    }
    zones.forEach(function (z) {
      z.addEventListener('pointerenter', function (e) {
        tx = e.clientX; ty = e.clientY;
        if (!on) { x = tx; y = ty; }
        on = true; badge.classList.add('is-on');
        if (!raf) raf = requestAnimationFrame(frame);
      });
      z.addEventListener('pointermove', function (e) { tx = e.clientX; ty = e.clientY; if (!raf) raf = requestAnimationFrame(frame); }, { passive: true });
      z.addEventListener('pointerleave', function () { on = false; badge.classList.remove('is-on'); });
    });
  })();

  // ລະບົບຄວບຄຸມເມນູ ແລະ Popup ບໍ່ໃຫ້ຊ້ອນກັນ: ເມື່ອກົດເປີດເມນູໃດໜຶ່ງ ອັນອື່ນທັງໝົດຈະຖືກປິດທັນທີ
  window.closeAllOverlays = function (except) {
    // 1. ປິດເມນູຫຼັກ ☰ ໃນມືຖື
    if (except !== 'nav') {
      var nav = document.querySelector('[data-nav]');
      var toggle = document.querySelector('[data-nav-toggle]');
      if (nav && nav.classList.contains('open')) {
        nav.classList.remove('open');
        document.body.classList.remove('nav-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
      }
    }

    // 2. ປິດກ່ອງແຊັດລອຍ (Floating Chat)
    if (except !== 'chat') {
      if (window.FloatingChat && typeof window.FloatingChat.close === 'function') {
        window.FloatingChat.close();
      }
    }

    // 3. ປິດເມນູເລືອກພາສາ
    if (except !== 'lang') {
      var langMenu = document.querySelector('[data-lang-menu]');
      if (langMenu && langMenu.classList.contains('open')) {
        langMenu.classList.remove('open');
        var langBtn = langMenu.querySelector('[data-lang-toggle]');
        if (langBtn) langBtn.setAttribute('aria-expanded', 'false');
      }
    }

    // 4. ປິດເມນູສະມາຊິກ
    if (except !== 'member') {
      var nm = document.querySelector('[data-nm]');
      if (nm && nm.classList.contains('open')) {
        nm.classList.remove('open');
        var nmBtn = nm.querySelector('[data-nm-toggle]');
        if (nmBtn) nmBtn.setAttribute('aria-expanded', 'false');
      }
    }

    // 5. ປິດກະດິ່ງແຈ້ງເຕືອນ
    if (except !== 'bell') {
      var bell = document.querySelector('[data-bell]');
      if (bell) {
        var bellPanel = bell.querySelector('[data-bell-panel]');
        var bellBtn = bell.querySelector('[data-bell-toggle]');
        if (bellPanel && !bellPanel.hidden) {
          bellPanel.hidden = true;
          if (bellBtn) bellBtn.setAttribute('aria-expanded', 'false');
        }
      }
    }

    try {
      document.dispatchEvent(new CustomEvent('wlao:close-overlays', { detail: { except: except } }));
    } catch (e) {}
  };

  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function (ev) {
      ev.stopPropagation();
      var willOpen = !nav.classList.contains('open');
      if (willOpen) {
        window.closeAllOverlays('nav');
      }
      nav.classList.toggle('open', willOpen);
      document.body.classList.toggle('nav-open', willOpen);
      toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    // ຄຣິກຂ້າງນອກເມນູມືຖື → ປິດເມນູ
    document.addEventListener('click', function (ev) {
      if (nav.classList.contains('open') && !nav.contains(ev.target) && !toggle.contains(ev.target)) {
        nav.classList.remove('open');
        document.body.classList.remove('nav-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });

    // ຄຣິກລິ້ງພາຍໃນເມນູ → ປິດເມນູ (ເຊັ່ນ: ກົດລິ້ງໄປໜ້າອື່ນ ຫຼື ກົດ popup ເຂົ້າສູ່ລະບົບ)
    nav.addEventListener('click', function (ev) {
      if (ev.target.closest('a')) {
        nav.classList.remove('open');
        document.body.classList.remove('nav-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && nav.classList.contains('open')) {
        nav.classList.remove('open');
        document.body.classList.remove('nav-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  var langMenu = document.querySelector('[data-lang-menu]');
  if (langMenu) {
    var langBtn = langMenu.querySelector('[data-lang-toggle]');
    var setLangOpen = function (open) {
      if (open) {
        window.closeAllOverlays('lang');
      }
      langMenu.classList.toggle('open', open);
      langBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    langBtn.addEventListener('click', function (ev) {
      ev.stopPropagation();
      setLangOpen(!langMenu.classList.contains('open'));
    });
    document.addEventListener('click', function (ev) {
      if (!langMenu.contains(ev.target)) setLangOpen(false);
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') setLangOpen(false);
    });
  }

  var toastTimer = null;
  window.showToast = function (message, type) {
    var el = document.getElementById('toast');
    if (!el || !message) return;
    el.textContent = message;
    el.className = 'toast show ' + (type === 'error' ? 'toast-error' : 'toast-success');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.className = 'toast'; }, 2500);
  };

  window.apiPost = function (path, data) {
    var body = new URLSearchParams();
    Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
    return fetch(window.APP.base + path, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': window.APP.csrf, 'Accept': 'application/json' },
      body: body
    }).then(function (res) {
      return res.json().catch(function () { return { success: false, message: window.APP.i18n.error.replace('%s', res.status) }; });
    }).catch(function () {
      return { success: false, message: window.APP.i18n.offline };
    });
  };

  document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
    el.addEventListener('change', function () { if (el.form) el.form.submit(); });
  });

  // ແຖວບັດ / ແຖວປຸ່ມໝວດ ເລື່ອນທາງຂ້າງ ([data-strip] ໃນ pages/equipment.php ແລະ pages/equipment_list.php): ປຸ່ມ ‹ › ເລື່ອນເທື່ອລະ 1 ໜ້າຈໍ,
  // ຂຶ້ນສະເພາະເມື່ອມີບັດລົ້ນ ແລະ ເຊື່ອງເມື່ອຮອດຕົ້ນ/ທ້າຍແຖວ
  document.querySelectorAll('[data-strip]').forEach(function (wrap) {
    var track = wrap.querySelector('[data-strip-track]');
    var prev = wrap.querySelector('[data-strip-prev]');
    var next = wrap.querySelector('[data-strip-next]');
    function update() {
      var max = track.scrollWidth - track.clientWidth - 8;
      prev.hidden = track.scrollLeft <= 8;
      next.hidden = track.scrollLeft >= max;
    }
    prev.addEventListener('click', function () { track.scrollBy({ left: -track.clientWidth, behavior: 'smooth' }); });
    next.addEventListener('click', function () { track.scrollBy({ left: track.clientWidth, behavior: 'smooth' }); });
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    // ແຖວປຸ່ມໝວດ: ເລື່ອນໃຫ້ເຫັນປຸ່ມທີ່ເລືອກຢູ່
    var active = track.querySelector('.active');
    if (active && active.offsetLeft + active.offsetWidth > track.clientWidth) {
      track.style.scrollBehavior = 'auto';
      track.scrollLeft = active.offsetLeft - 60;
      track.style.scrollBehavior = '';
    }
    update();
  });

  // ຄົ້ນຫາ real-time ([data-live-search] ຈາກ components/hero.php → api/search.php):
  // ພິມ → ລໍ 180ms ແລ້ວດຶງຜົນລັບ (ຍົກເລີກຄຳຂໍເກົ່າ); ລຶບໝົດ → ປິດທັນທີ. ↑/↓ ເລືອກ, Enter ເປີດລາຍການທີ່ເລືອກ,
  // Enter ໂດຍບໍ່ເລືອກ / ປຸ່ມ "ຄົ້ນຫາ" → ສົ່ງຟອມໄປໜ້າຜົນລັບ (pages/search.php). Esc / ກົດບ່ອນອື່ນ = ປິດ
  document.querySelectorAll('[data-live-search]').forEach(function (form) {
    var input = form.querySelector('input[name="q"]');
    var box = form.querySelector('.live-results');
    var endpoint = form.getAttribute('data-live-search');
    var timer = null, ctrl = null, active = -1;
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };

    function show(on) { box.hidden = !on; input.setAttribute('aria-expanded', on ? 'true' : 'false'); }
    function close() { show(false); box.innerHTML = ''; active = -1; }
    function links() { return box.querySelectorAll('.live-item'); }
    function highlight(i) {
      var all = links();
      if (!all.length) return;
      active = (i + all.length) % all.length;
      all.forEach(function (a, n) { a.classList.toggle('is-active', n === active); });
      all[active].scrollIntoView({ block: 'nearest' });
    }
    function render(items) {
      if (!input.value.trim()) { close(); return; }
      box.innerHTML = !items.length
        ? '<div class="live-empty">' + esc(form.getAttribute('data-empty-text')) + '</div>'
        : items.map(function (it) {
            var media = it.image ? '<img src="' + esc(it.image) + '" alt="" loading="lazy">' : '<span>' + esc(it.icon) + '</span>';
            var price = it.price ? '<span class="live-price">' + (it.old ? '<s>' + esc(it.old) + '</s>' : '') + '<b>' + esc(it.price) + '</b></span>' : '';
            return '<a class="live-item" role="option" href="' + esc(it.url) + '"><span class="live-thumb">' + media + '</span>' +
                   '<span class="live-text"><b>' + esc(it.title) + '</b><small>' + esc(it.sub) + '</small></span>' + price + '</a>';
          }).join('');
      active = -1;
      show(true);
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      if (ctrl) ctrl.abort();
      var q = input.value.trim();
      if (!q) { close(); return; }               // ລຶບໝົດ → ຫາຍທັນທີ
      timer = setTimeout(function () {
        ctrl = window.AbortController ? new AbortController() : null;
        fetch(endpoint + '?q=' + encodeURIComponent(q), { signal: ctrl ? ctrl.signal : undefined, headers: { Accept: 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (d) { if (input.value.trim() === q) render(d.items || []); })
          .catch(function () {});
      }, 180);
    });
    input.addEventListener('keydown', function (ev) {
      if (box.hidden) return;
      if (ev.key === 'ArrowDown') { ev.preventDefault(); highlight(active + 1); }
      else if (ev.key === 'ArrowUp') { ev.preventDefault(); highlight(active - 1); }
      else if (ev.key === 'Escape') { close(); }
      else if (ev.key === 'Enter' && active >= 0) { ev.preventDefault(); wlaoOpen(links()[active].href); }   // ຄືກົດລິ້ງ = ແທັບ wlao_site (ບໍ່ເພີ່ມແທັບໃໝ່)
    });
    form.addEventListener('submit', function (ev) { if (!input.value.trim()) ev.preventDefault(); });
    input.addEventListener('focus', function () { if (input.value.trim() && box.innerHTML) show(true); });
    document.addEventListener('click', function (ev) { if (!form.contains(ev.target)) show(false); });
  });

  // ໂຫຼດແບບ skeleton ຂອງທຸກບັດ: .mk-card.is-loading (ບັດສິນຄ້າ, ມີໂຄງຂອງມັນເອງ) ແລະ .skel.is-loading (ບັດອື່ນໆ:
  // ລະບົບ/ອຸປະກອນ, ຜົນງານ, ບໍລິການ, ສິນຄ້າໃນຮ້ານ, ຮູບໃນໜ້າຜົນງານ — ຮູບ/ຕົວໜັງສືຂອງບັດເອງກາຍເປັນແຖບສີເທົາ, CSS ໃນ pages.css).
  // ເອົາ is-loading ອອກເມື່ອຮູບທຸກຮູບໃນບັດໂຫຼດແລ້ວ (ສະແດງຢ່າງໜ້ອຍ 350ms ຫຼັງໜ້າເລີ່ມ; ຮູບ lazy / ຢູ່ໃນແຖບທີ່ເຊື່ອງ = ລໍຈົນໂຫຼດ)
  var skeletonStart = Date.now();
  window.initSkeletons = function (root) {
    (root || document).querySelectorAll('.mk-card.is-loading:not(.mk-card-skel), .skel.is-loading').forEach(function (card) {
      var imgs = Array.from(card.querySelectorAll('img')).filter(function (i) { return !(i.complete && i.naturalWidth); });
      var left = imgs.length;
      var done = function () {
        setTimeout(function () { card.classList.remove('is-loading'); }, Math.max(0, 350 - (Date.now() - skeletonStart)));
      };
      if (!left) { done(); return; }
      imgs.forEach(function (img) {
        var one = function () { if (--left === 0) done(); };
        img.addEventListener('load', one, { once: true });
        img.addEventListener('error', one, { once: true });
      });
    });
  };
  window.initSkeletons();

  // ລາຍການທີ່ປ່ຽນໜ້າ/ໝວດ ໂດຍບໍ່ refresh ([data-ajax-list] ໃນ pages/equipment_list.php): ກົດລິ້ງ [data-ajax] →
  // ສະແດງບັດ skeleton → ດຶງໜ້າໃໝ່ → ແທນ [data-ajax-results] + ອັບເດດ URL. ຜິດພາດ → ເປີດລິ້ງແບບປົກກະຕິ
  document.querySelectorAll('[data-ajax-list]').forEach(function (list) {
    var tpl = document.getElementById('mkSkeleton');
    function load(href, push) {
      var results = list.querySelector('[data-ajax-results]');
      var chipsWrap = list.querySelector('.eq-chips-wrap');
      var grid = results ? results.querySelector('.eq-grid') : null;
      if (grid && tpl) {
        var n = Math.max(10, grid.children.length);
        grid.innerHTML = '';
        for (var i = 0; i < n; i++) grid.appendChild(tpl.content.firstElementChild.cloneNode(true));
      }

      // ເລື່ອນລົງໄປຫາລາຍການໝວດນັ້ນທັນທີ (ຂ້າມແບນເນີ ໃຫ້ເຫັນລາຍການສິນຄ້າຊັດເຈນ)
      var scrollTarget = chipsWrap || results || list;
      var header = document.querySelector('.site-header');
      var headerH = header ? header.offsetHeight : 65;
      var targetTop = scrollTarget.getBoundingClientRect().top + window.scrollY - headerH - 12;
      window.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });

      fetch(href, { headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var fresh = doc.querySelector('[data-ajax-results]');
          if (!fresh) throw new Error('no results');
          skeletonStart = Date.now();
          results.innerHTML = fresh.innerHTML;
          var newChips = doc.querySelectorAll('.eq-chip');
          list.querySelectorAll('.eq-chip').forEach(function (c, i) {
            if (newChips[i]) {
              c.classList.toggle('active', newChips[i].classList.contains('active'));
              if (newChips[i].hasAttribute('href')) c.setAttribute('href', newChips[i].getAttribute('href'));
            }
          });
          // ອັບເດດໝວດໃນ toolbar (form) ຖ້າມີ
          var curToolbar = list.querySelector('.eq-toolbar');
          var newToolbar = doc.querySelector('.eq-toolbar');
          if (curToolbar && newToolbar) {
            var catInput = curToolbar.querySelector('input[name="cat"]');
            var newCatInput = newToolbar.querySelector('input[name="cat"]');
            if (catInput && newCatInput) {
              catInput.value = newCatInput.value;
            } else if (newCatInput) {
              curToolbar.insertAdjacentHTML('afterbegin', '<input type="hidden" name="cat" value="' + newCatInput.value + '">');
            } else if (catInput) {
              catInput.remove();
            }
          }
          if (push) history.pushState({ ajaxList: true }, '', href);
          window.initSkeletons(results);
        })
        .catch(function () { window.location.href = href; });
    }
    list.addEventListener('click', function (ev) {
      var a = ev.target.closest('a[data-ajax]');
      if (!a || ev.ctrlKey || ev.metaKey || ev.shiftKey) return;
      ev.preventDefault();
      load(a.href, true);
    });
    window.addEventListener('popstate', function () { load(window.location.href, false); });

    // ຖ້າເປີດໜ້າມາພ້ອມກັບ ?cat= ຫຼື ?hw= ຫຼື ?page= ໃຫ້ເລື່ອນຜ່ານແບນເນີ ໄປຫາລາຍການໝວດນັ້ນທັນທີ
    if (window.location.search && /[?&](cat|hw|page)=/i.test(window.location.search)) {
      setTimeout(function () {
        var scrollTarget = list.querySelector('.eq-chips-wrap') || list.querySelector('[data-ajax-results]');
        if (scrollTarget) {
          var header = document.querySelector('.site-header');
          var headerH = header ? header.offsetHeight : 65;
          var targetTop = scrollTarget.getBoundingClientRect().top + window.scrollY - headerH - 12;
          window.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
        }
      }, 150);
    }
  });

  // Lightbox ຮູບ ([data-lightbox] ລ້ອມລິ້ງ [data-lightbox-item], ເຊັ່ນ pages/work.php):
  // ເປີດ = ຮູບຂະຫຍາຍອອກຈາກຮູບນ້ອຍທີ່ກົດຊ້າໆ 0.6s (FLIP, ລໍຮູບໂຫຼດກ່ອນ ຈຶ່ງບໍ່ກະຕຸກ) + ພື້ນມືດ; ປິດ = ຫົດກັບເຂົ້າຮູບນ້ອຍ 0.3s.
  // ‹ › / ← → = ປ່ຽນຮູບແບບຈາງໄວ, ✕ / Esc / ກົດພື້ນ = ປິດ
  document.querySelectorAll('[data-lightbox]').forEach(function (group) {
    var items = Array.from(group.querySelectorAll('[data-lightbox-item]'));
    if (!items.length) return;
    var chev = function (d) { return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + d + '"/></svg>'; };
    var box = document.createElement('div');
    box.className = 'lb';
    box.hidden = true;
    box.innerHTML = '<button type="button" class="lb-btn lb-close" aria-label="close">' + chev('M6 6l12 12M18 6L6 18') + '</button>' +
      '<button type="button" class="lb-btn lb-nav lb-prev" aria-label="prev">' + chev('M15 18l-6-6 6-6') + '</button>' +
      '<button type="button" class="lb-btn lb-nav lb-next" aria-label="next">' + chev('M9 18l6-6-6-6') + '</button>' +
      '<img class="lb-img" alt=""><div class="lb-count"></div>';
    document.body.appendChild(box);
    var img = box.querySelector('.lb-img'), count = box.querySelector('.lb-count'), idx = 0, busy = false, activeEl = null;
    var EASE = 'cubic-bezier(.2, .8, .2, 1)';
    var thumbOf = function (i) {
      var el = activeEl || items[i];
      return (el && el.querySelector('img')) || el || items[i];
    };

    // ຍ້າຍ/ຂະຫຍາຍຮູບໃຫຍ່ ໃຫ້ທັບຮູບນ້ອຍພໍດີ (ໃຊ້ເປັນຈຸດເລີ່ມ/ຈຸດຈົບຂອງການຊູມ)
    function toThumb(i) {
      var t = thumbOf(i).getBoundingClientRect(), r = img.getBoundingClientRect();
      if (!r.width || !t.width) return 'scale(.85)';
      return 'translate(' + (t.left + t.width / 2 - (r.left + r.width / 2)) + 'px,' + (t.top + t.height / 2 - (r.top + r.height / 2)) + 'px) scale(' + Math.max(t.width / r.width, t.height / r.height) + ')';
    }
    function load(src) {
      img.src = src;
      return (img.decode ? img.decode() : Promise.resolve()).catch(function () {});
    }
    function setCount() { count.textContent = (idx + 1) + ' / ' + items.length; }

    function open(i, clickedEl) {
      if (busy) return;
      busy = true; idx = i; activeEl = clickedEl || items[i]; setCount();
      img.style.transition = 'none'; img.style.opacity = '0'; img.style.transform = 'none'; // ວັດຂະໜາດຈິງກ່ອນ
      box.hidden = false;
      // ເຊື່ອງ scrollbar ຂອງໜ້າ ແລ້ວຊົດເຊີຍຄວາມກວ້າງ ບໍ່ໃຫ້ໜ້າເລື່ອນໄປຂ້າງ
      document.body.style.paddingRight = (window.innerWidth - document.documentElement.clientWidth) + 'px';
      document.body.style.overflow = 'hidden';
      load(items[i].getAttribute('href')).then(function () {
        img.style.transform = toThumb(i);
        img.style.opacity = '1';
        img.getBoundingClientRect();                       // reflow ກ່ອນເລີ່ມ transition
        img.style.transition = 'transform .6s ' + EASE + ', opacity .3s ease'; // ຊູມເຂົ້າ 0.6 ວິນາທີ
        img.style.transform = 'none';
        box.classList.add('is-open');
        setTimeout(function () { busy = false; }, 600);
      });
    }
    function close() {
      if (busy || box.hidden) return;
      busy = true;
      img.style.transition = 'transform .3s ' + EASE + ', opacity .3s ease';
      img.style.transform = toThumb(idx);
      img.style.opacity = '0';
      box.classList.remove('is-open');
      setTimeout(function () {
        box.hidden = true; img.removeAttribute('src'); busy = false;
        document.body.style.overflow = ''; document.body.style.paddingRight = '';
      }, 300);
    }
    function go(step) {
      if (busy || items.length < 2) return;
      busy = true;
      idx = (idx + step + items.length) % items.length; activeEl = items[idx]; setCount();
      img.style.transition = 'opacity .15s ease, transform .15s ease';
      img.style.opacity = '0'; img.style.transform = 'translateX(' + (step > 0 ? -16 : 16) + 'px)';
      setTimeout(function () {
        load(items[idx].getAttribute('href')).then(function () {
          img.style.transition = 'none'; img.style.transform = 'translateX(' + (step > 0 ? 16 : -16) + 'px)';
          img.getBoundingClientRect();
          img.style.transition = 'opacity .2s ease, transform .25s ' + EASE;
          img.style.opacity = '1'; img.style.transform = 'none';
          setTimeout(function () { busy = false; }, 200);
        });
      }, 150);
    }

    items.forEach(function (a, i) { a.setAttribute('data-lb-idx', i); });
    group.addEventListener('click', function (ev) {
      var a = ev.target.closest('[data-lightbox-item]');
      if (!a) return;
      ev.preventDefault();
      open(parseInt(a.getAttribute('data-lb-idx'), 10) || 0, a);
    });
    box.querySelector('.lb-close').addEventListener('click', close);
    box.querySelector('.lb-prev').addEventListener('click', function () { go(-1); });
    box.querySelector('.lb-next').addEventListener('click', function () { go(1); });
    box.addEventListener('click', function (ev) { if (ev.target === box) close(); });
    document.addEventListener('keydown', function (ev) {
      if (box.hidden) return;
      if (ev.key === 'Escape') close();
      else if (ev.key === 'ArrowLeft') go(-1);
      else if (ev.key === 'ArrowRight') go(1);
    });
    if (items.length < 2) { box.querySelector('.lb-prev').hidden = true; box.querySelector('.lb-next').hidden = true; }
  });

  // ໂລໂກ້/ຮູບຜົນງານເລື່ອນຕາມເສັ້ນໂຄ້ງ ([data-arc] ໃນ pages/work.php): ແຕ່ລະຮູບ x = ຕຳແໜ່ງໃນແຖວ - offset,
  // y = a·x² (ກາງສູງ ສອງຂ້າງຕ່ຳ), ອຽງຕາມເສັ້ນສຳຜັດ atan(2ax). ຮູບບໍ່ພໍເຕັມຈໍ → ກັອບປີ້ເພີ່ມ (aria-hidden).
  // ເລື່ອນຕໍ່ເນື່ອງບໍ່ຢຸດເວລາເອົາເມົາຊີ້; ຢຸດສະເພາະຕອນກຳລັງເປີດເບິ່ງຮູບຂະຫຍາຍ (Lightbox) ແລ້ວເມື່ອປິດຮູບຈະເລື່ອນຕໍ່ທັນທີ.
  document.querySelectorAll('[data-arc]').forEach(function (arc) {
    var originals = Array.from(arc.children);
    if (!originals.length) return;
    var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var dir = arc.getAttribute('data-arc-dir') === '-1' ? -1 : 1;   // ແຖວທີ 2 ເລື່ອນສວນທາງ
    var items = [], gap = 0, total = 0, depth = 0, offset = 0, last = 0;

    function layout() {
      var w = arc.clientWidth;
      gap = w < 600 ? 176 : 242;                    // ໄລຍະລະຫວ່າງບັດຮູບ
      depth = w < 600 ? 42 : 86;                    // ຄວາມຕ່ຳຂອງສອງຂ້າງ
      arc.querySelectorAll('[data-arc-clone]').forEach(function (c) { c.remove(); });
      items = originals.slice();
      while (items.length * gap < w + gap * 2) {    // ໃຫ້ຍາວກວ່າຈໍ ຈຶ່ງວົນໄດ້ບໍ່ມີຊ່ອງວ່າງ
        originals.forEach(function (o) {
          var c = o.cloneNode(true);
          c.setAttribute('data-arc-clone', '');
          c.setAttribute('aria-hidden', 'true');
          var cImg = c.querySelector('img');
          if (cImg) cImg.alt = '';
          arc.appendChild(c);
          items.push(c);
        });
      }
      if (window.initSkeletons) window.initSkeletons(arc);
      total = items.length * gap;
      place();
    }
    function place() {
      var half = arc.clientWidth / 2, a = depth / (half * half);
      var leftEdge = -half - gap * 0.5;
      var maxDist = Math.max(1, half + gap * 0.4);
      items.forEach(function (el, i) {
        var x = ((i * gap - offset + gap) % total + total) % total + leftEdge;
        var y = a * x * x;
        var rot = Math.atan(2 * a * x) * 180 / Math.PI;
        var norm = Math.min(1, Math.abs(x) / maxDist);
        var sc = 1.03 - norm * 0.06; // ກາງຈໍໂດດເດັ່ນຂຶ້ນໜ້ອຍໜຶ່ງ (1.03) → ຂອບຊ້າຍຂວາ (0.97) ແບບນຸ້ມນວນ
        el.style.transform = 'translate3d(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px,0) rotate(' + rot.toFixed(2) + 'deg) scale(' + sc.toFixed(3) + ')';
      });
    }
    function tick(t) {
      var lbOpen = document.querySelector('.lb.is-open');
      if (last && !lbOpen) {
        offset += (t - last) * 0.038 * dir;   // ເລື່ອນຕໍ່ເນື່ອງ ≈ 38px/ວິນາທີ
        place();
      }
      last = t;
      requestAnimationFrame(tick);
    }
    window.addEventListener('resize', layout);
    layout();
    if (!still) requestAnimationFrame(tick);
  });

  // ບັດຊ້ອນບັດ: ພາກທີ່ມີ [data-stack-under] (ໂລໂກ້ລູກຄ້າ, ກາດບໍລິການ) ຕິດຢູ່ (sticky, CSS) ແລະ ພາກ .stack-over ຖັດໄປເລື່ອນຂຶ້ນມາທັບແບບເຕັມຈໍ (ບໍ່ຫົດຂອບຊ້າຍ-ຂວາ);
  // ຍິ່ງຖືກທັບ ພາກລຸ່ມຍິ່ງຈາງລົງ (ຖ້າມີກາດທີ 3 ມາທັບກາດທີ 2 ອີກຊັ້ນ ກາດທີ 1 ຈະຈາງຫາຍໄປເລີຍ ເພື່ອບໍ່ໃຫ້ຊ້ອນກັນทะລຸ)
  document.querySelectorAll('[data-stack-under]').forEach(function (under) {
    var over = under.nextElementSibling;
    while (over && !over.classList.contains('stack-over')) over = over.nextElementSibling;
    if (!over || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var nextOver = over.hasAttribute('data-stack-under') ? over.nextElementSibling : null;
    while (nextOver && !nextOver.classList.contains('stack-over')) nextOver = nextOver.nextElementSibling;
    var ticking = false;
    // ພາກທີ່ສູງກວ່າຈໍ (ເຊັ່ນ ກາດ 4 ອັນຊ້ອນກັນໃນມືຖື): ຕິດ (sticky) ຕອນລຸ່ມຂອງພາກຮອດລຸ່ມຈໍ — ເລື່ອນເຫັນຄົບທຸກກາດກ່ອນ ແລ້ວກາດຖັດໄປຈຶ່ງຊ້ອນທັບ
    function fitTop() {
      under.style.top = '';
      var top = parseFloat(getComputedStyle(under).top) || 0, vh = window.innerHeight, h = under.offsetHeight;
      if (h > vh - top) under.style.top = Math.round(vh - h) + 'px';
    }
    fitTop();
    window.addEventListener('resize', fitTop);
    window.addEventListener('load', fitTop);
    function update() {
      ticking = false;
      var u = under.getBoundingClientRect(), o = over.getBoundingClientRect();
      var p = Math.max(0, Math.min(1, (u.bottom - o.top) / Math.max(1, u.height)));   // 0 = ຍັງບໍ່ທັບ, 1 = ທັບໝົດ
      var p2 = nextOver ? Math.max(0, Math.min(1, (o.bottom - nextOver.getBoundingClientRect().top) / Math.max(1, o.height))) : 0;
      if (p <= 0) {
        under.style.opacity = '';
        under.style.transform = '';
        under.style.filter = '';
      } else if (p >= 1 || p2 >= 1) {
        under.style.opacity = '0';
      } else if (under.classList.contains('srv-section')) {
        // ສ່ວນບໍລິການໃຫ້ເຫັນເຕັມຄົມຊັດ ແລ້ວໃຫ້ກາດ .stack-over-rays ເລື່ອນຂຶ້ນມາທັບເລີຍໂດຍບໍ່ມົວ/ບໍ່ຈາງກ່ອນ
        under.style.opacity = '1';
        under.style.transform = '';
        under.style.filter = '';
      } else {
        var sc = (1 - p * 0.065).toFixed(4);
        var ty = (-p * 22).toFixed(1);
        var bl = (p * 3.5).toFixed(2);
        under.style.opacity = ((1 - p * 0.65) * (1 - p2)).toFixed(3);
        under.style.transform = 'scale(' + sc + ') translateY(' + ty + 'px)';
        under.style.filter = p > 0.02 && window.innerWidth > 767 ? 'blur(' + bl + 'px)' : '';   // ມືຖື: ບໍ່ມົວ (ຈາງ + ຫຍໍ້ເລັກນ້ອຍເທົ່ານັ້ນ)
      }
    }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    update();
  });

  // ຄ່ອຍໆລອຍສະໂລຂຶ້ນມາເມື່ອເລື່ອນລົງມາເຖິງ ([data-reveal]); ເລື່ອນຂຶ້ນ-ລົງ ຈະສະໂລລອຍຂຶ້ນ ແລະ ສະໂລລົງລຸ່ມຫາຍໄປຕາມທີ່ຜູ້ໃຊ້ຕ້ອງການ
  if ('IntersectionObserver' in window) {
    var revealIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('is-in');
        } else {
          en.target.classList.remove('is-in');
        }
      });
    }, { rootMargin: '0px 0px -4% 0px', threshold: 0.08 });
    document.querySelectorAll('[data-reveal]').forEach(function (el) { revealIO.observe(el); });
  } else {
    document.querySelectorAll('[data-reveal]').forEach(function (el) { el.classList.add('is-in'); });
  }

  // ສະໄລດ໌ ([data-carousel] ໃນ components/works.php): ປຸ່ມ ‹ › ເລື່ອນທີລະໜ້າ (ເທົ່າຄວາມກວ້າງທີ່ເຫັນ), ຈຸດລຸ່ມ = ຈຳນວນໜ້າ ກົດໄປໜ້ານັ້ນໄດ້.
  // ບັດພໍໃນໜ້າດຽວ → .is-static (ເຊື່ອງປຸ່ມ ແລະ ຈຸດ)
  document.querySelectorAll('[data-carousel]').forEach(function (box) {
    var track = box.querySelector('[data-car-track]');
    var prev = box.querySelector('[data-car-prev]'), next = box.querySelector('[data-car-next]');
    var dotsBox = box.querySelector('[data-car-dots]');
    if (!track) return;
    var pages = 1, ticking = false;
    function step() { return track.clientWidth + (parseFloat(getComputedStyle(track).columnGap) || 0); }
    function current() {
      var max = track.scrollWidth - track.clientWidth;
      return track.scrollLeft >= max - 2 ? pages - 1 : Math.round(track.scrollLeft / step());
    }
    function go(i) { track.scrollTo({ left: Math.max(0, Math.min(pages - 1, i)) * step() }); }
    function paint() {
      ticking = false;
      var cur = current();
      Array.prototype.forEach.call(dotsBox.children, function (d, i) { d.classList.toggle('is-active', i === cur); });
      if (prev) prev.disabled = cur <= 0;
      if (next) next.disabled = cur >= pages - 1;
    }
    function build() {
      pages = Math.max(1, Math.ceil((track.scrollWidth - track.clientWidth - 2) / step()) + 1);
      box.classList.toggle('is-static', pages < 2);
      dotsBox.innerHTML = '';
      for (var i = 0; i < pages; i++) {
        var d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', String(i + 1));
        d.addEventListener('click', go.bind(null, i));
        dotsBox.appendChild(d);
      }
      paint();
    }
    if (prev) prev.addEventListener('click', function () { go(current() - 1); });
    if (next) next.addEventListener('click', function () { go(current() + 1); });
    track.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(paint); } }, { passive: true });
    window.addEventListener('resize', build);
    build();
  });

  // ຕົວເລກນັບຂຶ້ນຈາກ 0 ເມື່ອເລື່ອນເຖິງ ([data-count] ໃນ index.php .stack-hub ແລະ ກ່ອງສະຖິຕິ footer). ເມື່ອເລື່ອນຂຶ້ນເທິງ ຣີເຊັດຕົວເລກເພື່ອໃຫ້ນັບສະໂລຂຶ້ນໃໝ່ເມື່ອເລື່ອນລົງມາອີກ
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var countIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        var el = en.target;
        var to = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (en.isIntersecting) {
          if (el._counted) return;
          el._counted = true;
          var t0 = 0;
          if (el._countRaf) cancelAnimationFrame(el._countRaf);
          (function step(t) {
            if (!t0) t0 = t;
            var k = Math.min(1, (t - t0) / 1400);
            var ease = 1 - Math.pow(1 - k, 3);
            el.textContent = Math.round(to * ease).toLocaleString('en-US');
            if (k < 1) {
              el._countRaf = requestAnimationFrame(step);
            } else {
              el.textContent = to.toLocaleString('en-US');
              el._countRaf = null;
            }
          })(performance.now());
        } else if (en.boundingClientRect.top > 0) {
          // ເມື່ອເລື່ອນຂຶ້ນເທິງ (ຫຼຸດລົງລຸ່ມໜ້າຈໍ): ຣີເຊັດຕົວເລກກັບເປັນ 0 ເພື່ອໃຫ້ນັບໃໝ່ເມື່ອເລື່ອນລົງມາອີກ
          if (el._countRaf) { cancelAnimationFrame(el._countRaf); el._countRaf = null; }
          el._counted = false;
          el.textContent = '0';
        }
      });
    }, { rootMargin: '0px 0px 0px 0px', threshold: 0.1 });
    document.querySelectorAll('[data-count]').forEach(function (el) { el.textContent = '0'; countIO.observe(el); });
  }

  // ເສັ້ນແສງ ([data-rays] ໃນ index.php): ເວລາເລື່ອນລົງ ເສັ້ນຈະຍາວລົງມາຈົນຕິດບັອກ WlaoDev (--ray-p: 0 → 1) ແລະ ເວລາເລື່ອນກັບຂຶ້ນ ເສັ້ນຈະຫົດກັບຄືນ (1 → 0)
  document.querySelectorAll('[data-rays]').forEach(function (el) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { el.style.setProperty('--ray-p', '1'); return; }
    var ticking = false;
    function update() {
      ticking = false;
      var r = el.getBoundingClientRect(), vh = window.innerHeight;
      var start = vh * 0.85, end = vh * 0.42;
      var p = Math.max(0, Math.min(1, (start - r.top) / (start - end)));
      el.style.setProperty('--ray-p', p.toFixed(4));
    }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    window.addEventListener('resize', update);
    update();
  });

  // ຫົວຂໍ້ພິມເອງໃນ hero ໜ້າຫຼັກ ([data-typing] ຈາກ components/hero.php): ພິມ → ຢຸດ → ລຶບ → ຄຳຕໍ່ໄປ
  document.querySelectorAll('[data-typing]').forEach(function (el) {
    var words;
    try { words = JSON.parse(el.getAttribute('data-typing')); } catch (e) { return; }
    if (!words || words.length < 2) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    // ແຍກເປັນຕົວອັກສອນ (ສະຫຼະ/ວັນນະຍຸດລາວ ຕິດກັບພະຍັນຊະນະ) ເພື່ອບໍ່ໃຫ້ສະແດງເຄື່ອງໝາຍລອຍ
    var seg = window.Intl && Intl.Segmenter ? new Intl.Segmenter(undefined, { granularity: 'grapheme' }) : null;
    var split = function (w) { return seg ? Array.from(seg.segment(w), function (x) { return x.segment; }) : Array.from(w); };
    var wordIndex = 0, chars = split(words[0]), pos = chars.length, deleting = true;
    function tick() {
      var delay;
      if (deleting) {
        pos--;
        delay = 45;
        if (pos <= 0) { deleting = false; wordIndex = (wordIndex + 1) % words.length; chars = split(words[wordIndex]); delay = 300; }
      } else {
        pos++;
        delay = 95;
        if (pos >= chars.length) { deleting = true; delay = 2000; }
      }
      el.textContent = chars.slice(0, pos).join('');
      setTimeout(tick, delay);
    }
    setTimeout(tick, 2000);
  });

  // ແຖບໝວດ ([data-tabs] ຈາກ components/solutions.php): ກົດແຖບ = ສະແດງກຸ່ມກ່ອງຂອງແຖບນັ້ນ;
  // ປຸ່ມ › ຂຶ້ນສະເພາະເມື່ອແຖບລົ້ນຈໍ (ມືຖື) ແລ້ວເລື່ອນໄປທາງຂວາ
  document.querySelectorAll('[data-tabs]').forEach(function (box) {
    var tabs = box.querySelectorAll('[data-tab]');
    var strip = box.querySelector('[data-tabs-strip]');
    var next = box.querySelector('[data-tabs-next]');
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var key = tab.getAttribute('data-tab');
        tabs.forEach(function (t) {
          var on = t === tab;
          t.classList.toggle('active', on);
          t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        box.querySelectorAll('[data-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== key; });
        // ເລື່ອນສະເພາະແຖບລວງນອນຫາກລົ້ນຈໍ (ບໍ່ໃຫ້ໜ້າເວັບກະຕຸກ)
        if (strip && strip.scrollWidth > strip.clientWidth) {
          var tabLeft = tab.offsetLeft;
          var tabWidth = tab.offsetWidth;
          var stripScroll = strip.scrollLeft;
          var stripWidth = strip.clientWidth;
          if (tabLeft < stripScroll) {
            strip.scrollTo({ left: Math.max(0, tabLeft - 12), behavior: 'smooth' });
          } else if (tabLeft + tabWidth > stripScroll + stripWidth) {
            strip.scrollTo({ left: tabLeft + tabWidth - stripWidth + 12, behavior: 'smooth' });
          }
        }
      });
    });
    if (strip && next) {
      var updateNext = function () {
        next.hidden = strip.scrollLeft + strip.clientWidth >= strip.scrollWidth - 4;
      };
      next.addEventListener('click', function () { strip.scrollBy({ left: strip.clientWidth * 0.6, behavior: 'smooth' }); });
      strip.addEventListener('scroll', updateNext);
      window.addEventListener('resize', updateNext);
      updateNext();
    }
  });

  // ຄຣິກລິ້ງ #solutions (ແບນເນີ POS ຫຼື ກາດລະບົບ) → ສະຫຼັບແທັບ "ລະບົບ POS" ແລະ ເລື່ອນລົງມາຫາກາດລະບົບແຕ່ລະຢ່າງ
  function activatePosTab() {
    var posTab = document.querySelector('[data-tab="pos"]');
    if (posTab && !posTab.classList.contains('active')) {
      posTab.click();
    }
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href*="#solutions"]');
    if (!a) return;
    var targetSec = document.getElementById('solutions');
    if (targetSec) {
      activatePosTab();
      if (!a.pathname || window.location.pathname.replace(/\/$/, '') === a.pathname.replace(/\/$/, '')) {
        e.preventDefault();
        targetSec.scrollIntoView({ behavior: 'smooth' });
        if (history.replaceState) {
          history.replaceState(null, '', '#solutions');
        }
      }
    }
  });

  if (window.location.hash === '#solutions') {
    activatePosTab();
    var solSec = document.getElementById('solutions');
    if (solSec) {
      setTimeout(function () { solSec.scrollIntoView({ behavior: 'smooth' }); }, 150);
    }
  }

  // Modal ສະແດງລະບົບໃນຈໍຄອມ (Computer Monitor Modal)
  var demoModal = document.getElementById('posDemoModal');
  if (demoModal) {
    var demoImg = document.getElementById('posDemoImage');
    var demoTitle = document.getElementById('posDemoTitle');
    var demoHint = document.getElementById('posDemoHint');
    var demoContactBtn = document.getElementById('posDemoContactBtn');

    function openDemo(imgUrl, title, subject, hint) {
      if (demoImg && imgUrl) demoImg.src = imgUrl;
      if (demoTitle && title) demoTitle.textContent = title;
      if (demoHint && hint) demoHint.textContent = hint;
      if (demoContactBtn && subject) {
        demoContactBtn.href = window.APP.base + 'pages/contact?subject=' + encodeURIComponent(subject);
      }
      demoModal.hidden = false;
      demoModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeDemo() {
      demoModal.hidden = true;
      demoModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-demo-trigger]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        var img = el.getAttribute('data-demo-img');
        var title = el.getAttribute('data-demo-title');
        var subject = el.getAttribute('data-demo-subject');
        var hint = el.getAttribute('data-demo-hint');
        openDemo(img, title, subject, hint);
      });
    });

    demoModal.querySelectorAll('[data-demo-close]').forEach(function (btn) {
      btn.addEventListener('click', closeDemo);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !demoModal.hidden) {
        closeDemo();
      }
    });
  }

  // ບັດຜົນງານ (components/work_card.php): ຖ້າຂໍ້ຄວາມຍາວ ກົດປຸ່ມ [data-wk-expand] ("ເບິ່ງເພີ່ມເຕີມ") ແລ້ວສະແດງຂໍ້ຄວາມເຕັມໃນບັດເລີຍ
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-wk-expand]');
    if (!btn) return;
    ev.preventDefault();
    ev.stopPropagation();
    var card = btn.closest('.wk-card');
    if (card) card.classList.add('is-expanded');
  });

  document.querySelectorAll('[data-filter-group]').forEach(function (group) {
    group.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-filter]');
      if (!btn) return;
      var value = btn.getAttribute('data-filter');
      group.querySelectorAll('[data-filter]').forEach(function (b) { b.classList.toggle('active', b === btn); });
      document.querySelectorAll('[data-filter-item]').forEach(function (item) {
        item.hidden = value !== '' && item.getAttribute('data-filter-item') !== value;
      });
    });
  });

  // ---------- Fastwork Style Solution Detail Page (Gallery, Tabs, Corporate Checkbox, Share/Save) ----------
  var solGallery = document.getElementById('solutionGallery');
  if (solGallery) {
    var mainImg = document.getElementById('galleryMainImg');
    var counter = document.getElementById('galleryCounter');
    var caption = document.getElementById('galleryCaption');
    var thumbs = Array.from(solGallery.querySelectorAll('.thumb-btn'));
    var thumbsStrip = document.getElementById('galleryThumbs');
    var prevBtn = solGallery.querySelector('[data-gallery-prev]');
    var nextBtn = solGallery.querySelector('[data-gallery-next]');
    var thumbsPrev = solGallery.querySelector('[data-thumbs-prev]');
    var thumbsNext = solGallery.querySelector('[data-thumbs-next]');
    var viewport = document.getElementById('galleryViewport');
    var currentIndex = 0;

    // ລູກສອນ ‹ › ຮູບໃຫຍ່: ຮອດຮູບສຸດທ້າຍ/ຮູບທຳອິດແລ້ວ ຢຸດ (ບໍ່ວົນກັບໄປຮູບທຳອິດ/ຮູບສຸດທ້າຍ) → ປຸ່ມຈາງ ກົດບໍ່ໄດ້
    function syncNavArrows() {
      if (prevBtn) prevBtn.disabled = currentIndex <= 0;
      if (nextBtn) nextBtn.disabled = currentIndex >= thumbs.length - 1;
    }

    function goToImage(index) {
      if (!thumbs.length) return;
      index = Math.max(0, Math.min(thumbs.length - 1, index));
      if (index === currentIndex && mainImg && mainImg.getAttribute('src') === thumbs[index].getAttribute('data-img')) { syncNavArrows(); return; }
      currentIndex = index;

      var targetThumb = thumbs[currentIndex];
      var imgUrl = targetThumb.getAttribute('data-img');
      var capText = targetThumb.getAttribute('data-caption') || '';

      if (mainImg) {
        mainImg.style.opacity = '0.35';
        setTimeout(function () {
          mainImg.src = imgUrl;
          mainImg.parentNode.style.setProperty('--bg', 'url("' + imgUrl + '")'); // ພື້ນຫຼັງມົວ (pages.css)
          mainImg.style.opacity = '1';
        }, 120);
      }
      if (caption) caption.textContent = capText;
      if (counter) counter.textContent = (currentIndex + 1) + '/' + thumbs.length;

      thumbs.forEach(function (btn, i) {
        btn.classList.toggle('active', i === currentIndex);
      });

      syncNavArrows();

      // ເລື່ອນສະເພາະແຖບຮູບນ້ອຍ ໃຫ້ຮູບທີ່ເລືອກຢູ່ໃນສາຍຕາ — ບໍ່ໃຊ້ scrollIntoView (ມັນເລື່ອນທັງໜ້າ ເຮັດໃຫ້ໜ້າເດັ້ງ)
      if (targetThumb && thumbsStrip) {
        var sr = thumbsStrip.getBoundingClientRect(), tr = targetThumb.getBoundingClientRect();
        if (tr.left < sr.left) thumbsStrip.scrollBy({ left: tr.left - sr.left, behavior: 'smooth' });
        else if (tr.right > sr.right) thumbsStrip.scrollBy({ left: tr.right - sr.right, behavior: 'smooth' });
      }
    }
    syncNavArrows();

    // ກົດຮູບໃຫຍ່ / ປຸ່ມ ⤢ → ເບິ່ງຮູບເຕັມຈໍ (components/gallery_viewer.php, assets/js/gallery_viewer.js);
    // ປິດແລ້ວ ແກເລີຣີໃນໜ້າໄປຮູບທີ່ເບິ່ງຄ້າງໄວ້
    var zoomBtn = document.getElementById('btnGalleryZoom');
    function openLightbox() { if (window.galleryViewerOpen) window.galleryViewerOpen(currentIndex); }
    window.galleryViewerOnClose = function (i) { if (thumbs.length && i !== currentIndex) goToImage(i); };
    // ປັດຊ້າຍ/ຂວາ ທີ່ຮູບໃຫຍ່ (ໂທລະສັບ) → ຮູບຖັດໄປ/ກ່ອນໜ້າ; ປັດແລ້ວບໍ່ເປີດເຕັມຈໍ
    var swX = null, swY = 0, swiped = false;
    if (viewport && thumbs.length > 1) {
      viewport.addEventListener('touchstart', function (e) { swX = e.touches[0].clientX; swY = e.touches[0].clientY; swiped = false; }, { passive: true });
      viewport.addEventListener('touchend', function (e) {
        if (swX === null) return;
        var dx = e.changedTouches[0].clientX - swX, dy = e.changedTouches[0].clientY - swY;
        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) { swiped = true; goToImage(currentIndex + (dx < 0 ? 1 : -1)); }
        swX = null;
      }, { passive: true });
      viewport.style.touchAction = 'pan-y';   // ເລື່ອນໜ້າຂຶ້ນ-ລົງໄດ້ຄືເກົ່າ, ຊ້າຍ-ຂວາ = ປ່ຽນຮູບ
    }
    if (viewport) viewport.addEventListener('click', function () { if (swiped) { swiped = false; return; } openLightbox(); });
    if (zoomBtn) zoomBtn.addEventListener('click', function (e) { e.stopPropagation(); openLightbox(); });

    thumbs.forEach(function (btn, i) {
      btn.addEventListener('click', function () {
        goToImage(i);
      });
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        goToImage(currentIndex - 1);
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        goToImage(currentIndex + 1);
      });
    }

    // ມີປຸ່ມ ‹ (data-thumbs-prev) → ເລື່ອນເທື່ອລະ 1 ຮູບ; ຢູ່ຕົ້ນແຖບ = ເຊື່ອງ ‹, ຢູ່ທ້າຍແຖບ = ເຊື່ອງ ›
    // ບໍ່ມີປຸ່ມ ‹ (ໜ້າສິນຄ້າ/ລະບົບ) → ເລື່ອນເກືອບ 1 ໜ້າຂອງແຖບ ຄືເກົ່າ
    function thumbStep() {
      var first = thumbs[0];
      var gap = thumbsStrip ? parseFloat(getComputedStyle(thumbsStrip).columnGap) || 0 : 0;
      return first ? first.getBoundingClientRect().width + gap : 180;
    }
    function syncThumbArrows() {
      if (!thumbsStrip || !thumbsPrev) return;
      var max = thumbsStrip.scrollWidth - thumbsStrip.clientWidth;
      thumbsPrev.hidden = thumbsStrip.scrollLeft <= 2;
      if (thumbsNext) thumbsNext.hidden = thumbsStrip.scrollLeft >= max - 2;
    }
    if (thumbsPrev && thumbsStrip) {
      thumbsPrev.addEventListener('click', function () {
        thumbsStrip.scrollBy({ left: -thumbStep(), behavior: 'smooth' });
      });
    }
    if (thumbsNext && thumbsStrip) {
      thumbsNext.addEventListener('click', function () {
        var step = thumbsPrev ? thumbStep() : Math.max(180, thumbsStrip.clientWidth * .8);
        thumbsStrip.scrollBy({ left: step, behavior: 'smooth' });
      });
    }
    if (thumbsPrev && thumbsStrip) {
      thumbsStrip.addEventListener('scroll', syncThumbArrows, { passive: true });
      window.addEventListener('resize', syncThumbArrows);
      syncThumbArrows();
    }

    // Checkbox corporate
    var chkCorp = document.getElementById('chkCorporate');
    var btnConsult = document.getElementById('btnConsult');
    if (chkCorp && btnConsult) {
      var baseHref = btnConsult.getAttribute('data-base-url');
      var solTitle = btnConsult.getAttribute('data-title');
      chkCorp.addEventListener('change', function () {
        if (chkCorp.checked) {
          var corpSubject = 'ສົນໃຈລະບົບ ' + solTitle + ' [ຂໍໃບສະເໜີລາຄາໃນນາມບໍລິສັດ]';
          btnConsult.href = window.APP.base + 'pages/contact?subject=' + encodeURIComponent(corpSubject);
        } else {
          btnConsult.href = baseHref;
        }
      });
    }

    // Share button
    var btnShare = document.getElementById('btnShareSolution');
    if (btnShare) {
      btnShare.addEventListener('click', function () {
        if (navigator.clipboard) {
          navigator.clipboard.writeText(window.location.href).then(function () {
            if (window.showToast) {
              window.showToast(window.APP.lang === 'lo' ? 'ຄັດລອກລິ້ງສຳເລັດແລ້ວ!' : (window.APP.lang === 'th' ? 'คัดลอกลิงก์สำเร็จแล้ว!' : 'Link copied to clipboard!'));
            }
          });
        }
      });
    }

    // Save button
    var btnSave = document.getElementById('btnSaveSolution');
    var lblSave = document.getElementById('lblSave');
    if (btnSave && lblSave) {
      var saveKey = 'saved_sol_' + (window.location.search || 'default');
      var isSaved = localStorage.getItem(saveKey) === '1';
      function updateSaveUI() {
        btnSave.querySelector('.action-icon').textContent = isSaved ? '❤️' : '🤍';
        lblSave.textContent = isSaved ? (window.APP.lang === 'lo' ? 'ບັນທຶກແລ້ວ' : 'บันทึกแล้ว') : (window.APP.lang === 'lo' ? 'ບັນທຶກ' : 'บันทึก');
      }
      updateSaveUI();
      btnSave.addEventListener('click', function () {
        isSaved = !isSaved;
        if (isSaved) localStorage.setItem(saveKey, '1');
        else localStorage.removeItem(saveKey);
        updateSaveUI();
        if (window.showToast) {
          window.showToast(isSaved ? 'ບັນທຶກລາຍການນີ້ແລ້ວ' : 'ຍົກເລີກການບັນທຶກແລ້ວ');
        }
      });
    }

    // Scrollspy for solution tabs
    var tabLinks = document.querySelectorAll('[data-sol-tabs] .sol-tab');
    var sections = Array.from(document.querySelectorAll('.sol-section'));
    if (tabLinks.length && sections.length) {
      window.addEventListener('scroll', function () {
        var scrollPos = window.scrollY + 160;
        var activeSec = null;
        sections.forEach(function (sec) {
          if (sec.offsetTop <= scrollPos) {
            activeSec = sec;
          }
        });
        if (activeSec) {
          var id = activeSec.getAttribute('id');
          tabLinks.forEach(function (link) {
            link.classList.toggle('active', link.getAttribute('href') === '#' + id);
          });
        }
      }, { passive: true });
    }
  }

  // ສະໄລໂຊແບນເນີໜ້າຫຼັກ ([data-promo-slider] ໃນ components/promo_banner.php): ຈາງເຂົ້າ-ອອກ ເລື່ອນເອງທຸກ 5 ວິ,
  // ຢຸດເມື່ອເອົາເມົ້າຊີ້ / ແທັບຖືກເຊື່ອງ, ປຸ່ມ ‹ › + ຈຸດ, ປັດທາງຂ້າງໃນມືຖື
  document.querySelectorAll('[data-promo-slider]').forEach(function (slider) {
    var slides = slider.querySelectorAll('[data-slide]');
    var dots = slider.querySelectorAll('[data-slide-dot]');
    if (slides.length < 2) return;
    var cur = 0, timer = null, hover = false, swiped = false;
    function go(i) {
      cur = (i + slides.length) % slides.length;
      slides.forEach(function (s, k) {
        var on = k === cur;
        s.classList.toggle('is-active', on);
        s.setAttribute('aria-hidden', on ? 'false' : 'true');
        s.tabIndex = on ? 0 : -1;
      });
      dots.forEach(function (d, k) { d.classList.toggle('is-active', k === cur); });
    }
    function play() { stop(); timer = setInterval(function () { if (!hover && !document.hidden) go(cur + 1); }, 5000); }
    function stop() { if (timer) clearInterval(timer); timer = null; }
    var prev = slider.querySelector('[data-slide-prev]'), next = slider.querySelector('[data-slide-next]');
    if (prev) prev.addEventListener('click', function () { go(cur - 1); play(); });
    if (next) next.addEventListener('click', function () { go(cur + 1); play(); });
    dots.forEach(function (d) { d.addEventListener('click', function () { go(parseInt(d.getAttribute('data-slide-dot'), 10) || 0); play(); }); });
    slider.addEventListener('mouseenter', function () { hover = true; });
    slider.addEventListener('mouseleave', function () { hover = false; });
    var x0 = null;
    slider.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; swiped = false; }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40) { swiped = true; go(cur + (dx < 0 ? 1 : -1)); play(); }
    });
    slider.addEventListener('click', function (e) { if (swiped && e.target.closest('[data-slide]')) { e.preventDefault(); swiped = false; } }, true);
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) play();
  });

  // ຟອມສັ່ງຊື້ສິນຄ້າ ([data-order-form] ໃນ components/product_order_form.php): ປັບຈຳນວນ + ຄິດໄລ່ຍອດລວມ (ພ້ອມສ່ວນຫຼຸດ 5%), ຕິກເລືອກຂົນສົ່ງ/ທະນາຄານ
  function initOrderForm(form) {   // ເອີ້ນຊ້ຳໄດ້: ຟອມທີ່ເພີ່ມທີຫຼັງ (modal ຕິດຕາມອໍເດີ — assets/js/order_manage.js)
    var unitPrice = parseFloat(form.getAttribute('data-unit-price') || '0');
    var oldUnitPrice = parseFloat(form.getAttribute('data-old-unit-price') || '0');
    var qtyInput = form.querySelector('[data-qty-input]');
    var totalEls = form.querySelectorAll('[data-order-total]');   // ຍອດລວມ (ແຖບສິນຄ້າ + ຍອດທີ່ຕ້ອງໂອນ)
    var totalEl = totalEls[0];
    var oldTotalEl = form.querySelector('[data-order-old-total]');
    var saveEl = form.querySelector('[data-order-save]');

    function updateTotal() {
      if (!qtyInput || !totalEl) return;
      var q = Math.max(1, Math.min(parseInt(qtyInput.getAttribute('max'), 10) || 999, parseInt(qtyInput.value, 10) || 1));
      qtyInput.value = q;
      if (unitPrice > 0) {
        var net = q * unitPrice;
        totalEls.forEach(function (el) { el.textContent = net.toLocaleString('en-US') + ' Kip'; });
        if (oldUnitPrice > unitPrice) {
          var gross = q * oldUnitPrice;
          var saved = Math.max(0, gross - net);
          if (oldTotalEl) oldTotalEl.textContent = gross.toLocaleString('en-US') + ' Kip';
          if (saveEl) saveEl.textContent = 'ປະຢັດ ' + saved.toLocaleString('en-US') + ' Kip';
        }
      }
    }
    form.querySelectorAll('[data-qty-step]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var step = parseInt(btn.getAttribute('data-qty-step'), 10) || 0;
        qtyInput.value = Math.max(1, (parseInt(qtyInput.value, 10) || 1) + step);
        updateTotal();
      });
    });
    if (qtyInput) qtyInput.addEventListener('input', updateTotal);

    function updateBankQrDisplay(card) {
      if (!card) return;
      var qrBox = form.querySelector('[data-bank-qr-box]');
      if (!qrBox) return;

      var bName = card.getAttribute('data-name') || '';
      var bAcc = card.getAttribute('data-acc') || '';
      var bAccName = card.getAttribute('data-acc-name') || '';
      var bLogo = card.getAttribute('data-logo') || '';
      var bQr = card.getAttribute('data-qr') || '';

      var logoEl = qrBox.querySelector('#activeBankLogo');
      var nameEl = qrBox.querySelector('#activeBankName');
      var accEl = qrBox.querySelector('#activeBankAcc');
      var accNameEl = qrBox.querySelector('#activeBankAccName');
      var copyBtn = qrBox.querySelector('#copyBankAccBtn');
      var qrWrap = qrBox.querySelector('#activeBankQrWrap');
      var qrImg = qrBox.querySelector('#activeBankQrImg');
      var guideEl = qrBox.querySelector('#activeBankQrGuide');
      var qrBtns = qrBox.querySelector('#activeBankQrBtns');
      var dlBtn = qrBox.querySelector('#activeBankQrDownload');
      var viewBtn = qrBox.querySelector('#activeBankQrView');

      if (logoEl) {
        if (bLogo) {
          logoEl.src = bLogo;
          logoEl.style.display = '';
        } else {
          logoEl.style.display = 'none';
        }
      }
      if (nameEl) nameEl.textContent = bName;
      if (accEl) accEl.textContent = bAcc;
      if (accNameEl) accNameEl.textContent = bAccName ? '(' + bAccName + ')' : '';
      if (copyBtn) copyBtn.setAttribute('data-copy', bAcc);

      var safeName = bName.toLowerCase().replace(/[^a-z0-9]+/g, '-');
      if (bQr) {
        qrBox.removeAttribute('data-no-qr');
        if (qrWrap) qrWrap.style.display = '';
        if (qrImg) qrImg.src = bQr;
        if (guideEl) guideEl.textContent = 'ສະແກນ QR ຜ່ານແອັບທະນາຄານ ຫຼື ດາວໂຫລດຮູບໄປສະແກນຈ່າຍ';
        if (qrBtns) qrBtns.style.display = '';
        if (dlBtn) {
          dlBtn.href = bQr;
          dlBtn.setAttribute('download', 'qr-' + safeName + '.jpg');
        }
        if (viewBtn) viewBtn.href = bQr;
      } else {
        qrBox.setAttribute('data-no-qr', '1');
        if (qrWrap) qrWrap.style.display = 'none';
        if (qrBtns) qrBtns.style.display = 'none';
        if (guideEl) guideEl.textContent = 'ບໍ່ມີ QR Code ສຳລັບບັນຊີນີ້ — ກະລຸນາໂອນເຂົ້າເລກບັນຊີຂ້າງເທິງນີ້';
      }
    }

    form.querySelectorAll('.po-courier-card input, .po-bank-card input, .po-bank-pill input').forEach(function (radio) {
      radio.addEventListener('change', function () {
        var group = form.querySelectorAll('input[name="' + radio.name + '"]');
        group.forEach(function (r) {
          var card = r.closest('.po-courier-card, .po-bank-card, .po-bank-pill');
          if (card) {
            card.classList.toggle('is-selected', r.checked);
            if (r.checked && radio.name === 'bank_account_id') {
              updateBankQrDisplay(card);
            }
          }
        });
      });
    });

    var copyBtn = form.querySelector('#copyBankAccBtn');
    if (copyBtn) {
      copyBtn.addEventListener('click', function () {
        var text = copyBtn.getAttribute('data-copy') || '';
        if (!text) return;
        var copyTxt = copyBtn.querySelector('.mf-copy-txt') || copyBtn;
        var origText = copyTxt.textContent;
        var done = function () {
          copyBtn.classList.add('copied');
          copyTxt.textContent = 'ຄັດລອກແລ້ວ! ✓';
          setTimeout(function () {
            copyBtn.classList.remove('copied');
            copyTxt.textContent = origText;
          }, 2000);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(done).catch(function () {
            fallbackCopy(text, done);
          });
        } else {
          fallbackCopy(text, done);
        }
      });
    }

    function fallbackCopy(text, cb) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      try { document.execCommand('copy'); if (cb) cb(); } catch (e) {}
      document.body.removeChild(ta);
    }

    form.querySelectorAll('[data-dl-qr]').forEach(function (dlBtn) {
      dlBtn.addEventListener('click', function (e) {
        var href = dlBtn.getAttribute('href');
        var filename = dlBtn.getAttribute('download') || 'qr-payment.jpg';
        if (!href || href === '#') return;
        if (window.fetch) {
          e.preventDefault();
          fetch(href)
            .then(function (resp) { return resp.blob(); })
            .then(function (blob) {
              var blobUrl = window.URL.createObjectURL(blob);
              var a = document.createElement('a');
              a.href = blobUrl;
              a.download = filename;
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);
              window.URL.revokeObjectURL(blobUrl);
            })
            .catch(function () {
              window.open(href, '_blank');
            });
        }
      });
    });

    // ເລືອກແຂວງ → ດຶງລາຍຊື່ເມືອງຂອງແຂວງນັ້ນຂຶ້ນມາອັດຕະໂນມັດ
    var provSec = form.querySelector('[data-prov-map]');
    var provSel = form.querySelector('[data-province-select]');
    var distSel = form.querySelector('[data-district-select]');
    if (provSec && provSel && distSel) {
      var provMap = {};
      try { provMap = JSON.parse(provSec.getAttribute('data-prov-map') || '{}'); } catch (e) {}
      var pickTxt = distSel.getAttribute('data-placeholder') || '-- ເລືອກເມືອງ --';
      var emptyTxt = distSel.getAttribute('data-empty-placeholder') || '-- ກະລຸນາເລືອກແຂວງກ່ອນ --';
      provSel.addEventListener('change', function () {
        var list = provMap[provSel.value] || [];
        distSel.innerHTML = '';
        var firstOpt = document.createElement('option');
        firstOpt.value = '';
        firstOpt.textContent = list.length ? pickTxt : emptyTxt;
        distSel.appendChild(firstOpt);
        list.forEach(function (d) {
          var opt = document.createElement('option');
          opt.value = d;
          opt.textContent = d;
          distSel.appendChild(opt);
        });
      });
    }

    var slipInput = form.querySelector('#slipFileInput');
    var slipPlaceholder = form.querySelector('#slipPlaceholder');
    var slipPreviewBox = form.querySelector('#slipPreviewBox');
    var slipPreviewImg = form.querySelector('#slipPreviewImg');
    if (slipInput && slipPreviewBox && slipPreviewImg) {
      slipInput.addEventListener('change', function () {
        var file = slipInput.files && slipInput.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (ev) {
          slipPreviewImg.src = ev.target.result;
          if (slipPlaceholder) slipPlaceholder.hidden = true;
          slipPreviewBox.hidden = false;
        };
        reader.readAsDataURL(file);
      });
    }
  }
  window.initOrderForm = initOrderForm;
  document.querySelectorAll('[data-order-form]').forEach(initOrderForm);

  // Modal ປ໊ອບອັບສັ່ງຊື້ສິນຄ້າສຳລັບສະມາຊິກ ([data-order-modal]):
  (function initProductOrderModal() {
    var modal = document.querySelector('[data-order-modal]');
    if (!modal) return;

    function openModal() {
      if (!window.CSS || !CSS.supports || !CSS.supports('scrollbar-gutter', 'stable')) {
        var sbWidth = window.innerWidth - document.documentElement.clientWidth;
        if (sbWidth > 0) document.body.style.paddingRight = sbWidth + 'px';
      }
      modal.removeAttribute('hidden');
      modal.classList.add('is-open');
      document.documentElement.classList.add('po-modal-open');
      document.body.classList.add('po-modal-open');
      var focusable = modal.querySelector('input:not([type=hidden]):not([readonly]), select, textarea, button');
      if (focusable) {
        setTimeout(function () {
          try { focusable.focus({ preventScroll: true }); } catch (e) { try { focusable.focus(); } catch (e2) {} }
        }, 120);
      }
    }

    function closeModal() {
      if (modal.hasAttribute('hidden') && !modal.classList.contains('is-open')) return;
      modal.classList.remove('is-open');
      modal.classList.add('is-closing');
      setTimeout(function () {
        modal.classList.remove('is-closing');
        modal.setAttribute('hidden', '');
        document.documentElement.classList.remove('po-modal-open');
        document.body.classList.remove('po-modal-open');
        document.body.style.paddingRight = '';
      }, 220);
    }

    document.querySelectorAll('[data-order-modal-open]').forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
      });
    });

    modal.querySelectorAll('[data-order-modal-close]').forEach(function (closer) {
      closer.addEventListener('click', function (e) {
        e.preventDefault();
        closeModal();
      });
    });

    document.addEventListener('keydown', function (e) {
      if ((e.key === 'Escape' || e.keyCode === 27) && !modal.hasAttribute('hidden')) {
        closeModal();
      }
    });

    // Lightbox ເບິ່ງຮູບສິນຄ້າໃຫຍ່ ([data-po-img-view])
    var imgModal = document.getElementById('poImgModal');
    if (imgModal) {
      document.querySelectorAll('[data-po-img-view]').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
          e.preventDefault();
          imgModal.hidden = false;
          imgModal.setAttribute('aria-hidden', 'false');
        });
      });
      imgModal.querySelectorAll('[data-po-img-close]').forEach(function (closer) {
        closer.addEventListener('click', function (e) {
          e.preventDefault();
          imgModal.hidden = true;
          imgModal.setAttribute('aria-hidden', 'true');
        });
      });
      document.addEventListener('keydown', function (e) {
        if ((e.key === 'Escape' || e.keyCode === 27) && !imgModal.hidden) {
          imgModal.hidden = true;
          imgModal.setAttribute('aria-hidden', 'true');
        }
      });
    }

    if (modal.getAttribute('data-auto-open') === '1') {
      openModal();
    }
  })();

  // ບໍລິການຂອງພວກເຮົາ ([data-srv-slider] ໃນ index.php) — ກາດຂະຫຍາຍເມື່ອຄຣິກ (Expandable Cards):
  // ເລີ່ມຕົ້ນກາດເຄິ່ງກາງໃຫຍ່ກວ່າໝູ່ (.is-center), ກາດອື່ນນ້ອຍສະແດງໄອຄອນຢູ່ກາງ;
  // ເມື່ອຄຣິກກາດໃດ ກາດນັ້ນຈະຂະຫຍາຍອອກພ້ອມຕົວໜັງສື ແລະ ໄອຄອນຍ້າຍໄປຢູ່ມຸມລຸ່ມຂວາ.
  (function initServiceSlider() {
    var slider = document.querySelector('[data-srv-slider]');
    if (!slider) return;
    var track = slider.querySelector('[data-srv-track]');
    if (!track) return;
    var parentSection = slider.closest('.srv-section') || slider;
    var prevBtn = parentSection.querySelector('[data-srv-prev]');
    var nextBtn = parentSection.querySelector('[data-srv-next]');

    var items = Array.prototype.slice.call(track.querySelectorAll('.srv-slide-col'));
    if (!items.length) return;

    var activeIndex = Math.floor(items.length / 2);
    items.forEach(function (col, idx) {
      if (col.classList.contains('is-center')) activeIndex = idx;
      col.style.setProperty('--i', idx);
    });

    function setActive(index, scrollIfNeeded) {
      activeIndex = (index + items.length) % items.length;
      items.forEach(function (col, idx) {
        col.classList.toggle('is-center', idx === activeIndex);
      });
      if (scrollIfNeeded && track.scrollWidth > track.clientWidth + 8) {
        var target = items[activeIndex];
        if (target) {
          var left = target.offsetLeft - (track.clientWidth - target.offsetWidth) / 2;
          track.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
        }
      }
    }

    var hoverTimer = null;
    items.forEach(function (col, idx) {
      col.addEventListener('click', function () {
        if (hoverTimer) clearTimeout(hoverTimer);
        setActive(idx, true);
      });
      // ເມື່ອເມົາຖືກ (Hover) ໃຫ້ມີເອັບເຟກຂະຫຍາຍກາດຄືກັນກັບຕອນກົດ ແບບສະໂລນຸ້ມນວນ
      col.addEventListener('mouseenter', function () {
        if (hoverTimer) clearTimeout(hoverTimer);
        hoverTimer = setTimeout(function () {
          setActive(idx, false);
        }, 60);
      });
      col.addEventListener('mouseleave', function () {
        if (hoverTimer) clearTimeout(hoverTimer);
      });
      col.addEventListener('focusin', function () {
        setActive(idx, false);
      });
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        setActive(activeIndex - 1, true);
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        setActive(activeIndex + 1, true);
      });
    }

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) {
          slider.classList.add('is-in');
        }
      }, { threshold: 0.18 }).observe(slider);
    } else {
      slider.classList.add('is-in');
    }

    setActive(activeIndex, false);
    // ມືຖື (ກາດເລື່ອນທາງຂວາງ): ເປີດມາເຫັນກາດຫຼັກ (.is-center "ພັດທະນາລະບົບ POS") ເປັນກາດທຳອິດເລີຍ ຄືກັບຈໍຄອມ — ບໍ່ເລີ່ມຈາກກາດຊ້າຍສຸດ
    function jumpToCenter() {
      if (track.scrollWidth <= track.clientWidth + 8) return;
      var t = items[activeIndex];
      if (!t) return;
      var sb = track.style.scrollBehavior;
      track.style.scrollBehavior = 'auto';
      track.scrollLeft = Math.max(0, t.offsetLeft - (track.clientWidth - t.offsetWidth) / 2);
      track.style.scrollBehavior = sb;
    }
    jumpToCenter();
    window.addEventListener('load', jumpToCenter);
  })();
})();

/* ເມນູສະມາຊິກທີ່ແຖບເທິງ (components/member_menu.php): ກົດ ☰ + ຮູບ → ເປີດ/ປິດ; ກົດຂ້າງນອກ / Esc → ປິດ */
(function () {
  var nm = document.querySelector('[data-nm]');
  if (!nm) return;
  var btn = nm.querySelector('[data-nm-toggle]');
  function set(open) {
    if (open && typeof window.closeAllOverlays === 'function') {
      window.closeAllOverlays('member');
    }
    nm.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  btn.addEventListener('click', function (e) { e.stopPropagation(); set(!nm.classList.contains('open')); });
  document.addEventListener('click', function (e) { if (!nm.contains(e.target)) set(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });

  document.addEventListener('wlao:close-overlays', function (e) {
    if (e.detail && e.detail.except === 'member') return;
    if (nm.classList.contains('open')) set(false);
  });
})();
