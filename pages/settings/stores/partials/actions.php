<?php
/**
 * ສ່ວນ: ການກະທຳ POST: ບັນທຶກຂໍ້ມູນຮ້ານ
 * ອັບໂຫຼດໂລໂກ້/QR, ບັນທຶກ tbcompanyinfo ຂອງສາຂາ, ການຕັ້ງຄ່າ VAT ແລະ ໝາຍເລກຄິວ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/stores/index.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Save Store Profile Info
    if ($action === 'save_store_info') {
        $com_name_la         = trim($_POST['com_name_la'] ?? '');
        $com_address         = trim($_POST['com_address'] ?? '');
        $com_tel             = trim($_POST['com_tel'] ?? '');
        $com_email           = trim($_POST['com_email'] ?? '');
        $tax_id              = trim($_POST['tax_id'] ?? '');
        $receipt_footer      = trim($_POST['receipt_footer'] ?? '');
        $tax_type            = trim($_POST['tax_type'] ?? 'none');
        $vat_percent         = ($tax_type === 'none') ? 0.00 : floatval($_POST['vat_percent'] ?? 0);
        $license_start_date  = trim($_POST['license_start_date'] ?? '2026-09-15');
        $license_expire_date = trim($_POST['license_expire_date'] ?? '2027-09-15');

        $branch_id_save = getActiveStoreId($pdo);

        // Daily-resetting queue number on the receipt (coffee-shop style) — one global on/off.
        // Only the main branch renders (and can submit) this toggle.
        if (isMainBranch($pdo, $branch_id_save)) {
            try {
                $pdo->prepare("INSERT INTO st_system_settings (setting_key, setting_value) VALUES ('queue_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                    ->execute([!empty($_POST['queue_enabled']) ? '1' : '0']);
            } catch (Throwable $e) {}
        }
        $isMainBranchSave = isMainBranch($pdo, $branch_id_save);

        // Every branch now owns its full profile (name/address/phone/tax/receipt-footer/logo/QR)
        // on its own tbcompanyinfo row.
        if ($com_name_la !== '') {
            try {
                $stmtCurComp = $pdo->prepare("SELECT * FROM st_tbcompanyinfo WHERE branch_id = ? LIMIT 1");
                $stmtCurComp->execute([$branch_id_save]);
                $curComp = $stmtCurComp->fetch();

                // The old logo file is removed by cleanupUnusedLogos() once the DB points at the
                // new one (below) — deleting it here could remove a logo another branch shares.
                $logoErr = '';
                $newLogoName = saveStoreImageUpload('logo_img', 'logo_', dirname(__DIR__) . '/../../../assets/img/logo/', $logoErr);

                $qrErr = '';
                $qrDir = dirname(__DIR__) . '/../../../assets/img/qr/';
                $newQrName = saveStoreImageUpload('qr_img', 'qr_', $qrDir, $qrErr);
                if ($newQrName) {
                    $oldQr = basename((string)($curComp['qr_img'] ?? ''));
                    if ($oldQr !== '' && is_file($qrDir . $oldQr)) {
                        @unlink($qrDir . $oldQr);
                    }
                }

                if ($curComp) {
                    $sql = "UPDATE st_tbcompanyinfo SET com_name_la = ?, com_address = ?, com_tel = ?, com_email = ?, tax_id = ?, barcode = ?, tax_type = ?, vat_percent = ?";
                    $params = [$com_name_la, $com_address, $com_tel, $com_email, $tax_id, $receipt_footer, $tax_type, $vat_percent];

                    // The license dates are the shop's own software-subscription dates, not a
                    // per-branch identity field — only the main branch may change them, even
                    // though every branch now saves the rest of its own profile.
                    if ($isMainBranchSave) {
                        $sql .= ", license_start_date = ?, license_expire_date = ?";
                        $params[] = $license_start_date;
                        $params[] = $license_expire_date;
                    }
                    if ($newLogoName) {
                        $sql .= ", img_url = ?";
                        $params[] = $newLogoName;
                    } elseif (($_POST['remove_logo'] ?? '') === '1') {
                        // ປຸ່ມ ✕ ສີແດງເທິງຮູບ: ເອົາໂລໂກ້ອອກ (ໄຟລ໌ເກົ່າ cleanupUnusedLogos() ລຶບໃຫ້ເມື່ອບໍ່ມີໃຜໃຊ້)
                        $sql .= ", img_url = ''";
                    }
                    if ($newQrName) {
                        $sql .= ", qr_img = ?";
                        $params[] = $newQrName;
                    }
                    $sql .= " WHERE Id = ?";
                    $params[] = $curComp['Id'];

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                } else {
                    $logo = $newLogoName ? $newLogoName : 'logo.png';
                    $qr = $newQrName ? $newQrName : '';
                    if ($isMainBranchSave) {
                        $stmt = $pdo->prepare("INSERT INTO st_tbcompanyinfo (com_name_la, com_address, com_tel, com_email, tax_id, barcode, img_url, qr_img, tax_type, vat_percent, license_start_date, license_expire_date, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$com_name_la, $com_address, $com_tel, $com_email, $tax_id, $receipt_footer, $logo, $qr, $tax_type, $vat_percent, $license_start_date, $license_expire_date, $branch_id_save]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO st_tbcompanyinfo (com_name_la, com_address, com_tel, com_email, tax_id, barcode, img_url, qr_img, tax_type, vat_percent, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$com_name_la, $com_address, $com_tel, $com_email, $tax_id, $receipt_footer, $logo, $qr, $tax_type, $vat_percent, $branch_id_save]);
                    }
                }

                // ປ່ຽນໂລໂກ້ແລ້ວ — ລົບໄຟລ໌ເກົ່າທີ່ບໍ່ມີສາຂາໃດໃຊ້ແລ້ວອອກຈາກໂຟນເດີ
                if ($newLogoName) {
                    cleanupUnusedLogos($pdo);
                }

                // tbstore's own name/address/tel stays in sync with this branch's profile.
                $stmtStore = $pdo->prepare("UPDATE st_tbstore SET store_name = ?, address = ?, tel = ? WHERE store_id = ?");
                $stmtStore->execute([$com_name_la, $com_address, $com_tel, $branch_id_save]);

                // Text fields are saved either way; a rejected image must say so instead of
                // reporting plain success while the old logo keeps showing.
                $imgErrors = array_filter([
                    $logoErr !== '' ? 'ໂລໂກ້: ' . $logoErr : '',
                    $qrErr !== '' ? 'QR: ' . $qrErr : '',
                ]);
                if ($imgErrors) {
                    $message = 'ບັນທຶກຂໍ້ມູນຮ້ານແລ້ວ ແຕ່ຮູບບໍ່ຖືກປ່ຽນ — ' . implode('; ', $imgErrors);
                    $message_type = 'warning';
                } else {
                    $message = 'ບັນທຶກຂໍ້ມູນຮ້ານຄ້າສຳເລັດແລ້ວ!';
                    $message_type = 'success';
                }
                logActivity($pdo, "ແກ້ໄຂຂໍ້ມູນຮ້ານຄ້າ", $com_name_la);
            } catch (Exception $e) {
                $message = 'ຜິດພາດ: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = 'ກະລຸນາປ້ອນຊື່ຮ້ານຄ້າ!';
            $message_type = 'warning';
        }
    }

    // 2. Save / Update Bank Account & Bank QR Code
    if ($action === 'save_bank_account') {
        $bank_id        = intval($_POST['bank_id'] ?? 0);
        $bank_name      = trim($_POST['bank_name'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $account_name   = trim($_POST['account_name'] ?? '');
        $bank_code      = trim($_POST['bank_code'] ?? 'BCEL');
        $is_active      = intval($_POST['is_active'] ?? 1);

        $bank_logo = null;
        if (isset($_FILES['bank_logo']) && !empty($_FILES['bank_logo']['name'])) {
            $ext = pathinfo($_FILES['bank_logo']['name'], PATHINFO_EXTENSION);
            $newLogo = 'bank_' . time() . '_' . rand(100,999) . '.' . $ext;
            $uploadDir = dirname(__DIR__) . '/../../../assets/img/banks/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($_FILES['bank_logo']['tmp_name'], $uploadDir . $newLogo)) {
                $bank_logo = $newLogo;
            }
        }

        $qr_code_img = null;
        if (isset($_FILES['qr_code_img']) && !empty($_FILES['qr_code_img']['name'])) {
            $ext = pathinfo($_FILES['qr_code_img']['name'], PATHINFO_EXTENSION);
            $newQr = 'qr_bank_' . time() . '_' . rand(100,999) . '.' . $ext;
            $uploadDir = dirname(__DIR__) . '/../../../assets/img/qr/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($_FILES['qr_code_img']['tmp_name'], $uploadDir . $newQr)) {
                $qr_code_img = $newQr;
            }
        }

        try {
            if ($bank_id > 0) {
                $sql = "UPDATE st_bank_accounts SET bank_name = ?, account_number = ?, account_name = ?, bank_code = ?, is_active = ?";
                $params = [$bank_name, $account_number, $account_name, $bank_code, $is_active];

                if ($bank_logo) {
                    $sql .= ", bank_logo = ?";
                    $params[] = $bank_logo;
                }
                if ($qr_code_img) {
                    $sql .= ", qr_code_img = ?";
                    $params[] = $qr_code_img;
                }
                $sql .= " WHERE id = ?";
                $params[] = $bank_id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = "ອັບເດດຂໍ້ມູນບັນຊີທະນາຄານ \"$bank_name\" ສຳເລັດແລ້ວ!";
                $message_type = "success";
            } else {
                $stmt = $pdo->prepare("INSERT INTO st_bank_accounts (bank_name, account_number, account_name, bank_code, bank_logo, qr_code_img, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$bank_name, $account_number, $account_name, $bank_code, $bank_logo, $qr_code_img, $is_active]);
                $message = "ເພີ່ມບັນຊີທະນາຄານ \"$bank_name\" ສຳເລັດແລ້ວ!";
                $message_type = "success";
            }
        } catch (Exception $e) {
            $message = "ຜິດພາດ: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // 3. Delete Bank Account
    if ($action === 'delete_bank_account') {
        $bank_id = intval($_POST['bank_id'] ?? 0);
        if ($bank_id > 0) {
            try {
                // Fetch image filenames before deleting
                $bankRow = $pdo->prepare("SELECT bank_logo, qr_code_img FROM st_bank_accounts WHERE id = ?");
                $bankRow->execute([$bank_id]);
                $bankData = $bankRow->fetch(PDO::FETCH_ASSOC);

                $pdo->prepare("DELETE FROM st_bank_accounts WHERE id = ?")->execute([$bank_id]);

                // Delete image files from filesystem
                if ($bankData) {
                    $bankLogoDir = dirname(__DIR__) . '/../../../assets/img/banks/';
                    $qrDir       = dirname(__DIR__) . '/../../../assets/img/qr/';

                    if (!empty($bankData['bank_logo']) && file_exists($bankLogoDir . $bankData['bank_logo'])) {
                        @unlink($bankLogoDir . $bankData['bank_logo']);
                    }
                    if (!empty($bankData['qr_code_img']) && file_exists($qrDir . $bankData['qr_code_img'])) {
                        @unlink($qrDir . $bankData['qr_code_img']);
                    }
                }

                $message = "ລົບບັນຊີທະນາຄານສຳເລັດແລ້ວ!";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "ຜິດພາດ: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

