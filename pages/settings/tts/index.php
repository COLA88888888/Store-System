<?php
/**
 * pages/settings/tts/index.php
 * ໜ້າຕັ້ງຄ່າລະບົບສຽງເວົ້າພາສາລາວ (Lao Text-to-Speech)
 * ຮອງຮັບ Microsoft Edge Neural TTS (ຟຣີ 100% ບໍ່ຈຳກັດ) ແລະ Siangmuan.com
 *
 * ລຳດັບສ່ວນປະກອບ (partials/) — ໃຊ້ຕົວແປທີ່ກຽມໄວ້ຂ້າງລຸ່ມ ($config, $currentProvider, $currentVoice, $quota*):
 *   1. hero.php          ຫົວໜ້າ: ສະຖານະເປີດ/ປິດ, ລະບົບສຽງທີ່ໃຊ້, ໂຄຕ້າ
 *   2. engine_card.php   ຟອມຕັ້ງຄ່າ: ເລືອກລະບົບສຽງ, API Key, ສຽງເວົ້າ, ຈຸດທີ່ໃຫ້ເວົ້າອັດຕະໂນມັດ
 *   3. lexicon_card.php  ວັດຈະນານຸກົມການອອກສຽງ
 *   4. test_card.php     ທົດສອບຫຼິ້ນສຽງ
 *   5. js.php            JS ຂອງໜ້າ — ແຈ້ງເຕືອນທັງໝົດຜ່ານ SweetAlert
 */
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

// ສິດ "ຕັ້ງຄ່າລະບົບສຽງ" (ໜ້າກຳນົດສິດ) — ປິດສະວິດ → ບໍ່ເຫັນເມນູ ແລະ ເຂົ້າໜ້ານີ້ບໍ່ໄດ້
if (empty($_SESSION['user_id']) || !hasPermission('tts')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$configFile = __DIR__ . '/../../../config/tts_config.php';
$config = file_exists($configFile) ? require $configFile : [
    'enabled'      => true,
    'provider'     => 'edge',
    'voice'        => 'lo-LA-KeomanyNeural',
    'api_url'      => 'https://siangmuan.com/tts',
    'api_key'      => '',
    'format'       => 'mp3',
    'pos_total'    => true,
    'pos_sale'     => true,
    'pos_stock'    => true,
    'pos_queue'    => true,
    'pos_welcome'  => true,
];

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tts_settings'])) {
    $config['enabled']     = !empty($_POST['tts_enabled']);
    $config['provider']    = trim($_POST['provider'] ?? 'edge');
    $config['api_key']     = trim($_POST['api_key'] ?? '');
    $config['voice']       = trim($_POST['voice'] ?? ($config['provider'] === 'edge' ? 'lo-LA-KeomanyNeural' : ''));
    $config['pos_total']   = !empty($_POST['pos_total']);
    $config['pos_sale']    = !empty($_POST['pos_sale']);
    $config['pos_stock']   = !empty($_POST['pos_stock']);
    $config['pos_queue']   = !empty($_POST['pos_queue']);
    $config['pos_welcome'] = !empty($_POST['pos_welcome']);

    $export = "<?php\n/**\n * ການຕັ້ງຄ່າ Lao TTS API (StoreMini)\n * ຮອງຮັບ Microsoft Edge Neural TTS (ຟຣີ 100% ບໍ່ຈຳກັດ) ແລະ Siangmuan.com\n */\nreturn " . var_export($config, true) . ";\n";
    if (@file_put_contents($configFile, $export) !== false) {
        $message = 'ບັນທຶກການຕັ້ງຄ່າລະບົບສຽງສຳເລັດແລ້ວ!';
        $message_type = 'success';
    } else {
        $message = 'ບັນທຶກບໍ່ສຳເລັດ: ຂຽນໄຟລ໌ config/tts_config.php ບໍ່ໄດ້';
        $message_type = 'error';
    }
}

$currentProvider = $config['provider'] ?? 'edge';
$currentVoice    = $config['voice'] ?? ($currentProvider === 'edge' ? 'lo-LA-KeomanyNeural' : '');
$ttsEnabled      = !empty($config['enabled']);

$quotaFile = __DIR__ . '/../../../config/tts_quota.json';
$quotaInfo = file_exists($quotaFile) ? json_decode(file_get_contents($quotaFile), true) : null;
$quotaRemaining = $quotaInfo['remaining'] ?? null;

require_once __DIR__ . '/../../../layouts/header.php';
?>
<?php echo themeCssLink('settings-tts.css'); ?>

<div class="container-fluid p-3 p-md-4 tts-page">
<?php require __DIR__ . '/partials/hero.php'; ?>
  <div class="row">
    <div class="col-lg-7">
<?php require __DIR__ . '/partials/engine_card.php'; ?>
<?php require __DIR__ . '/partials/lexicon_card.php'; ?>
    </div>
    <div class="col-lg-5">
<?php require __DIR__ . '/partials/test_card.php'; ?>
    </div>
  </div>
</div>

<script src="<?php echo $base_path; ?>assets/js/tts-speak.js?v=<?php echo @filemtime(__DIR__ . '/../../../assets/js/tts-speak.js'); ?>"></script>
<?php require __DIR__ . '/partials/js.php'; ?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
