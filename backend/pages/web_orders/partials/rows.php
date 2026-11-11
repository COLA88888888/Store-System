<?php
/*
 * ສ່ວນ: ແຖວຕາຕະລາງຄຳສັ່ງຊື້ຈາກເວັບໄຊ + ແຖບແບ່ງໜ້າ — ຖືກ require ຈາກ pages/web_orders/index.php
 * ທັງຕອນໂຫຼດໜ້າ ແລະ ຕອນຄົ້ນຫາສົດ (?ajax=1 → ສົ່ງ HTML ຂອງ tbody + pager ກັບເປັນ JSON).
 * ໃຊ້ຕົວແປຈາກ index.php: $orders, $itemsByOrder, $logByOrder, $statusLabels, $legacyAsConfirmed, $sysPrice, $page, $pages, $total
 */
$woRows = '';
$woPager = '';
ob_start();
if (!$orders): ?>
  <tr><td colspan="7" class="text-center text-muted py-5"><?php echo $total === 0 && ($q !== '' || $statusFilter !== '') ? 'ບໍ່ພົບຄຳສັ່ງຊື້ທີ່ຄົ້ນຫາ' : 'ຍັງບໍ່ມີຄຳສັ່ງຊື້ຈາກເວັບໄຊ'; ?></td></tr>
