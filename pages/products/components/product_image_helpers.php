<?php
// Product image helpers — shared by products.php (single add / edit) and api_product.php
// (bulk-add rows upload their image ahead of the form submit).
// ====== Helper Functions ສຳລັບຈັດການຮູບພາບສິນຄ້າ (ເກັບໄວ້ໃນ assets/product_img) ======
function saveUploadedProductImage($fileInputKey = 'product_img', &$errorMsg = null) {
    if (!isset($_FILES[$fileInputKey]) || empty($_FILES[$fileInputKey]['name'])) {
        return null;
    }
    
    $fileError = $_FILES[$fileInputKey]['error'];
    if ($fileError !== UPLOAD_ERR_OK) {
        if ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
            $errorMsg = 'ຂະໜາດຮູບພາບໃຫຍ່ເກີນກຳນົດຂອງເຊີເວີ';
        } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
            $errorMsg = 'ເກີດຂໍ້ຜິດພາດໃນການອັບໂຫຼດຮູບ (Error Code: ' . $fileError . ')';
        }
        return null;
    }

    $maxFileSize = 10 * 1024 * 1024; // 10MB
    if ($_FILES[$fileInputKey]['size'] > $maxFileSize) {
        $errorMsg = 'ຂະໜາດຮູບພາບໃຫຍ່ເກີນ 10MB';
        return null;
    }

    $fileTmp  = $_FILES[$fileInputKey]['tmp_name'];
    $fileName = $_FILES[$fileInputKey]['name'];
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'jfif', 'avif'];
    if (!in_array($fileExt, $allowed)) {
        $errorMsg = 'ຮູບແບບໄຟລ໌ບໍ່ຖືກຕ້ອງ (ຮອງຮັບ JPG, PNG, WEBP, GIF, JFIF)';
        return null;
    }

    $img_name = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExt;

    // ໂຟນເດີເກັບຮູບພາບ — ບ່ອນດຽວ (assets/product_img). ບໍ່ຕ້ອງກັອບປີ້ໄປໂຟນເດີອື່ນອີກຕໍ່ໄປ.
    $dir = __DIR__ . '/../../../assets/product_img/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    if (!@move_uploaded_file($fileTmp, $dir . $img_name)) {
        $errorMsg = 'ບໍ່ສາມາດບັນທຶກໄຟລ໌ລົງເຊີເວີໄດ້ ກະລຸນາກວດສອບ Permission';
        return null;
    }

    return $img_name;
}

function deleteProductImageFile($imgFile) {
    if (empty($imgFile)) return;
    $filename = basename(trim($imgFile));
    // ປ້ອງກັນບໍ່ໃຫ້ລົບໄຟລ໌ລະບົບ ຫຼື ໄຟລ໌ Default
    $protected = ['image.jpg', 'file.png', '404.png', 'repeat.png', '', '.', '..'];
    if (in_array(strtolower($filename), $protected)) {
        return;
    }
    $filePath = __DIR__ . '/../../../assets/product_img/' . $filename;
    if (file_exists($filePath) && is_file($filePath)) {
        @unlink($filePath);
    }
}

function getProductImagePath($imgFile) {
    if (empty($imgFile)) return null;
    static $imgPathCache = [];
    $key = trim($imgFile);
    if (isset($imgPathCache[$key])) {
        return $imgPathCache[$key];
    }
    $filename = basename($key);
    $res = '../../assets/product_img/' . $filename;
    $imgPathCache[$key] = $res;
    return $res;
}

