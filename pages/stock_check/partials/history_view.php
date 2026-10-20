<?php
/*
 * ສ່ວນ: ປະຫວັດການນັບສະຕັອກ (ລາຍສິນຄ້າ, ທຸກໃບ)
 * ສິນຄ້າໃດນັບແລ້ວ: ວັນເວລານັບ, ໃບ, ຈຳນວນໃນລະບົບ/ຕົວຈິງ, ຜົນ (ຄົບ/ບໍ່ຄົບ/ເກີນ), ສ່ວນຕ່າງ (ຈຳນວນສະຕັອກ), ຜູ້ນັບ.
 * ໃບທີ່ຍັງກຳລັງນັບ ບໍ່ສະແດງຈຳນວນໃນລະບົບ (ນັບແບບບໍ່ເຫັນ). ແບ່ງໜ້າ 50 ແຖວ; Excel/PDF/ພິມ ໄດ້ທຸກແຖວ.
 * ຖືກ require ຈາກ partials/list_view.php (ໃຊ້ $scPageRows, $scAllRows, $scHistory, $scHistoryLimit ຈາກ backend).
 */
$scHistMore = count($scHistory) > $scHistoryLimit;

// ແປງ 1 ແຖວປະຫວັດເປັນຄ່າທີ່ໃຊ້ທັງໜ້າຈໍ ແລະ ລາຍງານ
// hide = ພະນັກງານນັບ ເບິ່ງແຖວຂອງໃບທີ່ຍັງນັບຢູ່ → ບໍ່ສະແດງຜົນ ແລະ ສ່ວນຕ່າງ
$scHistFmt = function ($h) use ($scSeeResults) {
    $st = $h['status'];
    $isDone = ($st === 'completed' || $st === 'approved');
    $diff = (int)$h['counted_qty'] - (int)$h['system_qty'];
    return [
        'done'  => $isDone,
        'hide'  => !$isDone && !$scSeeResults,
        'diff'  => $diff,
        'when'  => $h['counted_at'] ? date('d/m/Y H:i', strtotime($h['counted_at'])) : '-',
        'res'   => $diff === 0 ? 'ຄົບ' : ($diff < 0 ? 'ບໍ່ຄົບ' : 'ເກີນ'),
        // ສ່ວນຕ່າງ = ຈຳນວນສະຕັອກ (ບໍ່ແມ່ນເງິນ) ພ້ອມຫົວໜ່ວຍ
        'qty'   => ($diff > 0 ? '+' : '') . number_format($diff) . (!empty($h['unit']) ? ' ' . $h['unit'] : ''),
    ];
};
?>
<?php require __DIR__ . '/filter_box.php'; ?>
      <div class="card sc-card">
        <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between flex-wrap" style="gap: 8px;">
          <h6 class="m-0 font-weight-bold sc-card-title">ປະຫວັດການນັບສະຕັອກ (ສິນຄ້າທີ່ນັບແລ້ວ)</h6>
