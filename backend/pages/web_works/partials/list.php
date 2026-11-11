<?php
/*
 * ສ່ວນ: ລາຍການຜົນງານແບບກະທັດຮັດ (1 ແຖວ / ຮ້ານ) + ແບ່ງໜ້າ — ຖືກ require ຈາກ pages/web_works/index.php
 * ທັງຕອນໂຫຼດໜ້າ ແລະ ຕອນຄົ້ນຫາສົດ (?ajax=1 → JSON {rows, pager, total, page}).
 * ໃຊ້ຕົວແປຈາກ index.php: $pdo, $base_path, $clientsDir, $worksBaseDir. ສ້າງ: $wwRows, $wwPager, $wwTotal, $wwPage
 * ຮູບຕົວຢ່າງນ້ອຍ (120px) ເກັບໄວ້ທີ່ assets/img/works/_thumbs/ — ບໍ່ໂຫຼດຮູບເຕັມ 200KB+ ທຸກແຖວ.
 */
require_once dirname(__DIR__, 2) . '/web_orders/partials/pager.php';

if (!function_exists('wwThumbUrl')) {
    // ຮູບນ້ອຍ 120px (JPEG) ສ້າງຄັ້ງດຽວ ແລ້ວໃຊ້ຊ້ຳ; ສ້າງບໍ່ໄດ້ (ບໍ່ມີ GD / ໄຟລ໌ເສຍ) → ໃຊ້ຮູບເດີມ
    function wwThumbUrl(string $worksBaseDir, string $base_path, string $slug, string $fn): string
    {
        $src = $worksBaseDir . '/' . $slug . '/' . $fn;
        $orig = $base_path . 'assets/img/works/' . rawurlencode($slug) . '/' . rawurlencode($fn);
        $tDir = $worksBaseDir . '/_thumbs';
        $tName = preg_replace('~[^A-Za-z0-9_.-]~', '_', $slug . '__' . pathinfo($fn, PATHINFO_FILENAME)) . '.jpg';
        $tFile = $tDir . '/' . $tName;
        if (!is_file($tFile) || filemtime($tFile) < filemtime($src)) {
            if (!function_exists('imagecreatefromstring')) return $orig;
            $im = @imagecreatefromstring((string)@file_get_contents($src));
            if (!$im) return $orig;
            $w = imagesx($im); $h = imagesy($im); $size = 120;
            $s = min($w, $h);   // ຕັດກາງເປັນສີ່ຫຼ່ຽມ
            $th = imagecreatetruecolor($size, $size);
            imagecopyresampled($th, $im, 0, 0, (int)(($w - $s) / 2), (int)(($h - $s) / 2), $size, $size, $s, $s);
            if (!is_dir($tDir)) @mkdir($tDir, 0775, true);
            @imagejpeg($th, $tFile, 78);
            imagedestroy($im); imagedestroy($th);
            if (!is_file($tFile)) return $orig;
        }
        return $base_path . 'assets/img/works/_thumbs/' . rawurlencode($tName) . '?v=' . filemtime($tFile);
    }
}

$wwQ = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$wwCat = trim((string)($_GET['cat'] ?? ''));
$wwPerPage = 10;
$wwPage = max(1, (int)($_GET['page'] ?? 1));
$where = '1=1';
$params = [];
if ($wwQ !== '') { $where .= ' AND (shop_name LIKE ? OR title LIKE ? OR description LIKE ?)'; array_push($params, "%$wwQ%", "%$wwQ%", "%$wwQ%"); }
if ($wwCat !== '') { $where .= ' AND category = ?'; $params[] = $wwCat; }
$cnt = $pdo->prepare("SELECT COUNT(*) FROM w_web_works WHERE $where");
$cnt->execute($params);
$wwTotal = (int)$cnt->fetchColumn();
$wwPages = max(1, (int)ceil($wwTotal / $wwPerPage));
$wwPage = min($wwPage, $wwPages);
$st = $pdo->prepare("SELECT * FROM w_web_works WHERE $where ORDER BY id DESC LIMIT $wwPerPage OFFSET " . (($wwPage - 1) * $wwPerPage));
$st->execute($params);
$wwList = $st->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (!$wwList): ?>
  <div class="ww-empty"><?php echo ($wwQ !== '' || $wwCat !== '') ? 'ບໍ່ພົບຜົນງານທີ່ຄົ້ນຫາ' : 'ຍັງບໍ່ມີຜົນງານ ຫຼື ໂລໂກ້ລູກຄ້າ — ເລີ່ມອັບໂຫຼດຈາກຟອມດ້ານຊ້າຍໄດ້ເລີຍ'; ?></div>
<?php endif;
foreach ($wwList as $row):
  $wid = (int)$row['id'];
  $slug = (string)$row['slug'];
  $hasLogo = !empty($row['logo_img']) && is_file($clientsDir . '/' . basename($row['logo_img']));
  $photos = [];
  foreach (json_decode((string)$row['photos_json'], true) ?: [] as $fn) {
      $fn = basename($fn);
      if ($fn !== '' && is_file($worksBaseDir . '/' . $slug . '/' . $fn)) $photos[] = $fn;
  }
  $nPhotos = count($photos);
  $shown = array_slice($photos, 0, 5);
  $photoList = [];
  foreach ($photos as $fn) {
      $photoList[] = [
          'full' => $base_path . 'assets/img/works/' . rawurlencode($slug) . '/' . rawurlencode($fn),
          'thumb' => wwThumbUrl($worksBaseDir, $base_path, $slug, $fn),
      ];
  }