<?php endif;
foreach ($orders as $o):
  $oid = (int)$o['id'];
  $its = $itemsByOrder[$oid] ?? [];
  $st = in_array($o['status'], $legacyAsConfirmed, true) ? 'confirmed' : $o['status'];
  [$stText, $stClass] = $statusLabels[$st] ?? [$st, 'badge-light'];
  $lastLog = ($logByOrder[$oid] ?? [])[0] ?? null;

  // ແຈ້ງລູກຄ້າທາງ WhatsApp: ບອກຊື່ສິນຄ້າເລີຍ (ບໍ່ໃຫ້ລູກຄ້າຕ້ອງຈື່ເລກບິນ)
  $rawTel = preg_replace('/\D+/', '', (string)$o['customer_tel']);
  if (strpos($rawTel, '0') === 0) $rawTel = '856' . substr($rawTel, 1);
  elseif (strpos($rawTel, '20') === 0 || strpos($rawTel, '30') === 0) $rawTel = '856' . $rawTel;
  // ລາຄາຕາມລະບົບ: ລາຄາທີ່ບັນທຶກໃນອໍເດີ (>0) ຫຼື ລາຄາຂາຍປັດຈຸບັນ w_products.price (ສາຂາຂອງອໍເດີກ່ອນ)
  $orderStore = (int)($o['store_id'] ?? 0);
  $lines = [];
  $sum = 0.0;
  $missing = 0;
  foreach ($its as $x) {
      $pid = (int)$x['product_id'];
      $unit = (float)$x['price'];
      if ($unit <= 0 && isset($sysPrice[$pid])) {
          $unit = $sysPrice[$pid][$orderStore] ?? max($sysPrice[$pid]);
      }
      $qty = (int)$x['qty'];
      if ($unit <= 0) $missing++;
      $sum += $unit * $qty;
      $lines[] = ['name' => $x['product_name'], 'qty' => $qty, 'unit' => $unit, 'sub' => $unit * $qty];
  }
  $fmt = fn($n) => number_format($n) . ' ₭';
  $itemLines = implode("\n", array_map(fn($l) => '- ' . $l['name'] . ' x' . $l['qty']
      . ($l['unit'] > 0 ? ' = ' . $fmt($l['sub']) . ($l['qty'] > 1 ? ' (' . $fmt($l['unit']) . '/ໜ່ວຍ)' : '') : ''), $lines));
  $priceLine = $sum > 0 ? 'ລວມທັງໝົດ: ' . $fmt($sum) : '';

  // ແຈ້ງລູກຄ້າທາງ WhatsApp: ຖ້າມີເລກໃບຝາກ ຫຼື ສົ່ງເຄື່ອງແລ້ວ → ສົ່ງເປັນ "ບິນຂົນສົ່ງສິນຄ້າ" ຄົບຊຸດ
  $hasTracking = !empty($o['tracking_no']);
  $isShipped = in_array($st, ['shipped', 'arrived', 'completed'], true);
  if ($hasTracking || $isShipped) {
      $courierInfo = !empty($o['courier_name']) ? $o['courier_name'] . (!empty($o['courier_branch']) ? " (ສາຂາ: {$o['courier_branch']})" : '') : 'ຂົນສົ່ງ';
      $addrInfo = !empty($o['customer_address']) ? "\n📍 ທີ່ຢູ່ຈັດສົ່ງ: " . $o['customer_address'] : '';
      $trackingLine = $hasTracking ? "🔢 *ເລກໃບຝາກເຄື່ອງ (Tracking):* {$o['tracking_no']}\n" : '';

      $notifyMsg = "📦 *ບິນຂົນສົ່ງສິນຄ້າ / ແຈ້ງເລກໃບຝາກເຄື່ອງ*\n"
          . "🏢 *ຮ້ານ WlaoDev POS*\n"
          . "--------------------------------\n"
          . "📋 ເລກທີຄຳສັ່ງຊື້: #{$o['order_no']}\n"
          . "👤 ລູກຄ້າ: {$o['customer_name']}\n"
          . "📞 ເບີໂທ: {$o['customer_tel']}{$addrInfo}\n"
          . "🚚 ຂົນສົ່ງ: {$courierInfo}\n"
          . ($trackingLine ? "{$trackingLine}" : '')
          . "--------------------------------\n"
          . "📦 *ລາຍການສິນຄ້າ:*\n{$itemLines}\n"
          . ($priceLine ? "💰 {$priceLine}\n" : '')
          . "--------------------------------\n"
          . "✅ ສິນຄ້າໄດ້ຈັດສົ່ງອອກແລ້ວ! ທ່ານສາມາດນຳເລກໃບຝາກນີ້ໄປກວດສອບການຈັດສົ່ງ ຫຼື ລໍຖ້າຮັບສາຍຈາກຂົນສົ່ງ.\n"
          . "🙏 ຂອບໃຈທີ່ໃຊ້ບໍລິການ WlaoDev!";
  } elseif ($st === 'pending') {
      $notifyMsg = "ສະບາຍດີ {$o['customer_name']}\nລາຄາສິນຄ້າທີ່ທ່ານສອບຖາມຈາກ WlaoDev:\n{$itemLines}" . ($priceLine ? "\n{$priceLine}" : '')
          . "\nຕິດຕັ້ງຟຣີ ສອນໃຊ້ງານໃຫ້ຟຣີ — ຕອບກັບເພື່ອຢືນຢັນການສັ່ງຊື້ໄດ້ເລີຍ";
  } else {
      $notifyMsg = "ສະບາຍດີ {$o['customer_name']}\nອໍເດີ #{$o['order_no']} ໄດ້ຮັບການຢືນຢັນແລ້ວ:\n{$itemLines}" . ($priceLine ? "\n{$priceLine}" : '')
          . "\nທາງຮ້ານກຳລັງດຳເນີນການຈັດກຽມສິນຄ້າ ເພື່ອຈັດສົ່ງໃຫ້ທ່ານໂດຍໄວ.\n🙏 ຂອບໃຈທີ່ໃຊ້ບໍລິການ WlaoDev";
  }
  $waNotifyUrl = 'https://wa.me/' . $rawTel . '?text=' . rawurlencode($notifyMsg);

  // ໂລໂກ້ຂົນສົ່ງ (ສະແດງສະເພາະໂລໂກ້ ບໍ່ມີຕົວໜັງສື)
  $cCode = strtolower(trim((string)($o['courier_code'] ?? '')));
  $cName = (string)($o['courier_name'] ?? '');
  if ($cCode === '' && $cName !== '') {
      if (stripos($cName, 'anousith') !== false || mb_strpos($cName, 'ອະນຸສິດ') !== false) $cCode = 'anousith';
      elseif (stripos($cName, 'hal') !== false || mb_strpos($cName, 'ຮຸ່ງອາລຸນ') !== false) $cCode = 'hal';
      elseif (stripos($cName, 'mixay') !== false || mb_strpos($cName, 'ມີໄຊ') !== false) $cCode = 'mixay';
      elseif (stripos($cName, 'j&t') !== false || stripos($cName, 'jnt') !== false) $cCode = 'jnt';
      elseif (stripos($cName, 'zto') !== false) $cCode = 'zto';
  }
  $courierLogo = '';
  if ($cCode !== '') {
      $cRel = 'assets/img/couriers/' . $cCode . '.jpg';
      if (is_file(dirname(__DIR__, 3) . '/' . $cRel)) {
          $courierLogo = '../../' . $cRel;
      } else {
          $cRelSvg = 'assets/img/couriers/' . $cCode . '.svg';
          if (is_file(dirname(__DIR__, 3) . '/' . $cRelSvg)) $courierLogo = '../../' . $cRelSvg;
      }
  }
