<!-- Component: Customer Table -->
<div class="card-body p-0">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 text-nowrap" id="customerTable">
      <thead class="bg-light text-secondary" style="font-size: 0.88rem; text-transform: uppercase;">
        <tr>
          <th class="py-3 text-center" style="width: 60px;"><?php echo htmlspecialchars(t('customers.col_no', 'ລຳດັບ')); ?></th>
          <th class="py-3 text-center" style="width: 120px;"><?php echo htmlspecialchars(t('customers.col_code', 'ລະຫັດ')); ?></th>
          <th class="py-3"><?php echo htmlspecialchars(t('customers.col_name', 'ຊື່')); ?></th>
          <th class="py-3"><?php echo htmlspecialchars(t('customers.col_branch', 'ສາຂາ')); ?></th>
          <th class="py-3"><?php echo htmlspecialchars(t('customers.col_phone', 'ເບີໂທ')); ?></th>
          <th class="py-3"><?php echo htmlspecialchars(t('customers.col_address', 'ທີ່ຢູ່')); ?></th>
          <th class="py-3 text-center" style="width: 150px;"><?php echo htmlspecialchars(t('customers.col_member_card', 'ເລກບັດສະມາຊິກ')); ?></th>
          <th class="py-3 text-center" style="width: 110px;"><?php echo htmlspecialchars(t('customers.col_points', 'ຄະແນນສະສົມ')); ?></th>
          <th class="py-3 text-center" style="width: 160px;"><?php echo htmlspecialchars(t('customers.col_registered', 'ເວລາທີ່ສະໝັກ')); ?></th>
          <th class="text-center py-3" style="width: 120px;"><?php echo htmlspecialchars(t('customers.col_actions', 'ຈັດການ')); ?></th>
        </tr>
      </thead>
      <tbody style="font-size: 0.95rem;">
        <tr id="noCustomerDataRow" style="<?php echo empty($allCustomers) ? '' : 'display: none;'; ?>">
          <td colspan="10" class="text-center py-5 text-muted">
            <i class="fas fa-user-slash fa-2x mb-2 text-secondary d-block"></i>
            <span class="font-weight-bold d-block" style="font-size: 1.05rem; color: #64748b;"><?php echo htmlspecialchars(t('customers.no_data', 'ບໍ່ມີຂໍ້ມູນລູກຄ້າໃນລະບົບ')); ?></span>
          </td>
        </tr>

        <?php if (!empty($allCustomers)): ?>
          <?php $idx = 1; foreach ($allCustomers as $cust): 
            $custJson = htmlspecialchars(json_encode($cust), ENT_QUOTES, 'UTF-8');
            $createdAt = !empty($cust['created_at']) ? date('d/m/Y H:i', strtotime($cust['created_at'])) : '-';
            $createdDateIso = !empty($cust['created_at']) ? date('Y-m-d', strtotime($cust['created_at'])) : date('Y-m-d');
            $memberCard = !empty($cust['member_card']) ? $cust['member_card'] : '-';
            $searchData = strtolower($cust['customer_code'] . ' ' . $cust['customer_name'] . ' ' . ($cust['phone'] ?? '') . ' ' . ($cust['member_card'] ?? '') . ' ' . ($cust['email'] ?? '') . ' ' . ($cust['store_name'] ?? '') . ' ' . ($cust['address'] ?? ''));
          ?>
            <tr class="cust-row" data-search="<?php echo htmlspecialchars($searchData); ?>" data-date="<?php echo $createdDateIso; ?>">
              <td class="align-middle text-center text-muted font-weight-bold row-index"><?php echo $idx++; ?></td>
              
              <td class="align-middle text-center font-weight-bold">
                <span class="cust-code-badge"><?php echo branchCodeHtml($cust['customer_code']); ?></span>
              </td>

              <td class="align-middle font-weight-bold text-dark cust-name-cell">
                <?php echo htmlspecialchars($cust['customer_name']); ?>
              </td>

              <td class="align-middle font-weight-bold">
                <span class="badge badge-light border text-primary px-2 py-1" style="font-size: 0.82rem;">
                  <i class="fas fa-store mr-1 text-primary"></i><?php echo htmlspecialchars($cust['store_name'] ?: t('customers.main_branch', 'ສາຂາຫຼັກ')); ?>
                </span>
              </td>

              <td class="align-middle text-dark cust-phone-cell">
                <?php if (!empty($cust['phone'])): ?>
                  <a href="tel:<?php echo htmlspecialchars($cust['phone']); ?>" class="text-dark">
                    <?php echo htmlspecialchars($cust['phone']); ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>

              <td class="align-middle text-dark cust-address-cell" style="max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php $custAddr = trim((string)($cust['address'] ?? '')); ?>
                <?php if ($custAddr !== ''): ?>
                  <span title="<?php echo htmlspecialchars($custAddr); ?>"><i class="fas fa-map-marker-alt text-danger mr-1" style="font-size: 0.82rem;"></i><?php echo htmlspecialchars($custAddr); ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>

              <td class="align-middle text-center font-weight-bold">
                <?php if (!empty($cust['member_card'])): ?>
                  <span class="badge badge-pill px-2.5 py-1.5" style="font-size: 0.85rem; background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;">
                    <i class="fas fa-id-card mr-1 text-primary"></i> <?php echo htmlspecialchars($cust['member_card']); ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>

              <td class="align-middle text-center font-weight-bold">
                <?php $custPoints = (int)($cust['points'] ?? 0); ?>
                <?php if ($custPoints > 0): ?>
                  <span class="badge badge-pill px-2.5 py-1.5" style="font-size: 0.85rem; background: #fef3c7; color: #b45309; border: 1px solid #fde68a;">
                    <i class="fas fa-star mr-1"></i> <?php echo number_format($custPoints); ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>

              <td class="align-middle text-center text-secondary" style="font-size: 0.88rem;">
                <i class="far fa-clock text-info mr-1"></i> <?php echo $createdAt; ?>
              </td>

              <td class="text-center align-middle">
                <?php if (hasPermission('customers', 'edit') || hasPermission('customers', 'del')): ?>
                  <div class="btn-group btn-group-sm">
                    <?php if (hasPermission('customers', 'edit')): ?>
                      <!-- Edit Button -->
                      <button type="button" class="btn btn-outline-warning" title="<?php echo htmlspecialchars(t('customers.edit', 'ແກ້ໄຂ')); ?>" onclick='openEditCustomerModal(<?php echo $custJson; ?>)'>
                        <i class="fas fa-edit"></i>
                      </button>
                    <?php endif; ?>
                    <?php if (hasPermission('customers', 'del')): ?>
                      <!-- Delete Button -->
                      <button type="button" class="btn btn-outline-danger" title="<?php echo htmlspecialchars(t('customers.delete', 'ລົບ')); ?>" onclick="confirmDeleteCustomer(<?php echo $cust['customer_id']; ?>, '<?php echo htmlspecialchars(addslashes($cust['customer_name'])); ?>')">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('customers.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                <?php endif; ?>
              </td>

            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
