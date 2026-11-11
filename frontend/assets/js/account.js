/**
 * ໜ້າບັນຊີສະມາຊິກ (pages/account.php) — ຍ້າຍອອກມາຈາກ <script> ໃນໜ້າ. ໂຫຼດຫຼັງ js/vendor/sweetalert2.all.min.js ($pageScripts).
 *  - modal: [data-ap-open="id"] ເປີດ, ×/ກົດນອກ/Esc ປິດ (ເລື່ອນລົງຊ້າໆ); dialog[data-open] = ເປີດເອງຕອນໂຫຼດ (ຟອມຜິດ, ?edit=1, ?pw=1)
 *  - ບັນທຶກແບບ real-time: ທຸກຟອມ POST ໃນ section.ap (ຍົກເວັ້ນ ອອກຈາກລະບົບ) ສົ່ງດ້ວຍ fetch → ໄດ້ HTML ໜ້າໃໝ່ກັບມາ:
 *      ສຳເລັດ (ບໍ່ມີ dialog[data-open]) → ປິດ modal, ປ່ຽນເນື້ອຫາ .ap-side / .ap-main / ຟອມໃນ modal ເປັນຂອງໃໝ່, ແຈ້ງເຕືອນ SweetAlert (toast)
 *      ຜິດພາດ → ປ່ຽນເນື້ອໃນ modal ນັ້ນເປັນຂອງເຊີເວີ (ມີຂໍ້ຄວາມຜິດ / ຊ່ອງລະຫັດຢືນຢັນອີເມວ) ແລະ ເປີດຄ້າງໄວ້
 *    ທຸກ event ໃຊ້ delegation ຢູ່ document → ປ່ຽນ HTML ແລ້ວບໍ່ຕ້ອງຜູກໃໝ່. ບໍ່ມີ fetch → ສົ່ງຟອມແບບປົກກະຕິ (flash → toast ຕອນໂຫຼດ)
 *  - ລືມລະຫັດຜ່ານ [data-ap-forgot]: ajax=1 → JSON (ອີເມວຜິດ = ແຈ້ງໃຕ້ຊ່ອງທັນທີ)
 *  - ແທັບ [data-ap-tab], ແທັບຍ່ອຍ [data-ap-sub], ປຸ່ມຕາ [data-ap-eye], ເບິ່ງຮູບເຕັມ [data-ap-view], ຕົວຢ່າງຮູບ [data-ap-av-input],
 *    ເລືອກແຂວງ → ລາຍຊື່ເມືອງ [data-province-select] (ແທນ main.js ຫຼັງປ່ຽນ HTML)
 */
