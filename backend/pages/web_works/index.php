<?php
/**
 * ຜົນງານ & ໂລໂກ້ລູກຄ້າ (ເວັບໄຊໜ້າບ້ານ) — ອັບໂຫຼດຮູບຜົນງານຫຼາຍຮູບພ້ອມກັນແບບ Facebook, ໂລໂກ້ລູກຄ້າ ແລະ ຊື່ຮ້ານລູກຄ້າ.
 *   - ໂລໂກ້ລູກຄ້າ + ຊື່ຮ້ານ → ສະແດງໃນແถບໂລໂກ້ລູກຄ້າໜ້າຫຼັກ (frontend/components/clients.php) ແລະ ຫົວບັດຜົນງານ
 *   - ຮູບຜົນງານຫຼາຍຮູບ + ຊື່ຮ້ານ + ລາຍລະອຽດ → ສະແດງໃນໜ້າຫຼັກ (components/works.php) ແລະ ໜ້າຜົນງານ (pages/portfolio.php, work.php)
 * ໄຟລ໌ຍ່ອຍ (ລຳດັບ require): partials/list.php (ລາຍການກະທັດຮັດ 1 ແຖວ/ຮ້ານ + ແບ່ງໜ້າ 10 + ຄົ້ນຫາສົດ ?ajax=1),
 *   partials/works_js.php (JS ອັບໂຫຼດຫຼາຍຮູບພ້ອມກັນ + ຕົວຢ່າງກ່ອນໂພສ). ສິດ: setup ຫຼື customers.
 */
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
require_once __DIR__ . '/../../config/db.php';
if (empty($_SESSION['user_id']) || (!hasPermission('setup') && !hasPermission('customers'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$clientsDir = dirname(__DIR__, 2) . '/assets/img/clients';
$worksBaseDir = dirname(__DIR__, 2) . '/assets/img/works';
if (!is_dir($clientsDir)) @mkdir($clientsDir, 0775, true);
if (!is_dir($worksBaseDir)) @mkdir($worksBaseDir, 0775, true);

function wwExtFromFile(string $tmp): ?string {
    $info = @getimagesize($tmp);
    if (!$info) return null;
    return match ($info[2]) {
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF  => 'gif',
        default        => null,
    };
}

function wwSaveUploadedPhotos(array $filesArr, string $targetDir): array {
    if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);
    $saved = [];
    $names = $filesArr['name'] ?? [];
    $tmps  = $filesArr['tmp_name'] ?? [];
    $errs  = $filesArr['error'] ?? [];
    if (!is_array($names)) {
        $names = [$names]; $tmps = [$tmps]; $errs = [$errs];
    }
    foreach ($tmps as $i => $tmp) {
        if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;
        $ext = wwExtFromFile($tmp);
        if (!$ext) continue;
        $fn = sprintf('photo_%d_%02d_%03d.%s', time(), $i, random_int(100, 999), $ext);
        if (@move_uploaded_file($tmp, $targetDir . '/' . $fn)) {
            $saved[] = $fn;
        }
    }
    return $saved;
}

// ກັບໄປໜ້າລາຍການ ໂດຍຮັກສາຄຳຄົ້ນຫາ / ໝວດ / ໜ້າ ທີ່ເປີດຢູ່
function wwBack(string $flag): void {
    $keep = array_filter(['q' => $_GET['q'] ?? '', 'cat' => $_GET['cat'] ?? '', 'page' => $_GET['page'] ?? '']);
    header('Location: index.php?' . http_build_query([$flag => 1] + $keep));
    exit();
}
// ລຶບຮູບນ້ອຍ (assets/img/works/_thumbs/) ຂອງຮູບໜຶ່ງ ຫຼື ທັງຜົນງານ ($fn = null)
function wwDropThumbs(string $worksBaseDir, string $slug, ?string $fn = null): void {
    $pat = preg_replace('~[^A-Za-z0-9_.-]~', '_', $slug . '__' . ($fn === null ? '' : pathinfo($fn, PATHINFO_FILENAME)));
    foreach (glob($worksBaseDir . '/_thumbs/' . $pat . ($fn === null ? '*' : '') . '.jpg') ?: [] as $t) @unlink($t);
}

// --- ຈັດການ POST (ບັນທຶກ / ເພີ່ມຮູບ / ລຶບຮູບ / ລຶບໂພສ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'save_work');
    $isAjax = !empty($_POST['ajax']);

    if ($action === 'save_work') {
        $wid = (int)($_POST['work_id'] ?? 0);
        $shopName = mb_substr(trim((string)($_POST['shop_name'] ?? '')), 0, 200);
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 255);
        $category = mb_substr(trim((string)($_POST['category'] ?? 'POS')), 0, 100) ?: 'POS';
        $workDate = trim((string)($_POST['work_date'] ?? ''));
        $workDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate) ? $workDate : date('Y-m-d');
        $desc = trim((string)($_POST['description'] ?? ''));
        if ($title === '') $title = $shopName;

        if ($shopName === '') {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ຮ້ານລູກຄ້າ']); exit(); }
            header('Location: index.php?err=1'); exit();
        }

        $existing = null;
        if ($wid > 0) {
            $st = $pdo->prepare("SELECT * FROM w_web_works WHERE id = ?");
            $st->execute([$wid]);
            $existing = $st->fetch(PDO::FETCH_ASSOC);
        }

        $slug = $existing['slug'] ?? ('work-' . time() . '-' . random_int(100, 999));
        $logoFn = $existing['logo_img'] ?? null;

        // ລຶບໂລໂກ້ເກົ່າຖ້າກົດປຸ່ມລຶບ
        if (!empty($_POST['remove_logo']) && $logoFn) {
            @unlink($clientsDir . '/' . basename($logoFn));
            $logoFn = null;
        }
        // ອັບໂຫຼດໂລໂກ້ລູກຄ້າໃໝ່
        if (!empty($_FILES['logo_file']['tmp_name']) && ($_FILES['logo_file']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $ext = wwExtFromFile($_FILES['logo_file']['tmp_name']);
            if ($ext) {
                if ($logoFn) @unlink($clientsDir . '/' . basename($logoFn));
                $newLogo = 'client_' . time() . '_' . random_int(100, 999) . '.' . $ext;
                if (@move_uploaded_file($_FILES['logo_file']['tmp_name'], $clientsDir . '/' . $newLogo)) {
                    $logoFn = $newLogo;
                }
            }
        }

        // ຮູບຜົນງານເກົ່າທີ່ເກັບໄວ້
        $workDir = $worksBaseDir . '/' . $slug;
        $oldPhotos = $existing ? (json_decode((string)$existing['photos_json'], true) ?: []) : [];
        $keepPhotos = isset($_POST['keep_photos_json']) ? (json_decode((string)$_POST['keep_photos_json'], true) ?: []) : $oldPhotos;
        $keepPhotos = array_values(array_filter(array_map('basename', $keepPhotos)));
        foreach ($oldPhotos as $oldFn) {
            if (!in_array(basename($oldFn), $keepPhotos, true)) {
                @unlink($workDir . '/' . basename($oldFn));
            }
        }

        // ອັບໂຫຼດຮູບຜົນງານໃໝ່ (ຫຼາຍຮູບພ້ອມກັນ)
        $newPhotos = !empty($_FILES['work_photos']) ? wwSaveUploadedPhotos($_FILES['work_photos'], $workDir) : [];
        $allPhotos = (!empty($_POST['new_cover_first']) && $newPhotos)
            ? array_merge([$newPhotos[0]], $keepPhotos, array_slice($newPhotos, 1))
            : array_merge($keepPhotos, $newPhotos);

        $photosJson = json_encode(array_values($allPhotos), JSON_UNESCAPED_UNICODE);
        if ($existing) {
            $pdo->prepare("UPDATE w_web_works SET shop_name=?, title=?, category=?, work_date=?, description=?, logo_img=?, photos_json=? WHERE id=?")
                ->execute([$shopName, $title, $category, $workDate, $desc, $logoFn, $photosJson, $wid]);
        } else {
            $pdo->prepare("INSERT INTO w_web_works (slug, shop_name, title, category, work_date, description, logo_img, photos_json) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$slug, $shopName, $title, $category, $workDate, $desc, $logoFn, $photosJson]);
            $wid = (int)$pdo->lastInsertId();
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'work_id' => $wid]);
            exit();
        }
        wwBack('saved');
    }

    if ($action === 'append_photos') {
        $wid = (int)($_POST['work_id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM w_web_works WHERE id = ?");
        $st->execute([$wid]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $workDir = $worksBaseDir . '/' . $row['slug'];
            $cur = json_decode((string)$row['photos_json'], true) ?: [];
            $added = !empty($_FILES['work_photos']) ? wwSaveUploadedPhotos($_FILES['work_photos'], $workDir) : [];
            $all = array_values(array_merge($cur, $added));
            $pdo->prepare("UPDATE w_web_works SET photos_json = ? WHERE id = ?")
                ->execute([json_encode($all, JSON_UNESCAPED_UNICODE), $wid]);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'added' => count($added)]);
                exit();
            }
        }
        wwBack('saved');
    }

    if ($action === 'delete_photo') {
        $wid = (int)($_POST['work_id'] ?? 0);
        $delFn = basename((string)($_POST['photo_name'] ?? ''));
        $st = $pdo->prepare("SELECT * FROM w_web_works WHERE id = ?");
        $st->execute([$wid]);
        if ($delFn !== '' && ($row = $st->fetch(PDO::FETCH_ASSOC))) {
            @unlink($worksBaseDir . '/' . $row['slug'] . '/' . $delFn);
            wwDropThumbs($worksBaseDir, $row['slug'], $delFn);
            $cur = array_values(array_filter(json_decode((string)$row['photos_json'], true) ?: [], fn($p) => basename($p) !== $delFn));
            $pdo->prepare("UPDATE w_web_works SET photos_json = ? WHERE id = ?")
                ->execute([json_encode($cur, JSON_UNESCAPED_UNICODE), $wid]);
        }
        wwBack('saved');
    }

    if ($action === 'delete_work') {
        $wid = (int)($_POST['work_id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM w_web_works WHERE id = ?");
        $st->execute([$wid]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['logo_img'])) {
                @unlink($clientsDir . '/' . basename($row['logo_img']));
            }
            $workDir = $worksBaseDir . '/' . $row['slug'];
            if (is_dir($workDir)) {
                foreach (glob($workDir . '/*') ?: [] as $f) { if (is_file($f)) @unlink($f); }
                @rmdir($workDir);
            }
            wwDropThumbs($worksBaseDir, $row['slug']);
            $pdo->prepare("DELETE FROM w_web_works WHERE id = ?")->execute([$wid]);
        }
        wwBack('deleted');
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $est = $pdo->prepare("SELECT * FROM w_web_works WHERE id = ?");
    $est->execute([$editId]);
    $editRow = $est->fetch(PDO::FETCH_ASSOC) ?: null;
}

