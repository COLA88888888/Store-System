/**
 * ຫ້ອງແຊັດ 3 ຖັນ (pages/contact.php, css/chat_room.css):
 *   - ແທັບຖັນຂວາ ([data-cr-tab]), ປຸ່ມ → ເປີດ/ປິດຖັນຂວາ
 *   - ແທັບ "ມີເດຍ ແລະ ໄຟລ໌": ຮູບພາບ ແລະ ວິດີໂອ / ໄຟລ໌ + ເພີ່ມໄຟລ໌ໃໝ່ເອງ (chat:messages), ເບິ່ງຮູບ/ວິດີໂອເຕັມຈໍ [data-cr-viewer]
 *   - popup ຊ່ວຍເຫຼືອ / ຕັ້ງຄ່າ ([data-cr-open] → [data-crm], components/chat_room_modals.php)
 *   - ຕັ້ງຄ່າ (localStorage WlaoDev.chatSettings): ສຽງແຈ້ງເຕືອນ, ແຈ້ງເຕືອນຂອງ browser, ຢືນຢັນກ່ອນສົ່ງສະຕິກເກີ,
 *     ໂຫຼດຂໍ້ມູນສະຕິກເກີໃໝ່ (ລ້າງ WlaoDev.stickers* → event chat:stickers-refresh ໃຫ້ chat_stickers.js), ທີມສີຫ້ອງແຊັດ
 *   - ຂໍ້ຄວາມໃໝ່ຈາກທີມງານ: event chat:incoming ຈາກ assets/js/chat.js → ສຽງ (WebAudio, ບໍ່ຕ້ອງມີໄຟລ໌) + Notification ຖ້າແທັບຖືກເຊື່ອງ
 */
