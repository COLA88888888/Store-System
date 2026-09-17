<?php
// pages/branches/partials/branches_table.php
if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}
?>
<!-- Branch Stores Table Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: white;">
  <div class="card-header bg-white font-weight-bold py-3 border-0 d-flex justify-content-between align-items-center">
    <span><i class="fas fa-store-alt text-info mr-2"></i> <?php echo htmlspecialchars(t('branches.table_card_title', 'ລາຍຊື່ສາຂາທັງໝົດໃນລະບົບ')); ?> (<?php echo count($all_branches); ?> <?php echo htmlspecialchars(t('branches.unit_branch', 'ສາຂາ')); ?>)</span>
  </div>
  <div class="card-body p-0 table-responsive">
    <table class="table table-hover align-middle mb-0" id="branchTable">
      <thead class="bg-light">
        <tr style="white-space: nowrap;">
          <th class="text-center" style="width: 60px;">ID</th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('branches.col_store_code', 'ລະຫັດສາຂາ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('branches.col_store_name', 'ຊື່ສາຂາ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('branches.col_type', 'ປະເພດ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('branches.col_tel', 'ເບີໂທ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('branches.col_address', 'ທີ່ຢູ່')); ?></th>
          <th class="text-center" style="white-space: nowrap;"><?php echo htmlspecialchars(t('branches.col_status', 'ສະຖານະ')); ?></th>
          <th class="text-center" style="width: 140px; white-space: nowrap;"><?php echo htmlspecialchars(t('branches.col_manage', 'ຈັດການ')); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($all_branches)): ?>
          <?php foreach ($all_branches as $b): ?>
            <tr class="branch-row" style="white-space: nowrap;">
              <td class="text-center font-weight-bold" style="white-space: nowrap;"><?php echo $b['store_id']; ?></td>
              <td style="white-space: nowrap;"><span class="badge badge-secondary px-2 py-1"><?php echo htmlspecialchars($b['store_code']); ?></span></td>
              <td class="font-weight-bold text-dark" style="white-space: nowrap;">
                <?php echo htmlspecialchars($b['store_name']); ?>
              </td>
              <td style="white-space: nowrap;">
                <?php if (!empty($b['is_main'])): ?>
                  <span class="badge badge-primary px-2 py-1"><?php echo htmlspecialchars(t('branches.badge_main', 'ສາຂາໃຫຍ່')); ?></span>
                <?php else: ?>
                  <span class="badge badge-info px-2 py-1"><?php echo htmlspecialchars(t('branches.badge_sub', 'ສາຂາຍ່ອຍ')); ?></span>
                <?php endif; ?>
              </td>
              <td style="white-space: nowrap;"><?php echo htmlspecialchars($b['tel'] ?? '-'); ?></td>
              <td style="white-space: nowrap;"><small class="text-muted"><?php echo htmlspecialchars($b['address'] ?? '-'); ?></small></td>
              <td class="text-center" style="white-space: nowrap;">
                <?php if ($b['status'] === 'active'): ?>
                  <span class="badge badge-success px-2 py-1"><?php echo htmlspecialchars(t('branches.badge_active', 'ເປີດໃຊ້ງານ')); ?></span>
                <?php else: ?>
                  <span class="badge badge-danger px-2 py-1"><?php echo htmlspecialchars(t('branches.badge_inactive', 'ປິດໃຊ້ງານ')); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center" style="white-space: nowrap;">
                <?php if (!empty($canManageBranches) && (hasPermission('branches', 'edit') || hasPermission('branches', 'del'))): ?>
                  <div class="btn-group btn-group-sm">
                    <?php if (hasPermission('branches', 'edit')): ?>
                      <button type="button" class="btn btn-sm btn-warning text-dark font-weight-bold rounded-circle mr-1"
                              onclick='openEditBranchModal(<?php echo json_encode($b); ?>)' title="<?php echo htmlspecialchars(t('branches.title_edit', 'ແກ້ໄຂ')); ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-sm <?php echo $b['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success'; ?> rounded-circle mr-1"
                              onclick='confirmToggleBranchStatus(<?php echo $b["store_id"]; ?>, <?php echo json_encode($b["store_name"]); ?>, <?php echo json_encode($b["status"]); ?>)'
                              title="<?php echo $b['status'] === 'active' ? htmlspecialchars(t('branches.title_close_branch', 'ປິດສາຂາ')) : htmlspecialchars(t('branches.title_open_branch', 'ເປີດໃຊ້ງານສາຂາ')); ?>">
                        <i class="fas fa-power-off"></i>
                      </button>
                    <?php endif; ?>
                    <?php if (hasPermission('branches', 'del') && empty($b['is_main'])): ?>
                      <button type="button" class="btn btn-sm btn-danger rounded-circle"
                              onclick='confirmDeleteBranch(<?php echo $b["store_id"]; ?>, <?php echo json_encode($b["store_name"]); ?>)'
                              title="<?php echo htmlspecialchars(t('branches.title_delete', 'ລົບສາຂາ')); ?>">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('branches.badge_view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted"><?php echo htmlspecialchars(t('branches.empty_state', 'ບໍ່ພົບຂໍ້ມູນສາຂາ')); ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Footer -->
  <div class="card-footer bg-white border-top d-flex align-items-center justify-content-between py-3 px-3 px-md-4" style="gap: 10px; flex-wrap: wrap; flex-direction: row;">
    <div class="d-flex align-items-center" style="gap: 8px; font-size: 0.85rem;">
      <span class="text-muted font-weight-bold">
        <?php echo htmlspecialchars(t('branches.page_showing', 'ສະແດງ')); ?>
        <span id="branch_page_info_start">0</span>-<span id="branch_page_info_end">0</span>
        <?php echo htmlspecialchars(t('branches.page_items', 'ລາຍການ')); ?>
      </span>
      <select id="branchPageSizeSelect" class="form-control form-control-sm font-weight-bold" style="width: auto; border-radius: 6px;" onchange="changeBranchPageSize()">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="all"><?php echo htmlspecialchars(t('branches.page_all', 'ທັງໝົດ')); ?></option>
      </select>
    </div>
    <nav aria-label="Branch Pagination" style="margin-left: auto;">
      <ul class="pagination pagination-circle mb-0 justify-content-end" id="branchPagination"></ul>
    </nav>
  </div>
</div>

<style>
.pagination-circle { display: flex; align-items: center; gap: 8px; list-style: none; padding: 0; margin: 0; }
.pagination-circle .page-item { margin: 0; }
.pagination-circle .page-link {
  width: 38px; height: 38px; border-radius: 50% !important; display: flex; align-items: center; justify-content: center;
  padding: 0; font-size: 0.95rem; font-weight: 700; color: #94a3b8; background-color: #f8fafc; border: 1px solid #edf2f7;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; cursor: pointer;
}
.pagination-circle .page-link:hover { color: #2563eb; background-color: #eff6ff; border-color: #bfdbfe; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15); }
.pagination-circle .page-item.active .page-link { color: #fff !important; background: #2563eb !important; border-color: #2563eb !important; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.45) !important; transform: scale(1.04); }
.pagination-circle .page-item.disabled .page-link { color: #cbd5e1 !important; background-color: #f8fafc !important; border-color: #f1f5f9 !important; cursor: not-allowed !important; transform: none !important; box-shadow: none !important; opacity: 0.9; }
.pagination-circle .page-link i { font-size: 0.85rem; font-weight: 900; }
</style>