require __DIR__ . '/partials/list.php';   // → $wwRows, $wwPager, $wwTotal, $wwPage, $wwQ, $wwCat
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['rows' => $wwRows, 'pager' => $wwPager, 'total' => $wwTotal, 'page' => $wwPage], JSON_UNESCAPED_UNICODE);
    exit();
}
$wwAll = (int)$pdo->query("SELECT COUNT(*) FROM w_web_works")->fetchColumn();
$wwWithLogo = (int)$pdo->query("SELECT COUNT(*) FROM w_web_works WHERE logo_img IS NOT NULL AND logo_img <> ''")->fetchColumn();

$editPhotosJs = [];
if ($editRow) {
    foreach (json_decode((string)$editRow['photos_json'], true) ?: [] as $fn) {
        $fn = basename($fn);
        if (is_file($worksBaseDir . '/' . $editRow['slug'] . '/' . $fn)) {
            $editPhotosJs[] = ['name' => $fn, 'url' => $base_path . 'assets/img/works/' . rawurlencode($editRow['slug']) . '/' . rawurlencode($fn)];
        }
    }
}

require_once __DIR__ . '/../../layouts/header.php';
?>
<?php echo themeCssLink('web_chats.css'); ?>
<?php echo themeCssLink('web_works.css'); ?>
<script>window.WW_EXISTING_PHOTOS = <?php echo json_encode($editPhotosJs, JSON_UNESCAPED_UNICODE); ?>;</script>