(function () {
  var room = document.querySelector('.cr');
  if (!room) return;
  var KEY = 'WlaoDev.chatSettings';
  var settings = { sound: true, theme: 'default', stickerConfirm: true };
  try { Object.assign(settings, JSON.parse(localStorage.getItem(KEY) || '{}')); } catch (e) {}
  function save() { try { localStorage.setItem(KEY, JSON.stringify(settings)); } catch (e) {} }

  // ---------- ແທັບ / ຖັນຂວາ ----------
  var info = room.querySelector('[data-cr-info]');
  if (info) {
    // ສະຫຼັບແທັບ: ເຊື່ອງເນື້ອໃນ → ຈຸດໂຫຼດ ~0.35 ວິ → ສະແດງແທັບໃໝ່ (ແບບແອັບແຊັດ)
    var loader = info.querySelector('[data-cr-loading]'), panes = info.querySelector('[data-cr-panes]'), timer = null;
    function withLoading(show) {
      clearTimeout(timer);
      info.querySelectorAll('[data-cr-pane]').forEach(function (p) { p.hidden = true; });
      if (loader) loader.hidden = false;
      timer = setTimeout(function () { if (loader) loader.hidden = true; show(); if (panes) panes.scrollTop = 0; }, 350);
    }
    info.querySelectorAll('[data-cr-tab]').forEach(function (b) {
      b.addEventListener('click', function () {
        if (b.classList.contains('is-active')) return;
        var k = b.getAttribute('data-cr-tab');
        info.querySelectorAll('[data-cr-tab]').forEach(function (x) { x.classList.toggle('is-active', x === b); });
        withLoading(function () {
          info.querySelectorAll('[data-cr-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-cr-pane') !== k; });
        });
      });
    });
    // ມີເດຍ ແລະ ໄຟລ໌: ປຸ່ມແຍກ ຮູບພາບ ແລະ ວິດີໂອ / ໄຟລ໌ ([data-cr-seg] → [data-cr-box], ວ່າງ → [data-cr-seg-empty])
    var mediaPane = info.querySelector('[data-cr-pane="media"]'), seg = 'media';
    function paintSeg(kind) {
      if (!mediaPane) return;
      seg = kind;
      mediaPane.querySelectorAll('[data-cr-seg]').forEach(function (x) { x.classList.toggle('is-active', x.getAttribute('data-cr-seg') === kind); });
      mediaPane.querySelectorAll('[data-cr-box]').forEach(function (b) { b.hidden = b.getAttribute('data-cr-box') !== kind || !b.children.length; });
      mediaPane.querySelectorAll('[data-cr-seg-empty]').forEach(function (em) {
        var box = mediaPane.querySelector('[data-cr-box="' + em.getAttribute('data-cr-seg-empty') + '"]');
        em.hidden = em.getAttribute('data-cr-seg-empty') !== kind || !!(box && box.children.length);
      });
    }
    if (mediaPane) {
      mediaPane.querySelectorAll('[data-cr-seg]').forEach(function (b) {
        b.addEventListener('click', function () {
          if (b.classList.contains('is-active')) return;
          withLoading(function () { mediaPane.hidden = false; paintSeg(b.getAttribute('data-cr-seg')); });
        });
      });
      paintSeg('media');
      // ໄຟລ໌ທີ່ສົ່ງ/ໄດ້ຮັບໃໝ່ (event chat:messages ຈາກ chat.js) → ເພີ່ມໄວ້ທາງໜ້າ (ໃໝ່ສຸດກ່ອນ) ຖ້າຍັງບໍ່ມີ
      document.addEventListener('chat:messages', function (e) {
        ((e.detail && e.detail.messages) || []).forEach(function (m) {
          if (!m.file || mediaPane.querySelector('[data-id="' + m.id + '"]')) return;
          var f = m.file, box = mediaPane.querySelector('[data-cr-box="' + (f.kind === 'file' ? 'file' : 'media') + '"]');
          if (!box) return;
          var a = document.createElement('a');
          a.href = f.url; a.setAttribute('data-id', m.id);
          if (f.kind === 'file') {
            a.className = 'cr-file'; a.setAttribute('download', f.name);
            var ext = document.createElement('span'); ext.className = 'chat-att-ext'; ext.textContent = (f.name.split('.').pop() || 'file').slice(0, 4).toUpperCase();
            var sp = document.createElement('span'), b = document.createElement('b'), sm = document.createElement('small');
            b.textContent = f.name;
            sm.textContent = (f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB') + ' · ' + (m.date || '');
            sp.appendChild(b); sp.appendChild(sm); a.appendChild(ext); a.appendChild(sp);
          } else {
            a.className = 'cr-g' + (f.kind === 'video' ? ' is-video' : ''); a.target = '_blank'; a.rel = 'noopener';
            a.setAttribute('data-cr-view', f.kind);
            var el = document.createElement(f.kind === 'video' ? 'video' : 'img');
            if (f.kind === 'video') { el.src = f.url + '#t=0.5'; el.preload = 'metadata'; el.muted = true; el.playsInline = true; a.appendChild(el); a.appendChild(document.createElement('i')); }
            else { el.src = f.url; el.alt = f.name; el.loading = 'lazy'; a.appendChild(el); }
          }
          box.insertBefore(a, box.firstChild);
        });
        if (!mediaPane.hidden) paintSeg(seg);
        else {   // ແທັບອື່ນເປີດຢູ່: ອັບເດດສະຖານະວ່າງ/ບໍ່ວ່າງ ໄວ້ກ່ອນ ໂດຍບໍ່ສະແດງແທັບ
          var keep = mediaPane.hidden; paintSeg(seg); mediaPane.hidden = keep;
        }
      });
    }
  }
  var tog = room.querySelector('[data-cr-toggle-info]');
  if (tog) tog.addEventListener('click', function () {
    if (window.innerWidth <= 992) {
      document.documentElement.classList.toggle('cr-info-on');
    } else {
      room.classList.toggle('is-info-off');
    }
  });

  // ຄລິກໂລໂກ້ / ຊື່ບໍລິສັດເທິງແຊັດ → ເປີດແຜງມີເດຍ ແລະ ໄຟລ໌ (ມືຖືເລື່ອນອອກມາຈາກຂວາ)
  document.addEventListener('click', function (e) {
    var opMedia = e.target.closest('[data-cr-open-media]');
    if (opMedia && !e.target.closest('[data-cr-toggle-info]')) {
      e.preventDefault();
      if (info) {
        var mediaTab = info.querySelector('[data-cr-tab="media"]');
        if (mediaTab) mediaTab.click();
      }
      if (window.innerWidth <= 992) {
        document.documentElement.classList.add('cr-info-on');
      } else {
        room.classList.remove('is-info-off');
      }
      return;
    }
    if (e.target.closest('[data-cr-info-close]')) {
      e.preventDefault();
      document.documentElement.classList.remove('cr-info-on');
      return;
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.documentElement.classList.contains('cr-info-on')) {
      document.documentElement.classList.remove('cr-info-on');
    }
  });

  // ---------- ເບິ່ງຮູບ / ວິດີໂອ ເຕັມຈໍ ([data-cr-view] ໃນແຊັດ ແລະ ແທັບມີເດຍ) ----------
  var viewer = document.querySelector('[data-cr-viewer]');
  if (viewer) {
    var vBody = viewer.querySelector('[data-cr-viewer-body]'), vDl = viewer.querySelector('[data-cr-viewer-dl]');
    document.addEventListener('click', function (e) {
      var a = e.target.closest('[data-cr-view]');
      if (!a) return;
      e.preventDefault();
      vBody.innerHTML = '';
      var el = document.createElement(a.getAttribute('data-cr-view') === 'video' ? 'video' : 'img');
      el.src = a.href;
      if (el.tagName === 'VIDEO') { el.controls = true; el.autoplay = true; el.playsInline = true; }
      vBody.appendChild(el);
      vDl.href = a.href;
      viewer.hidden = false;
      document.documentElement.classList.add('crm-open');
    });
    function closeViewer() { viewer.hidden = true; vBody.innerHTML = ''; document.documentElement.classList.remove('crm-open'); }
    viewer.addEventListener('click', function (e) { if (e.target === viewer || e.target.closest('[data-cr-viewer-close]')) closeViewer(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !viewer.hidden) closeViewer(); });
  }

  // ---------- ມືຖື: ລິ້ນຊັກເມນູຊ້າຍ (components/chat_rail.php) ----------
  function railOpen(on) { document.documentElement.classList.toggle('cr-rail-on', on); }
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-cr-rail-open]')) { e.preventDefault(); railOpen(true); return; }
    if (e.target.closest('[data-cr-rail-close]') || (e.target.closest('.cr-rail .cr-rail-btn') && document.documentElement.classList.contains('cr-rail-on'))) railOpen(false);
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') railOpen(false); });

  // ---------- popup ----------
  function openModal(name) {
    var m = document.querySelector('[data-crm="' + name + '"]');
    if (!m) return;
    m.hidden = false;
    document.documentElement.classList.add('crm-open');
    if (name === 'settings') paintSettings();
  }
  function closeModals() {
    document.querySelectorAll('[data-crm]').forEach(function (m) { m.hidden = true; });
    document.documentElement.classList.remove('crm-open');
  }
  document.addEventListener('click', function (e) {
    var op = e.target.closest('[data-cr-open]');
    if (op) { e.preventDefault(); openModal(op.getAttribute('data-cr-open')); return; }
    if (e.target.closest('[data-crm-close]')) closeModals();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModals(); });

  // ---------- ຕັ້ງຄ່າ ----------
  var soundSw = document.querySelector('[data-crm-set="sound"]');
  var stkSw = document.querySelector('[data-crm-set="stickerConfirm"]');
  var stkRefresh = document.querySelector('[data-crm-stk-refresh]');
  var notifyBtn = document.querySelector('[data-crm-notify]');
  var notifyState = document.querySelector('[data-crm-notify-state]');
  function applyTheme() {
    room.setAttribute('data-theme', settings.theme || 'default');
    document.querySelectorAll('[data-crm-theme]').forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-crm-theme') === settings.theme); });
    var cur = document.querySelector('[data-crm-theme="' + settings.theme + '"]');
    var nameEl = document.querySelector('[data-crm-theme-name]');
    if (nameEl && cur) nameEl.textContent = cur.getAttribute('data-name');
  }
  function paintSettings() {
    if (soundSw) soundSw.checked = !!settings.sound;
    if (stkSw) stkSw.checked = settings.stickerConfirm !== false;
    if (notifyBtn) {
      var ok = 'Notification' in window;
      var granted = ok && Notification.permission === 'granted';
      notifyBtn.disabled = !ok || granted || (ok && Notification.permission === 'denied');
      notifyBtn.classList.toggle('is-on', granted);
      if (granted) notifyBtn.textContent = notifyBtn.getAttribute('data-on') || '✓';
    }
    applyTheme();
  }
  if (notifyBtn) notifyBtn.setAttribute('data-on', '✓ ' + (document.documentElement.lang === 'lo' || !document.documentElement.lang ? 'ເປີດແລ້ວ' : 'On'));
  if (soundSw) soundSw.addEventListener('change', function () { settings.sound = soundSw.checked; save(); if (settings.sound) beep(); });
  if (stkSw) stkSw.addEventListener('change', function () { settings.stickerConfirm = stkSw.checked; save(); });
  if (stkRefresh) stkRefresh.addEventListener('click', function () {
    try { localStorage.removeItem('WlaoDev.stickers'); localStorage.removeItem('WlaoDev.stickersRecent'); } catch (e) {}
    var label = stkRefresh.textContent;
    stkRefresh.disabled = true;
    document.dispatchEvent(new CustomEvent('chat:stickers-refresh'));
    setTimeout(function () {
      stkRefresh.textContent = '✓ ' + (stkRefresh.getAttribute('data-done') || '');
      setTimeout(function () { stkRefresh.textContent = label; stkRefresh.disabled = false; }, 1500);
    }, 400);
  });
  if (notifyBtn) notifyBtn.addEventListener('click', function () {
    if (!('Notification' in window)) return;
    Notification.requestPermission().then(paintSettings);
  });
  document.querySelectorAll('[data-crm-theme]').forEach(function (b) {
    b.addEventListener('click', function () { settings.theme = b.getAttribute('data-crm-theme'); save(); applyTheme(); });
  });
  applyTheme();

  // ---------- ຂໍ້ຄວາມໃໝ່ຈາກທີມງານ → ສຽງ + ແຈ້ງເຕືອນ ----------
  var audioCtx = null;
  function beep() {
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      [880, 1320].forEach(function (f, i) {
        var o = audioCtx.createOscillator(), g = audioCtx.createGain(), t = audioCtx.currentTime + i * 0.14;
        o.type = 'sine'; o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.18, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.22);
        o.connect(g); g.connect(audioCtx.destination); o.start(t); o.stop(t + 0.24);
      });
    } catch (e) {}
  }
  document.addEventListener('chat:incoming', function (e) {
    var msgs = (e.detail && e.detail.messages) || [];
    if (!msgs.length) return;
    if (settings.sound) beep();
    if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
      var last = msgs[msgs.length - 1];
      try {
        var n = new Notification(document.title.split('|')[0].trim() || 'WlaoDev', { body: (last.body || (last.file ? last.file.name : '')).slice(0, 120), tag: 'wlao-chat' });
        n.onclick = function () { window.focus(); n.close(); };
      } catch (err) {}
    }
  });

  // ---------- ເມນູປຸ່ມບວກ + (ແນບເອກະສານ / ສົ່ງຕຳແໜ່ງ) ----------
  var plusBtn = document.querySelector('[data-chat-plus]');
  var plusMenu = document.querySelector('[data-chat-plus-menu]');
  if (plusBtn && plusMenu) {
    plusBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var show = plusMenu.hidden;
      plusMenu.hidden = !show;
      plusBtn.classList.toggle('is-active', show);
    });
    document.addEventListener('click', function (e) {
      if (!plusMenu.hidden && !e.target.closest('[data-chat-plus-menu]') && !e.target.closest('[data-chat-plus]')) {
        plusMenu.hidden = true;
        plusBtn.classList.remove('is-active');
      }
    });
    var plusDoc = plusMenu.querySelector('[data-chat-plus-doc]');
    if (plusDoc) {
      plusDoc.addEventListener('click', function () {
        plusMenu.hidden = true;
        plusBtn.classList.remove('is-active');
        var att = document.querySelector('[data-chat-attach]');
        if (att) att.click();
      });
    }
    var plusLoc = plusMenu.querySelector('[data-chat-plus-loc]');
    if (plusLoc) {
      plusLoc.addEventListener('click', function () {
        plusMenu.hidden = true;
        plusBtn.classList.remove('is-active');
        var loc = document.querySelector('[data-chat-loc]');
        if (loc) loc.click();
      });
    }
  }
})();
