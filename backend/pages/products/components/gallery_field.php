<?php
/**
 * ສ່ວນ: ຊ່ອງ "ຮູບເພີ່ມເຕີມ (ຫຼາຍມຸມ)" ໃຕ້ຮູບຫຼັກ — ຖືກ require ຈາກ components/add_product/pane_single.php ($galPrefix = 'add')
 * ແລະ components/form_edit_product.php ($galPrefix = 'edit').
 * ກົດ + → ເລືອກຫຼາຍຮູບພ້ອມກັນ (ເລືອກຕື່ມໄດ້ອີກ, ກົດ × ເອົາອອກ) → ສົ່ງເປັນ gallery_imgs[] ພ້ອມຟອມ.
 * ຟອມແກ້ໄຂ: ຮູບເກົ່າໂຫຼດຈາກ api/products_api.php?action=get_product_images; ກົດ × = ເພີ່ມ del_gallery[] (ລຶບຕອນບັນທຶກ).
 * ບັນທຶກ: actions/add_product.php, edit_product.php → components/product_gallery_helpers.php. JS: ລຸ່ມໄຟລ໌ນີ້ (window.pgGallery)
 */
$galMax = defined('PRODUCT_GALLERY_MAX') ? PRODUCT_GALLERY_MAX : 10;
?>
<?php if (empty($GLOBALS['pgGalleryCss'])): $GLOBALS['pgGalleryCss'] = true; echo themeCssLink('product_gallery.css'); endif; ?>
<div class="pg-field" data-pg="<?php echo $galPrefix; ?>" data-max="<?php echo (int)$galMax; ?>">
  <div class="pg-head"><i class="fas fa-images"></i> <?php echo htmlspecialchars(tl('ຮູບເພີ່ມເຕີມ (ຫຼາຍມຸມ)')); ?> <small data-pg-count>0/<?php echo (int)$galMax; ?></small></div>
  <div class="pg-thumbs" data-pg-thumbs>
    <button type="button" class="pg-add" data-pg-add title="<?php echo htmlspecialchars(tl('ເລືອກຫຼາຍຮູບພ້ອມກັນ')); ?>"><i class="fas fa-plus"></i></button>
  </div>
  <small class="pg-hint"><?php echo htmlspecialchars(tl('ເລືອກຫຼາຍຮູບພ້ອມກັນໄດ້ — ສະແດງເປັນແກເລີຣີໃນໜ້າເວັບໄຊ')); ?></small>
  <input type="file" name="gallery_imgs[]" id="<?php echo $galPrefix; ?>_gallery_input" accept="image/*" multiple hidden data-pg-input>
  <div data-pg-del hidden></div>
</div>
<?php if (empty($GLOBALS['pgGalleryJs'])): $GLOBALS['pgGalleryJs'] = true; ?>
<script>
// ຮູບເພີ່ມເຕີມ: ເກັບໄຟລ໌ທີ່ເລືອກໄວ້ໃນ DataTransfer (ເລືອກຫຼາຍເທື່ອໄດ້) ແລ້ວໃສ່ຄືນໃຫ້ input ກ່ອນສົ່ງຟອມ
window.pgGallery = (function () {
  var state = {};
  function box(prefix) { return document.querySelector('[data-pg="' + prefix + '"]'); }
  function get(prefix) {
    if (!state[prefix]) state[prefix] = { files: [], existing: [] };
    return state[prefix];
  }
  function sync(prefix) {
    var b = box(prefix); if (!b) return;
    var st = get(prefix), dt = new DataTransfer();
    st.files.forEach(function (f) { dt.items.add(f); });
    b.querySelector('[data-pg-input]').files = dt.files;
    b.querySelector('[data-pg-count]').textContent = (st.existing.length + st.files.length) + '/' + b.getAttribute('data-max');
    b.querySelector('[data-pg-add]').style.display = (st.existing.length + st.files.length) >= parseInt(b.getAttribute('data-max'), 10) ? 'none' : '';
  }
  function thumb(prefix, url, onRemove, isNew) {
    var b = box(prefix), el = document.createElement('span');
    el.className = 'pg-item' + (isNew ? ' is-new' : '');
    el.innerHTML = '<img alt=""><button type="button" class="pg-x" title="<?php echo htmlspecialchars(tl('ລຶບຮູບ'), ENT_QUOTES); ?>">&times;</button>';
    el.querySelector('img').src = url;
    el.querySelector('button').addEventListener('click', function () { onRemove(); el.remove(); sync(prefix); });
    b.querySelector('[data-pg-thumbs]').insertBefore(el, b.querySelector('[data-pg-add]'));
  }
  function addFiles(prefix, list) {
    var b = box(prefix), st = get(prefix), max = parseInt(b.getAttribute('data-max'), 10);
    Array.prototype.forEach.call(list, function (f) {
      if (!/^image\//.test(f.type) || st.existing.length + st.files.length >= max) return;
      st.files.push(f);
      thumb(prefix, URL.createObjectURL(f), function () { st.files.splice(st.files.indexOf(f), 1); }, true);
    });
    sync(prefix);
  }
  function reset(prefix) {
    var b = box(prefix); if (!b) return;
    state[prefix] = { files: [], existing: [] };
    b.querySelectorAll('.pg-item').forEach(function (el) { el.remove(); });
    b.querySelector('[data-pg-del]').innerHTML = '';
    sync(prefix);
  }
  // ຟອມແກ້ໄຂ: ໂຫຼດຮູບເກົ່າ; ກົດ × = ໝາຍລຶບ (del_gallery[]) ຕອນບັນທຶກ
  function loadExisting(prefix, productId) {
    reset(prefix);
    $.getJSON('../../api/products_api.php?action=get_product_images&product_id=' + encodeURIComponent(productId), function (res) {
      if (!res || !res.success) return;
      var st = get(prefix), del = box(prefix).querySelector('[data-pg-del]');
      res.images.forEach(function (img) {
        st.existing.push(img.id);
        thumb(prefix, img.url, function () {
          st.existing.splice(st.existing.indexOf(img.id), 1);
          var h = document.createElement('input');
          h.type = 'hidden'; h.name = 'del_gallery[]'; h.value = img.id;
          del.appendChild(h);
        }, false);
      });
      sync(prefix);
    });
  }
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-pg-add]');
    if (add) add.closest('[data-pg]').querySelector('[data-pg-input]').click();
  });
  document.addEventListener('change', function (e) {
    var inp = e.target.closest('[data-pg-input]');
    if (!inp || !inp.files.length) return;
    var prefix = inp.closest('[data-pg]').getAttribute('data-pg');
    var picked = Array.prototype.slice.call(inp.files);
    addFiles(prefix, picked);   // sync() ໃສ່ໄຟລ໌ທັງໝົດທີ່ເລືອກສະສົມໄວ້ຄືນໃຫ້ input
  });
  return { reset: reset, loadExisting: loadExisting };
})();
</script>
<?php endif; ?>
