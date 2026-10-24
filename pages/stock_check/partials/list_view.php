<?php
/*
 * ສ່ວນ: ລາຍການໃບເຊັກສະຕັອກ
 * ຫົວໜ້າ + ປຸ່ມເລີ່ມເຊັກສະຕັອກໃໝ່, ແຖບແຈ້ງໃບທີ່ກຳລັງນັບຄ້າງ, ກັ່ນຕອງຕາມວັນທີ ແລະ ຕາຕະລາງໃບທັງໝົດ.
 * ຖືກ require ຈາກ pages/stock_check/stock_check.php (ໃຊ້ $scChecks, $scOpenDraft, $scDateFrom, $scDateTo ຈາກ backend).
 */
?>
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h5 class="m-0 font-weight-bold text-dark sc-title">
            <i class="fas fa-history text-primary mr-2"></i> ປະຫວັດໃບເຊັກສະຕັອກ
          </h5>
          <small class="text-muted">ກວດເບິ່ງລາຍການ ແລະ ປະຫວັດການເຊັກສະຕັອກທີ່ຜ່ານມາ</small>
        </div>
        <div class="col-sm-6 text-right mt-2 mt-sm-0">
          <a href="stock_check.php" class="btn btn-primary sc-add-btn font-weight-bold shadow-sm">
            <i class="fas fa-barcode mr-1"></i> ໄປໜ້ານັບສະຕັອກ
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="content pb-5">
    <div class="container-fluid">

      <?php if ($scOpenDraft): ?>
      <div class="alert sc-draft-alert d-flex align-items-center justify-content-between flex-wrap mb-3">
        <div>
          <i class="fas fa-hourglass-half mr-2"></i>
          ມີໃບເຊັກສະຕັອກທີ່ກຳລັງນັບຄ້າງຢູ່: <b><?php echo htmlspecialchars($scOpenDraft['check_no']); ?></b>
          <span class="text-muted ml-1">(ເລີ່ມ <?php echo date('d/m/Y H:i', strtotime($scOpenDraft['created_at'])); ?>)</span>
        </div>
        <a href="stock_check.php?id=<?php echo (int)$scOpenDraft['id']; ?>" class="btn btn-sm btn-warning font-weight-bold mt-1 mt-sm-0">
          <i class="fas fa-arrow-right mr-1"></i> ນັບຕໍ່
        </a>
      </div>
      <?php endif; ?>

<?php require __DIR__ . '/tabs.php'; /* ໃບເຊັກສະຕັອກ | ນັບສະຕັອກ | ປະຫວັດການນັບ */ ?>

      <?php if ($scView === 'history'): ?>
<?php require __DIR__ . '/history_view.php'; ?>
      <?php else: ?>
      <div class="card sc-card rpt-card sys-card">
