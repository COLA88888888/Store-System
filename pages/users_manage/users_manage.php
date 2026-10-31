<?php
session_start();

// Dynamic base_path calculation based on URL request path
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

// Load Centralized Backend API Logic
require_once __DIR__ . '/../../api/users_manage_backend.php';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/users.css?v=<?php echo filemtime(__DIR__ . '/../../themes/users.css'); ?>">

<div class="content-wrapper bg-light">
  <!-- Content Header (Page header) -->
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h5 class="m-0 font-weight-bold text-dark" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon'; font-size:  18px;">
            <i class="fas fa-users-cog text-primary mr-2"></i> <?php echo htmlspecialchars(t('users_manage.page_title', 'ຈັດການຜູ້ນຳໃຊ້ລະບົບ')); ?>
          </h5>
        </div>
        <div class="col-sm-6 text-right">
          <?php if (hasPermission('users', 'add')): ?>
            <button type="button" class="btn btn-primary px-3 py-1.5 font-weight-bold shadow-sm" data-toggle="modal" data-target="#addUserModal" style="border-radius: 6px; white-space: nowrap; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
              <i class="fas fa-user-plus mr-1"></i> <?php echo htmlspecialchars(t('users_manage.btn_add_user', 'ເພີ່ມຜູ້ໃຊ້')); ?>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Main content -->
  <section class="content pb-5">
    <div class="container-fluid">
      <!-- MAIN USERS TABLE CARD -->
      <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center w-100 mp-search-head">
          <h6 class="card-title m-0 font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon'; color: #0f172a;">
            <?php echo htmlspecialchars(t('users_manage.card_title_all_users', 'ລາຍງານຜູ້ໃຊ້ງານທັງໝົດ')); ?>
          </h6>

          <!-- Search Box Aligned Absolute Far Right directly under Add User button -->
          <div class="position-relative ml-auto mp-search-box" style="width: 260px; max-width: 100%;">
            <i class="fas fa-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.85rem; z-index: 5;"></i>
            <input type="text" id="userSearchInput" class="form-control form-control-sm shadow-none"
                   placeholder="<?php echo htmlspecialchars(t('users_manage.search_placeholder', 'ຄົ້ນຫາຜູ້ໃຊ້ງານ...')); ?>"
                   style="padding-left: 34px !important; border-radius: 6px; border: 1.5px solid #cbd5e1; font-size: 0.85rem; height: 34px; background-color: #f8fafc;">
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
              <thead class="bg-light text-secondary" style="font-size: 0.88rem; text-uppercase: true;">
                <tr>
                  <th class="text-center py-3" style="width: 50px;"><?php echo htmlspecialchars(t('users_manage.th_no', 'ລຳດັບ')); ?></th>
                  <th class="py-3"><?php echo htmlspecialchars(t('users_manage.th_user_code', 'ລະຫັດຜູ້ໃຊ້')); ?></th>
                  <th class="text-center py-3" style="width: 80px;"><?php echo htmlspecialchars(t('users_manage.th_photo', 'ຮູບພາບ')); ?></th>
                  <th class="py-3"><?php echo htmlspecialchars(t('users_manage.th_username', 'ຊື່ຜູ້ໃຊ້ງານ')); ?></th>
                  <th class="py-3"><?php echo htmlspecialchars(t('users_manage.th_branch', 'ປະຈຳສາຂາ')); ?></th>
                  <th class="py-3"><?php echo htmlspecialchars(t('users_manage.th_tel', 'ເບີໂທລະສັບ')); ?></th>
                  <th class="text-center py-3"><?php echo htmlspecialchars(t('users_manage.th_status', 'ສະຖານະ')); ?></th>
                  <th class="text-center py-3"><?php echo htmlspecialchars(t('users_manage.th_role', 'ຕຳແໜ່ງ')); ?></th>
                  <?php if (isOwnerUser()): ?>
                    <th class="text-center py-3" data-i18n="users_manage.th_access_age"><?php echo htmlspecialchars(t('users_manage.th_access_age', 'ອາຍຸການໃຊ້ງານ')); ?></th>
                  <?php endif; ?>
                  <th class="text-center py-3" style="width: 140px;"><?php echo htmlspecialchars(t('users_manage.th_actions', 'ຈັດການ')); ?></th>
                </tr>
              </thead>
              <tbody style="font-size: 0.95rem;">
                <?php if (!empty($allUsers)): ?>
                  <?php 
                  $idx = $offset + 1;
                  foreach ($allUsers as $u): 
                    $imgName = !empty($u['profile_img']) ? $u['profile_img'] : 'default.png';
                    $imgPath = $base_path . 'assets/img/users/' . $imgName;
                    
                    // Check if online (active within 5 mins / 300 seconds)
                    $isOnline = false;
                    if (!empty($u['last_activity'])) {
                        $lastActiveTime = strtotime($u['last_activity']);
                        if ((time() - $lastActiveTime) <= 300) {
                            $isOnline = true;
                        }
                    }
                    $u['is_online'] = $isOnline;
                    $userJson = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8');
                    $userStatus = trim($u['status'] ?? $u['userstatus'] ?? 'ພະນັກງານ');
                    // Anyone can see an admin-labeled row here; only the owner (Id 1) may edit or
                    // delete ANOTHER admin's row — same rule enforced server-side in api/users_api.php.
                    $rowIsAdmin = (intval($u['Id']) === 1 || $userStatus === 'ຜູ້ບໍລິຫານ' || strtolower($userStatus) === 'admin' || strtolower($userStatus) === 'super admin');
                    // Nobody — admin or not — may delete the account they're logged in as.
                    $isOwnRow = (intval($u['Id']) === intval($_SESSION['user_id'] ?? 0));
                    // An admin may still edit their OWN row (self-service), just never someone else's.
                    $canEditRow = isOwnerUser() || !$rowIsAdmin || $isOwnRow;
                    $canDeleteRow = isOwnerUser() || !$rowIsAdmin;
                    // ບັນຊີຖືກປິດໃຊ້ງານ (ພະນັກງານລາອອກ) — ເຂົ້າລະບົບບໍ່ໄດ້
                    $isAccountActive = intval($u['account_active'] ?? 1) === 1;
                    $canToggleActive = hasPermission('users', 'edit') && intval($u['Id']) !== 1 && !$isOwnRow && (isOwnerUser() || !$rowIsAdmin);
                  ?>
                    <tr<?php echo $isAccountActive ? '' : ' class="user-row-disabled" style="opacity: 0.6;"'; ?>>
                      <td class="text-center align-middle font-weight-bold text-muted"><?php echo $idx++; ?></td>
                      <td class="align-middle text-secondary font-weight-bold"><?php echo $u['user_code'] !== '' && $u['user_code'] !== null ? branchCodeHtml($u['user_code']) : '-'; ?></td>
                      <td class="text-center align-middle">
                        <img src="<?php echo $base_path . 'assets/img/users/' . htmlspecialchars($imgName); ?>"
                             class="shadow-sm previewable-image"
                             style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0; cursor: pointer;"
                             alt="<?php echo htmlspecialchars($u['fname'] ?: ($u['username'] ?? '')); ?>"
                             data-preview-title="<?php echo htmlspecialchars($u['fname'] ?: ($u['username'] ?? '')); ?>"
                             title="<?php echo htmlspecialchars(t('users_manage.click_to_view_large', 'ຄລິກເພື່ອເບິ່ງຮູບໃຫຍ່')); ?>">
                      </td>
                      <td class="align-middle font-weight-bold text-dark">
                        <?php echo htmlspecialchars($u['fname'] ?: ($u['username'] ?? '')); ?>
                      </td>
                      <td class="align-middle">
                        <span class="badge badge-light border text-dark px-2 py-1" style="font-size: 0.82rem;">
                          <i class="fas fa-store-alt text-primary mr-1"></i><?php echo htmlspecialchars($u['store_name'] ?? t('users_manage.default_store', 'ສາຂາຫຼັກ')); ?>
                        </span>
                      </td>
                      <td class="align-middle text-muted"><?php echo htmlspecialchars($u['tel'] ?: '-'); ?></td>
                      
                      <!-- COLUMN 1: ສະຖານະ (Online / Offline) -->
                      <td class="text-center align-middle">
                        <div class="user-online-badge-box" data-user-id="<?php echo $u['Id']; ?>">
                          <?php if (!$isAccountActive): ?>
                            <span class="badge badge-dark px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;">
                              <i class="fas fa-user-slash mr-1"></i> <?php echo htmlspecialchars(t('users_manage.status_disabled', 'ປິດໃຊ້ງານ (ລາອອກ)')); ?>
                            </span>
                          <?php elseif ($isOnline): ?>
                            <span class="badge badge-success px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;">
                              <i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> <?php echo htmlspecialchars(t('users_manage.status_online', 'ໃຊ້ງານຢູ່')); ?>
                            </span>
                          <?php else: ?>
                            <span class="badge badge-danger px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;">
                              <i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> <?php echo htmlspecialchars(t('users_manage.status_offline', 'ບໍ່ໄດ້ໃຊ້ງານ')); ?>
                            </span>
                          <?php endif; ?>
                        </div>
                        <?php
                          $lastOnlineStr = '-';
                          if (!empty($u['last_activity'])) {
                              $lastTs = strtotime($u['last_activity']);
                              if ($lastTs) {
                                  // ຖ້າອອນລາຍມື້ນີ້: ສະແດງແຕ່ເວລາ (H:i); ຖ້າກາຍມື້ນີ້: ສະແດງວັນທີ (H:i d/m/Y)
                                  if (date('Y-m-d', $lastTs) === date('Y-m-d')) {
                                      $lastOnlineStr = date('H:i', $lastTs);
                                  } else {
                                      $lastOnlineStr = date('H:i d/m/Y', $lastTs);
                                  }
                              }
                          }
                        ?>
                        <span class="user-last-active-text text-muted ml-1" style="font-size: 0.74rem; line-height: 1.25;<?php echo $isOnline ? ' display:none;' : ''; ?>">
                          <i class="far fa-clock mr-1 text-secondary"></i><?php echo tlh('ອອນລາຍລ່າສຸດ:'); ?> <?php echo htmlspecialchars($lastOnlineStr); ?>
                        </span>
                      </td>

                      <!-- COLUMN 2: ຕຳແໜ່ງ (Role / Position) -->
                      <td class="text-center align-middle">
                        <?php 
                          $cleanStatus = mb_strtolower(trim((string)$userStatus));
                          $isRowAdmin = (intval($u['Id']) === 1 || in_array($cleanStatus, ['ຜູ້ບໍລິຫານ', 'ผู้บริหาร', 'admin', 'administrator', 'super admin', 'superadmin'], true));
                          $isRowManager = in_array($cleanStatus, ['ຜູ້ຈັດການ', 'ผู้จัดการ', 'manager'], true);
                          if ($isRowAdmin): 
                        ?>
                          <span class="badge badge-primary px-2.5 py-1.5" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-user-shield mr-1"></i> <?php echo htmlspecialchars(t('users_manage.role_admin', 'ຜູ້ບໍລິຫານ')); ?></span>
                        <?php elseif ($isRowManager): ?>
                          <span class="badge badge-info px-2.5 py-1.5" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-user-tie mr-1"></i> <?php echo htmlspecialchars(t('users_manage.role_manager_short', 'ຜູ້ຈັດການ')); ?></span>
                        <?php else: ?>
                          <span class="badge badge-secondary px-2.5 py-1.5" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars(t('users_manage.role_employee', 'ພະນັກງານ')); ?></span>
                        <?php endif; ?>
                      </td>

                      <?php if (isOwnerUser()): ?>
                        <!-- ວັນໝົດອາຍຸການໃຊ້ງານ (ສະເພາະຜູ້ບໍລິຫານຄົນທຳອິດເຫັນ ແລະ ກຳນົດໄດ້; ແຍກອອກຈາກສະຖານະອອນລາຍ/ຕຳແໜ່ງ) -->
                        <td class="text-center align-middle">
                          <?php if (intval($u['Id']) === 1): ?>
                            <span class="text-muted">—</span>
                          <?php elseif (!empty($u['access_expire_date'])):
                            $daysLeft = (int)ceil((strtotime($u['access_expire_date']) - strtotime(date('Y-m-d'))) / 86400);
                          ?>
                            <?php if ($daysLeft <= 0): ?>
                              <span class="badge badge-danger" style="font-size: 0.78rem;"><i class="fas fa-hourglass-end mr-1"></i><?php echo htmlspecialchars(t('users_manage.access_expired', 'ໝົດອາຍຸແລ້ວ')); ?></span>
                            <?php else: ?>
                              <span class="badge badge-warning" style="font-size: 0.78rem;"><i class="fas fa-hourglass-half mr-1"></i><?php echo htmlspecialchars(sprintf(t('users_manage.access_days_left', 'ເຫຼືອ %d ມື້'), $daysLeft)); ?></span>
                            <?php endif; ?>
                          <?php else: ?>
                            <span class="badge badge-light border text-muted" style="font-size: 0.78rem;"><?php echo htmlspecialchars(t('users_manage.access_unlimited', 'ບໍ່ຈຳກັດ')); ?></span>
                          <?php endif; ?>
                        </td>
                      <?php endif; ?>

                      <td class="text-center align-middle">
                        <div class="btn-group btn-group-sm">
                          <!-- View Details Button -->
                          <button type="button" class="btn btn-outline-info" title="<?php echo htmlspecialchars(t('users_manage.btn_view_title', 'ເບິ່ງລາຍລະອຽດ')); ?>" onclick='openViewModal(<?php echo $userJson; ?>)'>
                            <i class="fas fa-eye"></i>
                          </button>
                          <?php if (hasPermission('users', 'edit') && $canEditRow): ?>
                            <!-- Edit Button -->
                            <button type="button" class="btn btn-outline-warning" title="<?php echo htmlspecialchars(t('users_manage.btn_edit_title', 'ແກ້ໄຂ')); ?>" onclick='openEditModal(<?php echo $userJson; ?>)'>
                              <i class="fas fa-edit"></i>
                            </button>
                          <?php endif; ?>
                          <?php if ($canToggleActive): ?>
                            <!-- ເປີດ / ປິດໃຊ້ງານບັນຊີ (ພະນັກງານລາອອກ) -->
                            <button type="button" class="btn <?php echo $isAccountActive ? 'btn-outline-secondary' : 'btn-outline-success'; ?>"
                                    title="<?php echo htmlspecialchars($isAccountActive ? t('users_manage.btn_disable_title', 'ປິດໃຊ້ງານ (ພະນັກງານລາອອກ)') : t('users_manage.btn_enable_title', 'ເປີດໃຊ້ງານຄືນ')); ?>"
                                    onclick="toggleUserActive(<?php echo $u['Id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>', <?php echo $isAccountActive ? 0 : 1; ?>)">
                              <i class="fas <?php echo $isAccountActive ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                            </button>
                          <?php endif; ?>
                          <?php if (hasPermission('users', 'del') && intval($u['Id']) !== 1 && $canDeleteRow && !$isOwnRow): ?>
                            <!-- Delete Button -->
                            <button type="button" class="btn btn-outline-danger" title="<?php echo htmlspecialchars(t('users_manage.btn_delete_title', 'ລົບ')); ?>" onclick="confirmDeleteUser(<?php echo $u['Id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')">
                              <i class="fas fa-trash-alt"></i>
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="<?php echo isOwnerUser() ? 10 : 9; ?>" class="text-center py-5 text-muted">
                      <i class="fas fa-users-slash fa-3x mb-3 text-secondary d-block"></i>
                      <?php echo htmlspecialchars(t('users_manage.empty_state', 'ບໍ່ພົບຂໍ້ມູນຜູ້ໃຊ້ງານໃນລະບົບ')); ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PAGINATION FOOTER (FAR RIGHT) -->
        <div class="card-footer bg-white py-3 px-4 border-top d-flex justify-content-between align-items-center w-100 flex-wrap" style="gap: 12px;">
          <!-- <small class="text-muted font-weight-bold" style="font-size: 0.85rem;">
            <i class="fas fa-file-alt text-primary mr-1"></i> ສະແດງໜ້າ <?php echo $page; ?> ຈາກທັງໝົດ <?php echo max(1, $total_pages); ?> ໜ້າ (ທັງໝົດ <?php echo $total_records; ?> ບັນຊີ)
          </small> -->
          <div class="ml-auto d-flex justify-content-end align-items-center">
            <ul class="pagination pagination-sm m-0">
              <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>" title="<?php echo htmlspecialchars(t('users_manage.pagination_prev', 'ໜ້າກ່ອນໜ້າ')); ?>">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              <?php for ($p = 1; $p <= max(1, $total_pages); $p++): ?>
                <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo min(max(1, $total_pages), $page + 1); ?>" title="<?php echo htmlspecialchars(t('users_manage.pagination_next', 'ໜ້າຖັດໄປ')); ?>">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- INCLUDE MODAL COMPONENTS -->