?>
  <tr>
    <td class="wo-col-order">
      <div class="wo-order-no font-weight-bold text-primary"><?php echo htmlspecialchars((string)$o['order_no']); ?></div>
      <div class="wo-order-date text-muted"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></div>
    </td>
    <td class="wo-col-customer">
      <div class="wo-cust-name-line">
        <b class="text-dark"><?php echo htmlspecialchars($o['customer_name']); ?></b>
        <?php if (!empty($o['member_no'])): ?><span class="badge badge-primary wo-member"><?php echo memberCode($o['member_no']); ?></span><?php endif; ?>
      </div>
      <div class="wo-cust-contact-line">
        <a href="tel:<?php echo htmlspecialchars($o['customer_tel']); ?>" class="wo-cust-phone"><i class="fas fa-phone-alt fa-xs"></i> <?php echo htmlspecialchars($o['customer_tel']); ?></a>
        <?php if ($courierLogo): ?>
          <img src="<?php echo htmlspecialchars($courierLogo); ?>" alt="<?php echo htmlspecialchars($o['courier_name']); ?>" class="wo-courier-logo-img" title="ຂົນສົ່ງ: <?php echo htmlspecialchars($o['courier_name']); ?><?php echo !empty($o['courier_branch']) ? ' (ສາຂາ: ' . htmlspecialchars($o['courier_branch']) . ')' : ''; ?>">
        <?php elseif (!empty($o['courier_name'])): ?>
          <span class="wo-cust-courier-icon" title="ຂົນສົ່ງ: <?php echo htmlspecialchars($o['courier_name']); ?><?php echo !empty($o['courier_branch']) ? ' (ສາຂາ: ' . htmlspecialchars($o['courier_branch']) . ')' : ''; ?>"><i class="fas fa-truck text-muted"></i></span>
        <?php endif; ?>
        <?php if (!empty($o['customer_address'])): ?>
          <span class="wo-info-icon" title="ທີ່ຢູ່: <?php echo htmlspecialchars((string)$o['customer_address']); ?>"><i class="fas fa-map-marker-alt text-secondary"></i></span>
        <?php endif; ?>
        <?php if (!empty($o['note'])): ?>
          <span class="wo-info-icon" title="ໝາຍເຫດ: <?php echo htmlspecialchars($o['note']); ?>"><i class="fas fa-sticky-note text-info"></i></span>
        <?php endif; ?>
      </div>
    </td>
    <td class="wo-col-slip text-center">
      <?php if (!empty($o['slip_img'])): $slipHref = 'slip.php?id=' . $oid; /* ສະແດງສະເພາະຮູບສະລິບ ບໍ່ມີຕົວໜັງສື — ກົດເພື່ອຂະຫຍາຍ */ ?>
        <a href="<?php echo htmlspecialchars($slipHref); ?>" class="wo-slip-thumb-link" data-wo-slip title="<?php echo !empty($o['bank_name']) ? htmlspecialchars((string)$o['bank_name']) . ' · ' : ''; ?><?php echo !empty($o['slip_uploaded_at']) ? date('d/m/Y H:i', strtotime($o['slip_uploaded_at'])) : 'ກົດເພື່ອເບິ່ງສະລິບ'; ?>">
          <img src="<?php echo htmlspecialchars($slipHref); ?>" alt="slip" class="wo-slip-thumb" loading="lazy">
          <span class="wo-slip-zoom-icon"><i class="fas fa-search-plus"></i></span>
        </a>
      <?php else: ?>
        <span class="text-muted font-weight-bold" style="font-size: 1.1rem;">-</span>
      <?php endif; ?>
    </td>
    <td class="wo-product-cell">
      <div class="wo-product-wrap">
        <div class="wo-product-items">
          <?php foreach ($lines as $l): ?>
            <div class="wo-item-line">
              <span class="wo-item-name" title="<?php echo htmlspecialchars($l['name']); ?>"><?php echo htmlspecialchars($l['name']); ?> <b>× <?php echo $l['qty']; ?></b></span>
              <?php if ($l['unit'] > 0): ?><span class="wo-item-sub"><?php echo $fmt($l['sub']); ?></span>
              <?php else: ?><span class="wo-noprice">ຍັງບໍ່ມີລາຄາ</span><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="wo-sum-tag">ລວມ: <b><?php echo $sum > 0 ? $fmt($sum) : '-'; ?></b></div>
      </div>
      <?php if ($missing): ?><div class="small text-danger mt-1">ມີ <?php echo $missing; ?> ລາຍການຍັງບໍ່ມີລາຄາ</div><?php endif; ?>
    </td>
    <td class="wo-col-status">
      <select class="form-control wo-status-select is-<?php echo $st; ?>" data-wo-select="<?php echo $oid; ?>" aria-label="ສະຖານະ">
        <?php foreach ($statusLabels as $sk => [$sl]): ?><option value="<?php echo $sk; ?>"<?php echo $sk === $st ? ' selected' : ''; ?>><?php echo htmlspecialchars($sl); ?></option><?php endforeach; ?>
      </select>
    </td>
    <td class="wo-col-tracking">
      <?php if ($st !== 'cancelled' && $st !== 'pending'): ?>
        <?php $hasTrk = !empty($o['tracking_no']); ?>
        <div class="wo-ship-cell">
          <?php if ($hasTrk): ?>
            <div class="wo-ship-track-row">
              <span class="wo-ship-pill" title="ເລກໃບຝາກເຄື່ອງ">
                <i class="fas fa-barcode"></i>
                <b class="wo-ship-no"><?php echo htmlspecialchars((string)$o['tracking_no']); ?></b>
              </span>
              <button type="button" class="wo-ship-edit-ico" data-wo-track-toggle="<?php echo $oid; ?>" title="ແກ້ໄຂເລກໃບຝາກ"><i class="fas fa-pen"></i></button>
            </div>
            <div class="wo-ship-edit-wrap" id="woTrackEdit_<?php echo $oid; ?>" style="display: none;">
              <div class="wo-track-box">
                <input type="text" class="wo-track-input" value="<?php echo htmlspecialchars((string)$o['tracking_no']); ?>" placeholder="ເລກໃບຝາກເຄື່ອງ">
                <button type="button" class="wo-track-btn" data-wo-track="<?php echo $oid; ?>" title="ບັນທຶກ"><i class="fas fa-save"></i> ບັນທຶກ</button>
              </div>
            </div>
          <?php else: ?>
            <div class="wo-track-box">
              <i class="fas fa-truck wo-track-icon"></i>
              <input type="text" class="wo-track-input" value="" placeholder="ເລກໃບຝາກເຄື່ອງ" maxlength="100">
              <button type="button" class="wo-track-btn" data-wo-track="<?php echo $oid; ?>" title="ບັນທຶກເລກໃບຝາກເຄື່ອງ"><i class="fas fa-save"></i> ບັນທຶກ</button>
            </div>
          <?php endif; ?>
          <div class="wo-ship-actions">
            <a href="print_shipping.php?id=<?php echo $oid; ?>" target="_blank" class="wo-btn-waybill" title="ເບິ່ງ / ພິມບິນຂົນສົ່ງ (Waybill)">
              <i class="fas fa-print"></i> ບິນຂົນສົ່ງ
            </a>
            <?php if (!empty($o['receipt_img'])): $rcHref = 'slip.php?id=' . $oid . '&type=receipt'; ?>
              <a href="<?php echo htmlspecialchars($rcHref); ?>" data-wo-receipt-view class="wo-btn-receipt is-view" title="ເບິ່ງຮູບບິນຂົນສົ່ງ"><i class="fas fa-receipt"></i> ຮູບບິນ</a>
            <?php else: ?>
              <label class="wo-btn-receipt is-upload" title="ແນບຮູບບິນຮັບເຄື່ອງຈາກຂົນສົ່ງ">
                <input type="file" accept="image/jpeg,image/png,image/webp" data-wo-receipt-file="<?php echo $oid; ?>" style="display:none;">
                <i class="fas fa-camera"></i> ແນບບິນ
              </label>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <span class="text-muted font-weight-bold" style="font-size: 1.1rem;">-</span>
      <?php endif; ?>
    </td>
    <td class="wo-col-actions text-center">
      <div class="wo-actions-wrap">
        <?php if ($st !== 'cancelled'): /* ສະແດງສະເພາະໄອຄອນ WhatsApp ຢ່າງດຽວ */ ?>
          <a href="<?php echo htmlspecialchars($waNotifyUrl); ?>" target="_blank" rel="noopener" class="wo-btn-wa" data-wo-notify="<?php echo $oid; ?>" title="<?php echo $hasTracking ? 'ສົ່ງບິນຂົນສົ່ງທາງ WhatsApp' : ($st === 'pending' ? 'ແຈ້ງລາຄາທາງ WhatsApp' : 'ສົ່ງ WhatsApp ຫາລູກຄ້າ'); ?>" aria-label="WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </a>
        <?php endif; ?>
        <?php if ($st !== 'completed' && $st !== 'cancelled'): ?>
          <button type="button" class="wo-btn-action is-done" data-wo-status="completed" data-wo-id="<?php echo $oid; ?>" data-confirm="ຢືນຢັນອໍເດີ <?php echo htmlspecialchars((string)$o['order_no']); ?> ສຳເລັດ (ລູກຄ້າຮັບສິນຄ້າແລ້ວ)?"><i class="fas fa-check"></i> ສຳເລັດ</button>
          <button type="button" class="wo-btn-action is-cancel" data-wo-status="cancelled" data-wo-id="<?php echo $oid; ?>" data-confirm="ຍົກເລີກອໍເດີ <?php echo htmlspecialchars((string)$o['order_no']); ?>?"><i class="fas fa-times"></i> ຍົກເລີກ</button>
        <?php else: ?>
          <button type="button" class="wo-btn-action is-reopen" data-wo-status="pending" data-wo-id="<?php echo $oid; ?>" data-confirm="ເປີດອໍເດີ <?php echo htmlspecialchars((string)$o['order_no']); ?> ຄືນເປັນ ໃໝ່?"><i class="fas fa-undo"></i> ເປີດຄືນ</button>
        <?php endif; ?>
      </div>
    </td>
  </tr>
<?php endforeach;
$woRows = ob_get_clean();

// ແຖບແບ່ງໜ້າ (ຟັງຊັນຮ່ວມ partials/pager.php)
require_once __DIR__ . '/pager.php';
$woPager = woPagerHtml($page, $pages, $total, 'ລາຍການ', true);   // ແບ່ງໜ້າສະແດງສະເໝີ
