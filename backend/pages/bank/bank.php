<?php
// ============================================================
// pages/bank/bank.php - BANK MANAGEMENT MODULE (ຈັດການທະນາຄານ)
// ============================================================
session_start();

if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

// Load Centralized Backend API Logic
require_once __DIR__ . '/../../api/bank_backend.php';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/bank.css?v=<?php echo filemtime(__DIR__ . '/../../themes/bank.css'); ?>">

<div class="container-fluid p-4">
  <!-- Bank Management Page Header -->
  <?php require_once __DIR__ . '/partials/bank_header.php'; ?>

  <!-- Bank Accounts Management Content Grid & Table -->
  <?php require_once __DIR__ . '/partials/bank_accounts_tab.php'; ?>
</div>

<!-- Add / Edit Bank Account Modals -->
<?php require_once __DIR__ . '/partials/bank_modals.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