<?php
require_once __DIR__ . '/components/view_modal.php';
require_once __DIR__ . '/components/form_add_user.php';
require_once __DIR__ . '/components/form_edit_user.php';
?>

<!-- JAVASCRIPT LOGIC -->
<script>
  var BASE_PATH = '<?php echo $base_path; ?>';
  var API_URL   = '<?php echo $base_path; ?>api/users_api.php';
  var I18N_USERS = <?php echo tjson([
    'users_manage.status_online' => 'ໃຊ້ງານຢູ່',
    'users_manage.status_offline' => 'ບໍ່ໄດ້ໃຊ້ງານ',
    'users_manage.status_disabled' => 'ປິດໃຊ້ງານ (ລາອອກ)',
    'users_manage.role_admin' => 'ຜູ້ບໍລິຫານ',
    'users_manage.role_employee' => 'ພະນັກງານ',
    'users_manage.role_manager_short' => 'ຜູ້ຈັດການ',
  ]); ?>;
  // popup/ແຈ້ງເຕືອນໃນ js/users.js + js/users_modals.js (lang/*/users_js.php) — ອ່ານຜ່ານ uT() ຕອນເປີດ popup
  Object.assign(I18N_USERS, <?php echo tjson(require __DIR__ . '/../../lang/lo/users_js.php'); ?>);
  // ອີງກົດເກນດຽວກັນກັບປຸ່ມແກ້ໄຂໃນຕາຕະລາງ: ມີແຕ່ຜູ້ບໍລິຫານຄົນທຳອິດ (Id 1) ເທົ່ານັ້ນທີ່ແກ້ໄຂ
  // ບັນຊີທີ່ມີສະຖານະຜູ້ບໍລິຫານຄົນອື່ນໄດ້ — ໃຊ້ຄວບຄຸມປຸ່ມ "ແກ້ໄຂ" ໃນ modal ເບິ່ງລາຍລະອຽດນຳ.
  var IS_OWNER_USER = <?php echo isOwnerUser() ? 'true' : 'false'; ?>;
  var CAN_EDIT_USERS = <?php echo hasPermission('users', 'edit') ? 'true' : 'false'; ?>;
  var CURRENT_SESSION_USER_ID = <?php echo intval($_SESSION['user_id'] ?? 0); ?>;
