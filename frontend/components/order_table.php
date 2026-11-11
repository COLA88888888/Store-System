<?php
/**
 * ຕາຕະລາງອໍເດີ (pages/my_orders.php): ສິນຄ້າ | ລະຫັດຄຳສັ່ງຊື້ | ວັນທີສັ່ງ | ຂົນສົ່ງ | ສະຖານະ | ອັບເດດເມື່ອ | ລາຄາ | ຕິດຕາມ
 * ປຸ່ມ "ຕິດຕາມ" [data-ot-track] → modal ລາຍລະອຽດຄົບ (components/order_track_modal.php → <dialog data-otm> ໃນ pages/my_orders.php)
 * ກົດແຖວ ຫຼື ປຸ່ມ "ຕິດຕາມ" → modal ລາຍລະອຽດ (ບໍ່ມີໜ້າລາຍລະອຽດແຍກແລ້ວ). ຈໍນ້ອຍ: ແຕ່ລະແຖວກາຍເປັນກາດ (css/order_manage.css .ot-*).
 * [data-ot-skeleton] = ແຖວໂຫຼດ (ເງົາສີເທົາ) ສະແດງຕອນກົດແທັບ / ຕົວກອງ ລໍຖ້າໜ້າໃໝ່ (assets/js/order_manage.js).
 * ຕົວແປ: $orders (memberOrders), $otThumbs (memberOrderThumbs)
 */
$otCouriers = getLaoCouriers();
// ບັນຊີທະນາຄານ (ໂລໂກ້) + ຮູບສິນຄ້າ ສຳລັບ modal ຕິດຕາມ
$otBankList = getActiveBankAccounts($pdo, (int)($orders[0]['store_id'] ?? 0));   // ຟອມແນບສະລິບໃນ modal
$otBanks = array_column($otBankList, null, 'id');
$otItemImgs = memberItemImages($pdo, array_merge([], ...array_map(fn($x) => $x['items'] ?? [], $orders ?: [[]])));
$otHeads = [t('ສິນຄ້າ'), t('ລະຫັດຄຳສັ່ງຊື້'), t('ວັນທີສັ່ງ'), t('ຂົນສົ່ງ'), t('ສະຖານະ'), t('ອັບເດດເມື່ອ'), t('ລາຄາ'), t('ຕິດຕາມ')];
?>
<div class="ot" data-ot<?php echo $orders ? '' : ' hidden'; ?>>
    <table class="ot-table">
        <thead><tr><?php foreach ($otHeads as $i => $h): ?><th<?php echo $i === 6 ? ' class="is-num"' : ($i === 7 ? ' class="is-act"' : ''); ?>><?php echo e($h); ?></th><?php endforeach; ?></tr></thead>
        <tbody data-om-list>
            <?php foreach ($orders as $o):
                $oMeta = orderStatusMeta((string)$o['status']);
                $oItems = $o['items'] ?? [];
                $oMore = count($oItems) - 1;
                $oUrl = url('pages/my_orders.php?s=all&track=' . rawurlencode((string)$o['order_no']));   // ກົດແຖວ = ເປີດ modal ຕິດຕາມ (JS)
                $oCour = $otCouriers[$o['courier_code']] ?? null;
                $oTotal = orderDisplayTotal($o);
                $oThumb = $otThumbs[(int)$o['id']] ?? null; ?>
                <tr data-ot-row="<?php echo (int)$o['id']; ?>" data-om-text="<?php echo e(mb_strtolower($o['order_no'] . ' ' . implode(' ', array_column($oItems, 'product_name')))); ?>">
                    <td data-label="<?php echo e($otHeads[0]); ?>">
                        <a class="ot-prod" href="<?php echo e($oUrl); ?>">
                            <span class="ot-thumb"><?php if ($oThumb): ?><img src="<?php echo e($oThumb); ?>" alt="" loading="lazy"><?php else: ?><img src="<?php echo e($oMeta['img']); ?>" alt="" class="is-icon"><?php endif; ?></span>
                            <span class="ot-prod-txt"><b><?php echo e($oItems[0]['product_name'] ?? $o['order_no']); ?></b><?php if ($oMore > 0): ?><small><?php echo te('+ ອີກ %d ລາຍການ', $oMore); ?></small><?php endif; ?></span>
                        </a>
                    </td>
                    <td data-label="<?php echo e($otHeads[1]); ?>"><span class="ot-no"><?php echo e($o['order_no']); ?></span></td>
                    <td data-label="<?php echo e($otHeads[2]); ?>"><?php echo e(date('d/m/Y H:i', strtotime($o['created_at']))); ?></td>
                    <td data-label="<?php echo e($otHeads[3]); ?>">
                        <?php if ($oCour): ?><span class="ot-cour"><img src="<?php echo e($oCour['logo']); ?>" alt="" width="24" height="24"><span><?php echo e($oCour['short']); ?></span></span><?php else: ?>—<?php endif; ?>
                    </td>
                    <td data-label="<?php echo e($otHeads[4]); ?>">
                        <span class="mo-badge is-<?php echo e($oMeta['tone']); ?>"><?php echo e($oMeta['label']); ?></span>
                        <?php if (empty($o['slip_img']) && in_array($o['status'], ['pending', 'confirmed'], true)): ?><small class="mo-need-slip ot-slip"><?php echo te('ຍັງບໍ່ແນບສະລິບ'); ?></small><?php endif; ?>
                    </td>
                    <td data-label="<?php echo e($otHeads[5]); ?>"><?php echo e(date('d/m/Y H:i', strtotime($o['updated_at'] ?: $o['created_at']))); ?></td>
                    <td data-label="<?php echo e($otHeads[6]); ?>" class="is-num"><b class="ot-price"><?php echo $oTotal > 0 ? e(money($oTotal)) : '—'; ?></b></td>
                    <td data-label="<?php echo e($otHeads[7]); ?>" class="is-act">
                        <button type="button" class="ot-track-btn" data-ot-track="<?php echo (int)$o['id']; ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 4h14v12H1zM15 9h4l3 3v4h-7z"/><circle cx="5.5" cy="18.5" r="1.8"/><circle cx="18.5" cy="18.5" r="1.8"/></svg>
                            <span><?php echo te('ຕິດຕາມ'); ?></span>
                        </button>
                    </td>
                </tr>
                <?php require __DIR__ . '/order_track_modal.php'; /* <template> ລາຍລະອຽດຕິດຕາມ → modal */ ?>
            <?php endforeach; ?>
        </tbody>
        <tbody class="ot-skel" data-ot-skeleton hidden aria-hidden="true">
            <?php for ($i = 0; $i < 5; $i++): ?>
                <tr>
                    <td><span class="ot-prod"><i class="sk sk-img"></i><span class="ot-prod-txt"><i class="sk" style="width:180px"></i><i class="sk" style="width:120px"></i></span></span></td>
                    <td><i class="sk" style="width:110px"></i></td>
                    <td><i class="sk" style="width:120px"></i></td>
                    <td><span class="ot-cour"><i class="sk sk-dot"></i><i class="sk" style="width:80px"></i></span></td>
                    <td><i class="sk sk-pill"></i></td>
                    <td><i class="sk" style="width:100px"></i></td>
                    <td class="is-num"><i class="sk" style="width:70px"></i></td>
                    <td class="is-act"><i class="sk sk-pill"></i></td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>