<div class="content-wrapper bg-light">
  <section class="content pt-3 pb-4">
    <div class="container-fluid">
      <div class="wm-head">
        <h1 class="wc-title"><i class="fas fa-images text-primary"></i> ຈັດການຜົນງານ &amp; ໂລໂກ້ລູກຄ້າ (ເວັບໄຊ)</h1>
        <div>
          <button type="button" class="btn btn-primary btn-sm ww-new-btn" data-toggle="modal" data-target="#wwModal"><i class="fas fa-plus-circle"></i> ໂພສຜົນງານໃໝ່</button>
          <a href="../../../index.php" target="_blank" class="btn btn-outline-primary btn-sm ml-1"><i class="fas fa-external-link-alt"></i> ເບິ່ງໜ້າຫຼັກເວັບໄຊ</a>
          <a href="../../../pages/portfolio.php" target="_blank" class="btn btn-outline-info btn-sm ml-1"><i class="fas fa-th-large"></i> ເບິ່ງໜ້າຜົນງານ</a>
        </div>
      </div>

      <?php if (!empty($_GET['saved'])): ?>
        <div class="alert alert-success py-2">ບັນທຶກຂໍ້ມູນຜົນງານ ແລະ ໂລໂກ້ລູກຄ້າສຳເລັດແລ້ວ — ສະແດງໃນໜ້າເວັບໄຊທັນທີ</div>
      <?php elseif (!empty($_GET['deleted'])): ?>
        <div class="alert alert-secondary py-2">ລຶບລາຍການສຳເລັດແລ້ວ</div>
      <?php endif; ?>

      <div class="ww-layout is-full">
        <!-- ຂວາ: ລາຍການຜົນງານແບບກະທັດຮັດ (partials/list.php) — ຄົ້ນຫາສົດ + ໝວດ + ແບ່ງໜ້າ -->
        <div class="ww-feed">
          <div class="card ww-list-card mb-0">
            <div class="ww-list-head">
              <div class="ww-list-title">ຜົນງານທັງໝົດ <small class="text-muted"><?php echo number_format($wwAll); ?> ຮ້ານ · ມີໂລໂກ້ <?php echo number_format($wwWithLogo); ?></small></div>
              <form class="ww-list-tools" id="wwFilter" autocomplete="off" onsubmit="return false;">
                <div class="ww-search">
                  <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                  <input type="search" name="q" class="form-control form-control-sm" value="<?php echo htmlspecialchars($wwQ); ?>" placeholder="ຄົ້ນຫາຊື່ຮ້ານ / ລາຍລະອຽດ">
                </div>
                <select name="cat" class="form-control form-control-sm">
                  <option value="">ທຸກໝວດ</option>
                  <?php foreach (['POS' => 'ລະບົບ POS', 'ເວັບໄຊ' => 'ເວັບໄຊ', 'ອຸປະກອນ' => 'ອຸປະກອນ POS', 'ລະບົບ' => 'ລະບົບອື່ນໆ'] as $val => $lbl): ?>
                    <option value="<?php echo htmlspecialchars($val); ?>"<?php echo $wwCat === $val ? ' selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" name="page" value="<?php echo $wwPage; ?>">
              </form>
            </div>
            <div id="wwRows"><?php echo $wwRows; ?></div>
            <div id="wwPager"><?php echo $wwPager; ?></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- LIGHTBOX MODAL ສໍາລັບເບິ່ງຮູບຜົນງານ: ຄລິກເບິ່ງຮູບໄດ້ + ປຸ່ມປິດ + ປຸ່ມເລື່ອນ (ຖ້າ 2 ຮູບຂຶ້ນໄປ) -->
