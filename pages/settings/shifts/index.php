<?php
/**
 * ໜ້າຈັດການກະເຮັດວຽກ (Work Shifts Management)
 * ຕັ້ງຄ່າກຳນົດເວລາກະເຮັດວຽກ
 */
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

if (empty($_SESSION['user_id']) || (!hasPermission('shifts') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$userStatus = $_SESSION['status'] ?? '';
$isAdmin = (intval($_SESSION['user_id'] ?? 0) === 1 || $userStatus === 'ຜູ້ບໍລິຫານ' || strtolower($userStatus) === 'admin' || strtolower($userStatus) === 'super admin');
// ພະນັກງານ = ຕຳແໜ່ງ "ພະນັກງານ" ເທົ່ານັ້ນ (ຜູ້ຈັດການ ບໍ່ແມ່ນ — ປິດກະກ່ອນເວລາໄດ້ ແລະ ເຫັນສ່ວນຕ່າງ)
$isCashier = ($userStatus === 'ພະນັກງານ' && !$isAdmin);

require_once __DIR__ . '/../../../layouts/header.php';
?>

<!-- ໂຄງສ້າງ ແລະ CSS ຫຼັກຂອງລະບົບລາຍງານ (reports.css: Table Cards, Buttons) -->
<link rel="stylesheet" href="<?php echo $base_path; ?>themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../../themes/reports.css'); ?>">

<style>
/* ກາດລາຍງານ ມຸມ 8px, ປຸ່ມ ແລະ ຟອມ ມຸມ 4px */
.report-table-card,
.card {
    border-radius: 8px !important;
}
.btn,
.form-control {
    border-radius: 4px !important;
}

/* ປຸ່ມຈັດການໃນຕາຕະລາງ */
.shift-act-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px !important;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    transition: all 0.15s ease;
}
.shift-act-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #94a3b8;
}
.shift-act-btn.btn-edit:hover {
    background: #eff6ff;
    color: #2563eb;
    border-color: #bfdbfe;
}
.shift-act-btn.btn-del:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}
</style>

<script>
window.IS_CASHIER_USER = <?php echo $isCashier ? 'true' : 'false'; ?>;
window.IS_ADMIN_USER = <?php echo $isAdmin ? 'true' : 'false'; ?>;
</script>

<div class="container-fluid p-3 p-md-4">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 12px;">
    <div>
      <h5 class="font-weight-bold text-dark mb-0" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-calendar-alt mr-2" style="color: #2563eb;"></i> <?php echo htmlspecialchars(t('layout.shifts', 'ຈັດການກະເຮັດວຽກ')); ?>
      </h5>
      <small class="text-muted" style="font-size: 0.82rem;">ຕັ້ງຄ່າກຳນົດເວລາເລີ່ມຕົ້ນ ແລະ ສິ້ນສຸດຂອງກະເຮັດວຽກພາຍໃນຮ້ານ</small>
    </div>
    <div class="page-actions"><?php /* ຊິດຂວາສະເໝີ ລວມທັງໂທລະສັບ */ ?>
      <button type="button" class="btn btn-primary btn-sm font-weight-bold px-3.5 d-inline-flex align-items-center" onclick="openAddShiftModal()" style="border-radius: 4px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none; box-shadow: 0 4px 10px rgba(36, 72, 134, 0.2);">
        <i class="fas fa-plus-circle mr-2"></i> ເພີ່ມກະໃໝ່
      </button>
    </div>
  </div>

  <!-- Shifts Configuration Table Card -->
  <div class="card sys-card border-0 shadow-sm mb-3" style="border-radius: 12px; overflow: hidden; max-width: 100%;">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.95rem;">
        <i class="fas fa-list text-primary mr-2"></i> ລາຍການກະເຮັດວຽກທີ່ຕັ້ງຄ່າໄວ້
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle report-table mb-0" style="font-size: 0.88rem; min-width: 600px;">
        <thead>
          <tr>
            <th class="text-center" style="width: 60px;">#</th>
            <th class="text-left">ຊື່ກະເຮັດວຽກ</th>
            <th class="text-center">ເວລາເລີ່ມເປີດກະ</th>
            <th class="text-center">ເວລາສິ້ນສຸດກະ</th>
            <th class="text-left">ພະນັກງານໃນກະ</th>
            <th class="text-center">ສະຖານະ</th>
            <th class="text-center" style="width: 120px;">ຈັດການ</th>
          </tr>
        </thead>
        <tbody id="shiftsConfigTableBody">
          <tr>
            <td colspan="6" class="text-center text-muted py-5 font-weight-bold">
              <i class="fas fa-spinner fa-spin mr-1"></i> ກຳລັງໂຫຼດລາຍການກະ...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/modals.php'; ?>
<?php require_once __DIR__ . '/partials/js.php'; ?>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
