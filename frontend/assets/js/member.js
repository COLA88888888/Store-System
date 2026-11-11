/**
 * ປຸ່ມ Google ([data-google-login] ໃນ components/google_button.php): ລໍຖ້າ library ຂອງ Google ໂຫຼດ → ວາດປຸ່ມ →
 * ໄດ້ credential (ID token) → POST api/member.php action=google → ໄປໜ້າທີ່ server ບອກ (redirect) ຫຼື ໂຫຼດໜ້າໃໝ່.
 * window.renderGoogleButtons() = ວາດປຸ່ມໃໝ່ (ຫຼັງ popup ປ່ຽນພາສາ ແລະ ແທນເນື້ອໃນ)
 */
(function () {
  var tries = 0, inited = false;
  function showMsg(txt) {
    document.querySelectorAll('[data-google-msg]').forEach(function (m) { m.textContent = txt; m.hidden = false; });
  }
  function render() {
    var boxes = document.querySelectorAll('[data-google-login]');
    if (!boxes.length) return;
    if (!(window.google && google.accounts && google.accounts.id)) {
      if (++tries < 100) setTimeout(render, 100);
      return;
    }
    var first = boxes[0];
    if (!inited) {
      inited = true;
      google.accounts.id.initialize({
        client_id: first.getAttribute('data-client-id'),
        ux_mode: 'popup',
        callback: function (resp) {
          var box = document.querySelector('[data-google-login]');
          var fd = new FormData();
          fd.append('action', 'google');
          fd.append('credential', resp.credential);
          fd.append('csrf', box.getAttribute('data-csrf'));
          fetch(box.getAttribute('data-endpoint'), { method: 'POST', credentials: 'same-origin', body: fd, headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (res.success) { if (res.redirect) location.href = res.redirect; else location.reload(); return; }
              showMsg(res.message || box.getAttribute('data-error'));
            })
            .catch(function () { showMsg(box.getAttribute('data-error')); });
        }
      });
    }
    boxes.forEach(function (box) {
      box.innerHTML = '';
      // ປຸ່ມແທ້ຂອງ Google ວາງທັບໜ້າປຸ່ມ (.gsi-face) ແບບໂປ່ງໃສ — ກວ້າງເຕັມກອບ ເພື່ອກົດໄດ້ທຸກບ່ອນຂອງປຸ່ມ
      google.accounts.id.renderButton(box, {
        theme: 'outline', size: 'large', shape: 'pill', width: Math.max(200, Math.min(400, box.clientWidth || 360)),
        text: box.getAttribute('data-text') || 'continue_with', locale: box.getAttribute('data-locale') || 'lo'
      });
    });
  }
  window.renderGoogleButtons = function () { tries = 0; render(); };
  render();
})();

/**
 * Popup ສະມາຊິກ ([data-member-modal] ໃນ components/member_modal.php) ↔ api/member.php
 *   - [data-member-open="register|login"] ເປີດ popup (+ data-member-note = ຂໍ້ຄວາມແຈ້ງເຕືອນເທິງຟອມ, ເຊັ່ນ ກົດກະຕ່າ)
 *   - [data-mm-go] ສະຫຼັບ ລົງທະບຽນ ↔ ເຂົ້າສູ່ລະບົບ, [data-mm-eye] ສະແດງ/ເຊື່ອງລະຫັດຜ່ານ, × / Esc / ກົດພື້ນ = ປິດ
 *   - ປຸ່ມສົ່ງຟອມ ກົດບໍ່ໄດ້ ຈົນກວ່າປ້ອນຊ່ອງທີ່ຕ້ອງມີຄົບ (required) + ຕິກຍອມຮັບເງື່ອນໄຂ
 *   - ປ່ຽນພາສາ ([data-mm-lang-link]): ດຶງ popup ພາສາໃໝ່ມາແທນທັນທີ (ບໍ່ໂຫຼດໜ້າ) — ຮັກສາຂໍ້ມູນທີ່ປ້ອນ ແລະ ແທັບທີ່ເປີດຢູ່;
 *     ປິດ popup ແລ້ວ ໂຫຼດໜ້າໃໝ່ 1 ເທື່ອ ໃຫ້ທັງໜ້າເປັນພາສາທີ່ເລືອກ
 *   - ສົ່ງສຳເລັດ → ໂຫຼດໜ້າໃໝ່ (ເມນູສະແດງເລກສະມາຊິກ + ຂໍ້ຄວາມ flash)
 * ທຸກ event ຜູກກັບ modal (delegation) ຈຶ່ງໃຊ້ໄດ້ຕໍ່ຫຼັງແທນເນື້ອໃນ popup.
 */