<?php require __DIR__ . '/filter_box.php'; ?>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table rpt-table mb-0 text-nowrap sc-table">
              <thead class="bg-light text-secondary">
                <tr>
                  <th class="text-center" style="width: 60px;">ລຳດັບ</th>
                  <th>ເລກທີ</th>
                  <th>ວັນທີເລີ່ມ</th>
                  <th>ຜູ້ກວດ</th>
                  <th class="text-center">ລາຍການທີ່ນັບ</th>
                  <th class="text-right">ບໍ່ຄົບ</th>
                  <th class="text-right">ເກີນ</th>
                  <th class="text-right">ສ່ວນຕ່າງ</th>
                  <th class="text-center">ສະຖານະ</th>
                  <th class="text-center" style="width: 110px;">ຈັດການ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($scChecks)): ?>
                  <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                      <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary d-block"></i>
                      <?php if (!empty($scSearch)): ?>
                        ບໍ່ພົບໃບເຊັກສະຕັອກທີ່ກົງກັບ "<b><?php echo htmlspecialchars($scSearch); ?></b>"
                      <?php else: ?>
                        ບໍ່ມີໃບເຊັກສະຕັອກໃນຊ່ວງວັນທີນີ້
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php else: $scIdx = $scPageOffset + 1; foreach ($scPageRows as $c):
                  $st = $c['status'];
                  $isDraft = ($st !== 'completed');
                  $diffVal = (int)$c['diff_qty'];   // ສ່ວນຕ່າງລວມ = ຈຳນວນສະຕັອກ (ເກີນ − ບໍ່ຄົບ)
                  $hideRes = !$scCanApprove || (($st === 'draft' || $st === 'pending') && !$scSeeResults);
                  $isSingleCounted = ((int)$c['counted_count'] === 1 && !empty($c['last_counted_name']));
                  $singleQuery = $isSingleCounted ? ($c['last_counted_barcode'] ?: $c['last_counted_name']) : '';
                  $checkUrl = 'stock_check.php?id=' . (int)$c['id'] . ($isSingleCounted ? '&q=' . urlencode($singleQuery) : '');
                ?>
                  <tr class="sc-check-row" data-url="<?php echo $checkUrl; ?>">
                    <td class="text-center text-muted font-weight-bold"><?php echo $scIdx++; ?></td>
                    <td class="font-weight-bold">
                      <a href="<?php echo $checkUrl; ?>"><?php echo htmlspecialchars($c['check_no']); ?></a>
                      <?php if ($isSingleCounted): ?>
                        <div class="small mt-1 text-success font-weight-bold">
                          <i class="fas fa-box-open mr-1"></i>ກຳລັງນັບ: <?php echo htmlspecialchars($c['last_counted_name']); ?>
                          <?php if ($c['last_counted_qty'] !== null): ?>
                            <span class="text-dark">(<?php echo number_format((int)$c['last_counted_qty']); ?>)</span>
                          <?php endif; ?>
                        </div>
                      <?php elseif ((int)$c['counted_count'] > 1): ?>
                        <div class="small mt-1 text-primary">
                          <i class="fas fa-layer-group mr-1"></i>ນັບແລ້ວ <?php echo number_format((int)$c['counted_count']); ?> ລາຍການ
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($c['note'])): ?>
                        <div class="small text-muted text-truncate" style="max-width: 220px;"><?php echo htmlspecialchars($c['note']); ?></div>
                      <?php endif; ?>
                      <?php if (!empty($c['matched_items']) && $scCanApprove): ?>
                        <div class="mt-2 p-2 rounded" style="background: #f0fdf4; border: 1px solid #bbf7d0; min-width: 260px; max-width: 440px;">
                          <div class="small font-weight-bold text-success mb-1">
                            <i class="fas fa-box-open mr-1"></i> ສິນຄ້າທີ່ກົງກັນ (<?php echo count($c['matched_items']); ?>):
                          </div>
                          <?php foreach ($c['matched_items'] as $mi):
                            $mDiff = ($mi['counted_qty'] !== null) ? ((int)$mi['counted_qty'] - (int)$mi['system_qty']) : null;
                            $mUrl = 'stock_check.php?id=' . (int)$c['id'] . '&q=' . urlencode($mi['barcode'] ?: $mi['product_name']);
                          ?>
                            <div class="small d-flex flex-wrap align-items-center justify-content-between text-dark py-1 border-top" style="gap: 6px; border-color: #dcfce7 !important;">
                              <div>
                                <a href="<?php echo $mUrl; ?>" class="font-weight-bold text-primary"><?php echo htmlspecialchars($mi['product_name']); ?></a>
                                <?php if (!empty($mi['barcode'])): ?>
                                  <span class="text-muted small ml-1">(<?php echo htmlspecialchars($mi['barcode']); ?>)</span>
                                <?php endif; ?>
                              </div>
                              <div class="text-right">
                                <span>ຕົວຈິງ: <b><?php echo $mi['counted_qty'] !== null ? number_format((int)$mi['counted_qty']) : '<span class="text-muted">ຍັງບໍ່ນັບ</span>'; ?></b></span>
                                <?php if ($mDiff !== null): ?>
                                  <span class="mx-1">|</span>
                                  <span class="font-weight-bold <?php echo $mDiff < 0 ? 'text-danger' : ($mDiff > 0 ? 'text-success' : 'text-muted'); ?>">
                                    <?php echo ($mDiff > 0 ? '+' : '') . number_format($mDiff) . (!empty($mi['unit']) ? ' ' . htmlspecialchars($mi['unit']) : ''); ?>
                                  </span>
                                <?php endif; ?>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="text-secondary"><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($c['created_by_name'] ?? '-'); ?></td>
                    <?php $scPct = (int)$c['item_count'] > 0 ? round((int)$c['counted_count'] * 100 / (int)$c['item_count']) : 0; ?>
                    <!-- ນັບໄປແລ້ວຈັກລາຍການ ຈາກສິນຄ້າທັງໝົດໃນໃບ + ແຖບຄວາມຄືບໜ້າ -->
                    <td class="text-center sc-progress-cell">
                      <div><b><?php echo number_format((int)$c['counted_count']); ?></b> ລາຍການ <span class="text-muted small">ຈາກ <?php echo number_format((int)$c['item_count']); ?></span></div>
                      <div class="sc-progress"><div class="sc-progress-bar" style="width: <?php echo $scPct; ?>%;"></div></div>
                    </td>
                    <?php if ($hideRes): ?>
                    <td class="text-right text-muted">-</td><td class="text-right text-muted">-</td><td class="text-right text-muted">-</td>
                    <?php else: ?>
                    <td class="text-right <?php echo $c['short_qty'] > 0 ? 'text-danger font-weight-bold' : 'text-muted'; ?>">
                      <?php echo $c['short_qty'] > 0 ? '-' . number_format((int)$c['short_qty']) : '0'; ?>
                    </td>
                    <td class="text-right <?php echo $c['over_qty'] > 0 ? 'text-success font-weight-bold' : 'text-muted'; ?>">
                      <?php echo $c['over_qty'] > 0 ? '+' . number_format((int)$c['over_qty']) : '0'; ?>
                    </td>
                    <td class="text-right font-weight-bold <?php echo $diffVal < 0 ? 'text-danger' : ($diffVal > 0 ? 'text-success' : 'text-muted'); ?>">
                      <?php echo ($diffVal > 0 ? '+' : '') . number_format($diffVal); ?>
                    </td>
                    <?php endif; ?>
                    <td class="text-center">
                      <?php if ($st === 'draft'): ?>
                        <span class="badge sc-badge-draft">ກຳລັງນັບ</span>
                      <?php elseif ($st === 'pending'): ?>
                        <span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i>ລໍຖ້າຫົວໜ້າກວດສອບ</span>
                      <?php elseif ($st === 'approved'): ?>
                        <span class="badge badge-info px-2 py-1"><i class="fas fa-user-check mr-1"></i>ຫົວໜ້າກວດສອບແລ້ວ</span>
                      <?php else: ?>
                        <span class="badge sc-badge-done"><i class="fas fa-check-double mr-1"></i>ປັບສະຕັອກແລ້ວ</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <a href="<?php echo $checkUrl; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo $isSingleCounted ? 'ແກ້ໄຂ ' . htmlspecialchars($c['last_counted_name']) : ($isDraft ? 'ແກ້ໄຂ / ນັບຕໍ່' : 'ເບິ່ງລາຍການ'); ?>">
                        <i class="fas <?php echo $isDraft ? 'fa-edit' : 'fa-eye'; ?>"></i>
                      </a>
                      <?php if ($isDraft && $scCanDelete): ?>
                      <button type="button" class="btn btn-sm btn-outline-danger" title="ລຶບ"
                              onclick="scDeleteCheck(<?php echo (int)$c['id']; ?>, <?php echo htmlspecialchars(json_encode($c['check_no']), ENT_QUOTES); ?>)">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
