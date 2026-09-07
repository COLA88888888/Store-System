<!-- Sticky Top Bar: Search/Scan + Category Pills stay pinned while products scroll below -->
<div class="pos-sticky-topbar">
  <!-- Top Search & Barcode Scan Bar -->
  <div class="row mb-1">
    <div class="col-12">
      <div class="pos-search-wrapper">
        <!-- Barcode Icon Left -->
        <div class="pos-search-icon-left">
          <i class="fas fa-barcode"></i>
        </div>
        <!-- Input -->
        <input type="text" id="barcodeInput" class="pos-search-input"
               placeholder="<?php echo htmlspecialchars(t('pos.search_placeholder', 'ສະແກນບາໂຄ້ດ ຫຼື ພິມຊື່ສິນຄ້າ...')); ?>"
               autocomplete="off" autofocus oninput="filterGrid()">
        <!-- Clear Button Right -->
        <button type="button" class="pos-search-clear" onclick="document.getElementById('barcodeInput').value=''; filterGrid(); document.getElementById('barcodeInput').focus();" title="<?php echo htmlspecialchars(t('pos.clear', 'ລ້າງ')); ?>">
          <i class="fas fa-times"></i>
        </button>
        <!-- Scan indicator label -->
        <div class="pos-search-badge">
          <i class="fas fa-wifi fa-rotate-90 mr-1" style="font-size:0.82rem;"></i> SCAN
        </div>
      </div>
    </div>
  </div>

  <!-- Category Pill Buttons Bar -->
  <div class="category-pills-bar" style="display:flex !important; flex-wrap:nowrap !important; overflow-x:auto !important; overflow-y:hidden !important; gap:10px !important; padding-bottom:8px; margin-bottom:0; width:100%;">
    <button type="button" class="btn btn-outline-primary category-pill-btn active" data-id="" onclick="selectCategoryPill(this, '')" style="flex-shrink:0 !important; white-space:nowrap !important; border-radius:10px !important;">
      <?php echo htmlspecialchars(t('pos.category_all', 'ທັງໝົດ')); ?>
    </button>
    <?php foreach ($categories as $cat): ?>
      <button type="button" class="btn btn-outline-primary category-pill-btn" data-id="<?php echo $cat['category_id']; ?>" onclick="selectCategoryPill(this, '<?php echo $cat['category_id']; ?>')" style="flex-shrink:0 !important; white-space:nowrap !important; border-radius:10px !important;">
        <?php echo htmlspecialchars($cat['category_name']); ?>
      </button>
    <?php endforeach; ?>
  </div>
</div>