<?php require __DIR__ . '/export_buttons.php'; ?>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-nowrap sc-table">
              <thead class="bg-light text-secondary">
                <tr>
                  <th class="text-center" style="width: 60px;">ລຳດັບ</th>
                  <th>ນັບເມື່ອ</th>
                  <th>ເລກທີໃບ</th>
                  <th>ສິນຄ້າ</th>
                  <th class="text-right">ໃນລະບົບ</th>
                  <th class="text-right">ຕົວຈິງ</th>
                  <th class="text-center">ຜົນການນັບ</th>
                  <th class="text-right">ສ່ວນຕ່າງ</th>
                  <th>ຜູ້ນັບ</th>
                  <th>ໝາຍເຫດ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($scPageRows)): ?>
                  <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                      <i class="fas fa-history fa-3x mb-3 text-secondary d-block"></i>
                      ບໍ່ມີປະຫວັດການນັບໃນຊ່ວງນີ້
                    </td>
                  </tr>
                <?php else: foreach ($scPageRows as $hIdx => $h): $f = $scHistFmt($h); ?>
                  <tr>
                    <td class="text-center text-muted"><?php echo $scPageOffset + $hIdx + 1; ?></td>
                    <td class="text-secondary"><?php echo $f['when']; ?></td>
                    <td>
                      <a href="stock_check.php?id=<?php echo (int)$h['check_id']; ?>" class="font-weight-bold"><?php echo htmlspecialchars($h['check_no']); ?></a>
                      <?php if (!$f['done']): ?><span class="badge sc-badge-draft ml-1">ກຳລັງນັບ</span><?php endif; ?>
                    </td>
                    <td>
                      <div class="font-weight-bold text-dark"><?php echo htmlspecialchars($h['product_name']); ?></div>
                      <div class="small text-muted"><?php echo htmlspecialchars($h['barcode'] ?: '-'); ?><?php echo $h['unit'] ? ' · ' . htmlspecialchars($h['unit']) : ''; ?></div>
                    </td>
                    <td class="text-right"><?php echo $f['done'] ? number_format((int)$h['system_qty']) : '<span class="text-muted">-</span>'; ?></td>
                    <td class="text-right font-weight-bold"><?php echo number_format((int)$h['counted_qty']); ?></td>
                    <?php if ($f['hide']): ?>
                    <td class="text-center text-muted">ລໍຖ້າກວດ</td>
                    <td class="text-right text-muted">-</td>
                    <?php else: ?>
                    <td class="text-center">
                      <span class="badge sc-res <?php echo $f['diff'] === 0 ? 'sc-res-match' : ($f['diff'] < 0 ? 'sc-res-short' : 'sc-res-over'); ?>">
                        <i class="fas <?php echo $f['diff'] === 0 ? 'fa-check' : ($f['diff'] < 0 ? 'fa-arrow-down' : 'fa-arrow-up'); ?> mr-1"></i><?php echo $f['res']; ?>
                      </span>
                    </td>
                    <td class="text-right font-weight-bold <?php echo $f['diff'] < 0 ? 'text-danger' : ($f['diff'] > 0 ? 'text-success' : 'text-muted'); ?>">
                      <?php echo htmlspecialchars($f['qty']); ?>
                    </td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($h['counted_by_name'] ?? '-'); ?></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($h['note'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
          <?php if ($scHistMore): ?>
            <div class="small text-muted text-center py-2">ສະແດງ <?php echo number_format($scHistoryLimit); ?> ລາຍການລ່າສຸດ — ກະລຸນາເລືອກຊ່ວງວັນທີ ຫຼື ຄົ້ນຫາສິນຄ້າໃຫ້ແຄບລົງ</div>
          <?php endif; ?>
        </div>
<?php require __DIR__ . '/pagination.php'; ?>
<?php $scExpPlace = 'bottom'; require __DIR__ . '/export_buttons.php'; /* ໂທລະສັບ: ປຸ່ມລຸ່ມສຸດ */ ?>
      </div>

      <script>
        // ຂໍ້ມູນລາຍງານ (ທຸກໜ້າ) ສຳລັບ Excel / PDF / ພິມ — export_js.php
        function scGetReport() {
          var rows = <?php echo json_encode(array_map(function ($h, $i) use ($scHistFmt) {
              $f = $scHistFmt($h);
              return [$i + 1, $f['when'], $h['check_no'] . ($f['done'] ? '' : ' (ກຳລັງນັບ)'), $h['product_name'], $h['barcode'] ?: '-',
                      $f['done'] ? number_format((int)$h['system_qty']) : '-', number_format((int)$h['counted_qty']),
                      $f['hide'] ? 'ລໍຖ້າກວດ' : $f['res'],
                      $f['hide'] ? '-' : $f['qty'], $h['counted_by_name'] ?? '-', $h['note'] ?? ''];
          }, $scAllRows, array_keys($scAllRows)), JSON_UNESCAPED_UNICODE); ?>;
          return {
            title: 'ລາຍງານປະຫວັດການນັບສະຕັອກ', fileBase: 'stock_count_history',
            meta: ['ວັນທີ <?php echo date('d/m/Y', strtotime($scDateFrom)); ?> - <?php echo date('d/m/Y', strtotime($scDateTo)); ?>',
                   <?php echo json_encode($scHistorySearch !== '' ? 'ຄົ້ນຫາ: ' . $scHistorySearch : '', JSON_UNESCAPED_UNICODE); ?>,
                   'ທັງໝົດ ' + rows.length + ' ລາຍການ'].filter(Boolean),
            headers: [{t: 'ລຳດັບ', a: 'c'}, {t: 'ນັບເມື່ອ'}, {t: 'ເລກທີໃບ'}, {t: 'ສິນຄ້າ'}, {t: 'ບາໂຄ້ດ'}, {t: 'ໃນລະບົບ', a: 'r'},
                      {t: 'ຕົວຈິງ', a: 'r'}, {t: 'ຜົນການນັບ', a: 'c'}, {t: 'ສ່ວນຕ່າງ', a: 'r'}, {t: 'ຜູ້ນັບ'}, {t: 'ໝາຍເຫດ'}],
            rows: rows, footer: null
          };
        }
      </script>