(function () {
  var modal = document.querySelector('[data-member-modal]');
  if (!modal) return;
  var endpoint = modal.getAttribute('data-endpoint');
  var langChanged = false;

  function views() { return modal.querySelectorAll('[data-mm-view]'); }
  function activeView() {
    var v = 'login';
    views().forEach(function (f) { if (!f.hidden) v = f.getAttribute('data-mm-view'); });
    return v;
  }
  function show(view) {
    views().forEach(function (f) { f.hidden = f.getAttribute('data-mm-view') !== view; });
    var input = modal.querySelector('[data-mm-view="' + view + '"] input:not([type=hidden])');
    // ຈໍສຳຜັດ (ໂທລະສັບ): ບໍ່ຍ້າຍ cursor ເຂົ້າຊ່ອງທຳອິດເອງ — ແປ້ນພິມຈະເດັ້ງ ແລະ iPhone ເລື່ອນ/ຊູມໜ້າ ຈົນເບິ່ງຟອມບໍ່ເຫັນ
    if (input && !window.matchMedia('(hover: none)').matches) setTimeout(function () { input.focus(); }, 180);
    views().forEach(updateSubmit);
  }
  // ບໍ່ໃຫ້ເບີ/ອີເມວ ແລະ ລະຫັດຜ່ານ ຂຶ້ນຄ້າງໄວ້ (browser autofill): ລ້າງທຸກເທື່ອທີ່ເປີດ popup, ຊ່ອງເປັນ readonly ຈົນກວ່າຈະກົດ/focus
  function clearLogin() {
    modal.querySelectorAll('[data-mm-noautofill]').forEach(function (el) { if (document.activeElement !== el || el.readOnly) el.value = ''; });
    var f = modal.querySelector('[data-mm-view="login"]');
    if (f) updateSubmit(f);
  }
  modal.addEventListener('focusin', function (e) {
    if (e.target.hasAttribute && e.target.hasAttribute('data-mm-noautofill')) e.target.readOnly = false;
  });
  // ໂທລະສັບ: ຊ່ອງ readonly ກົດແລ້ວແປ້ນພິມບໍ່ຂຶ້ນ (ຕ້ອງກົດ 2 ເທື່ອ) → ປົດ readonly ຕອນນິ້ວແຕະ (ກ່ອນ focus) ໃຫ້ແປ້ນພິມເດັ້ງຂຶ້ນເລີຍເທື່ອທຳອິດ
  ['pointerdown', 'touchstart', 'mousedown'].forEach(function (ev) {
    modal.addEventListener(ev, function (e) {
      var el = e.target.closest && e.target.closest('[data-mm-noautofill]');
      if (!el) { var w = e.target.closest && e.target.closest('.mm-input-wrap'); el = w && w.querySelector('[data-mm-noautofill]'); }
      if (el) el.readOnly = false;
    }, { passive: true, capture: true });
  });
  // ກົດໄອຄອນ / ຂອບຂອງຊ່ອງ (ນອກ input) → focus ຊ່ອງນັ້ນເລີຍ (ແປ້ນພິມຂຶ້ນ)
  modal.addEventListener('click', function (e) {
    var w = e.target.closest && e.target.closest('.mm-input-wrap');
    if (!w || e.target.closest('input, button, select, textarea')) return;
    var inp = w.querySelector('input');
    if (inp) { inp.readOnly = false; inp.focus(); }
  });
  function open(view) {
    if (!window.CSS || !CSS.supports || !CSS.supports('scrollbar-gutter', 'stable')) {
      var sbWidth = window.innerWidth - document.documentElement.clientWidth;
      if (sbWidth > 0) document.body.style.paddingRight = sbWidth + 'px';
    }
    modal.hidden = false;
    modal.classList.remove('is-open');
    void modal.offsetWidth;   // reflow → ອະນິເມຊັນເປີດຫຼິ້ນທຸກເທື່ອ
    modal.classList.add('is-open');
    document.documentElement.classList.add('mm-open');
    document.body.classList.add('mm-open');
    clearLogin();
    show(view || 'login');
    setTimeout(clearLogin, 400);   // browser ບາງຕົວຕື່ມຊ້າ → ລ້າງອີກເທື່ອ
  }
  function close() {
    modal.classList.remove('is-open');
    modal.hidden = true;
    document.documentElement.classList.remove('mm-open');
    document.body.classList.remove('mm-open');
    document.body.style.paddingRight = '';
    if (langChanged) location.reload();   // ປ່ຽນພາສາໃນ popup ແລ້ວ → ທັງໜ້າເປັນພາສາໃໝ່
  }
  function setNote(view, note) {
    var box = modal.querySelector('[data-mm-view="' + view + '"] [data-mm-alert]');
    if (!box) return;
    box.classList.toggle('is-note', !!note);
    box.textContent = note || '';
    box.hidden = !note;
  }

  // ປຸ່ມສົ່ງ: ກົດໄດ້ເມື່ອທຸກຊ່ອງ required ມີຄ່າ ແລະ ຕິກຍອມຮັບເງື່ອນໄຂແລ້ວ
  function updateSubmit(form) {
    var btn = form.querySelector('.mm-submit');
    if (!btn || form.classList.contains('is-sending')) return;
    var ok = true;
    form.querySelectorAll('[required]').forEach(function (el) {
      if (el.type === 'checkbox' ? !el.checked : !String(el.value).trim()) ok = false;
    });
    var terms = form.querySelector('[name="accept_terms"]');
    if (terms && !terms.checked) ok = false;
    btn.disabled = !ok;
  }

  // ແຂວງ → ລາຍຊື່ເມືອງ (ໃນ popup — ໃຊ້ໄດ້ຫຼັງແທນເນື້ອໃນ)
  function fillDistricts(provSel, keepIndex) {
    var wrap = provSel.closest('[data-prov-map]');
    var distSel = wrap && wrap.querySelector('[data-district-select]');
    if (!distSel) return;
    var map = {};
    try { map = JSON.parse(wrap.getAttribute('data-prov-map') || '{}'); } catch (e) {}
    var list = map[provSel.value] || [];
    distSel.innerHTML = '';
    var first = document.createElement('option');
    first.value = '';
    first.textContent = list.length ? (distSel.getAttribute('data-placeholder') || '--') : (distSel.getAttribute('data-empty-placeholder') || '--');
    distSel.appendChild(first);
    list.forEach(function (d) { var o = document.createElement('option'); o.value = d; o.textContent = d; distSel.appendChild(o); });
    if (keepIndex) distSel.selectedIndex = Math.min(keepIndex, distSel.options.length - 1);
  }

  // delegation: ໃຊ້ໄດ້ກັບປຸ່ມທີ່ຢູ່ຫຼັງ script ນີ້ (ເຊັ່ນ ແຖບລົງທະບຽນໃນແຊັດລອຍ components/chat_guest_bar.php)
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-member-open]');
    if (!btn) return;
    e.preventDefault();
    var view = btn.getAttribute('data-member-open');
    open(view);
    setNote(view, btn.getAttribute('data-member-note'));
  });
  // ເປີດ popup ຈາກ JS (assets/js/cart.js: ລູກຄ້າທົ່ວໄປກົດໃສ່ກະຕ່າ)
  window.openMemberModal = function (view, note) {
    open(view || 'register');
    if (note) setNote(view || 'register', note);
  };
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
  document.addEventListener('click', function (e) {
    var corner = modal.querySelector('[data-mm-lang]');
    if (corner && !corner.contains(e.target)) corner.classList.remove('open');
  });

  modal.addEventListener('click', function (e) {
    var t = e.target;
    if (t.closest('[data-mm-close]')) { close(); return; }
    var go = t.closest('[data-mm-go]');
    if (go) { e.preventDefault(); show(go.getAttribute('data-mm-go')); return; }
    var eye = t.closest('[data-mm-eye]');
    if (eye) {
      var inp = eye.previousElementSibling;
      inp.type = inp.type === 'password' ? 'text' : 'password';
      eye.classList.toggle('is-on', inp.type === 'text');
      return;
    }
    var toggle = t.closest('[data-mm-lang-toggle]');
    if (toggle) { e.stopPropagation(); toggle.closest('[data-mm-lang]').classList.toggle('open'); return; }
    var langLink = t.closest('[data-mm-lang-link]');
    if (langLink) { e.preventDefault(); switchLang(langLink.getAttribute('href')); }
  });
  modal.addEventListener('input', function (e) { var f = e.target.closest('[data-mm-view]'); if (f) updateSubmit(f); });
  modal.addEventListener('change', function (e) {
    if (e.target.matches('[data-province-select]')) fillDistricts(e.target, 0);
    var f = e.target.closest('[data-mm-view]');
    if (f) updateSubmit(f);
  });

  // ປ່ຽນພາສາ: GET ?lang=xx (ຕັ້ງພາສາໃນ session) → ເອົາ popup ຈາກໜ້າທີ່ໄດ້ ມາແທນ popup ປັດຈຸບັນ
  function switchLang(href) {
    var view = activeView();
    var saved = {};   // ຂໍ້ມູນທີ່ປ້ອນ: input ຕາມຊື່, select ຕາມລຳດັບ (ຊື່ແຂວງ/ເມືອງ ປ່ຽນຕາມພາສາ)
    views().forEach(function (f) {
      var key = f.getAttribute('data-mm-view');
      saved[key] = {};
      f.querySelectorAll('input[name], select[name]').forEach(function (el) {
        if (el.type === 'hidden') return;
        saved[key][el.name] = el.tagName === 'SELECT' ? el.selectedIndex : (el.type === 'checkbox' ? el.checked : el.value);
      });
    });
    modal.classList.add('is-switching');
    fetch(href.split('#')[0], { credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var fresh = doc.querySelector('[data-member-modal]');
        if (!fresh) { location.href = href; return; }
        modal.innerHTML = fresh.innerHTML;
        langChanged = true;
        document.documentElement.lang = doc.documentElement.lang || document.documentElement.lang;
        views().forEach(function (f) {
          var data = saved[f.getAttribute('data-mm-view')] || {};
          var prov = f.querySelector('[data-province-select]');
          if (prov && data.province) { prov.selectedIndex = data.province; fillDistricts(prov, data.district || 0); }
          f.querySelectorAll('input[name], select[name]').forEach(function (el) {
            if (!(el.name in data) || el.matches('[data-province-select], [data-district-select]')) return;
            if (el.tagName === 'SELECT') el.selectedIndex = data[el.name];
            else if (el.type === 'checkbox') el.checked = data[el.name];
            else if (el.type !== 'hidden') el.value = data[el.name];
          });
        });
        show(view);
        if (window.renderGoogleButtons) window.renderGoogleButtons();
      })
      .catch(function () { location.href = href; })
      .then(function () { modal.classList.remove('is-switching'); });
  }

  // ລະຫັດຢືນຢັນທາງອີເມວ (OTP): ສະແດງຊ່ອງລະຫັດ, ຊ່ອງ data-otp-req ກາຍເປັນ required, ໂຟກັສຊ່ອງລະຫັດ
  function showOtp(form) {
    var box = form.querySelector('[data-mm-otp]');
    if (!box) return;
    var first = box.hidden;
    box.hidden = false;
    box.querySelectorAll('[data-otp-req]').forEach(function (el) { el.required = true; });
    var txt = form.querySelector('[data-mm-submit-text][data-otp-text]');
    if (txt) txt.textContent = txt.getAttribute('data-otp-text');
    if (first) setTimeout(function () { var o = box.querySelector('[name=otp]'); if (o) o.focus(); }, 60);
  }
  function resetOtp(form) {
    var box = form && form.querySelector('[data-mm-otp]');
    if (!box) return;
    box.hidden = true;
    box.querySelectorAll('[data-otp-req]').forEach(function (el) { el.required = false; el.value = ''; });
  }
  // ກົດ "ສົ່ງລະຫັດໃໝ່": ລ້າງຊ່ອງລະຫັດ ແລ້ວສົ່ງຟອມ (server ເຫັນ otp ວ່າງ → ສົ່ງລະຫັດໃໝ່)
  modal.addEventListener('click', function (e) {
    var rs = e.target.closest('[data-mm-otp-resend]');
    if (!rs) return;
    var form = rs.closest('[data-mm-view]');
    var o = form.querySelector('[name=otp]');
    if (o) o.value = '';
    form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
  });
  // ປ່ຽນອີເມວ / ເບີ ຫຼັງຂໍລະຫັດແລ້ວ → ຕ້ອງຂໍລະຫັດໃໝ່ສຳລັບອີເມວໃໝ່
  modal.addEventListener('change', function (e) {
    if (e.target.matches('[data-mm-view] [name=email], [data-mm-view="forgot"] [name=login]')) resetOtp(e.target.closest('[data-mm-view]'));
  });

  modal.addEventListener('submit', function (e) {
    var form = e.target.closest('[data-mm-view]');
    if (!form) return;
    e.preventDefault();
    var alertBox = form.querySelector('[data-mm-alert]');
    var btn = form.querySelector('.mm-submit');
    form.querySelectorAll('[data-mm-err]').forEach(function (s) { s.textContent = ''; });
    alertBox.hidden = true;
    alertBox.classList.remove('is-note');   // ຂໍ້ຜິດພາດ = ສີແດງ
    btn.disabled = true;
    form.classList.add('is-sending');
    fetch(endpoint, { method: 'POST', credentials: 'same-origin', body: new FormData(form), headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) { if (res.redirect) location.href = res.redirect; else window.location.reload(); return; }
        form.classList.remove('is-sending');
        if (res.need_otp) showOtp(form);
        updateSubmit(form);
        if (res.note && res.message) { alertBox.textContent = res.message; alertBox.classList.add('is-note'); alertBox.hidden = false; return; }
        var shown = false;
        Object.keys(res.errors || {}).forEach(function (k) {
          var slot = form.querySelector('[data-mm-err="' + k + '"]');
          if (slot) { slot.textContent = res.errors[k]; shown = true; }
        });
        if (!shown) { alertBox.textContent = res.message || (res.errors && Object.values(res.errors)[0]) || ''; alertBox.hidden = false; }
      })
      .catch(function () {
        form.classList.remove('is-sending');
        updateSubmit(form);
        alertBox.textContent = (window.APP.i18n && window.APP.i18n.offline) || 'Error';
        alertBox.hidden = false;
      });
  });

  views().forEach(updateSubmit);
  if (location.hash === '#register' || location.hash === '#login' || location.hash === '#forgot') open(location.hash.slice(1));
})();

