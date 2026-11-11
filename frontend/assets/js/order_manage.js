/**
 * ການຈັດການຄຳສັ່ງຊື້ (pages/my_orders.php):
 *   - ຄົ້ນຫາ [data-om-search] ກອງແຖວ [data-om-text] (ເລກອໍເດີ + ຊື່ສິນຄ້າ) ທັນທີ ໂດຍບໍ່ໂຫຼດໜ້າໃໝ່
 *   - ເມນູ "ສະຖານະ:" [data-om-filter] (ລິ້ງ ?st=) — ປິດເມື່ອກົດຂ້າງນອກ
 *   - ກົດແຖວຕາຕະລາງ (tr[data-ot-row]) ຫຼື ປຸ່ມ "ຕິດຕາມ" [data-ot-track] → modal <dialog data-otm> (ມີຟອມແນບສະລິບ); ?track=ເລກອໍເດີ → ເປີດເອງ
 *   - ກົດແທັບ / ສະຖານະ → ສະແດງແຖວໂຫຼດ [data-ot-skeleton] ລະຫວ່າງລໍຖ້າໜ້າໃໝ່
 */
(function () {
  // ເມນູສະຖານະ (<details data-om-filter>): ກົດຂ້າງນອກ / Esc → ປິດ
  var flt = document.querySelector('[data-om-filter]');
  if (flt) {
    document.addEventListener('click', function (e) { if (flt.open && !flt.contains(e.target)) flt.open = false; });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') flt.open = false; });
  }

  var ot = document.querySelector('[data-ot]');
  var list = document.querySelector('[data-om-list]');
  var empty = document.querySelector('[data-om-empty]');
  var skel = document.querySelector('[data-ot-skeleton]');

  // modal ຕິດຕາມອໍເດີ: ສຳເນົາ <template data-ot-detail="id"> ໃສ່ <dialog data-otm>
  var dlg = document.querySelector('[data-otm]'), dlgBody = dlg && dlg.querySelector('[data-otm-body]');
  function openTrack(id) {
    var tpl = document.querySelector('template[data-ot-detail="' + id + '"]');
    if (!tpl || !dlg || !dlg.showModal) return;
    dlgBody.innerHTML = '';
    dlgBody.appendChild(tpl.content.cloneNode(true));
    if (window.initOrderForm) dlgBody.querySelectorAll('[data-order-form]').forEach(window.initOrderForm);   // ຟອມແນບສະລິບ (assets/js/main.js)
    dlg.showModal();
    dlgBody.scrollTop = 0;
  }
  // ?track=ເລກອໍເດີ → ເປີດ modal ຂອງອໍເດີນັ້ນເລີຍ (ລ້າງ ?track ອອກຈາກ URL)
  if (dlg && dlg.getAttribute('data-otm-open')) {
    setTimeout(function () { openTrack(dlg.getAttribute('data-otm-open')); }, 50);
    try { var u = new URL(location.href); u.searchParams.delete('track'); history.replaceState(null, '', u); } catch (e) {}
  }
  if (dlg) dlg.addEventListener('click', function (e) {
    if (e.target === dlg || e.target.closest('[data-otm-close]')) { dlg.classList.add('is-closing'); setTimeout(function () { dlg.classList.remove('is-closing'); dlg.close(); }, 300); return; }   // ປິດແບບເລື່ອນລົງ
    var zs = e.target.closest('[data-otm-zoom-src]');   // ປຸ່ມ "ຂະຫຍາຍ" → ຂະຫຍາຍຮູບໃນກາດດຽວກັນ
    if (zs) { var zi = zs.closest('.otm-doc').querySelector('[data-otm-zoom]'); if (zi) zi.classList.add('is-zoom'); return; }
    var z = e.target.closest('[data-otm-zoom]');   // ຮູບສະລິບ: ກົດ = ຂະຫຍາຍ / ຫຍໍ້ (ບໍ່ເປີດແທັບໃໝ່)
    if (z) { z.classList.toggle('is-zoom'); return; }
    var c = e.target.closest('[data-otm-copy]');
    if (c) {
      var txt = c.getAttribute('data-otm-copy'), lbl = c.querySelector('span');
      var done = function () { if (lbl) { var o = lbl.textContent; lbl.textContent = c.getAttribute('data-done'); setTimeout(function () { lbl.textContent = o; }, 1500); } };
      if (navigator.clipboard) navigator.clipboard.writeText(txt).then(done, function () {}); else done();
    }
  });

  // ແຖວຕາຕະລາງ: ກົດບ່ອນໃດກໍໄປໜ້າອໍເດີ (ລິ້ງໃນແຖວ / ກົດປຸ່ມກາງຂອງເມົາ ໃຊ້ຕາມປົກກະຕິ)
  if (list) list.addEventListener('click', function (e) {
    // ປຸ່ມ "ຕິດຕາມ" → modal ລາຍລະອຽດອໍເດີ (ບໍ່ໄປໜ້າອື່ນ)
    var tb = e.target.closest('[data-ot-track]');
    if (tb) { openTrack(tb.getAttribute('data-ot-track')); return; }
    var tr = e.target.closest('tr[data-ot-row]');   // ກົດແຖວ = ເປີດ modal ຕິດຕາມ
    if (tr) { e.preventDefault(); openTrack(tr.getAttribute('data-ot-row')); }
  });

  // ກົດແທັບ / ເມນູສະຖານະ → ແຖວໂຫຼດ (ຄືແອັບ) ຈົນກວ່າໜ້າໃໝ່ຈະມາ
  document.querySelectorAll('.om-tabs a, .om-menu a, .om-pages a').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0 || !ot || !skel) return;
      if (flt) flt.open = false;
      document.querySelectorAll('.om-tabs a').forEach(function (x) { x.classList.toggle('is-active', x === a || (x.classList.contains('is-active') && !a.closest('.om-tabs'))); });
      ot.hidden = false;
      if (list) list.hidden = true;
      skel.hidden = false;
      if (empty) empty.hidden = true;
    });
  });
  // ກັບມາດ້ວຍປຸ່ມ Back (bfcache) → ເອົາແຖວໂຫຼດອອກ
  window.addEventListener('pageshow', function (e) { if (e.persisted) location.reload(); });

  var q = document.querySelector('[data-om-search]');
  if (!q || !list) return;
  var items = list.querySelectorAll('[data-om-text]');
  q.addEventListener('input', function () {
    var v = q.value.trim().toLowerCase(), shown = 0;
    items.forEach(function (it) {
      var ok = v === '' || it.getAttribute('data-om-text').indexOf(v) >= 0;
      it.hidden = !ok;
      if (ok) shown++;
    });
    if (ot) ot.hidden = shown === 0;
    var pager = document.querySelector('[data-om-pager]');
    if (pager) pager.hidden = v !== '';   // ກຳລັງຄົ້ນຫາ (ໃນໜ້ານີ້) → ເຊື່ອງແຖບແບ່ງໜ້າ
    if (empty) empty.hidden = shown > 0;
  });
})();
