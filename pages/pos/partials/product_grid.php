<!-- Products Grid (2 on mobile, 6 on desktop screens) -->
<div class="row px-1" id="productGrid">
  <?php foreach ($products as $p): ?>
    <?php
      $rawImg = $p['img_url'] ?? '';
      $imgName = $rawImg ? basename($rawImg) : '';
      if (!empty($imgName) && file_exists(__DIR__ . '/../../../assets/product_img/' . $imgName)) {
          $imgPath = $base_path . 'assets/product_img/' . $imgName;
      } else {
          $imgPath = $base_path . 'assets/img/image.jpg';
      }
      $stock = floatval($p['qty'] ?? 0);
      $hasPromo = !empty($p['has_promo']);
      $isGiftPromo = ($p['promo_type'] ?? '') === 'buy_x_get_y' || !empty($p['gift_product_name']);
      $promoIsGiftStyle = $isGiftPromo && floatval($p['original_price'] ?? 0) <= floatval($p['price'] ?? 0);
      $badgeBg = $promoIsGiftStyle ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)';
      $badgeShadow = $promoIsGiftStyle ? 'rgba(16,185,129,0.35)' : 'rgba(239,68,68,0.35)';
    ?>
    <div class="col-6 col-sm-4 col-md-3 col-lg-2 pos-product-col product-item-card px-1 mb-2" 
         data-name="<?php echo htmlspecialchars(strtolower($p['product_name'])); ?>"
         data-barcode="<?php echo htmlspecialchars($p['barcode'] ?? ''); ?>"
         data-id="<?php echo $p['product_id']; ?>"
         data-category="<?php echo $p['category_id']; ?>">
         
      <div class="card product-card product-card-<?php echo $p['product_id']; ?> h-100 shadow-sm" 
           data-id="<?php echo $p['product_id']; ?>"
           style="border-radius: 12px !important; border: none !important; background: <?php echo $stock <= 0 ? '#fff5f5' : '#ffffff'; ?>; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; <?php echo $stock <= 0 ? 'opacity: 0.82;' : ''; ?>;">
        
        <div class="product-img-wrap" style="position: relative; width: 100%; height: 135px; overflow: hidden; background: #ffffff; padding: 0 !important; display: block; border-radius: 9px 9px 0 0; border-bottom: 1px solid #e2e8f0;">
          <img src="<?php echo htmlspecialchars($imgPath); ?>" 
               class="product-img-full"
               style="width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 100% !important; object-fit: contain !important; padding: 2px !important; background: #ffffff !important; display: block !important; border-radius: 9px 9px 0 0;"
               onerror="this.src='<?php echo $base_path; ?>assets/img/image.jpg';">
          
          <!-- Stock Status Badge Top Left -->
          <span class="stock-status-badge stock-status-badge-<?php echo $p['product_id']; ?> <?php echo $stock <= 0 ? 'out-of-stock' : ($stock <= 10 ? 'low-stock' : ''); ?>" 
                style="display: <?php echo $stock <= 10 ? 'inline-block' : 'none'; ?>; position: absolute; top: 4px; left: 4px; z-index: 10;">
            <?php echo $stock <= 0 ? htmlspecialchars(t('pos.stock_out', 'ໝົດແລ້ວ')) : htmlspecialchars(t('pos.stock_low', 'ໃກ້ໝົດ')); ?>
          </span>

          <!-- Remaining Stock Badge Top Right -->
          <div class="stock-info-wrap stock-info-wrap-<?php echo $p['product_id']; ?> <?php echo $stock <= 0 ? 'out-of-stock' : ($stock <= 10 ? 'low-stock' : 'in-stock'); ?>" style="position: absolute; top: 4px; right: 4px; z-index: 10;">
            <span><span class="stock-remaining-label"><?php echo htmlspecialchars(t('pos.stock_remaining', 'ເຫຼືອ')); ?></span>: <span class="font-weight-bold product-stock-val product-stock-val-<?php echo $p['product_id']; ?>" data-initial-stock="<?php echo $p['qty']; ?>" data-cut-qty="<?php echo intval($p['cut_qty'] ?? 1); ?>"><?php echo number_format($p['qty']); ?></span></span>
          </div>

          <!-- Cart Quantity Badge (quantity currently in cart) Top Left, opposite side from remaining stock -->
          <span class="cart-qty-badge product-qty-badge-<?php echo $p['product_id']; ?>" style="position: absolute; top: 4px; left: 4px; z-index: 11;">0</span>

          <!-- Promo name — full-width band along the bottom of the product image, left-aligned -->
          <?php if ($hasPromo): ?>
            <div style="position: absolute; left: 0; right: 0; bottom: 0; z-index: 12; pointer-events: none; font-size: 0.8rem; font-weight: 800; line-height: 1.3; text-align: left; color: #ffffff; padding: 5px 9px; background: <?php echo $badgeBg; ?>; box-shadow: 0 -2px 8px <?php echo $badgeShadow; ?>; white-space: normal; word-break: break-word; text-shadow: 0 1px 3px rgba(0,0,0,0.45);">
              <?php echo htmlspecialchars($p['promo_badge']); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="card-body d-flex flex-column justify-content-between text-left" style="padding: 6px 8px !important;">
          <div>
            <h6 class="font-weight-bold text-dark mb-1" style="font-size: 0.84rem; line-height: 1.2; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($p['product_name']); ?>">
              <?php echo htmlspecialchars($p['product_name']); ?>
            </h6>

          </div>

          <div class="mt-auto pt-1 border-top text-left">
            <?php
              $pgCurCode  = $posDisplayCurrency['code'] ?? 'KIP';
              $pgCurRates = $posDisplayCurrency['rates'] ?? null;
            ?>
            <?php if ($hasPromo && floatval($p['original_price'] ?? 0) > floatval($p['price'] ?? 0)): ?>
              <div class="d-flex align-items-baseline flex-wrap justify-content-start" style="gap: 3px;">
                <small class="font-weight-bold text-muted" style="font-size: 0.80rem; text-decoration: line-through; color: #94a3b8 !important;">
                  <?php echo formatMoneyIn($p['original_price'], $pgCurCode, $pgCurRates); ?>
                </small>
                <span class="font-weight-bold text-danger" style="font-size: 0.94rem; color: #ef4444 !important;">
                  <?php echo formatMoneyIn($p['price'], $pgCurCode, $pgCurRates); ?>
                </span>
              </div>
            <?php else: ?>
              <span class="font-weight-bold" style="font-size: 0.96rem; color: #16a34a;">
                <?php echo formatMoneyIn($p['price'], $pgCurCode, $pgCurRates); ?>
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div id="productGridEmpty" class="text-center text-muted py-5" style="display:none; width:100%;">
  <i class="fas fa-box-open" style="font-size:2.2rem; opacity:0.4;"></i>
  <div class="mt-2" style="font-size:0.95rem; font-weight:600;"><?php echo htmlspecialchars(t('pos.no_products', 'ບໍ່ມີຂໍ້ມູນສິນຄ້າ')); ?></div>
</div>

<script>
(function() {
  try {
    var localStocks = {};
    <?php foreach ($products as $p): ?>
      localStocks[<?php echo intval($p['product_id']); ?>] = <?php echo floatval($p['qty']); ?>;
    <?php endforeach; ?>
    localStorage.setItem('pos_local_stocks', JSON.stringify(localStocks));
  } catch(e) {}
})();
</script>
