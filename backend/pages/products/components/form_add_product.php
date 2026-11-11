<?php
/*
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * Popup ເພີ່ມສິນຄ້າ: ແທັບ "ເພີ່ມທີລະລາຍການ" ແລະ "ເພີ່ມຫຼາຍລາຍການ" ພ້ອມ JS ຂອງທັງສອງ.
 *   - add_product/pane_single.php : ແທັບ: ເພີ່ມທີລະລາຍການ
 *   - add_product/pane_bulk.php : ແທັບ: ເພີ່ມຫຼາຍລາຍການ
 *   - add_product/js_single.php : JS: ເພີ່ມທີລະລາຍການ
 *   - add_product/js_bulk_rows.php : JS: ແຖວຂອງເພີ່ມຫຼາຍລາຍການ
 *   - add_product/js_bulk_submit.php : JS: ນຳເຂົ້າ Excel ແລະ ບັນທຶກທັງໝົດ
 */
?>
<!-- Modal: Add Product -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1120px;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">

      <!-- HEADER -->
      <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
        <h5 class="modal-title font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 1.15rem;">
          <i class="fas fa-plus-circle mr-2"></i> <?php echo htmlspecialchars(t('products.modal_add_title', 'ເພີ່ມສິນຄ້າໃໝ່')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- MODE TABS: ເພີ່ມດ່ຽວ / ເພີ່ມຫຼາຍລາຍການ -->
      <ul class="nav nav-tabs px-4 pt-2" id="addProductModeTabs" role="tablist" style="border-bottom: 1px solid #e2e8f0;">
        <li class="nav-item">
          <a class="nav-link active font-weight-bold" id="tab-add-single" data-toggle="tab" href="#pane-add-single" role="tab" style="font-size: 0.9rem;">
            <i class="fas fa-box mr-1"></i> <?php echo htmlspecialchars(t('products.tab_add_single', 'ເພີ່ມດ່ຽວ')); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link font-weight-bold" id="tab-add-bulk" data-toggle="tab" href="#pane-add-bulk" role="tab" style="font-size: 0.9rem;">
            <i class="fas fa-layer-group mr-1"></i> <?php echo htmlspecialchars(t('products.tab_add_bulk', 'ເພີ່ມຫຼາຍລາຍການ')); ?>
          </a>
        </li>
      </ul>

      <div class="tab-content">

<?php require __DIR__ . '/add_product/pane_single.php'; /* ແທັບ: ເພີ່ມທີລະລາຍການ */ ?>
<?php require __DIR__ . '/add_product/pane_bulk.php'; /* ແທັບ: ເພີ່ມຫຼາຍລາຍການ */ ?>

<?php require __DIR__ . '/add_product/js_single.php'; /* JS: ເພີ່ມທີລະລາຍການ */ ?>
<?php require __DIR__ . '/add_product/js_bulk_rows.php'; /* JS: ແຖວຂອງເພີ່ມຫຼາຍລາຍການ */ ?>
<?php require __DIR__ . '/add_product/js_bulk_submit.php'; /* JS: ນຳເຂົ້າ Excel ແລະ ບັນທຶກທັງໝົດ */ ?>
</script>
