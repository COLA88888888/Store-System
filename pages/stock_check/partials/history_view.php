<?php
/*
 * ສ່ວນ: ປະຫວັດການນັບສະຕັອກ (ລາຍສິນຄ້າ, ທຸກໃບ)
 * ສິນຄ້າໃດນັບແລ້ວ: ວັນເວລານັບ, ໃບ, ຈຳນວນໃນລະບົບ/ຕົວຈິງ, ຜົນ (ຄົບ/ບໍ່ຄົບ/ເກີນ), ສ່ວນຕ່າງ (ຈຳນວນສະຕັອກ), ຜູ້ນັບ.
 * ສະແດງສະເພາະໃບທີ່ຫົວໜ້າອະນຸມັດແລ້ວ (backend ກັ່ນຕອງ). ໃບທີ່ປັບສະຕັອກແລ້ວ: ລາຍການທີ່ເກີນ/ບໍ່ພໍ
 * ຖືກແກ້ໃຫ້ຕົງແລ້ວ → ຜົນເປັນ "ຄົບ (ປັບແລ້ວ)" ພ້ອມບອກວ່າເດີມເກີນ/ຂາດເທົ່າໃດ (ສ່ວນຕ່າງ = ຈຳນວນທີ່ປັບ).
 * ແບ່ງໜ້າ; Excel/PDF/ພິມ ໄດ້ທຸກແຖວ.
 * ຖືກ require ຈາກ partials/list_view.php (ໃຊ້ $scPageRows, $scAllRows, $scHistory, $scHistoryLimit ຈາກ backend).
 */
$scHistMore = count($scHistory) > $scHistoryLimit;

// ແປງ 1 ແຖວປະຫວັດເປັນຄ່າທີ່ໃຊ້ທັງໜ້າຈໍ ແລະ ລາຍງານ
// ພະນັກງານ ບໍ່ໃຫ້ເຫັນຈຳນວນໃນລະບົບ ແລະ ສ່ວນຕ່າງ
$scHistFmt = function ($h) use ($scShowSystem, $scShowDiff) {
    $st = $h['status'];
    $isDone = ($st === 'completed');
    $diff = (int)$h['counted_qty'] - (int)$h['system_qty'];
    $fixed = $isDone && $diff !== 0;   // ປັບສະຕັອກແລ້ວ → ຕອນນີ້ຄົບ
    $was   = $diff < 0 ? t('sc.s180') . ' ' . number_format($diff) : t('sc.s181') . number_format($diff);
    return [
        'done'    => $isDone,
        'fixed'   => $fixed,
        'hide'    => !$scShowDiff,
        'showSys' => $scShowSystem,
        'diff'    => $diff,
        'when'    => $h['counted_at'] ? date('d/m/Y H:i', strtotime($h['counted_at'])) : '-',
        'res'     => ($diff === 0 || $fixed) ? t('sc.s083') : ($diff < 0 ? t('sc.s159') : t('sc.s085')),
        'resNote' => $fixed ? t('sc.s182') . ' ' . $was : '',
        // ສ່ວນຕ່າງ = ຈຳນວນສະຕັອກ (ບໍ່ແມ່ນເງິນ) ພ້ອມຫົວໜ່ວຍ
        'qty'     => ($diff > 0 ? '+' : '') . number_format($diff) . (!empty($h['unit']) ? ' ' . $h['unit'] : ''),
    ];
};
?>
      <div class="card sc-card rpt-card sys-card">
