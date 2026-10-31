<?php
/**
 * ສ່ວນ: ການກະທຳ: ລົບສິນຄ້າ
 * ລົບສິນຄ້າ (ແລະ ຮູບພາບ) ຂອງສາຂາທີ່ກຳລັງເບິ່ງ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/products.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

        $id = intval($_POST['product_id'] ?? 0);
        if ($id > 0) {
            try {
                $userStoreId = intval($_SESSION['store_id'] ?? 1);
                $isMain = isMainBranch($pdo, $userStoreId);
                $canAllBranches = canViewAllBranches($pdo);

                // Delete associated image file
                $curStmt = ($canAllBranches)
                    ? $pdo->prepare("SELECT img_url FROM st_products WHERE product_id = ?")
                    : $pdo->prepare("SELECT img_url FROM st_products WHERE product_id = ? AND store_id = ?");
                if ($canAllBranches) {
                    $curStmt->execute([$id]);
                } else {
                    $curStmt->execute([$id, $userStoreId]);
                }
                $currentImg = $curStmt->fetchColumn();
                if ($currentImg) {
                    deleteProductImageFile($currentImg);
                }

                // Delete associated units in product_units
                $delUnitsStmt = $pdo->prepare("DELETE FROM st_product_units WHERE product_id = ?");
                $delUnitsStmt->execute([$id]);

                $stmt = ($canAllBranches)
                    ? $pdo->prepare("DELETE FROM st_products WHERE product_id = ?")
                    : $pdo->prepare("DELETE FROM st_products WHERE product_id = ? AND store_id = ?");
                if ($canAllBranches) {
                    $stmt->execute([$id]);
                } else {
                    $stmt->execute([$id, $userStoreId]);
                }
                $message = tl('ລົບສິນຄ້າສຳເລັດ!');
                $message_type = 'success';
                logActivity($pdo, "ລົບສິນຄ້າ", "ID: $id");
            } catch (Exception $e) {
                $message = (tl('ຜິດພາດ (ອາດມີຂໍ້ມູນທີ່ກ່ຽວຂ້ອງ):') . ' ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