?>
  <div class="ww-row" data-photos='<?php echo htmlspecialchars(json_encode($photoList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>' data-shop="<?php echo htmlspecialchars($row['shop_name'], ENT_QUOTES, 'UTF-8'); ?>">
    <div class="ww-row-main">
      <div class="ww-row-logo">
        <?php if ($hasLogo): ?><img src="<?php echo $base_path . 'assets/img/clients/' . rawurlencode(basename($row['logo_img'])); ?>" alt="" loading="lazy">
        <?php else: ?><span><?php echo htmlspecialchars(mb_substr($row['shop_name'], 0, 1)); ?></span><?php endif; ?>
      </div>
      <div class="ww-row-info">
        <b><?php echo htmlspecialchars($row['shop_name']); ?></b>
        <small>
          <span class="ww-cat"><?php echo htmlspecialchars($row['category']); ?></span>
          <?php if (!empty($row['work_date'])): ?> · <?php echo date('d/m/Y', strtotime($row['work_date'])); ?><?php endif; ?>
          · <?php echo $nPhotos; ?> ຮູບ<?php echo $hasLogo ? '' : ' · <span class="text-danger">ບໍ່ມີໂລໂກ້</span>'; ?>
        </small>
      </div>
      <button type="button" class="ww-thumbs" title="ເບິ່ງ / ລຶບຮູບທັງໝົດ" data-toggle-photos="<?php echo $wid; ?>"<?php echo $nPhotos ? '' : ' disabled'; ?>>
        <?php foreach ($shown as $i => $fn): ?>
          <span class="ww-thumb" data-thumb-index="<?php echo $i; ?>"><img src="<?php echo htmlspecialchars(wwThumbUrl($worksBaseDir, $base_path, $slug, $fn)); ?>" alt="" loading="lazy">
            <?php if ($i === count($shown) - 1 && $nPhotos > count($shown)): ?><em>+<?php echo $nPhotos - count($shown); ?></em><?php endif; ?>
          </span>
        <?php endforeach; ?>
      </button>
      <div class="ww-acts">
        <label class="ww-ico is-add" title="ເພີ່ມຮູບ (ເລືອກຫຼາຍຮູບພ້ອມກັນໄດ້)">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
          <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden data-quick-add-input="<?php echo $wid; ?>">
        </label>
        <a class="ww-ico is-edit" href="index.php?edit=<?php echo $wid; ?>" title="ແກ້ໄຂ">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
        </a>
        <form method="post" data-swal-confirm="ທ່ານຕ້ອງການລຶບຜົນງານ «<?php echo htmlspecialchars($row['shop_name']); ?>» ພ້ອມຮູບທັງໝົດ <?php echo $nPhotos; ?> ຮູບແທ້ບໍ? ການກະທຳນີ້ບໍ່ສາມາດຍົກເລີກໄດ້." data-swal-title="ຢືນຢັນລຶບຜົນງານ?" data-swal-danger>
          <input type="hidden" name="action" value="delete_work">
          <input type="hidden" name="work_id" value="<?php echo $wid; ?>">
          <button type="submit" class="ww-ico is-del" title="ລຶບ">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
          </button>
        </form>
      </div>
    </div>
    <?php if ($nPhotos): ?>
      <div class="ww-row-photos" id="wwAll<?php echo $wid; ?>" hidden>
        <?php foreach ($photos as $idx => $fn): $full = $base_path . 'assets/img/works/' . rawurlencode($slug) . '/' . rawurlencode($fn); ?>
          <div class="ww-row-photo">
            <a href="<?php echo htmlspecialchars($full); ?>" class="ww-photo-link" data-index="<?php echo $idx; ?>" title="ຄລິກເບິ່ງຮູບ"><img src="<?php echo htmlspecialchars(wwThumbUrl($worksBaseDir, $base_path, $slug, $fn)); ?>" alt="" loading="lazy"></a>
            <form method="post" data-swal-confirm="ທ່ານຕ້ອງການລຶບຮູບນີ້ອອກຈາກຜົນງານ «<?php echo htmlspecialchars($row['shop_name']); ?>» ແທ້ບໍ?" data-swal-title="ຢືນຢັນລຶບຮູບ?" data-swal-danger>
              <input type="hidden" name="action" value="delete_photo">
              <input type="hidden" name="work_id" value="<?php echo $wid; ?>">
              <input type="hidden" name="photo_name" value="<?php echo htmlspecialchars($fn); ?>">
              <button type="submit" title="ລຶບຮູບນີ້">&times;</button>
            </form>
          </div>
        <?php endforeach; ?>
        <a class="ww-row-site" href="../../../pages/work.php?w=<?php echo rawurlencode($slug); ?>" target="_blank">ເບິ່ງໃນໜ້າເວັບໄຊ ↗</a>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach;
$wwRows = ob_get_clean();
$wwPager = woPagerHtml($wwPage, $wwPages, $wwTotal, 'ຮ້ານ');