(function () {
  var root = document.querySelector('section.ap');
  if (!root) return;

  function toast(msg, icon) {
    if (!msg) return;
    if (window.Swal) {
      Swal.fire({ toast: true, position: 'top-end', icon: icon || 'success', title: msg, showConfirmButton: false, timer: 2600, timerProgressBar: true });
    } else {
      alert(msg);
    }
  }
  function closeDlg(d, now) {
    if (!d || !d.open) return;
    if (now) { d.close(); return; }
    d.classList.add('is-closing');
    setTimeout(function () { d.classList.remove('is-closing'); d.close(); }, 300);
  }
  function openDlg(d) { if (d && d.showModal && !d.open) d.showModal(); }

  // flash ຈາກເຊີເວີ (ໂຫຼດໜ້າປົກກະຕິ) → toast ແທນແຖບສີຂຽວເທິງໜ້າ
  function flashFrom(doc) {
    var el = null;
    doc.querySelectorAll('.alert').forEach(function (a) { if (!el && !a.closest('section.ap')) el = a; });
    if (!el) return null;
    var f = { msg: el.textContent.trim(), icon: el.classList.contains('alert-error') ? 'error' : 'success' };
    if (doc === document) (el.parentNode.classList.contains('container') ? el.parentNode : el).remove();
    return f;
  }
  var f0 = flashFrom(document);
  if (f0) toast(f0.msg, f0.icon);

  document.querySelectorAll('dialog.ap-modal[data-open]').forEach(openDlg);

  // ---------- clicks (delegation) ----------
  var viewer = document.createElement('div');
  viewer.className = 'ap-viewer'; viewer.hidden = true; viewer.innerHTML = '<img alt="">';
  document.body.appendChild(viewer);
  viewer.addEventListener('click', function () { viewer.classList.remove('is-open'); setTimeout(function () { viewer.hidden = true; }, 250); });

  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t.tagName === 'DIALOG' && t.classList.contains('ap-modal')) { closeDlg(t); return; }   // ກົດພື້ນນອກຟອມ
    var x = t.closest('[data-ap-close]');
    if (x) { closeDlg(x.closest('dialog')); return; }
    var o = t.closest('[data-ap-open]');
    if (o) {
      closeDlg(o.closest('dialog[open]'), true);   // ສະຫຼັບ modal (ລະຫັດຜ່ານ ↔ ລືມລະຫັດຜ່ານ)
      openDlg(document.getElementById(o.getAttribute('data-ap-open')));
      return;
    }
    var eye = t.closest('[data-ap-eye]');
    if (eye) { var i = eye.previousElementSibling; i.type = i.type === 'password' ? 'text' : 'password'; eye.classList.toggle('is-on', i.type === 'text'); return; }
    var v = t.closest('[data-ap-view]');
    if (v) {
      var im = v.querySelector('img');
      viewer.querySelector('img').src = v.getAttribute('data-ap-view') || (im ? im.src : '');
      viewer.hidden = false;
      requestAnimationFrame(function () { viewer.classList.add('is-open'); });
      return;
    }
    var tab = t.closest('[data-ap-tab]');
    if (tab) {
      e.preventDefault();
      var k = tab.getAttribute('data-ap-tab');
      document.querySelectorAll('[data-ap-tab]').forEach(function (a) { a.classList.toggle('is-active', a === tab); });
      document.querySelectorAll('[data-ap-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-ap-pane') !== k; });
      try { history.replaceState(null, '', tab.href); } catch (err) {}
      return;
    }
    var sub = t.closest('[data-ap-sub]');
    if (sub) {
      var s = sub.getAttribute('data-ap-sub');
      document.querySelectorAll('[data-ap-sub]').forEach(function (b) { b.classList.toggle('is-active', b === sub); });
      document.querySelectorAll('[data-ap-sub-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-ap-sub-pane') !== s; });
    }
  });

  // ---------- change (delegation): ຕົວຢ່າງຮູບ, ແຂວງ → ເມືອງ ----------
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches('[data-ap-av-input]') && t.files && t.files[0]) {
      var img = t.closest('form').querySelector('[data-ap-av-preview]');
      if (img) img.src = URL.createObjectURL(t.files[0]);
      return;
    }
    if (t.matches('section.ap [data-province-select]')) {
      e.stopImmediatePropagation();
      var box = t.closest('[data-prov-map]'), dist = box && box.querySelector('[data-district-select]');
      if (!dist) return;
      var map = {}; try { map = JSON.parse(box.getAttribute('data-prov-map') || '{}'); } catch (err) {}
      var list = map[t.value] || [];
      dist.innerHTML = '';
      var first = document.createElement('option');
      first.value = '';
      first.textContent = list.length ? (dist.getAttribute('data-placeholder') || '--') : (dist.getAttribute('data-empty-placeholder') || '--');
      dist.appendChild(first);
      list.forEach(function (d) { var op = document.createElement('option'); op.value = d; op.textContent = d; dist.appendChild(op); });
    }
  }, true);

  // ---------- submit (delegation): ບັນທຶກແບບ real-time ----------
  if (!window.fetch || !window.FormData || !window.DOMParser) return;
  var busy = false;
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.closest('section.ap') || (form.method || '').toLowerCase() !== 'post' || form.hasAttribute('data-logout-confirm')) return;
    e.preventDefault();
    if (busy) return;
    busy = true;
    var btns = form.querySelectorAll('[type=submit]');
    btns.forEach(function (b) { b.disabled = true; });
    var done = function () { busy = false; btns.forEach(function (b) { b.disabled = false; }); };
    var fd = new FormData(form);

    if (form.hasAttribute('data-ap-forgot')) {   // ລືມລະຫັດຜ່ານ → JSON
      var err = form.querySelector('[data-fg-err]');
      fd.append('ajax', '1'); err.textContent = '';
      fetch(location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          done();
          if (!j.success) { err.textContent = (j.errors && (j.errors.email || j.errors.form)) || ''; form.querySelector('[name=email]').focus(); return; }
          closeDlg(form.closest('dialog'), true);
          form.reset();
          if (window.Swal) {
            Swal.fire({ icon: 'success', title: j.message || '', confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--primary') || '#1d4ed8' })
              .then(function () { openDlg(document.getElementById('acPw')); });
          } else { toast(j.message); openDlg(document.getElementById('acPw')); }
        })
        .catch(function () { done(); form.submit(); });
      return;
    }

    var url = location.pathname + location.search;
    fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.text().then(function (html) { return { url: r.url, html: html }; }); })
      .then(function (res) {
        done();
        var doc = new DOMParser().parseFromString(res.html, 'text/html');
        var newRoot = doc.querySelector('section.ap');
        if (!newRoot) { location.reload(); return; }   // ໝົດ session ຯລຯ → ໂຫຼດໃໝ່
        var flash = flashFrom(doc);
        var errDlg = newRoot.querySelector('dialog.ap-modal[data-open]');
        var isErr = errDlg && !(flash && flash.icon === 'success');
        if (isErr) {   // ຟອມຜິດ: ເອົາເນື້ອໃນ modal ຂອງເຊີເວີ (ຂໍ້ຄວາມຜິດ + ຄ່າທີ່ປ້ອນ) ມາແທນ
          var cur = document.getElementById(errDlg.id);
          if (cur) {
            var nb = errDlg.querySelector('.ap-modal-body'), cb = cur.querySelector('.ap-modal-body');
            if (nb && cb) cb.innerHTML = nb.innerHTML;
            document.querySelectorAll('dialog.ap-modal[open]').forEach(function (d) { if (d !== cur) closeDlg(d, true); });
            openDlg(cur);
            var first = cur.querySelector('.field-error, .alert-error');
            if (first) first.scrollIntoView({ block: 'center', behavior: 'smooth' });
          }
          if (flash) toast(flash.msg, flash.icon);
          return;
        }
        // ສຳເລັດ: ປິດ modal + ປ່ຽນເນື້ອຫາທັງໝົດເປັນຂອງໃໝ່ (ຮັກສາແທັບທີ່ເປີດຢູ່)
        var oldAva = (root.querySelector('.ap-avatar img') || {}).src;
        document.querySelectorAll('dialog.ap-modal[open]').forEach(function (d) { closeDlg(d); });
        var activeTab = (root.querySelector('[data-ap-tab].is-active') || {}).getAttribute ? root.querySelector('[data-ap-tab].is-active').getAttribute('data-ap-tab') : '';
        setTimeout(function () {
          ['.ap-side', '.ap-main'].forEach(function (sel) {
            var a = root.querySelector(sel), b = newRoot.querySelector(sel);
            if (a && b) a.innerHTML = b.innerHTML;
          });
          newRoot.querySelectorAll('dialog.ap-modal').forEach(function (nd) {
            var od = document.getElementById(nd.id), nb2 = nd.querySelector('.ap-modal-body');
            if (od && nb2 && !od.open) od.querySelector('.ap-modal-body').innerHTML = nb2.innerHTML;
          });
          if (activeTab) {
            var ta = root.querySelector('[data-ap-tab="' + activeTab + '"]');
            if (ta) {
              root.querySelectorAll('[data-ap-tab]').forEach(function (a) { a.classList.toggle('is-active', a === ta); });
              root.querySelectorAll('[data-ap-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-ap-pane') !== activeTab; });
            }
          }
          var newAva = (root.querySelector('.ap-avatar img') || {}).src;   // ປ່ຽນຮູບໂປຣໄຟລ໌ → ຮູບໃນເມນູເທິງກໍປ່ຽນ
          if (oldAva && newAva && oldAva !== newAva) document.querySelectorAll('img').forEach(function (im) { if (im.src === oldAva) im.src = newAva; });
          var h1 = root.querySelector('.ap-profile h1');
          document.querySelectorAll('[data-member-name]').forEach(function (n) { if (h1) n.textContent = h1.textContent; });
        }, 310);
        toast(flash ? flash.msg : '', flash ? flash.icon : 'success');
      })
      .catch(function () { done(); form.submit(); });
  });
})();