<div id="wwGalleryModal" class="ww-gallery-modal" style="display: none;" aria-hidden="true">
  <div class="ww-gallery-backdrop"></div>
  <div class="ww-gallery-container">
    <div class="ww-gallery-header">
      <div class="ww-gallery-info">
        <span class="ww-gallery-shop" id="wwGalleryShop"></span>
        <span class="ww-gallery-counter" id="wwGalleryCounter"></span>
      </div>
      <button type="button" class="ww-gallery-close" id="wwGalleryClose" aria-label="Close" title="ປິດ (Esc)">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="ww-gallery-stage">
      <button type="button" class="ww-gallery-nav is-prev" id="wwGalleryPrev" aria-label="Previous" title="ຮູບກ່ອນໜ້າ (←)">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <div class="ww-gallery-main">
        <img id="wwGalleryImg" class="ww-gallery-img" src="" alt="Work photo">
        <div class="ww-gallery-spinner" id="wwGallerySpinner" style="display: none;">
          <div class="spinner-border text-light" role="status"></div>
        </div>
      </div>
      <button type="button" class="ww-gallery-nav is-next" id="wwGalleryNext" aria-label="Next" title="ຮູບຖັດໄປ (→)">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
      </button>
    </div>
    <div class="ww-gallery-thumbs-wrap" id="wwGalleryThumbsWrap">
      <div class="ww-gallery-thumbs" id="wwGalleryThumbs"></div>
    </div>
  </div>
