/**
 * ກະຕ່າສິນຄ້າ — ເອີ້ນ api/cart.php (ຕ້ອງໂຫຼດຫຼັງ main.js ເພາະໃຊ້ apiPost ແລະ showToast).
 *   [data-add-to-cart="id"]   ປຸ່ມໃສ່ກະຕ່າ (data-qty-from="#selector" = ອ່ານຈຳນວນຈາກ input)
 *   [data-cart-qty="id"]      input ຈຳນວນໃນໜ້າກະຕ່າ → ຕັ້ງຈຳນວນ ແລ້ວໂຫຼດໜ້າໃໝ່
 *   [data-cart-remove="id"]   ປຸ່ມລຶບໃນໜ້າກະຕ່າ
 */
(function () {
  'use strict';

  // ຈຳນວນສິນຄ້າໃນກະຕ່າ ເທິງປຸ່ມກະຕ່າໃນເມນູ (layouts/navbar.php)
  function updateCount(n) {
    if (typeof n !== 'number') return;
    document.querySelectorAll('[data-cart-count]').forEach(function (el) { el.textContent = n; el.hidden = n <= 0; });
  }


  document.addEventListener('click', function (ev) {
    var add = ev.target.closest('[data-add-to-cart]');
    if (add && !add.disabled) {
      ev.preventDefault();
      ev.stopPropagation();
      var qty = 1;
      var from = add.getAttribute('data-qty-from');
      if (from && document.querySelector(from)) qty = parseInt(document.querySelector(from).value, 10) || 1;
      add.disabled = true;
      var oldHtml = add.innerHTML;
      add.innerHTML = '<span>…</span>';
      window.apiPost('api/cart.php', { action: 'add', id: add.getAttribute('data-add-to-cart'), qty: qty }).then(function (res) {
        add.disabled = false;
        add.innerHTML = oldHtml;
        if (res.need_member && window.openMemberModal) { window.openMemberModal('register', res.message); return; }   // ຍັງບໍ່ລົງທະບຽນ
        window.showToast(res.message, res.success ? 'success' : 'error');
        updateCount(res.count);
      }).catch(function () {
        add.disabled = false;
        add.innerHTML = oldHtml;
      });
      return;
    }

    var remove = ev.target.closest('[data-cart-remove]');
    if (remove) {
      remove.disabled = true;
      window.apiPost('api/cart.php', { action: 'remove', id: remove.getAttribute('data-cart-remove') }).then(function () {
        window.location.reload();
      });
    }
  });

  document.addEventListener('change', function (ev) {
    var input = ev.target.closest('[data-cart-qty]');
    if (!input) return;
    var qty = Math.max(0, parseInt(input.value, 10) || 0);
    window.apiPost('api/cart.php', { action: 'set', id: input.getAttribute('data-cart-qty'), qty: qty }).then(function (res) {
      if (!res.success) window.showToast(res.message, 'error');
      setTimeout(function () { window.location.reload(); }, res.success ? 0 : 900);
    });
  });
})();