</script>
<script src="js/users.js?v=<?php echo filemtime(__DIR__ . '/js/users.js'); ?>"></script>
<script src="js/users_modals.js?v=<?php echo filemtime(__DIR__ . '/js/users_modals.js'); ?>"></script>
<script>
// Real-Time Polling: ອັບເດດສະຖານະ Online/Offline ໂດຍບໍ່ລຶບ "ອອນລາຍລ່າສຸດ"
window.pollLiveUserStatuses = function() {
  var apiUrl = typeof API_URL !== 'undefined' ? API_URL : '../../api/users_api.php';
  $.ajax({
    url: apiUrl + '?action=get_live_user_statuses',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success' && Array.isArray(res.users)) {
        var i18n = typeof I18N_USERS !== 'undefined' ? I18N_USERS : {};
        var onlineText = i18n['users_manage.status_online'] || '<?php echo tlj('ໃຊ້ງານຢູ່'); ?>';
        var offlineText = i18n['users_manage.status_offline'] || '<?php echo tlj('ບໍ່ໄດ້ໃຊ້ງານ'); ?>';
        var disabledText = i18n['users_manage.status_disabled'] || '<?php echo tlj('ປິດໃຊ້ງານ (ລາອອກ)'); ?>';
        var formatLastOnlineJs = function(dtStr) {
          if (!dtStr) return '-';
          var d = new Date(dtStr.replace(/-/g, '/'));
          if (isNaN(d.getTime())) return dtStr;
          var now = new Date();
          var isToday = d.getFullYear() === now.getFullYear() &&
                        d.getMonth() === now.getMonth() &&
                        d.getDate() === now.getDate();
          var pad = function(n) { return (n < 10 ? '0' : '') + n; };
          var timeStr = pad(d.getHours()) + ':' + pad(d.getMinutes());
          if (isToday) {
            return timeStr;
          } else {
            var dateStr = pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
            return timeStr + ' ' + dateStr;
          }
        };

        res.users.forEach(function(u) {
          var box = $('.user-online-badge-box[data-user-id="' + u.id + '"]');
          if (box.length > 0) {
            var lastText = box.siblings('.user-last-active-text');
            if (u.last_activity && lastText.length > 0) {
              lastText.html('<i class="far fa-clock mr-1 text-secondary"></i><?php echo tlj('ອອນລາຍລ່າສຸດ:'); ?> ' + formatLastOnlineJs(u.last_activity));
            }
            var bHtml = '';
            if (u.account_active === 0) {
              bHtml = '<span class="badge badge-dark px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-user-slash mr-1"></i> ' + disabledText + '</span>';
              lastText.show();
            } else if (u.is_online) {
              bHtml = '<span class="badge badge-success px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> ' + onlineText + '</span>';
              lastText.hide();
            } else {
              bHtml = '<span class="badge badge-danger px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> ' + offlineText + '</span>';
              lastText.show();
            }
            if (box.html().trim() !== bHtml) {
              box.html(bHtml);
            }
          }
        });
      }
    }
  });
};
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
