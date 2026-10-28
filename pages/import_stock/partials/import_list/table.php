<?php
/*
 * ສ່ວນ: ຕາຕະລາງລາຍການນຳເຂົ້າ
 * ສະແດງຕາຕະລາງແບບບິນ ຫຼື ແບບລາຍລະອຽດ ພ້ອມປຸ່ມແກ້ໄຂ/ລົບ/ພິມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/import_stock/import_list.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>          <!-- TABLE RENDER: VIEW TYPE == 'bill' OR 'detail' -->
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle text-nowrap">
              
              <?php if ($view_type === 'bill'): ?>
                <!-- HEADER FOR MODE 1: ຕາມບິນ -->
                <thead class="bg-light text-secondary" style="font-size: 0.88rem; text-transform: uppercase;">
                  <tr>
                    <th class="py-3 text-center" style="width: 50px;"><?php echo htmlspecialchars(t('import_list.col_no', 'ລຳດັບ')); ?></th>
                    <th class="py-3 text-center"><?php echo htmlspecialchars(t('import_list.col_import_date', 'ວັນທີຮັບເຂົ້າ')); ?></th>
                    <th class="py-3 text-center"><?php echo htmlspecialchars(t('import_list.col_invoice_no', 'ເລກທີໃບບິນ')); ?></th>
                    <th class="py-3"><?php echo htmlspecialchars(t('import_list.col_store', 'ສາຂາ')); ?></th>
                    <th class="py-3"><?php echo htmlspecialchars(t('import_list.col_supplier', 'ຜູ້ສະໜອງ')); ?></th>
                    <th class="py-3 text-center"><?php echo htmlspecialchars(t('import_list.col_item_count', 'ຈຳນວນລາຍການ')); ?></th>
                    <th class="py-3 text-right"><?php echo htmlspecialchars(t('import_list.col_total_cost', 'ມູນຄ່າທຶນລວມ')); ?></th>
                    <th class="py-3"><?php echo htmlspecialchars(t('import_list.col_creator', 'ຜູ້ບັນທຶກ')); ?></th>
                    <th class="py-3"><?php echo htmlspecialchars(t('import_list.col_notes', 'ໝາຍເຫດ')); ?></th>
                    <th class="py-3 text-center" style="width: 120px;"><?php echo htmlspecialchars(t('import_list.col_actions', 'ຈັດການ')); ?></th>
                  </tr>
                </thead>
                <tbody id="historyTableBody">
                  <?php if (empty($importList)): ?>
                    <tr>
                      <td colspan="10" class="text-center text-muted py-5">
                        <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
                        <?php echo htmlspecialchars(t('import_list.empty_bills', 'ບໍ່ມີລາຍການໃບບິນຮັບເຂົ້າໃນຊ່ວງວັນທີນີ້')); ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php $idx = 1; foreach ($importList as $row): ?>
                      <?php 
                        $importDate = !empty($row['import_date']) ? date('d/m/Y', strtotime($row['import_date'])) : '-';
                        $creatorName = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
                        if (empty($creatorName)) $creatorName = $row['username'] ?? 'Admin';

                        $searchStr = strtolower(($row['invoice_number'] ?? '') . ' ' . ($row['supplier_name'] ?? '') . ' ' . ($row['store_name'] ?? '') . ' ' . ($row['notes'] ?? '') . ' ' . $creatorName);
                        $hasMov = !empty($row['has_movement']);
                        // Stock transferred out to another branch: a negative ledger row — red, minus.
                        $isTrfOut = (float)($row['total_cost'] ?? 0) < 0;
                      ?>
                      <tr class="import-row<?php echo $isTrfOut ? ' text-danger' : ''; ?>" data-search="<?php echo htmlspecialchars($searchStr); ?>" data-cost="<?php echo (float)($row['total_cost'] ?? 0); ?>">
                        <td class="align-middle text-center text-muted font-weight-bold row-index"><?php echo $idx++; ?></td>
                        
                        <td class="align-middle text-center text-secondary font-weight-bold" style="font-size: 0.9rem;">
                          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo $importDate; ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold">
                          <span class="invoice-badge"><?php echo htmlspecialchars($row['invoice_number']); ?></span>
                        </td>

                        <td class="align-middle font-weight-bold">
                          <span class="badge badge-light border text-primary px-2 py-1" style="font-size: 0.82rem;">
                            <i class="fas fa-store mr-1 text-primary"></i><?php echo htmlspecialchars($row['store_name'] ?: t('import_list.main_store_default', 'ສາຂາຫຼັກ')); ?>
                          </span>
                        </td>

                        <td class="align-middle font-weight-bold <?php echo $isTrfOut ? 'text-danger' : 'text-dark'; ?>">
                          <?php if (!empty($row['supplier_name'])): ?>
                            <i class="fas <?php echo $isTrfOut ? 'fa-exchange-alt text-danger' : 'fa-building text-secondary'; ?> mr-1"></i> <?php echo htmlspecialchars($row['supplier_name']); ?>
                          <?php else: ?>
                            <span class="text-muted font-weight-normal">-</span>
                          <?php endif; ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold text-info">
                          <span class="badge badge-info px-2 py-1" style="font-size: 0.88rem;">
                            <?php echo number_format($row['item_count']); ?> <?php echo htmlspecialchars(t('import_list.unit_items', 'ລາຍການ')); ?>
                          </span>
                        </td>

                        <td class="align-middle text-right font-weight-bold <?php echo $isTrfOut ? 'text-danger' : 'text-success'; ?>" style="font-size: 1rem;">
                          <?php echo displayMoney($pdo, $row['total_cost']); ?>
                        </td>

                        <td class="align-middle text-secondary font-weight-bold" style="font-size: 0.88rem;">
                          <i class="fas fa-user-circle mr-1 text-muted"></i> <?php echo htmlspecialchars($creatorName); ?>
                        </td>

                        <td class="align-middle text-muted" style="font-size: 0.88rem;">
                          <?php echo !empty($row['notes']) ? htmlspecialchars($row['notes']) : '-'; ?>
                        </td>

                        <td class="text-center align-middle">
                          <?php if ($isTrfOut): ?>
                            <span class="badge badge-danger px-2 py-1" style="font-size: 0.8rem;"><i class="fas fa-exchange-alt mr-1"></i><?php echo htmlspecialchars(t('import_list.transfer_out_badge', 'ໂອນອອກ')); ?></span>
                          <?php else: ?>
                          <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-info btn-view-bill" title="<?php echo htmlspecialchars(t('import_list.view_bill_title', 'ເບິ່ງລາຍລະອຽດສິນຄ້າໃນບິນ')); ?>" data-id="<?php echo $row['import_id']; ?>" data-invoice="<?php echo htmlspecialchars($row['invoice_number']); ?>" data-supplier="<?php echo htmlspecialchars($row['supplier_name'] ?? ''); ?>" data-date="<?php echo $importDate; ?>" data-cost="<?php echo floatval($row['total_cost']); ?>">
                              <i class="fas fa-eye"></i>
                            </button>

                            <a href="print_import.php?import_id=<?php echo $row['import_id']; ?>" target="_blank" class="btn btn-outline-primary" title="<?php echo htmlspecialchars(t('import_list.print_bill_title', 'ພິມໃບບິນ')); ?>">
                              <i class="fas fa-print"></i>
                            </a>

                            <?php if (hasPermission('import_list', 'del') || hasPermission('import_stock', 'del')): ?>
                              <?php if (!$hasMov): ?>
                                <button type="button" class="btn btn-outline-danger btn-delete-master-bill" title="<?php echo htmlspecialchars(t('import_list.delete_bill_title', 'ລົບບິນນີ້')); ?>" data-id="<?php echo $row['import_id']; ?>" data-invoice="<?php echo htmlspecialchars($row['invoice_number']); ?>">
                                  <i class="fas fa-trash-alt"></i>
                                </button>
                              <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary disabled" title="<?php echo htmlspecialchars(t('import_list.cannot_delete_movement_title', 'ບໍ່ສາມາດລົບໄດ້ ເນື່ອງຈາກສິນຄ້າມີການເຄື່ອນໄຫວແລ້ວ')); ?>">
                                  <i class="fas fa-lock"></i>
                                </button>
                              <?php endif; ?>
                            <?php endif; ?>
                          </div>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <?php if (!empty($importList)): ?>
                <tfoot>
                  <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #cbd5e1;">
                    <td colspan="6" class="text-right"><?php echo htmlspecialchars(t('import_list.grand_total', 'ລວມທັງໝົດ:')); ?></td>
                    <td class="text-right text-success" style="font-size:1rem;" id="importGrandTotal"><?php echo displayMoney($pdo, $grandTotalCost); ?></td>
                    <td colspan="3"></td>
                  </tr>
                </tfoot>
                <?php endif; ?>

              <?php else: ?>
                <!-- HEADER FOR MODE 2: ລາຍລະອຽດ -->
                <thead class="bg-light text-secondary" style="font-size: 0.88rem; text-transform: uppercase;">
                  <tr>
                    <th class="py-3 text-center" style="width: 50px;"><?php echo htmlspecialchars(t('import_list.col_no', 'ລຳດັບ')); ?></th>
                    <th class="py-3 text-center"><?php echo htmlspecialchars(t('import_list.col_import_date', 'ວັນທີຮັບເຂົ້າ')); ?></th>
                    <th class="py-3 text-center"><?php echo htmlspecialchars(t('import_list.col_invoice_no', 'ເລກທີໃບບິນ')); ?></th>
                    <th class="py-3"><?php echo htmlspecialchars(t('import_list.col_store', 'ສາຂາ')); ?></th>
                    <th class="py-3"><?php echo htmlspecialchars(t('import_list.col_product_name', 'ຊື່ສິນຄ້າ')); ?></th>
                    <th class="py-3 text-center"><?php echo htmlspecialchars(t('import_list.col_received_qty', 'ຈຳນວນຮັບເຂົ້າ')); ?></th>
                    <th class="py-3 text-center"><?php echo htmlspecialchars(t('import_list.col_total_base_qty', 'ຈຳນວນຍ່ອຍລວມ')); ?></th>
                    <th class="py-3 text-right"><?php echo htmlspecialchars(t('import_list.col_total_cost', 'ມູນຄ່າທຶນລວມ')); ?></th>
                    <th class="py-3 text-center"><?php echo htmlspecialchars(t('import_list.col_expiry_date', 'ວັນໝົດອາຍຸ')); ?></th>
                    <th class="py-3"><?php echo htmlspecialchars(t('import_list.col_creator', 'ຜູ້ບັນທຶກ')); ?></th>
                    <th class="py-3 text-center" style="width: 150px;"><?php echo htmlspecialchars(t('import_list.col_actions', 'ຈັດການ')); ?></th>
                  </tr>
                </thead>
                <tbody id="historyTableBody">
                  <?php if (empty($importList)): ?>
                    <tr>
                      <td colspan="11" class="text-center text-muted py-5">
                        <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
                        <?php echo htmlspecialchars(t('import_list.empty_details', 'ບໍ່ມີລາຍການສິນຄ້າຮັບເຂົ້າໃນຊ່ວງວັນທີນີ້')); ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php $idx = 1; foreach ($importList as $row): ?>
                      <?php 
                        $importDate = !empty($row['import_date']) ? date('d/m/Y', strtotime($row['import_date'])) : '-';
                        
                        // Expiry badge evaluation
                        $expBadge = '<span class="text-muted">-</span>';
                        if (!empty($row['expiry_date'])) {
                            $expTime = strtotime($row['expiry_date']);
                            $nowTime = time();
                            $daysDiff = floor(($expTime - $nowTime) / (60 * 60 * 24));
                            $formattedExp = date('d/m/Y', $expTime);

                            if ($daysDiff < 0) {
                                $expBadge = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> ' . htmlspecialchars(t('import_list.expired_label', 'ໝົດອາຍຸ')) . ' (' . $formattedExp . ')</span>';
                            } elseif ($daysDiff <= 30) {
                                $expBadge = '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i> ' . htmlspecialchars(t('import_list.near_expiry_label', 'ໃກ້ໝົດອາຍຸ')) . ' (' . $formattedExp . ')</span>';
                            } else {
                                $expBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> ' . $formattedExp . '</span>';
                            }
                        }

                        $creatorName = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
                        if (empty($creatorName)) $creatorName = $row['username'] ?? 'Admin';

                        $searchStr = strtolower(($row['invoice_number'] ?? '') . ' ' . ($row['product_name'] ?? '') . ' ' . ($row['supplier_name'] ?? '') . ' ' . ($row['store_name'] ?? '') . ' ' . $creatorName);

                        // Stock movement check
                        $batchRem     = isset($row['batch_rem_qty']) ? intval($row['batch_rem_qty']) : null;
                        $batchInit    = isset($row['batch_initial_qty']) ? intval($row['batch_initial_qty']) : null;
                        $productQty   = intval($row['current_product_qty']);
                        $totalBaseQty = intval($row['total_base_qty']);

                        $hasMovement = false;
                        $isTrfOut = !empty($row['is_transfer_out']);
                        if ($batchRem !== null && $batchInit !== null && $batchRem < $batchInit) {
                            $hasMovement = true;
                        } elseif ($productQty < $totalBaseQty) {
                            $hasMovement = true;
                        }
                      ?>
                      <tr class="import-row<?php echo $isTrfOut ? ' text-danger' : ''; ?>" data-search="<?php echo htmlspecialchars($searchStr); ?>" data-cost="<?php echo (float)($row['total_cost'] ?? 0); ?>">
                        <td class="align-middle text-center text-muted font-weight-bold row-index"><?php echo $idx++; ?></td>
                        
                        <td class="align-middle text-center text-secondary font-weight-bold" style="font-size: 0.88rem;">
                          <?php echo $importDate; ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold">
                          <span class="invoice-badge"><?php echo htmlspecialchars($row['invoice_number']); ?></span>
                        </td>

                        <td class="align-middle font-weight-bold">
                          <span class="badge badge-light border text-primary px-2 py-1" style="font-size: 0.82rem;">
                            <i class="fas fa-store mr-1 text-primary"></i><?php echo htmlspecialchars($row['store_name'] ?: t('import_list.main_store_default', 'ສາຂາຫຼັກ')); ?>
                          </span>
                        </td>

                        <td class="align-middle font-weight-bold <?php echo $isTrfOut ? 'text-danger' : 'text-dark'; ?>">
                          <?php echo htmlspecialchars($row['product_name'] ?? '-'); /* ສິນຄ້າຖືກລຶບອອກຈາກລະບົບແລ້ວ → '-' (ຄືກັບ Excel/PDF) */ ?>
                          <?php if (!empty($row['supplier_name'])): ?>
                            <small class="text-muted font-weight-normal ml-2"><i class="fas fa-store mr-1"></i><?php echo htmlspecialchars($row['supplier_name']); ?></small>
                          <?php endif; ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold <?php echo $isTrfOut ? 'text-danger' : 'text-dark'; ?>">
                          <?php echo number_format($row['quantity']); ?> <?php echo htmlspecialchars($row['unit_name']); ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold <?php echo $isTrfOut ? 'text-danger' : 'text-info'; ?>">
                          <?php echo number_format($row['total_base_qty']); ?> <?php echo htmlspecialchars($row['base_unit'] ?: t('import_list.default_unit', 'ອັນ')); ?>
                        </td>

                        <td class="align-middle text-right font-weight-bold <?php echo $isTrfOut ? 'text-danger' : 'text-primary'; ?>">
                          <?php echo displayMoney($pdo, $row['total_cost']); ?>
                        </td>

                        <td class="align-middle text-center">
                          <?php echo $expBadge; ?>
                        </td>

                        <td class="align-middle text-secondary font-weight-bold" style="font-size: 0.88rem;">
                          <i class="fas fa-user-circle mr-1 text-muted"></i> <?php echo htmlspecialchars($creatorName); ?>
                        </td>

                        <td class="text-center align-middle">
                          <?php $rowJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>
                          <?php if ($isTrfOut): ?>
                            <span class="badge badge-danger px-2 py-1" style="font-size: 0.8rem;"><i class="fas fa-exchange-alt mr-1"></i><?php echo htmlspecialchars(t('import_list.transfer_out_badge', 'ໂອນອອກ')); ?></span>
                          <?php elseif (hasPermission('import_list', 'edit') || hasPermission('import_stock', 'edit') || hasPermission('import_list', 'del') || hasPermission('import_stock', 'del')): ?>
                            <div class="btn-group">
                              <?php if (hasPermission('import_list', 'edit') || hasPermission('import_stock', 'edit')): ?>
                                <button type="button" class="btn btn-outline-warning btn-edit-import px-3" title="<?php echo htmlspecialchars(t('import_list.edit_title', 'ແກ້ໄຂ')); ?>" data-id="<?php echo $row['import_detail_id']; ?>" data-json="<?php echo $rowJson; ?>">
                                  <i class="fas fa-edit fa-lg"></i>
                                </button>
                              <?php endif; ?>
                              <?php if (hasPermission('import_list', 'del') || hasPermission('import_stock', 'del')): ?>
                                <?php if (!$hasMovement): ?>
                                  <button type="button" class="btn btn-outline-danger btn-delete-import px-3" title="<?php echo htmlspecialchars(t('import_list.cancel_delete_title', 'ຍົກເລີກ/ລົບບິນນີ້')); ?>" data-id="<?php echo $row['import_detail_id']; ?>" data-invoice="<?php echo htmlspecialchars($row['invoice_number']); ?>" data-name="<?php echo htmlspecialchars($row['product_name']); ?>">
                                    <i class="fas fa-trash fa-lg"></i>
                                  </button>
                                <?php else: ?>
                                  <button type="button" class="btn btn-outline-secondary disabled px-3" title="<?php echo htmlspecialchars(t('import_list.cannot_cancel_movement_title', 'ບໍ່ສາມາດຍົກເລີກໄດ້ ເນື່ອງຈາກສິນຄ້າມີການເຄື່ອນໄຫວແລ້ວ')); ?>">
                                    <i class="fas fa-lock fa-lg"></i>
                                  </button>
                                <?php endif; ?>
                              <?php endif; ?>
                            </div>
                          <?php else: ?>
                            <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('import_list.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <?php if (!empty($importList)): ?>
                <tfoot>
                  <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #cbd5e1;">
                    <td colspan="7" class="text-right"><?php echo htmlspecialchars(t('import_list.grand_total', 'ລວມທັງໝົດ:')); ?></td>
                    <td class="text-right text-primary" style="font-size:1rem;" id="importGrandTotal"><?php echo displayMoney($pdo, $grandTotalCost); ?></td>
                    <td colspan="3"></td>
                  </tr>
                </tfoot>
                <?php endif; ?>
              <?php endif; ?>

            </table>
          </div>

