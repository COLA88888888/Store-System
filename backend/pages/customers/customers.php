<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

// Load Centralized Backend API Logic
require_once __DIR__ . '/../../api/customers_backend.php';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/customers.css?v=<?php echo filemtime(__DIR__ . '/../../themes/customers.css'); ?>">

<div class="content-wrapper bg-light">
  
  <!-- 1. Content Header Component -->
  <?php require_once __DIR__ . '/components/customer_header.php'; ?>

  <!-- SweetAlert Notification -->
  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        <?php if ($message_type === 'success'): ?>
        Swal.fire({
          icon: 'success',
          title: '<?php echo htmlspecialchars(t('customers.msg_success_title', 'ສຳເລັດ'), ENT_QUOTES); ?>',
          text: '<?php echo $message; ?>',
          showConfirmButton: false,
          timer: 1500
        });
        <?php else: ?>
        Swal.fire({
          icon: 'error',
          title: '<?php echo htmlspecialchars(t('customers.msg_error_title', 'ແຈ້ງເຕືອນ'), ENT_QUOTES); ?>',
          text: '<?php echo $message; ?>',
          confirmButtonColor: '#2563eb',
          confirmButtonText: '<?php echo htmlspecialchars(t('customers.ok_button', 'ຕົກລົງ'), ENT_QUOTES); ?>'
        });
        <?php endif; ?>
      });
    </script>
  <?php endif; ?>

  <!-- Main Content -->
  <section class="content pb-5">
    <div class="container-fluid">

      <!-- MAIN CUSTOMER TABLE CARD -->
      <div class="card customer-card shadow-sm border-0" style="border-radius: 14px;">
        
        <!-- 2. Filter Bar Component (Page Size, Date Range, Search) -->
        <?php require_once __DIR__ . '/components/customer_filter_bar.php'; ?>

        <!-- 3. Customer Data Table Component -->
        <?php require_once __DIR__ . '/components/customer_table.php'; ?>

        <!-- 4. Circular Blue Pagination Footer Component -->
        <?php require_once __DIR__ . '/components/customer_pagination.php'; ?>

      </div>
    </div>
  </section>
</div>

<!-- 5. Modal Components (Add & Edit Customer) -->
<?php
require_once __DIR__ . '/components/form_add_customer.php';
require_once __DIR__ . '/components/form_edit_customer.php';
?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<!-- 6. JavaScript Logic Component -->
<?php require_once __DIR__ . '/components/customer_js.php'; ?>
