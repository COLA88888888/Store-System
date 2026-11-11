/*
 * ແບ່ງໜ້າຕາຕະລາງຝັ່ງ browser (ແຖວທັງໝົດ render ມາແລ້ວ) — ແບບດຽວກັບໜ້າປະເພດສິນຄ້າ (pages/categories/categories.php).
 * ໃຊ້ຢູ່: ຫົວໜ່ວຍ (pages/units/units.php), ໂຊນ (pages/stock_check/zones.php), ຊັ້ນວາງ (pages/stock_check/shelves.php).
 *
 * Markup ທີ່ຕ້ອງມີໃນ footer:
 *   [data-pager-start] [data-pager-end]   ຕົວເລກ "ສະແດງ X-Y ລາຍການ"
 *   select[data-pager-size]               10 / 25 / 50 / all
 *   ul[data-pager-ul]                     ປຸ່ມວົງມົນ (class="pagination pagination-circle")
 * ແຖວທີ່ມີ .row-no ຈະຖືກນັບລຳດັບໃໝ່ຕໍ່ເນື່ອງຂ້າມໜ້າ.
 *
 *   var pager = TablePager.create({ rows: '#zoneTable tbody tr.zone-row', footer: '#zonePagFooter',
 *                                   filter: function (tr) { return true; } });
 *   pager.refresh();   // ເອີ້ນຫຼັງປ່ຽນຄຳຄົ້ນຫາ — ກັບໄປໜ້າ 1
 */
(function (window) {
  'use strict';

  function create(opts) {
    var footer = document.querySelector(opts.footer);
    var allRows = Array.prototype.slice.call(document.querySelectorAll(opts.rows));
    var pageSize = 10;
    var current = 1;
    var rows = [];

    var sizeSel = footer ? footer.querySelector('[data-pager-size]') : null;
    var ul = footer ? footer.querySelector('[data-pager-ul]') : null;
    var startEl = footer ? footer.querySelector('[data-pager-start]') : null;
    var endEl = footer ? footer.querySelector('[data-pager-end]') : null;

    function item(page, label, disabled, active) {
      return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
        '<a class="page-link" href="javascript:void(0)"' + (disabled || active ? '' : ' data-page="' + page + '"') + '>' + label + '</a></li>';
    }

    function renderControls(totalPages) {
      if (!ul) return;
      var dots = '<li class="page-item disabled"><span class="page-link" style="border:none; background:transparent;">...</span></li>';
      var html = item(current - 1, '<i class="fas fa-chevron-left"></i>', current <= 1, false);

      var maxButtons = 5;
      var startPage = Math.max(1, current - 2);
      var endPage = Math.min(totalPages, startPage + maxButtons - 1);
      if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

      if (startPage > 1) {
        html += item(1, '1', false, false);
        if (startPage > 2) html += dots;
      }
      for (var p = startPage; p <= endPage; p++) html += item(p, p, false, p === current);
      if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += dots;
        html += item(totalPages, totalPages, false, false);
      }
      html += item(current + 1, '<i class="fas fa-chevron-right"></i>', current >= totalPages, false);
      ul.innerHTML = html;
    }

    function apply() {
      var size = pageSize === 'all' ? (rows.length || 1) : pageSize;
      var total = rows.length;
      var totalPages = Math.ceil(total / size) || 1;
      if (current > totalPages) current = totalPages;
      var startIdx = (current - 1) * size;
      var endIdx = Math.min(startIdx + size, total);

      allRows.forEach(function (tr) { tr.style.display = 'none'; });
      for (var i = startIdx; i < endIdx; i++) {
        rows[i].style.display = '';
        var no = rows[i].querySelector('.row-no');
        if (no) no.textContent = i + 1;
      }

      if (startEl) startEl.textContent = total === 0 ? 0 : startIdx + 1;
      if (endEl) endEl.textContent = endIdx;
      renderControls(totalPages);
    }

    function refresh() {
      rows = allRows.filter(function (tr) { return opts.filter ? opts.filter(tr) : true; });
      current = 1;
      apply();
    }

    if (sizeSel) {
      sizeSel.addEventListener('change', function () {
        pageSize = sizeSel.value === 'all' ? 'all' : parseInt(sizeSel.value, 10);
        current = 1;
        apply();
      });
    }
    if (ul) {
      ul.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-page]');
        if (!a) return;
        current = parseInt(a.getAttribute('data-page'), 10) || 1;
        apply();
      });
    }

    refresh();
    return { refresh: refresh };
  }

  window.TablePager = { create: create };
})(window);
