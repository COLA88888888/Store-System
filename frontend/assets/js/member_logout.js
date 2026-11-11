/**
 * ຢືນຢັນກ່ອນອອກຈາກລະບົບ: ຟອມ [data-logout-confirm] (components/member_menu.php, pages/account.php)
 * → popup "ທ່ານຕ້ອງການອອກຈາກລະບົບແທ້ບໍ?" (ຂໍ້ຄວາມ/ປຸ່ມ ມາຈາກ data-* ທີ່ແປແລ້ວ) → ກົດ "ອອກຈາກລະບົບ" ຈຶ່ງສົ່ງຟອມ.
 * CSS: .lo-* ໃນ assets/css/member.css. ໂຫຼດສະເພາະຕອນເປັນສະມາຊິກ (layouts/footer.php)
 */
(function () {
  var dlg = null, pending = null;
  function build(form) {
    dlg = document.createElement('div');
    dlg.className = 'lo-modal';
    dlg.hidden = true;
    dlg.setAttribute('role', 'alertdialog');
    dlg.setAttribute('aria-modal', 'true');
    dlg.innerHTML =
      '<div class="lo-backdrop" data-lo-no></div>' +
      '<div class="lo-box">' +
        '<span class="lo-ico"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/><path d="M14 8l4 4-4 4M18 12H9"/></svg></span>' +
        '<h3 class="lo-title"></h3>' +
        '<p class="lo-text"></p>' +
        '<div class="lo-btns"><button type="button" class="lo-no" data-lo-no></button><button type="button" class="lo-yes" data-lo-yes></button></div>' +
      '</div>';
    document.body.appendChild(dlg);
    dlg.addEventListener('click', function (e) {
      if (e.target.closest('[data-lo-no]')) close();
      else if (e.target.closest('[data-lo-yes]') && pending) {
        var f = pending;
        pending = null;
        f.setAttribute('data-lo-ok', '1');
        dlg.querySelector('.lo-yes').disabled = true;
        if (f.requestSubmit) f.requestSubmit(); else f.submit();
      }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && dlg && !dlg.hidden) close(); });
  }
  function open(form) {
    if (!dlg) build(form);
    pending = form;
    dlg.querySelector('.lo-title').textContent = form.getAttribute('data-logout-confirm');
    dlg.querySelector('.lo-text').textContent = form.getAttribute('data-logout-text') || '';
    dlg.querySelector('.lo-no').textContent = form.getAttribute('data-logout-no') || 'Cancel';
    dlg.querySelector('.lo-yes').textContent = form.getAttribute('data-logout-yes') || 'OK';
    dlg.querySelector('.lo-yes').disabled = false;
    dlg.hidden = false;
    requestAnimationFrame(function () { dlg.classList.add('is-open'); dlg.querySelector('.lo-no').focus(); });
  }
  function close() {
    pending = null;
    dlg.classList.remove('is-open');
    setTimeout(function () { dlg.hidden = true; }, 150);
  }
  document.addEventListener('submit', function (e) {
    var form = e.target.closest('form[data-logout-confirm]');
    if (!form || form.getAttribute('data-lo-ok') === '1') return;
    e.preventDefault();
    open(form);
  }, true);
})();
