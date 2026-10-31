<?php
/*
 * ສ່ວນ: JS ສິດແບບ realtime ຂອງໂຄງຫຼັກ (ແຍກອອກຈາກ home/dashboard.php — require ຫຼັງ js_layout.php)
 * ຖາມ api/my_permissions.php ທຸກ 4 ວິນາທີ (passive — ບໍ່ຕໍ່ເວລາ idle). ເມື່ອສິດປ່ຽນ (ໜ້າກຳນົດສິດ):
 *  - ເມນູ sidebar ທຸກອັນມີ data-perm="ໂມດູນ,..." (permMenuAttr ໃນ config/helpers/permissions.php) → ສະແດງ/ເຊື່ອງທັນທີ
 *  - ໜ້າທີ່ເປີດຢູ່ໃນ iframe: ຖ້າສິດຂອງໂມດູນໜ້ານັ້ນປ່ຽນ (ເບິ່ງ/ເພີ່ມ/ແກ້ໄຂ/ລຶບ) → ໂຫຼດໜ້ານັ້ນໃໝ່ ໃຫ້ເຊີເວີ
 *    ວາດປຸ່ມຕາມສິດໃໝ່ (ປຸ່ມທຸກໜ້າກວດ hasPermission() ຢູ່ເຊີເວີ — ເງື່ອນໄຂອື່ນຂອງປຸ່ມຍັງຖືກຕ້ອງຄົບ);
 *    ຖ້າສິດເບິ່ງໜ້ານັ້ນຖືກປິດ → ໄປເມນູທຳອິດທີ່ຍັງມີສິດ
 * ໂມດູນຂອງໜ້າ = data-perm ຂອງເມນູທີ່ລິ້ງກົງກັບໜ້າໃນ iframe (ໜ້າທີ່ບໍ່ມີໃນເມນູ: ບໍ່ໂຫຼດໃໝ່ເອງ).
 */
?>
<script>
(function () {
  var PERM_URL = '<?php echo $base_path; ?>api/my_permissions.php';
  var POLL_MS = 4000;
  var lastPerms = null, lastSig = null, busy = false;

  function canView(perms, list) {
    return String(list || '').split(',').some(function (m) { return perms[m] && perms[m].charAt(0) === '1'; });
  }

  // ສະແດງ/ເຊື່ອງເມນູຕາມສິດ (ກຸ່ມ ແລະ ຫົວຂໍ້ມີ data-perm ລວມໂມດູນລູກ). ກຸ່ມ dropdown ທີ່ເມນູຍ່ອຍຖືກເຊື່ອງໝົດ
  // ກໍເຊື່ອງນຳ — ບໍ່ໃຫ້ເຫຼືອກຸ່ມເປົ່າ (ເຊັ່ນ ມີຖັນ report ແຕ່ປິດສະວິດລາຍງານຍ່ອຍທຸກອັນ)
  function applyMenu(perms) {
    $('.main-sidebar [data-perm]').each(function () {
      this.hidden = !canView(perms, this.getAttribute('data-perm'));
    });
    $('.main-sidebar li.has-treeview').each(function () {
      if (this.hidden) return;
      var subs = $(this).find('> .nav-treeview > li.nav-item');
      if (subs.length && !subs.filter(function () { return !this.hidden; }).length) this.hidden = true;
    });
  }

  function framePath() {
    try { return document.querySelector('iframe[name="frame"]').contentWindow.location.pathname; } catch (e) { return ''; }
  }

  // ລິ້ງເມນູ: js_layout.php ຍ້າຍ href → data-href ຕອນໂຫຼດ ຈຶ່ງອ່ານທັງສອງ
  function menuLinks() {
    return $('.main-sidebar a.nav-link[target="frame"]').filter(function () {
      return !!(this.getAttribute('href') || this.getAttribute('data-href'));
    });
  }
  function linkPath(a) {
    try { return new URL(a.getAttribute('href') || a.getAttribute('data-href'), window.location.href).pathname; } catch (e) { return ''; }
  }

  // ເມນູ (li[data-perm]) ຂອງໜ້າທີ່ເປີດຢູ່ໃນ iframe
  function currentMenuItem() {
    var path = framePath();
    if (!path) return null;
    var hit = null;
    menuLinks().each(function () {
      if (linkPath(this) === path) { hit = $(this).closest('li[data-perm]')[0] || null; return false; }
    });
    return hit;
  }

  function firstAllowedLink() {
    return menuLinks().filter(function () { return !$(this).closest('[hidden]').length; }).first()[0] || null;
  }

  function notify(icon, title) {
    if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 2500 });
  }

  function onChange(prev, cur) {
    applyMenu(cur);
    var item = currentMenuItem();
    if (!item) return;
    var mods = item.getAttribute('data-perm').split(',');
    var changed = mods.some(function (m) { return prev[m] !== cur[m]; });
    if (!changed) return;

    if (!canView(cur, mods.join(','))) {
      // ສິດເບິ່ງໜ້ານີ້ຖືກປິດ → ໄປເມນູທຳອິດທີ່ຍັງມີສິດ (ຄລິກ ເພື່ອໃຫ້ js_layout.php ຕັ້ງ active / ຈື່ໜ້າ)
      notify('warning', '<?php echo tlj('ສິດເຂົ້າໜ້ານີ້ຖືກປິດແລ້ວ'); ?>');
      var link = firstAllowedLink();
      if (link) {
        link.click();
      } else {
        var f = document.querySelector('iframe[name="frame"]');
        f.removeAttribute('src');
        f.srcdoc = '<div style="font-family:\'Noto Sans Lao\',sans-serif;text-align:center;padding:80px 20px;color:#64748b;">' +
                   '<?php echo tlj('ທ່ານບໍ່ມີສິດເຂົ້າເມນູໃດໃນຕອນນີ້'); ?> — <?php echo tlj('ກະລຸນາຕິດຕໍ່ຜູ້ບໍລິຫານ'); ?></div>';
      }
      return;
    }
    // ຍັງມີສິດເບິ່ງ ແຕ່ສິດເພີ່ມ/ແກ້ໄຂ/ລຶບ ປ່ຽນ → ໂຫຼດໜ້ານີ້ໃໝ່ ໃຫ້ປຸ່ມສະແດງ/ເຊື່ອງຕາມສິດໃໝ່
    notify('info', '<?php echo tlj('ສິດຂອງທ່ານຖືກປັບປ່ຽນ'); ?> — <?php echo tlj('ໂຫຼດໜ້າໃໝ່'); ?>');
    try { document.querySelector('iframe[name="frame"]').contentWindow.location.reload(); } catch (e) {}
  }

  function poll() {
    if (busy || document.hidden) return;
    busy = true;
    $.ajax({ url: PERM_URL, dataType: 'json', cache: false, timeout: 8000 })
      .done(function (res) {
        if (!res || !res.success || !res.perms) return;
        if (lastSig === null) {
          applyMenu(res.perms);           // ຄັ້ງທຳອິດ: ໃຫ້ກົງກັບເຊີເວີ (ບໍ່ໂຫຼດໜ້າໃໝ່)
        } else if (res.sig !== lastSig) {
          onChange(lastPerms, res.perms);
        }
        lastPerms = res.perms;
        lastSig = res.sig;
      })
      .always(function () { busy = false; });
  }

  $(function () {
    poll();
    setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
  });
})();
</script>
