<!-- Top Header Row -->
<div class="row mb-3 align-items-center">
  <div class="col-sm-6">
    <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
      <i class="fas fa-tags text-primary mr-2"></i> <?php echo tlh('ຈັດການໂປຣໂມຊັ່ນ'); ?>
    </h5>
  </div>
  <div class="col-sm-6 text-right">
    <?php if (hasPermission('promotions', 'add')): ?>
      <button type="button" class="btn btn-primary px-3.5 font-weight-bold" data-toggle="modal" data-target="#addPromoModal" style="border-radius: 6px; height: 38px; border: none;">
        <i class="fas fa-plus-circle mr-1.5"></i> <?php echo tlh('ເພີ່ມໂປຣໂມຊັ່ນໃໝ່'); ?>
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if ($message !== ''): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
        title: '<?php echo $message_type === "success" ? tl('ສຳເລັດ') : tl('ແຈ້ງເຕືອນ'); ?>',
        text: '<?php echo $message; ?>',
        confirmButtonColor: '#2563eb'
      });
    });
  </script>
<?php endif; ?>