<?php require __DIR__ . '/pagination.php'; ?>
<?php $scExpPlace = 'bottom'; require __DIR__ . '/export_buttons.php'; /* ໂທລະສັບ: ປຸ່ມລຸ່ມສຸດ */ ?>
      </div>

      <script>
        // ຂໍ້ມູນລາຍງານ (ທຸກໜ້າ) ສຳລັບ Excel / PDF / ພິມ — export_js.php
        function scGetReport() {
          var rows = <?php echo json_encode(array_map(function ($c, $i) use ($scSeeResults) {
              $st = $c['status'];
              $dv = (int)$c['diff_qty'];
              $hide = ($st === 'draft' || $st === 'pending') && !$scSeeResults;
              $stLabel = $st === 'draft' ? 'ກຳລັງນັບ' : ($st === 'pending' ? 'ລໍຖ້າອະນຸມັດ' : ($st === 'approved' ? 'ອະນຸມັດແລ້ວ' : 'ນັບແລ້ວ'));
              return [$i + 1, $c['check_no'], date('d/m/Y H:i', strtotime($c['created_at'])), $c['created_by_name'] ?? '-',
                      number_format((int)$c['counted_count']) . ' ລາຍການ (ຈາກ ' . number_format((int)$c['item_count']) . ')',
                      $hide ? '-' : ($c['short_qty'] > 0 ? '-' . number_format((int)$c['short_qty']) : '0'),
                      $hide ? '-' : ($c['over_qty'] > 0 ? '+' . number_format((int)$c['over_qty']) : '0'),
                      $hide ? '-' : ($dv > 0 ? '+' : '') . number_format($dv),
                      $stLabel, $c['note'] ?? ''];
          }, $scChecks, array_keys($scChecks)), JSON_UNESCAPED_UNICODE); ?>;
          return {
            title: 'ລາຍງານໃບເຊັກສະຕັອກ', fileBase: 'stock_checks',
            meta: [
              'ວັນທີ <?php echo date('d/m/Y', strtotime($scDateFrom)); ?> - <?php echo date('d/m/Y', strtotime($scDateTo)); ?>',
              <?php if (!empty($scSearch)): ?>'ຄົ້ນຫາ: ' + <?php echo json_encode($scSearch, JSON_UNESCAPED_UNICODE); ?>,<?php endif; ?>
              'ທັງໝົດ ' + rows.length + ' ໃບ'
            ],
            headers: [{t: 'ລຳດັບ', a: 'c'}, {t: 'ເລກທີ'}, {t: 'ວັນທີເລີ່ມ'}, {t: 'ຜູ້ກວດ'}, {t: 'ລາຍການທີ່ນັບ', a: 'c'},
                      {t: 'ບໍ່ຄົບ', a: 'r'}, {t: 'ເກີນ', a: 'r'}, {t: 'ສ່ວນຕ່າງ', a: 'r'}, {t: 'ສະຖານະ', a: 'c'}, {t: 'ໝາຍເຫດ'}],
            rows: rows, footer: null
          };
        }
      </script>
      <?php endif; ?>

    </div>
  </section>

  <script>
    // ຄລິກແຖວໃບເຊັກສະຕັອກ → ເປີດໜ້ານັບໃບນັ້ນທັນທີ
    $(document).on('click', '.sc-check-row', function(e) {
      if ($(e.target).closest('a, button, input, .badge').length) return;
      var url = $(this).attr('data-url');
      if (url) window.location.href = url;
    });

    // ໄປໜ້ານັບສະຕັອກທັນທີ
    function scStartNew() {
      window.location.href = 'stock_check.php';
    }

    function scDeleteCheck(id, checkNo) {
      Swal.fire({
        icon: 'warning',
        title: 'ລຶບໃບເຊັກສະຕັອກ?',
        text: 'ລຶບໃບ ' + checkNo + ' ແລະ ຈຳນວນທີ່ນັບໄວ້ທັງໝົດ (ສະຕັອກບໍ່ຖືກປ່ຽນ)',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> ລຶບເລີຍ',
        cancelButtonText: 'ຍົກເລີກ',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b'
      }).then(function(r) {
        if (!r.isConfirmed) return;
        $.post('../../api/stock_check_backend.php', { action: 'delete_check', check_id: id }, function(res) {
          if (res && res.success) {
            Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false })
              .then(function() { window.location.reload(); });
          } else {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: (res && res.message) || 'ລຶບບໍ່ສຳເລັດ' });
          }
        }, 'json').fail(function() {
          Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ເຊື່ອມຕໍ່ເຊີບເວີບໍ່ໄດ້' });
        });
      });
    }
  </script>
