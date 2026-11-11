<?php
/**
 * ລາຍລະອຽດ "ຕິດຕາມອໍເດີ" ຂອງ 1 ອໍເດີ ເປັນ <template> — ປຸ່ມ "ຕິດຕາມ" ໃນ components/order_table.php ສຳເນົາໄປໃສ່
 * <dialog class="otm"> (pages/my_orders.php) ດ້ວຍ assets/js/order_manage.js. ບໍ່ອອກໄປໜ້າອື່ນ.
 * ເນື້ອໃນ: ໂລໂກ້ບໍລິສັດ + ເລກອໍເດີ + ສະຖານະ | ຂັ້ນຕອນ 6 ຂັ້ນ | ແຖບຜູ້ຮັບ | 2 ກາດເທົ່າກັນ: ຂົນສົ່ງ + ບິນຮັບເຄື່ອງ / ທະນາຄານ + ສະລິບ
 *          (ຮູບໃຫຍ່, ປຸ່ມ ຂະຫຍາຍ + ດາວໂຫຼດ) | ສິນຄ້າ (ຮູບ) + ຍອດລວມ
 * ຕົວແປຈາກ order_table.php: $o, $oMeta, $oCour, $oTotal, $otBanks (id → ບັນຊີທະນາຄານ), $otItemImgs (product_id → ຮູບ). CSS: .otm-* ໃນ css/order_manage.css
 */
