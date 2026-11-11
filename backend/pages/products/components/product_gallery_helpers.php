<?php
/**
 * ສ່ວນ: ຮູບສິນຄ້າຫຼາຍມຸມ (w_product_images, migration v39) — ຮູບຫຼັກຍັງເປັນ w_products.img_url.
 * ຟອມເພີ່ມ/ແກ້ໄຂສິນຄ້າ ສົ່ງ <input type="file" name="gallery_imgs[]" multiple> (ເລືອກຫຼາຍຮູບພ້ອມກັນ),
 * ແກ້ໄຂ: ຕິກລຶບຮູບເກົ່າດ້ວຍ del_gallery[] (id). ໄຟລ໌ເກັບໃນ assets/product_img/ ຄືຮູບຫຼັກ (saveUploadedProductImage
 * ປັບເປັນ 600×600 ໃຫ້). ໃຊ້ໂດຍ actions/add_product.php, edit_product.php, delete_product.php ແລະ api/products_api.php.
 * ຕ້ອງ require ຫຼັງ product_image_helpers.php.
 */

const PRODUCT_GALLERY_MAX = 10;   // ຮູບເພີ່ມເຕີມສູງສຸດຕໍ່ສິນຄ້າ

/** ຮູບເພີ່ມເຕີມຂອງສິນຄ້າ (ລຽງຕາມລຳດັບ) */
function getProductGallery(PDO $pdo, int $productId): array
{
    try {
        $st = $pdo->prepare("SELECT id, img_file, sort_order FROM w_product_images WHERE product_id = ? ORDER BY sort_order, id");
        $st->execute([$productId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/** ບັນທຶກຮູບທີ່ອັບໂຫຼດມາໃນ $_FILES['gallery_imgs'] (ຫຼາຍໄຟລ໌). ຄືນຈຳນວນທີ່ບັນທຶກໄດ້; ຂໍ້ຜິດພາດ → $errorMsg */
function saveUploadedProductGallery(PDO $pdo, int $productId, ?string &$errorMsg = null): int
{
    $f = $_FILES['gallery_imgs'] ?? null;
    if ($productId <= 0 || !$f || !is_array($f['name'] ?? null)) {
        return 0;
    }
    $st = $pdo->prepare("SELECT COUNT(*), COALESCE(MAX(sort_order), 0) FROM w_product_images WHERE product_id = ?");
    $st->execute([$productId]);
    [$have, $order] = array_map('intval', $st->fetch(PDO::FETCH_NUM));
    $ins = $pdo->prepare("INSERT INTO w_product_images (product_id, img_file, sort_order) VALUES (?, ?, ?)");
    $saved = 0;
    $skipped = 0;
    foreach ($f['name'] as $i => $name) {
        if ($name === '' || (int)$f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($have + $saved >= PRODUCT_GALLERY_MAX) {
            $skipped++;
            continue;
        }
        // saveUploadedProductImage() ອ່ານໄຟລ໌ດຽວ → ຈຳລອງເປັນ key ຊົ່ວຄາວ
        $_FILES['__gallery_one'] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        $err = null;
        $file = saveUploadedProductImage('__gallery_one', $err);
        unset($_FILES['__gallery_one']);
        if ($file === null) {
            $errorMsg = $err ?: $errorMsg;
            continue;
        }
        $ins->execute([$productId, $file, ++$order]);
        $saved++;
    }
    if ($skipped) {
        $errorMsg = tl('ຮູບເພີ່ມເຕີມໄດ້ສູງສຸດ') . ' ' . PRODUCT_GALLERY_MAX . ' ' . tl('ຮູບຕໍ່ສິນຄ້າ');
    }
    return $saved;
}

/** ລຶບຮູບເພີ່ມເຕີມ (ໄຟລ໌ + ແຖວ). $ids = null → ລຶບທັງໝົດຂອງສິນຄ້າ (ຕອນລຶບສິນຄ້າ) */
function deleteProductGallery(PDO $pdo, int $productId, ?array $ids = null): void
{
    $rows = getProductGallery($pdo, $productId);
    if ($ids !== null) {
        $ids = array_map('intval', $ids);
        $rows = array_filter($rows, fn($r) => in_array((int)$r['id'], $ids, true));
    }
    if (!$rows) {
        return;
    }
    $del = $pdo->prepare("DELETE FROM w_product_images WHERE id = ? AND product_id = ?");
    // ໄຟລ໌ດຽວກັນອາດຍັງໃຊ້ຢູ່: ຮູບເພີ່ມເຕີມຂອງສິນຄ້າ/ສາຂາອື່ນ ຫຼື ເປັນຮູບຫຼັກ (img_url) — ເຊັ່ນ ຮູບທຳອິດທີ່ຖືກຍ້າຍເປັນຮູບຫຼັກ
    $used = $pdo->prepare("SELECT (SELECT COUNT(*) FROM w_product_images WHERE img_file = ? AND id <> ?)
                                + (SELECT COUNT(*) FROM w_products WHERE img_url = ?)");
    foreach ($rows as $r) {
        $del->execute([(int)$r['id'], $productId]);
        $used->execute([$r['img_file'], (int)$r['id'], $r['img_file']]);
        if (!$used->fetchColumn()) {
            deleteProductImageFile($r['img_file']);
        }
    }
}