</div>


<!-- ຟອມໂພສ / ແກ້ໄຂຜົນງານ (modal) — ຫົວ .bg-primary ປ່ຽນສີຕາມສີທີມທີ່ເລືອກ (home/partials/dashboard/js_theme_color.php).
     ?edit=ID → ເປີດ modal ເອງ (ຟອມມີຂໍ້ມູນເດີມ). JS ຟອມ: partials/works_js.php (ໃຊ້ id ເດີມ #wwForm…) -->
<div class="modal fade ww-modal" id="wwModal" tabindex="-1" role="dialog" aria-labelledby="wwModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title" id="wwModalTitle">
          <span class="ww-modal-ico"><i class="fas <?php echo $editRow ? 'fa-edit' : 'fa-images'; ?>"></i></span>
          <span>
            <?php echo $editRow ? 'ແກ້ໄຂຜົນງານ / ໂລໂກ້ລູກຄ້າ' : 'ໂພສຜົນງານ &amp; ໂລໂກ້ລູກຄ້າໃໝ່'; ?>
            <small><?php echo $editRow ? htmlspecialchars($editRow['shop_name'] ?? '') : 'ສະແດງໃນໜ້າເວັບໄຊທັນທີຫຼັງໂພສ'; ?></small>
          </span>
        </h5>
        <?php if ($editRow): ?>
          <a href="index.php" class="ww-modal-x" aria-label="ຍົກເລີກແກ້ໄຂ" title="ຍົກເລີກແກ້ໄຂ">&times;</a>
        <?php else: ?>
          <button type="button" class="ww-modal-x" data-dismiss="modal" aria-label="ປິດ" title="ປິດ">&times;</button>
        <?php endif; ?>
      </div>
      <div class="modal-body">
            <form method="post" enctype="multipart/form-data" id="wwForm">
              <input type="hidden" name="action" value="save_work">
              <input type="hidden" name="work_id" value="<?php echo (int)($editRow['id'] ?? 0); ?>">
              <input type="hidden" name="remove_logo" id="wwRemoveLogo" value="0">
              <input type="hidden" name="keep_photos_json" value="">
              <input type="hidden" name="new_cover_first" value="0">

              <!-- ໂລໂກ້ລູກຄ້າ + ຊື່ຮ້ານລູກຄ້າ -->
              <label class="small text-muted mb-1 d-block">1. ໂລໂກ້ລູກຄ້າ &amp; ຊື່ຮ້ານລູກຄ້າ <span class="text-danger">*</span></label>
              <div class="ww-client-row">
                <?php
                  $hasEditLogo = !empty($editRow['logo_img']) && is_file($clientsDir . '/' . basename($editRow['logo_img']));
                  $editLogoUrl = $hasEditLogo ? ($base_path . 'assets/img/clients/' . rawurlencode(basename($editRow['logo_img']))) : '';
                ?>
                <div class="ww-logo-pick" id="wwLogoPick" title="ກົດເພື່ອເລືອກຮູບໂລໂກ້ລູກຄ້າ">
                  <img id="wwLogoPreview" src="<?php echo htmlspecialchars($editLogoUrl); ?>" alt="" <?php echo $hasEditLogo ? '' : 'hidden'; ?>>
                  <div class="ww-logo-placeholder" id="wwLogoPlaceholder" <?php echo $hasEditLogo ? 'hidden' : ''; ?>>
                    <i class="fas fa-store"></i>
                    <span>+ ໂລໂກ້ລູກຄ້າ</span>
                  </div>
                  <button type="button" class="ww-logo-clear" id="wwLogoClear" title="ລຶບໂລໂກ້" <?php echo $hasEditLogo ? '' : 'hidden'; ?>>&times;</button>
                </div>
                <input type="file" name="logo_file" id="wwLogoInput" accept="image/jpeg,image/png,image/webp,image/gif" hidden>

                <div class="flex-grow-1">
                  <input type="text" name="shop_name" class="form-control font-weight-bold mb-2" placeholder="ຊື່ຮ້ານລູກຄ້າ (ເຊັ່ນ: ຮ້ານ ດາວກາເຟ)..." value="<?php echo htmlspecialchars($editRow['shop_name'] ?? ''); ?>" required>
                  <div class="form-row">
                    <div class="col-6">
                      <select name="category" class="form-control form-control-sm">
                        <?php foreach (['POS' => 'ລະບົບ POS', 'ເວັບໄຊ' => 'ເວັບໄຊ', 'ອຸປະກອນ' => 'ອຸປະກອນ POS', 'ລະບົບ' => 'ລະບົບອື່ນໆ'] as $val => $lbl): ?>
                          <option value="<?php echo htmlspecialchars($val); ?>" <?php echo (($editRow['category'] ?? 'POS') === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-6">
                      <input type="date" name="work_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($editRow['work_date'] ?? date('Y-m-d')); ?>">
                    </div>
                  </div>
                </div>
              </div>

              <!-- ຫົວຂໍ້ ແລະ ລາຍລະອຽດ -->
              <label class="small text-muted mb-1 d-block">2. ຫົວຂໍ້ &amp; ລາຍລະອຽດຜົນງານ (ບໍ່ບັງຄັບ)</label>
              <input type="text" name="title" class="form-control form-control-sm mb-2" placeholder="ຫົວຂໍ້ຜົນງານ (ຖ້າບໍ່ໃສ່ ຈະໃຊ້ຊື່ຮ້ານລູກຄ້າເປັນຫົວຂໍ້)" value="<?php echo htmlspecialchars(($editRow && $editRow['title'] !== $editRow['shop_name']) ? $editRow['title'] : ''); ?>">
              <textarea name="description" class="form-control form-control-sm mb-3" rows="3" placeholder="ຂຽນລາຍລະອຽດຜົນງານການຕິດຕັ້ງ, ອຸປະກອນທີ່ໃຊ້ ຫຼື ຄຳບັນຍາຍໂພສ..."><?php echo htmlspecialchars($editRow['description'] ?? ''); ?></textarea>

              <!-- ອັບໂຫຼດຮູບຜົນງານຫຼາຍຮູບພ້ອມກັນ (ແບບ Facebook) -->
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="small text-muted mb-0">3. ຮູບພາບຜົນງານ (ເລືອກຫຼາຍຮູບພ້ອມກັນໄດ້)</label>
                <span class="badge badge-secondary" id="wwPhotoCountBadge">ຍັງບໍ່ໄດ້ເລືອກຮູບ</span>
              </div>
              <input type="file" name="work_photos[]" id="wwPhotosInput" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
              <div class="ww-dropzone" id="wwDropzone">
                <i class="fas fa-cloud-upload-alt d-block"></i>
                <div class="font-weight-bold text-primary">ກົດເລືອກຮູບຜົນງານ (ເລືອກຫຼາຍຮູບພ້ອມກັນໄດ້)</div>
              </div>

              <!-- ຕາຕະລາງສະແດງຮູບທີ່ເລືອກ + ປຸ່ມເພີ່ມຮູບອີກ -->
              <div class="ww-preview-grid" id="wwPreviewGrid" hidden></div>

              <!-- ແຖບສະຖານະກຳລັງອັບໂຫຼດ -->
              <div id="wwProgressWrap" class="mt-3" hidden>
                <div class="progress" style="height: 8px;">
                  <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="wwProgressBar" style="width: 0%"></div>
                </div>
                <small class="text-primary font-weight-bold d-block mt-1 text-center" id="wwProgressText">ກຳລັງອັບໂຫຼດ...</small>
              </div>

              <button type="submit" class="btn btn-primary btn-block mt-3 font-weight-bold" id="wwSubmitBtn">
                <i class="fas fa-paper-plane mr-1"></i> <?php echo $editRow ? 'ບັນທຶກການແກ້ໄຂ' : 'ໂພສຜົນງານ & ໂລໂກ້ລູກຄ້າ'; ?>
              </button>
            </form>
      </div>
    </div>
  </div>
</div>
<script>
// ?edit=ID → ເປີດ modal ແກ້ໄຂເລີຍ; ປິດ modal ແກ້ໄຂ → ກັບໄປໜ້າລາຍການ (ລ້າງຂໍ້ມູນແກ້ໄຂ)
// ສີທີມ: ຄືກັບ shell (localStorage 'WlaoDev.theme_color', ຄ່າເລີ່ມຕົ້ນ #244886) → --ww-theme ຂອງ modal; ປ່ຽນສີໃນແທັບອື່ນ → ອັບເດດ
(function () {
  function shade(hex, p) {
    hex = String(hex).replace('#', ''); if (hex.length !== 6) return '#' + hex;
    var n = [0, 2, 4].map(function (i) { return parseInt(hex.substr(i, 2), 16); });
    return '#' + n.map(function (v) { return ('0' + Math.round(v * (1 + p)).toString(16)).slice(-2); }).join('');
  }
  function paint() {
    var c = '#244886';
    try { c = localStorage.getItem('WlaoDev.theme_color') || c; } catch (e) {}
    var r = document.documentElement;   // ທັງໜ້າ: modal + ປຸ່ມ "ໂພສຜົນງານໃໝ່"
    r.style.setProperty('--ww-theme', c); r.style.setProperty('--ww-theme-dark', shade(c, -0.18));
  }
  paint();
  window.addEventListener('storage', function (e) { if (e.key === 'WlaoDev.theme_color') paint(); });
  $('#wwModal').on('show.bs.modal', paint);
})();
$(function () {
  <?php if ($editRow): ?>
  $('#wwModal').modal({ backdrop: 'static', show: true });
  <?php endif; ?>
});
</script>
<?php require __DIR__ . '/partials/works_js.php'; ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