<?php require __DIR__ . '/filter_box.php'; ?>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table rpt-table mb-0 text-nowrap sc-table">
              <thead class="bg-light text-secondary">
                <tr>
                  <th class="text-center" style="width: 60px;"><?php echo $sc('sc.s137'); ?></th>
                  <th><?php echo $sc('sc.s183'); ?></th>
                  <th><?php echo $sc('sc.s184'); ?></th>
                  <th><?php echo $sc('sc.s138'); ?></th>
                  <th class="text-right"><?php echo $sc('sc.s062'); ?></th>
                  <th class="text-right"><?php echo $sc('sc.s140'); ?></th>
                  <th class="text-center"><?php echo $sc('sc.s185'); ?></th>
                  <th class="text-right"><?php echo $sc('sc.s066'); ?></th>
                  <th><?php echo $sc('sc.s186'); ?></th>
                  <th><?php echo $sc('sc.s068'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($scPageRows)): ?>
                  <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                      <i class="fas fa-history fa-3x mb-3 text-secondary d-block"></i>
                      <?php echo $sc('sc.s187'); ?>
                    </td>
                  </tr>
                <?php else: foreach ($scPageRows as $hIdx => $h): $f = $scHistFmt($h); ?>
                  <tr>
                    <td class="text-center text-muted"><?php echo $scPageOffset + $hIdx + 1; ?></td>
                    <td class="text-secondary"><?php echo $f['when']; ?></td>
                    <td>
                      <a href="stock_check.php?id=<?php echo (int)$h['check_id']; ?>" class="font-weight-bold"><?php echo htmlspecialchars($h['check_no']); ?></a>
                      <?php if ($h['status'] === 'completed'): ?>
                        <span class="badge badge-success ml-1"><?php echo $sc('sc.s008'); ?></span>
                      <?php elseif ($h['status'] === 'approved'): ?>
                        <span class="badge badge-info ml-1"><?php echo $sc('sc.s172'); ?></span>
                      <?php elseif ($h['status'] === 'pending'): ?>
                        <span class="badge badge-warning ml-1"><?php echo $sc('sc.s188'); ?></span>
                      <?php else: ?>
                        <span class="badge badge-secondary ml-1"><?php echo $sc('sc.s005'); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="font-weight-bold text-dark"><?php echo htmlspecialchars($h['product_name']); ?></div>
                      <div class="small text-muted">
                        <?php echo htmlspecialchars($h['barcode'] ?: '-'); ?><?php echo $h['unit'] ? ' · ' . htmlspecialchars($h['unit']) : ''; ?>
                        <?php
                          $hZone = trim((string)($h['zone'] ?? ''));
                          $hShelf = trim((string)($h['shelf'] ?? ''));
                          $hHasLoc = ($hZone !== '' || $hShelf !== '');
                          $hZd = $hZone !== '' ? ((mb_stripos($hZone, 'ໂຊນ') === 0) ? $hZone : (t('sc.s045') . ' ' . $hZone)) : t('sc.s046');
                          $hSd = $hShelf !== '' ? ((mb_stripos($hShelf, 'ຊັ້ນ') === 0) ? $hShelf : (t('sc.s136') . ' ' . $hShelf)) : t('sc.s223');
                        ?>
                        · <i class="fas fa-map-marker-alt <?php echo $hHasLoc ? 'text-primary' : 'text-secondary'; ?>"></i>
                        <span class="<?php echo $hHasLoc ? 'text-secondary' : 'text-muted'; ?>">
                          <?php echo htmlspecialchars($hZd . ' · ' . $hSd); ?>
                        </span>
                      </div>
                    </td>
                    <td class="text-right"><?php echo $f['showSys'] ? number_format((int)$h['system_qty']) : '<span class="text-muted">-</span>'; ?></td>
                    <td class="text-right font-weight-bold"><?php echo number_format((int)$h['counted_qty']); ?></td>
                    <?php if ($f['hide']): ?>
                    <td class="text-center"><span class="badge sc-res sc-res-match"><i class="fas fa-check mr-1"></i><?php echo $sc('sc.s039'); ?></span></td>
                    <td class="text-right text-muted">-</td>
                    <?php else: ?>
                    <td class="text-center">
                      <?php $isOk = ($f['diff'] === 0 || $f['fixed']); ?>
                      <span class="badge sc-res <?php echo $isOk ? 'sc-res-match' : ($f['diff'] < 0 ? 'sc-res-short' : 'sc-res-over'); ?>">
                        <i class="fas <?php echo $isOk ? 'fa-check' : ($f['diff'] < 0 ? 'fa-arrow-down' : 'fa-arrow-up'); ?> mr-1"></i><?php echo $f['res']; ?>
                      </span>
                      <?php if ($f['resNote'] !== ''): ?><div class="small text-muted mt-1"><?php echo htmlspecialchars($f['resNote']); ?></div><?php endif; ?>
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
            <div class="small text-muted text-center py-2"><?php echo $sc('sc.s074'); ?> <?php echo number_format($scHistoryLimit); ?> <?php echo $sc('sc.s189'); ?></div>
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
              $stLabel = ($h['status'] === 'completed') ? ' ' . t('sc.s190') : (($h['status'] === 'draft') ? ' ' . t('sc.s191') : ' ' . t('sc.s192'));
              return [$i + 1, $f['when'], $h['check_no'] . $stLabel, $h['product_name'], $h['barcode'] ?: '-',
                      $f['showSys'] ? number_format((int)$h['system_qty']) : '-', number_format((int)$h['counted_qty']),
                      $f['hide'] ? t('sc.s039') : ($f['res'] . ($f['resNote'] !== '' ? ' (' . $f['resNote'] . ')' : '')),
                      $f['hide'] ? '-' : $f['qty'], $h['counted_by_name'] ?? '-', $h['note'] ?? ''];
          }, $scAllRows, array_keys($scAllRows)), JSON_UNESCAPED_UNICODE); ?>;
          return {
            title: scT('sc.s193'), fileBase: 'stock_count_history',
            meta: [scT('sc.s175') + ' <?php echo date('d/m/Y', strtotime($scDateFrom)); ?> - <?php echo date('d/m/Y', strtotime($scDateTo)); ?>',
                   <?php echo json_encode($scHistorySearch !== '' ? t('sc.s176') . ' ' . $scHistorySearch : '', JSON_UNESCAPED_UNICODE); ?>,
                   scT('sc.s047') + ' ' + rows.length + ' ' + scT('sc.s075')].filter(Boolean),
            headers: [{t: scT('sc.s137'), a: 'c'}, {t: scT('sc.s183')}, {t: scT('sc.s184')}, {t: scT('sc.s138')}, {t: scT('sc.s058')}, {t: scT('sc.s062'), a: 'r'},
                      {t: scT('sc.s140'), a: 'r'}, {t: scT('sc.s185'), a: 'c'}, {t: scT('sc.s066'), a: 'r'}, {t: scT('sc.s186')}, {t: scT('sc.s068')}],
            rows: rows, footer: null
          };
        }
      </script>
