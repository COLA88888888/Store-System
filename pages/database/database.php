<?php
// ============================================================
// database.php - DATABASE MANAGEMENT & BACKUP PAGE
// ໜ້າຈັດການຖານຂໍ້ມູນ & สำຮອງ/ຟື້ນຟູຂໍ້ມູນລະບົບ
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base_path = '../../';

// Load Database Connection
require_once __DIR__ . '/../../config/db.php';

// Check permissions (ກ່ອນ header — ບໍ່ສົ່ງ HTML ຂອງໜ້າອອກໄປຫາຜູ້ທີ່ບໍ່ມີສິດ)
if (empty($_SESSION['user_id']) || (!hasPermission('database'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}
require_once __DIR__ . '/../../layouts/header.php';

// ສາຂາ: ຜູ້ທີ່ເຫັນທຸກສາຂາ ເລືອກສຳຮອງ "ທັງໝົດ" ຫຼື ສາຂາໃດໜຶ່ງ; ຜູ້ໃຊ້ສາຂາຍ່ອຍ ສຳຮອງ/ເຫັນ ສະເພາະສາຂາຕົນເອງ
$dbCanAllBranches = canViewAllBranches($pdo);
$dbOwnStoreId = (int)getActiveStoreId($pdo);
$dbStores = [];
foreach ($pdo->query('SELECT store_id, store_name, status FROM st_tbstore ORDER BY store_id ASC')->fetchAll(PDO::FETCH_ASSOC) as $st) {
    $dbStores[(int)$st['store_id']] = $st;
}

// Scan Backup Files in backups/ directory
$backupDir = __DIR__ . '/../../backups/';
$backupFiles = [];
if (file_exists($backupDir)) {
    $files = glob($backupDir . '*.sql');
    if (!empty($files)) {
        rsort($files); // Latest first
        foreach ($files as $f) {
            // ແຖວທຳອິດ "-- StoreMini branch backup: store_id=N" = ໄຟລ໌ສາຂາ (api/database_backend.php)
            $fh = @fopen($f, 'rb');
            $first = $fh ? (string)fgets($fh, 256) : '';
            if ($fh) fclose($fh);
            $fileStore = preg_match('/^-- StoreMini branch backup: store_id=(\d+)/', $first, $m) ? (int)$m[1] : 0;
            if (!$dbCanAllBranches && $fileStore !== $dbOwnStoreId) continue;
            $backupFiles[] = [
                'name' => basename($f),
                'size' => number_format(filesize($f) / 1024, 2) . ' KB',
                'time' => date('d/m/Y H:i:s', filemtime($f)),
                'store_id' => $fileStore,
                'store_name' => $fileStore > 0 ? ($dbStores[$fileStore]['store_name'] ?? ('#' . $fileStore)) : '',
            ];
        }
    }
}
?>

<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/pages/database-backup.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/pages/database-backup.css'); ?>">

<div class="container-fluid backup-page">
    <!-- Header Title & Compact Action Buttons -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h4 class="font-weight-bold text-dark mb-1">
                <i class="fas fa-database text-info mr-2"></i><?php echo htmlspecialchars(t('database.title', 'ສຳຮອງຖານຂໍ້ມູນ')); ?>
            </h4>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars(t('database.subtitle', 'ສ້າງໄຟລ໌ສຳຮອງ Backup')); ?></p>
        </div>
        <div class="page-actions mt-2 mt-md-0"><?php /* ຊິດຂວາສະເໝີ ລວມທັງໂທລະສັບ (03-controls.css) */ ?>
            <button class="btn btn-primary-custom btn-sm font-weight-bold shadow-sm px-3 py-2" style="border-radius: 6px; font-size: 0.82rem;" onclick="createBackup()">
                <i class="fas fa-file-download mr-1"></i> <?php echo htmlspecialchars(t('database.btn_create_backup', 'ສ້າງໄຟລ໌ສຳຮອງ')); ?>
            </button>
            <button class="btn btn-secondary-custom btn-sm font-weight-bold shadow-sm px-3 py-2" style="border-radius: 6px; font-size: 0.82rem;" onclick="$('#uploadRestoreModal').modal('show')">
                <i class="fas fa-file-upload mr-1"></i> <?php echo htmlspecialchars(t('database.btn_upload', 'ອັບໂຫຼດ')); ?>
            </button>
        </div>
    </div>

    <!-- Backup Files Grid Section (Card View Layout) -->
    <!-- ລາຍການໄຟລ໌ສຳຮອງ: ຫົວຂໍ້ທຳມະດາ + ແຕ່ລະໄຟລ໌ເປັນກາດຂອງມັນເອງ (CSS: .backup-row ໃນ assets/css/pages/database-backup.css) -->
    <div class="backup-section mb-4">
        <div class="backup-section-head">
            <h5 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-history text-primary mr-2"></i> <?php echo htmlspecialchars(t('database.list_title', 'ລາຍການໄຟລ໌ສຳຮອງຖານຂໍ້ມູນ')); ?>
            </h5>
        </div>
        <div>
            <?php if (!empty($backupFiles)): ?>
                <div class="backup-list">
                    <?php foreach ($backupFiles as $bf): ?>
                            <div class="backup-row">
                                <div class="backup-row-icon"><i class="fas fa-database"></i></div>
                                <div class="backup-row-info">
                                    <div class="backup-row-name" title="<?php echo htmlspecialchars($bf['name']); ?>"><?php echo htmlspecialchars($bf['name']); ?></div>
                                    <div class="backup-row-meta">
                                        <span><i class="far fa-clock mr-1"></i><?php echo $bf['time']; ?></span>
                                        <span><i class="fas fa-hdd mr-1"></i><?php echo $bf['size']; ?></span>
                                        <span class="badge badge-success px-2 py-1" style="font-size: 0.7rem; border-radius: 6px;"><i class="fas fa-check-circle mr-1"></i>SQL</span>
                                        <?php if ($bf['store_id'] > 0): ?>
                                            <span class="badge badge-info px-2 py-1" style="font-size: 0.7rem; border-radius: 6px;"><i class="fas fa-store mr-1"></i><?php echo htmlspecialchars($bf['store_name']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-primary px-2 py-1" style="font-size: 0.7rem; border-radius: 6px;"><i class="fas fa-globe mr-1"></i><?php echo htmlspecialchars(t('database.scope_all', 'ທັງໝົດ')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="backup-row-actions">
                                    <!-- Download Button -->
                                    <a href="<?php echo $base_path; ?>api/database_backend.php?action=download_backup&file=<?php echo urlencode($bf['name']); ?>"
                                       class="btn-backup-action download flex-fill" title="<?php echo htmlspecialchars(t('database.download_title', 'ດາວໂຫຼດໄຟລ໌')); ?>">
                                        <i class="fas fa-download mr-1"></i> <?php echo htmlspecialchars(t('database.download', 'ດາວໂຫຼດ')); ?>
                                    </a>
                                    <!-- Restore Button -->
                                    <button type="button" class="btn-backup-action restore flex-fill"
                                            onclick="restoreBackup('<?php echo htmlspecialchars($bf['name']); ?>', <?php echo htmlspecialchars(json_encode($bf['store_name'], JSON_UNESCAPED_UNICODE)); ?>)" title="<?php echo htmlspecialchars(t('database.restore_title', 'ຟື້ນຟູຖານຂໍ້ມູນ')); ?>">
                                        <i class="fas fa-undo mr-1"></i> <?php echo htmlspecialchars(t('database.restore', 'ຟື້ນຟູ')); ?>
                                    </button>
                                    <!-- Delete Button -->
                                    <button type="button" class="btn-backup-action delete"
                                            onclick="deleteBackup('<?php echo htmlspecialchars($bf['name']); ?>')" title="<?php echo htmlspecialchars(t('database.delete_title', 'ລົບໄຟລ໌')); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="backup-empty text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-3x mb-3 text-secondary" style="opacity: 0.5;"></i>
                    <h6 class="font-weight-bold mb-1"><?php echo htmlspecialchars(t('database.no_backups', 'ຍັງບໍ່ມີໄຟລ໌ສຳຮອງຖານຂໍ້ມູນ')); ?></h6>
                    <p class="small text-muted mb-0"><?php echo t('database.no_backups_hint', 'ກົດປຸ່ມ <strong>"ສ້າງໄຟລ໌ສຳຮອງ"</strong> ດ້ານເທິງເພື່ອເລີ່ມສ້າງໄຟລ໌ສຳຮອງ'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL: Upload & Restore SQL File -->
<div class="modal fade" id="uploadRestoreModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-primary text-white border-0 px-4 py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-file-upload mr-2"></i> <?php echo htmlspecialchars(t('database.modal_title', 'ອັບໂຫຼດ ແລະ ຟື້ນຟູຖານຂໍ້ມູນ')); ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="uploadRestoreForm" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 mb-3" style="border-radius: 10px; font-size: 0.88rem;">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong><?php echo htmlspecialchars(t('database.warning_label', 'ຄຳເຕືອນ:')); ?></strong> <?php echo htmlspecialchars(t('database.warning_text', 'ການຟື້ນຟູຖານຂໍ້ມູນຈະຂຽນທັບຂໍ້ມູນເກົ່າທັງໝົດ! ກະລຸນາກວດສອບໄຟລ໌ `.sql` ໃຫ້ຖືກຕ້ອງກ່ອນດຳເນີນການ.')); ?>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark"><?php echo htmlspecialchars(t('database.select_file_label', 'ເລືອກໄຟລ໌ຖານຂໍ້ມູນ (.sql):')); ?></label>
                        <input type="file" name="backup_file" id="backup_file" class="form-control-file border p-2 rounded" accept=".sql" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-dismiss="modal"><?php echo htmlspecialchars(t('database.cancel', 'ຍົກເລີກ')); ?></button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4">
                        <i class="fas fa-sync-alt mr-1"></i> <?php echo htmlspecialchars(t('database.btn_start_restore', 'ເລີ່ມຟື້ນຟູຂໍ້ມູນ')); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var I18N_DATABASE = <?php echo tjson([
    'database.creating_backup_title' => 'ກຳລັງສ້າງໄຟລ໌ສຳຮອງ...',
    'database.please_wait' => 'ກະລຸນາລໍຖ້າຈັກຄູ່',
    'database.success_title' => 'ສຳເລັດ!',
    'database.error_title' => 'ຜິດພາດ!',
    'database.err_connect' => 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ກັບ Server',
    'database.confirm_restore_title' => 'ຢືນຢັນການຟື້ນຟູ?',
    'database.confirm_restore_text' => 'ທ່ານຕ້ອງການຟື້ນຟູຖານຂໍ້ມູນຈາກໄຟລ໌ "%s" ແທ້ບໍ? ຂໍ້ມູນປັດຈຸບັນຈະຖືກແທນທີ່!',
    'database.confirm_restore_btn' => '<i class="fas fa-check"></i> ຢືນຢັນຟື້ນຟູ',
    'database.cancel' => 'ຍົກເລີກ',
    'database.restoring_title' => 'ກຳລັງຟື້ນຟູຂໍ້ມູນ...',
    'database.restore_success_title' => 'ຟື້ນຟູສຳເລັດ!',
    'database.err_restore' => 'ເກີດຂໍ້ຜິດພາດໃນການຟື້ນຟູຖານຂໍ້ມູນ',
    'database.confirm_delete_title' => 'ຢືນຢັນການລົບ?',
    'database.confirm_delete_text' => 'ທ່ານຕ້ອງການລົບໄຟລ໌ສຳຮອງ "%s" ນີ້ແທ້ບໍ?',
    'database.confirm_delete_btn' => '<i class="fas fa-trash"></i> ຢືນຢັນລົບ',
    'database.delete_success_title' => 'ລົບສຳເລັດ!',
    'database.uploading_title' => 'ກຳລັງອັບໂຫຼດ ແລະ ຟື້ນຟູ...',
    'database.err_upload' => 'ເກີດຂໍ້ຜິດພາດໃນການອັບໂຫຼດໄຟລ໌',
    'database.pick_scope_title' => 'ສຳຮອງຂໍ້ມູນສາຂາໃດ?',
    'database.pick_scope_all' => 'ທັງໝົດ (ທຸກສາຂາ + ການຕັ້ງຄ່າລະບົບ)',
    'database.pick_scope_hint' => 'ໄຟລ໌ສາຂາ: ຟື້ນຟູແລ້ວແທນທີ່ສະເພາະຂໍ້ມູນຂອງສາຂານັ້ນ, ສາຂາອື່ນບໍ່ຖືກກະທົບ',
    'database.btn_continue' => 'ສ້າງໄຟລ໌ສຳຮອງ',
    'database.branch_inactive' => '(ປິດໃຊ້ງານ)',
    'database.confirm_restore_branch_text' => 'ຟື້ນຟູຂໍ້ມູນຂອງສາຂາ "%b" ຈາກໄຟລ໌ "%s"? ຂໍ້ມູນປັດຈຸບັນຂອງສາຂານີ້ຈະຖືກແທນທີ່ — ສາຂາອື່ນບໍ່ຖືກກະທົບ.',
]); ?>;

// ສາຂາທີ່ເລືອກສຳຮອງໄດ້ (ຫວ່າງ = ບໍ່ຕ້ອງເລືອກ: ມີສາຂາດຽວ ຫຼື ຜູ້ໃຊ້ສາຂາຍ່ອຍ ທີ່ຖືກລັອກໄວ້ທີ່ສາຂາຕົນເອງ)
var DB_BACKUP_STORES = <?php
    $dbPick = [];
    if ($dbCanAllBranches && count($dbStores) > 1) {
        foreach ($dbStores as $sid => $st) {
            $dbPick[] = ['id' => $sid, 'name' => $st['store_name'] . ($st['status'] !== 'active' ? ' ' . t('database.branch_inactive', '(ປິດໃຊ້ງານ)') : '')];
        }
    }
    echo json_encode($dbPick, JSON_UNESCAPED_UNICODE);
?>;

// ຜິດພາດ / ຟື້ນຟູບໍ່ຄົບ: ສະແດງລາຍການຕາຕະລາງທີ່ຂາດ ແຖວລະອັນ (res.partial = ຟື້ນຟູແລ້ວແຕ່ບໍ່ຄົບຄືຕອນສຳຮອງ)
function dbShowFail(res) {
    var $box = $('<div style="white-space: pre-line; text-align: left; font-size: 0.88rem;"></div>').text(res.message || '');
    Swal.fire({
        icon: res.partial ? 'warning' : 'error',
        title: I18N_DATABASE['database.error_title'],
        html: $box[0].outerHTML,
        confirmButtonColor: '#2563eb'
    });
}

// 1. Create Backup Action — ມີຫຼາຍສາຂາ: ຖາມກ່ອນວ່າຈະສຳຮອງທັງໝົດ ຫຼື ສາຂາໃດ
function createBackup() {
    if (!DB_BACKUP_STORES.length) { runCreateBackup(0); return; }
    var opts = { '0': I18N_DATABASE['database.pick_scope_all'] };
    DB_BACKUP_STORES.forEach(function (s) { opts[String(s.id)] = s.name; });
    Swal.fire({
        title: I18N_DATABASE['database.pick_scope_title'],
        text: I18N_DATABASE['database.pick_scope_hint'],
        icon: 'question',
        input: 'select',
        inputOptions: opts,
        inputValue: '0',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6c757d',
        confirmButtonText: I18N_DATABASE['database.btn_continue'],
        cancelButtonText: I18N_DATABASE['database.cancel']
    }).then(function (r) {
        if (r.isConfirmed) runCreateBackup(parseInt(r.value, 10) || 0);
    });
}

function runCreateBackup(storeId) {
    Swal.fire({
        title: I18N_DATABASE['database.creating_backup_title'],
        text: I18N_DATABASE['database.please_wait'],
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.post('<?php echo $base_path; ?>api/database_backend.php', { action: 'create_backup', store_id: storeId }, function(res) {
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: I18N_DATABASE['database.success_title'],
                text: res.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            dbShowFail(res);
        }
    }, 'json').fail(function() {
        Swal.fire(I18N_DATABASE['database.error_title'], I18N_DATABASE['database.err_connect'], 'error');
    });
}

// 2. Restore Backup Action
function restoreBackup(filename, branchName) {
    var text = branchName
        ? I18N_DATABASE['database.confirm_restore_branch_text'].replace('%b', branchName).replace('%s', filename)
        : I18N_DATABASE['database.confirm_restore_text'].replace('%s', filename);
    Swal.fire({
        title: I18N_DATABASE['database.confirm_restore_title'],
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6c757d',
        confirmButtonText: I18N_DATABASE['database.confirm_restore_btn'],
        cancelButtonText: I18N_DATABASE['database.cancel']
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: I18N_DATABASE['database.restoring_title'],
                text: I18N_DATABASE['database.please_wait'],
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.post('<?php echo $base_path; ?>api/database_backend.php', { action: 'restore_backup', filename: filename }, function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: I18N_DATABASE['database.restore_success_title'],
                        text: res.message,
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    dbShowFail(res);
                }
            }, 'json').fail(function() {
                Swal.fire(I18N_DATABASE['database.error_title'], I18N_DATABASE['database.err_restore'], 'error');
            });
        }
    });
}

// 3. Delete Backup Action
function deleteBackup(filename) {
    Swal.fire({
        title: I18N_DATABASE['database.confirm_delete_title'],
        text: I18N_DATABASE['database.confirm_delete_text'].replace('%s', filename),
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        confirmButtonText: I18N_DATABASE['database.confirm_delete_btn'],
        cancelButtonText: I18N_DATABASE['database.cancel']
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('<?php echo $base_path; ?>api/database_backend.php', { action: 'delete_backup', filename: filename }, function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: I18N_DATABASE['database.delete_success_title'],
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    dbShowFail(res);
                }
            }, 'json');
        }
    });
}

// 4. Upload & Restore Form Submit
$('#uploadRestoreForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('action', 'upload_restore');

    $('#uploadRestoreModal').modal('hide');

    Swal.fire({
        title: I18N_DATABASE['database.uploading_title'],
        text: I18N_DATABASE['database.please_wait'],
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: '<?php echo $base_path; ?>api/database_backend.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: I18N_DATABASE['database.restore_success_title'],
                    text: res.message,
                    timer: 2500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                dbShowFail(res);
            }
        },
        error: function() {
            Swal.fire(I18N_DATABASE['database.error_title'], I18N_DATABASE['database.err_upload'], 'error');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