$oHist = orderStatusHistory($pdo, $o);
$oTimes = orderStepTimes($oHist);
$oStep = (int)$oMeta['step'];
$oBank = $otBanks[(int)($o['bank_account_id'] ?? 0)] ?? null;
$oSlip = slipUrl($o['slip_img'] ?? '', (string)$o['order_no']);
$oReceipt = receiptUrl($o['receipt_img'] ?? '', (string)$o['order_no']);
$oOk = !in_array($o['status'], ['pending', 'cancelled'], true);
$ico = fn(string $p, int $s = 18) => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
?>
<template data-ot-detail="<?php echo (int)$o['id']; ?>">
    <div class="otm-head">
        <img class="otm-logo" src="<?php echo asset('img/logo.png'); ?>" alt="WlaoDev" width="54" height="36">
        <div class="otm-head-txt">
            <small><?php echo te('ຕິດຕາມອໍເດີ'); ?></small>
            <b><?php echo e($o['order_no']); ?></b>
            <span><?php echo te('ສັ່ງເມື່ອ'); ?> <?php echo e(date('d/m/Y H:i', strtotime($o['created_at']))); ?></span>
        </div>
        <span class="mo-badge is-<?php echo e($oMeta['tone']); ?> otm-status"><?php echo e($oMeta['label']); ?></span>
        <button type="button" class="otm-x" data-otm-close aria-label="<?php echo te('ປິດ'); ?>"><?php echo $ico('<path d="M18 6 6 18M6 6l12 12"/>', 20); ?></button>
    </div>
    <div class="otm-body">
        <?php if ($o['status'] === 'cancelled'): ?>
            <p class="ot-track-cancel"><?php echo e($oMeta['title']); ?> — <?php echo e($oMeta['desc']); ?></p>
        <?php else: ?>
            <ol class="ot-steps" style="--ot-progress: <?php echo round(max(0, ($oStep - 1) / (count(ORDER_STEPS) - 1)), 4); ?>">
                <?php foreach (ORDER_STEPS as $i => $code): $sm = orderStatusMeta($code); $done = $i + 1 <= $oStep; ?>
                    <li class="<?php echo $done ? 'is-done' : ''; ?><?php echo $i + 1 === $oStep ? ' is-current' : ''; ?>">
                        <span class="ot-dot"><?php if ($done): ?><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg><?php else: echo $i + 1; endif; ?></span>
                        <b><?php echo e($sm['label']); ?></b>
                        <small><?php echo isset($oTimes[$code]) ? e(date('d/m H:i', strtotime($oTimes[$code]))) : '—'; ?></small>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p class="ot-track-now"><b><?php echo e($oMeta['title']); ?></b> · <?php echo e($oMeta['desc']); ?></p>
        <?php endif; ?>

        <?php /* ຜູ້ຮັບ: ແຖບບາງເຕັມແຖວ (ບໍ່ມີກາດຫວ່າງ) */ ?>
        <div class="otm-recv">
            <span class="otm-recv-ico"><?php echo $ico('<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'); ?></span>
            <span><small><?php echo te('ຜູ້ຮັບ'); ?></small><b><?php echo e($o['customer_name']); ?></b></span>
            <span><small><?php echo te('ເບີໂທ'); ?></small><b><?php echo e(laoPhoneFormat($o['customer_tel'])); ?></b></span>
            <?php if (!empty($o['courier_branch'])): ?><span><small><?php echo te('ສາຂາປາຍທາງ:'); ?></small><b><?php echo e($o['courier_branch']); ?></b></span><?php endif; ?>
            <?php if (!empty($o['customer_address'])): ?><span class="is-wide"><small><?php echo te('ທີ່ຢູ່'); ?></small><b><?php echo e((string)$o['customer_address']); ?></b></span><?php endif; ?>
            <?php if (!empty($o['note'])): ?><span class="is-wide"><small><?php echo te('ໝາຍເຫດ'); ?></small><b><?php echo e((string)$o['note']); ?></b></span><?php endif; ?>
        </div>

        <?php /* 2 ກາດເທົ່າກັນ ໂຄງດຽວກັນ: ຫົວ (ໂລໂກ້ + ຊື່) → ເອກະສານ (ຮູບໃຫຍ່ + ລາຍລະອຽດ + ປຸ່ມ ຂະຫຍາຍ / ດາວໂຫຼດ) */ ?>
        <div class="otm-grid">
            <!-- ການຈັດສົ່ງ + ບິນຮັບເຄື່ອງ -->
            <section class="otm-card otm-doc-card">
                <h4><?php echo $ico('<path d="M1 4h14v12H1zM15 9h4l3 3v4h-7z"/><circle cx="5.5" cy="18.5" r="1.8"/><circle cx="18.5" cy="18.5" r="1.8"/>'); ?> <?php echo te('ການຈັດສົ່ງ'); ?></h4>
                <div class="otm-row">
                    <?php if ($oCour): ?><img class="otm-brand" src="<?php echo e($oCour['logo']); ?>" alt="" width="44" height="44"><?php else: ?><span class="otm-brand is-ph"><?php echo $ico('<path d="M1 4h14v12H1zM15 9h4l3 3v4h-7z"/>'); ?></span><?php endif; ?>
                    <div><b><?php echo e(($o['courier_name'] ?: ($oCour['name'] ?? '')) ?: '—'); ?></b><small><?php echo te('ບໍລິສັດຂົນສົ່ງ'); ?></small></div>
                </div>
                <div class="otm-doc<?php echo $oReceipt ? '' : ' is-empty'; ?>">
                    <?php if ($oReceipt): ?>
                        <button type="button" class="otm-doc-img" data-otm-zoom title="<?php echo te('ກົດເພື່ອຂະຫຍາຍ'); ?>"><img src="<?php echo e($oReceipt); ?>" alt="receipt" loading="lazy"></button>
                        <div class="otm-doc-info">
                            <small><?php echo te('ບິນຮັບເຄື່ອງ'); ?></small>
                            <span class="otm-pay is-ok"><?php echo te('ສົ່ງເຄື່ອງແລ້ວ'); ?></span>
                            <span class="otm-doc-time"><?php echo te('ແນບເມື່ອ'); ?> <?php echo e(date('d/m/Y H:i', strtotime((string)$o['receipt_at']))); ?></span>
                            <div class="otm-doc-btns">
                                <button type="button" class="otm-doc-btn" data-otm-zoom-src><?php echo $ico('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>', 15); ?> <?php echo te('ຂະຫຍາຍ'); ?></button>
                                <a class="otm-doc-btn is-dl" href="<?php echo e($oReceipt); ?>" download="<?php echo e('receipt-' . $o['order_no'] . '.jpg'); ?>"><?php echo $ico('<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>', 15); ?> <?php echo te('ດາວໂຫຼດ'); ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="otm-doc-ph"><?php echo $ico('<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h6M9 17h4"/>', 26); ?></span>
                        <div class="otm-doc-info"><small><?php echo te('ບິນຮັບເຄື່ອງ'); ?></small><span class="otm-doc-time"><?php echo te('ທີມງານຈະສົ່ງຮູບບິນຮັບເຄື່ອງ ເມື່ອຝາກເຄື່ອງກັບຂົນສົ່ງແລ້ວ'); ?></span></div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ການຊຳລະເງິນ + ສະລິບ -->
            <section class="otm-card otm-doc-card">
                <h4><?php echo $ico('<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>'); ?> <?php echo te('ການຊຳລະເງິນ'); ?></h4>
                <div class="otm-row">
                    <?php if (!empty($oBank['logo_url'])): ?><img class="otm-brand" src="<?php echo e($oBank['logo_url']); ?>" alt="" width="44" height="44"><?php else: ?><span class="otm-brand is-ph"><?php echo $ico('<path d="M3 10 12 4l9 6M5 10v8M19 10v8M9 10v8M15 10v8M3 20h18"/>'); ?></span><?php endif; ?>
                    <div><b><?php echo e($oBank['bank_name'] ?? preg_replace('/\s*\(.*$/u', '', (string)($o['bank_name'] ?? '')) ?: '—'); ?></b><small><?php echo te('ທະນາຄານທີ່ໂອນ'); ?></small></div>
                </div>
                <div class="otm-doc<?php echo $oSlip ? '' : ' is-empty'; ?>">
                    <?php if ($oSlip): ?>
                        <button type="button" class="otm-doc-img" data-otm-zoom title="<?php echo te('ກົດເພື່ອຂະຫຍາຍ'); ?>"><img src="<?php echo e($oSlip); ?>" alt="slip" loading="lazy"></button>
                        <div class="otm-doc-info">
                            <small><?php echo te('ສະລິບການໂອນ'); ?></small>
                            <span class="otm-pay <?php echo $oOk ? 'is-ok' : 'is-wait'; ?>"><?php echo $oOk ? te('ທີມງານກວດຍອດເງິນແລ້ວ') : te('ທີມງານກຳລັງກວດຍອດເງິນ'); ?></span>
                            <?php if (!empty($o['slip_uploaded_at'])): ?><span class="otm-doc-time"><?php echo te('ແນບເມື່ອ'); ?> <?php echo e(date('d/m/Y H:i', strtotime($o['slip_uploaded_at']))); ?></span><?php endif; ?>
                            <div class="otm-doc-btns">
                                <button type="button" class="otm-doc-btn" data-otm-zoom-src><?php echo $ico('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>', 15); ?> <?php echo te('ຂະຫຍາຍ'); ?></button>
                                <a class="otm-doc-btn is-dl" href="<?php echo e($oSlip); ?>" download="<?php echo e('slip-' . $o['order_no'] . '.jpg'); ?>"><?php echo $ico('<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>', 15); ?> <?php echo te('ດາວໂຫຼດ'); ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="otm-doc-ph"><?php echo $ico('<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>', 26); ?></span>
                        <div class="otm-doc-info"><small><?php echo te('ສະລິບການໂອນ'); ?></small><span class="otm-doc-time"><?php echo te('ຍັງບໍ່ແນບສະລິບ'); ?></span></div>
                    <?php endif; ?>
                </div>

                <?php if (!in_array($o['status'], ['completed', 'cancelled'], true) && (!$oSlip || $o['status'] === 'pending') && !empty($otBankList)): ?>
                    <?php /* ແນບ / ປ່ຽນ ສະລິບ → POST pages/my_orders.php action=slip */ ?>
                    <details class="otm-slip-form"<?php echo $oSlip ? '' : ' open'; ?>>
                        <summary><?php echo $oSlip ? te('ປ່ຽນຮູບສະລິບ') : te('ໂອນເງິນ ແລະ ແນບສະລິບ'); ?></summary>
                        <form method="post" action="<?php echo e(url('pages/my_orders.php')); ?>" enctype="multipart/form-data" novalidate data-order-form>
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="slip">
                            <input type="hidden" name="no" value="<?php echo e($o['order_no']); ?>">
                            <?php $banks = $otBankList; $bankSel = (int)($o['bank_account_id'] ?? 0); $slipRequired = true; $slipAmount = $oTotal; $afErr = fn(string $n) => '';
                            require __DIR__ . '/bank_slip_fields.php'; ?>
                            <button class="btn btn-primary btn-block" type="submit"><?php echo te('ສົ່ງສະລິບ'); ?></button>
                        </form>
                    </details>
                <?php endif; ?>
            </section>
        </div>

        <!-- ສິນຄ້າ -->
        <section class="otm-card otm-items">
            <h4><?php echo $ico('<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>'); ?> <?php echo te('ລາຍການສິນຄ້າ'); ?></h4>
            <table>
                <thead><tr><th><?php echo te('ສິນຄ້າ'); ?></th><th class="is-num"><?php echo te('ລາຄາ'); ?></th><th class="is-num"><?php echo te('ຈຳນວນ'); ?></th><th class="is-num"><?php echo te('ລວມ'); ?></th></tr></thead>
                <tbody>
                    <?php foreach ($o['items'] ?? [] as $it): $im = $otItemImgs[(int)$it['product_id']] ?? null; ?>
                        <tr>
                            <td><span class="otm-prod"><span class="otm-thumb"><?php if ($im): ?><img src="<?php echo e($im); ?>" alt="" loading="lazy"><?php endif; ?></span><b><?php echo e($it['product_name']); ?></b></span></td>
                            <td class="is-num"><?php echo (float)$it['price'] > 0 ? e(money((float)$it['price'])) : '—'; ?></td>
                            <td class="is-num"><?php echo (int)$it['qty']; ?></td>
                            <td class="is-num"><b><?php echo (float)$it['subtotal'] > 0 ? e(money((float)$it['subtotal'])) : te('ຕາມໃບສະເໜີລາຄາ'); ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="otm-total"><span><?php echo te('ຄ່າຂົນສົ່ງ'); ?>: <?php echo te('ຈ່າຍປາຍທາງ'); ?></span><span><?php echo te('ຍອດທີ່ຕ້ອງຊຳລະ'); ?> <b><?php echo $oTotal > 0 ? e(money($oTotal)) : te('ຕາມໃບສະເໜີລາຄາ'); ?></b></span></div>
        </section>
    </div>
</template>
