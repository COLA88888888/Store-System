<!-- Sticky Top Bar: Search/Scan + Category Pills stay pinned while products scroll below -->
<div class="pos-sticky-topbar">
  <!-- Top Search & Barcode Scan Bar (sits inside the dashboard navbar on wide screens — see
       html.pos-nav-mode in themes/pos/02-mobile-cart-search.css) -->
  <div class="row mb-1 pos-search-row">
    <div class="col-12 d-flex align-items-center" style="gap: 10px; width: 100%;">
      <div class="pos-search-wrapper" style="flex: 1 1 0%; min-width: 0;">
        <!-- Barcode Icon Left -->
        <div class="pos-search-icon-left">
          <i class="fas fa-barcode"></i>
        </div>
        <!-- Input -->
        <input type="text" id="barcodeInput" class="pos-search-input"
               placeholder="<?php echo htmlspecialchars(t('pos.search_placeholder', 'ສະແກນບາໂຄ້ດ ຫຼື ພິມຊື່ສິນຄ້າ...')); ?>"
               autocomplete="off" inputmode="none" autofocus oninput="filterGrid()">
        <!-- Clear Button Right -->
        <button type="button" class="pos-search-clear" onclick="document.getElementById('barcodeInput').value=''; filterGrid(); ensureBarcodeInputFocused();" title="<?php echo htmlspecialchars(t('pos.clear', 'ລ້າງ')); ?>">
          <i class="fas fa-times"></i>
        </button>
        <!-- Scan indicator label -->
        <div class="pos-search-badge">
          <i class="fas fa-wifi fa-rotate-90 mr-1" style="font-size:0.82rem;"></i> SCAN
        </div>
      </div>

      <!-- ປຸ່ມຈໍລູກຄ້າ ຢູ່ Navbar ຖັດຈາກບັອກສະແກນ (ຢູ່ລະດັບກາດຂອງລາຍການສິນຄ້າ, ມຸມ 4px) -->

      <button type="button" class="btn btn-sm btn-light font-weight-bold pos-topbar-btn shadow-xs d-none d-md-inline-flex" id="btnCustomerDisplay" onclick="openCustomerDisplayWindow()" title="<?php echo htmlspecialchars(t('pos.customer_display_title', 'ເປີດໜ້າຈໍສະແດງຜົນລູກຄ້າ (Customer Display)')); ?>" style="background: #ffffff; color: #1e293b; border: 1.5px solid #cbd5e1; border-radius: 4px;">
        <i class="fas fa-desktop mr-2 text-primary" style="font-size: 0.95rem; margin-right: 8px !important;"></i> <?php echo htmlspecialchars(t('pos.customer_display_btn', 'ຈໍລູກຄ້າ')); ?>
      </button>
    </div>
  </div>

  <!-- Category Pill Buttons Bar -->
  <?php
    $catCounts = [];
    $totalProductsCount = 0;
    if (!empty($products) && is_array($products)) {
      $totalProductsCount = count($products);
      foreach ($products as $pr) {
        $cId = (string)($pr['category_id'] ?? '');
        if ($cId !== '') {
          $catCounts[$cId] = ($catCounts[$cId] ?? 0) + 1;
        }
      }
    }
  ?>
  <?php /* gap / padding ຢູ່ໃນ themes/pos/03-products-checkout.css (ຄອມ) ແລະ 04-cart-items.css (ມືຖື) — ຢ່າໃສ່ inline !important ຄືນ ບໍ່ດັ່ງນັ້ນ CSS ມືຖືຈະໃຊ້ບໍ່ໄດ້ */ ?>
  <div class="category-pills-bar" style="display:flex !important; flex-wrap:nowrap !important; overflow-x:auto !important; overflow-y:hidden !important; margin-bottom:0; width:100%;">
    <button type="button" class="btn btn-outline-primary category-pill-btn active" data-id="" onclick="selectCategoryPill(this, '')" style="position:relative !important; flex-shrink:0 !important; white-space:nowrap !important; border-radius:6px !important;">
      <span><?php echo htmlspecialchars(t('pos.category_all', 'ທັງໝົດ')); ?></span>
      <span class="category-count-badge"><?php echo $totalProductsCount; ?></span>
    </button>
    <?php foreach ($categories as $cat): ?>
      <?php $cCount = $catCounts[(string)$cat['category_id']] ?? 0; ?>
      <button type="button" class="btn btn-outline-primary category-pill-btn" data-id="<?php echo $cat['category_id']; ?>" onclick="selectCategoryPill(this, '<?php echo $cat['category_id']; ?>')" style="position:relative !important; flex-shrink:0 !important; white-space:nowrap !important; border-radius:6px !important;">
        <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
        <span class="category-count-badge"><?php echo $cCount; ?></span>
      </button>
    <?php endforeach; ?>
  </div>
</div>
