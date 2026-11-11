<?php
// ============================================================
// branches.php - DEDICATED MULTI-BRANCH MANAGEMENT SYSTEM
// ໜ້າຈັດການສາຂາທັງໝົດໃນລະບົບ (ແຍກຕ່າງຫາກ)
// ============================================================
session_start();
$base_path = '../../';

// Load Centralized Backend API Logic
require_once __DIR__ . '/../../api/branches_backend.php';

require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid p-4">
  <?php require_once __DIR__ . '/partials/branches_header.php'; ?>
  <?php require_once __DIR__ . '/partials/branches_table.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/branches_modals.php'; ?>
<?php require_once __DIR__ . '/partials/js/branches_js.php'; ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
