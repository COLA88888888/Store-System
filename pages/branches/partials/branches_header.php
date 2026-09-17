<?php
// pages/branches/partials/branches_header.php
if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}
?>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
      <i class="fas fa-network-wired text-primary mr-2"></i> <?php echo htmlspecialchars(t('branches.page_title', 'ຈັດການສາຂາທັງໝົດ')); ?>
    </h5>
  </div>
  <?php if (!empty($canManageBranches) && hasPermission('branches', 'add')): ?>
    <button type="button" class="btn btn-sm btn-primary font-weight-bold shadow-sm" data-toggle="modal" data-target="#addBranchModal" style="border-radius: 6px; font-size: 0.84rem; padding: 5px 12px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
      <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('branches.btn_add', 'ເພີ່ມສາຂາໃໝ່')); ?>
    </button>
  <?php endif; ?>
</div>

<?php if (!empty($message)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
        title: '<?php echo $message_type === "success" ? t('branches.msg_success_title', 'ສຳເລັດ') : t('branches.msg_alert_title', 'ແຈ້ງເຕືອນ'); ?>',
        text: <?php echo json_encode($message); ?>,
        confirmButtonColor: '#2563eb'
      });
    });
  </script>
<?php endif; ?>