// ລະບົບກວດສອບຄວາມບໍ່ມີການເຄື່ອນໄຫວ (Inactivity Timeout):
// ຖ້າເປີດໜ້າປະໄວ້ແລ້ວບໍ່ໃຊ້ງານເກີນ 15 ນາທີ → ອອກຈາກລະບົບອັດຕະໂນມັດ
(function initMemberInactivity() {
  if (!window.APP || !window.APP.isMember) return;
  var TIMEOUT_MS = 15 * 60 * 1000; // 15 ນາທີ
  var lastActive = Date.now();
  var isLoggingOut = false;

  function markActive() {
    lastActive = Date.now();
  }

  ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(function (evt) {
    window.addEventListener(evt, markActive, { passive: true });
  });

  function performInactivityLogout() {
    if (isLoggingOut) return;
    isLoggingOut = true;
    var fd = new FormData();
    fd.append('action', 'logout');
    fd.append('csrf', (window.APP && window.APP.csrf) || '');
    var url = (window.APP && window.APP.base ? window.APP.base : '/') + 'api/member.php';
    fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
      .finally(function () {
        location.reload();
      });
  }

  setInterval(function () {
    if (Date.now() - lastActive >= TIMEOUT_MS) {
      performInactivityLogout();
    }
  }, 10000);

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && Date.now() - lastActive >= TIMEOUT_MS) {
      performInactivityLogout();
    }
  });
})();
