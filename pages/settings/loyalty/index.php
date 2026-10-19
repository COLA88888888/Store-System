<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າຕັ້ງຄ່າຄະແນນສະສົມ: ກົດການໄດ້ຄະແນນ, ລາງວັນແລກຄະແນນ, ລູກຄ້າຄະແນນສູງສຸດ ແລະ ປະຫວັດ.
 *   - partials/actions.php : ການກະທຳ POST
 *   - partials/data.php : ຂໍ້ມູນຂອງໜ້າ
 *   - partials/page_body.php : ສ່ວນສະແດງຜົນ
 */
// ============================================================
// pages/settings/loyalty/index.php
// ຕັ້ງຄ່າຄະແນນສະສົມຂອງລູກຄ້າ (Customer Loyalty Points)
// Earning rule, the rewards customers can exchange points for, and an overview of who
// holds points / what was recently earned and redeemed.
// ============================================================
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

// view/add/edit/delete each have their own switch in Permissions (ຄະແນນສະສົມລູກຄ້າ). Admins pass
// inside hasPermission(); a user with only the old "setup" flag still passes via the session's
// loyalty fallback until a switch is turned off for them.
if (empty($_SESSION['user_id']) || !hasPermission('loyalty')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';
$canAddLoyalty  = hasPermission('loyalty', 'add');    // add a reward
$canEditLoyalty = hasPermission('loyalty', 'edit');   // earning rule + edit / switch a reward on-off
$canDelLoyalty  = hasPermission('loyalty', 'del');    // delete a reward
$activeStoreId = getActiveStoreId($pdo);

$postAction = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($_POST['action'] ?? '') : '';
$loyaltyActionAllowed = [
    'save_loyalty'  => $canEditLoyalty,
    'add_reward'    => $canAddLoyalty,
    'edit_reward'   => $canEditLoyalty,
    'toggle_reward' => $canEditLoyalty,
    'delete_reward' => $canDelLoyalty,
];
if ($postAction !== '' && empty($loyaltyActionAllowed[$postAction])) {
    $message = t('loyalty.err_no_permission', 'ທ່ານບໍ່ມີສິດແກ້ໄຂການຕັ້ງຄ່ານີ້!');
    $message_type = 'warning';
    $postAction = '';
}

// ການກະທຳ POST
require __DIR__ . '/partials/actions.php';
// ຂໍ້ມູນຂອງໜ້າ
require __DIR__ . '/partials/data.php';
require_once __DIR__ . '/../../../layouts/header.php';
?>

<style>
  .lp-card { border-radius: 12px; border: 1.5px solid #e2e8f0 !important; background: #ffffff; }
  .lp-code-badge { display: inline-block; padding: 2px 10px; border-radius: 6px; background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 0.8rem; }
</style>

<?php require __DIR__ . '/partials/page_body.php'; /* ສ່ວນສະແດງຜົນ */ ?>
<script>
var LP_I18N = <?php echo tjson([
    'loyalty.add_reward_title'    => 'ເພີ່ມລາຍການແລກຄະແນນ',
    'loyalty.edit_reward_title'   => 'ແກ້ໄຂລາຍການແລກຄະແນນ',
    'loyalty.preview_spend_hint'  => 'ລູກຄ້າຕ້ອງຊື້ປະມານ %s ₭ ຈຶ່ງຈະໄດ້ຄະແນນພໍແລກ (ຄືນໃຫ້ລູກຄ້າປະມານ %s%%)',
]); ?>;
function lpT(key) {
  var s = LP_I18N[key] || key, args = Array.prototype.slice.call(arguments, 1);
  return s.replace(/%%/g, '\u0000').replace(/%s/g, function () { return args.length ? args.shift() : ''; }).replace(/\u0000/g, '%');
}
function lpNum(v) { return (parseFloat(String(v || '').replace(/,/g, '')) || 0); }
function lpFmt(n) { return Math.round(n).toLocaleString('en-US'); }

function lpToggleType() {
  var isProduct = $('#lpType').val() === 'product';
  $('#lpDiscountField').toggle(!isProduct);
  $('#lpProductField').toggle(isProduct);
  lpUpdatePreview();
}

// How much a customer has to spend to earn this reward — helps pick a sensible point cost.
function lpUpdatePreview() {
  var pts = lpNum($('#lpPoints').val());
  var value = 0;
  if ($('#lpType').val() === 'product') {
    var opt = $('#lpProduct option:selected');
    value = opt.val() ? lpNum(opt.data('price')) * Math.max(1, lpNum($('#lpQty').val())) : 0;
  } else {
    value = lpNum($('#lpAmount').val());
  }
  var spend = lpNum($('#loyalty_spend_amount').val());
  var per = lpNum($('#loyalty_points_earned').val());
  if (pts > 0 && value > 0 && spend > 0 && per > 0) {
    var needSpend = Math.ceil(pts / per) * spend;
    var pct = value / needSpend * 100;
    $('#lpPreviewHint').text(lpT('loyalty.preview_spend_hint', lpFmt(needSpend), pct.toFixed(pct < 10 ? 1 : 0)));
  } else {
    $('#lpPreviewHint').text('');
  }
}

function lpOpenRewardModal(rw) {
  var isEdit = !!rw;
  rw = rw || { id: '', name: '', points: '', type: 'discount', amount: '', min_spend: '', product_id: '', qty: 1 };
  $('#lpRewardAction').val(isEdit ? 'edit_reward' : 'add_reward');
  $('#lpRewardId').val(rw.id);
  $('#lpRewardModalTitle').text(lpT(isEdit ? 'loyalty.edit_reward_title' : 'loyalty.add_reward_title'));
  $('#lpType').val(rw.type);
  $('#lpPoints').val(rw.points);
  $('#lpAmount').val(rw.amount ? lpFmt(rw.amount) : '');
  $('#lpMinSpend').val(rw.min_spend ? lpFmt(rw.min_spend) : '');
  $('#lpProduct').val(rw.product_id || '').trigger('change.select2');
  $('#lpQty').val(rw.qty || 1);
  $('#lpName').val(isEdit ? rw.name : '');
  lpToggleType();
  $('#lpRewardModal').modal('show');
}

function confirmDeleteReward(form) {
  Swal.fire({
    title: <?php echo json_encode(t('loyalty.delete_confirm_title', 'ລຶບລາຍການແລກຄະແນນນີ້?'), JSON_UNESCAPED_UNICODE); ?>,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: <?php echo json_encode(t('loyalty.delete_btn', 'ລຶບ'), JSON_UNESCAPED_UNICODE); ?>,
    cancelButtonText: <?php echo json_encode(t('loyalty.cancel_btn', 'ຍົກເລີກ'), JSON_UNESCAPED_UNICODE); ?>
  }).then(function (r) { if (r.isConfirmed) form.submit(); });
  return false;
}

$(function () {

  // Searchable product picker (Select2 is loaded by layouts/footer.php)
  if ($.fn.select2 && $('#lpProduct').length) {
    $('#lpProduct').select2({ theme: 'bootstrap4', dropdownParent: $('#lpRewardModal'), width: '100%' })
      .on('change', lpUpdatePreview);
  }
});
</script>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
