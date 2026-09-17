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
require_once __DIR__ . '/../../layouts/header.php';

// Check permissions
if (empty($_SESSION['user_id']) || (!hasPermission('database') && !hasPermission('setup') && !hasPermission('permissions'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// Scan Backup Files in backups/ directory
$backupDir = __DIR__ . '/../../backups/';
$backupFiles = [];
if (file_exists($backupDir)) {
    $files = glob($backupDir . '*.sql');
    if (!empty($files)) {
        rsort($files); // Latest first
        foreach ($files as $f) {
            $backupFiles[] = [
                'name' => basename($f),
                'size' => number_format(filesize($f) / 1024, 2) . ' KB',
                'time' => date('d/m/Y H:i:s', filemtime($f))
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
        <div class="mt-2 mt-md-0">
            <button class="btn btn-primary-custom btn-sm font-weight-bold shadow-sm px-3 py-2" style="border-radius: 6px; font-size: 0.82rem;" onclick="createBackup()">
                <i class="fas fa-file-download mr-1"></i> <?php echo htmlspecialchars(t('database.btn_create_backup', 'ສ້າງໄຟລ໌ສຳຮອງ')); ?>
            </button>
            <button class="btn btn-secondary-custom btn-sm font-weight-bold shadow-sm ml-2 px-3 py-2" style="border-radius: 6px; font-size: 0.82rem;" onclick="$('#uploadRestoreModal').modal('show')">
                <i class="fas fa-file-upload mr-1"></i> <?php echo htmlspecialchars(t('database.btn_upload', 'ອັບໂຫຼດ')); ?>
            </button>
        </div>
    </div>

    <!-- Backup Files Grid Section (Card View Layout) -->
    <div class="card card-custom mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
            <h5 class="card-title font-weight-bold text-dark mb-0">
                <i class="fas fa-history text-primary mr-2"></i> <?php echo htmlspecialchars(t('database.list_title', 'ລາຍການໄຟລ໌ສຳຮອງຖານຂໍ້ມູນ')); ?>
            </h5>
        </div>
        <div class="card-body px-4 pb-4">
            <?php if (!empty($backupFiles)): ?>
                <div class="row">
                    <?php foreach ($backupFiles as $bf): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card backup-file-card h-100 p-3 d-flex flex-column justify-content-between border shadow-sm" style="border-radius: 12px; background: #ffffff;">
                                <div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="mr-3 text-primary" style="font-size: 1.5rem; background: #eff6ff; width: 44px; height: 44px; min-width: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1px solid #bfdbfe;">
                                            <i class="fas fa-database"></i>
                                        </div>
                                        <div style="overflow: hidden;">
                                            <h6 class="font-weight-bold text-dark mb-1 text-truncate" style="font-size: 0.9rem;" title="<?php echo htmlspecialchars($bf['name']); ?>">
                                                <?php echo htmlspecialchars($bf['name']); ?>
                                            </h6>
                                            <div class="text-muted" style="font-size: 0.78rem;">
                                                <i class="far fa-clock mr-1"></i><?php echo $bf['time']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge badge-light border px-2 py-1" style="font-size: 0.78rem;">
                                            <i class="fas fa-hdd text-secondary mr-1"></i> <?php echo $bf['size']; ?>
                                        </span>
                                        <span class="badge badge-success px-2 py-1" style="font-size: 0.72rem; border-radius: 6px;">
                                            <i class="fas fa-check-circle mr-1"></i> SQL Backup
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center pt-3 border-top" style="gap: 6px;">
                                    <!-- Download Button -->
                                    <a href="<?php echo $base_path; ?>api/database_backend.php?action=download_backup&file=<?php echo urlencode($bf['name']); ?>"
                                       class="btn-backup-action download flex-fill" title="<?php echo htmlspecialchars(t('database.download_title', 'ດາວໂຫຼດໄຟລ໌')); ?>">
                                        <i class="fas fa-download mr-1"></i> <?php echo htmlspecialchars(t('database.download', 'ດາວໂຫຼດ')); ?>
                                    </a>
                                    <!-- Restore Button -->
                                    <button type="button" class="btn-backup-action restore flex-fill"
                                            onclick="restoreBackup('<?php echo htmlspecialchars($bf['name']); ?>')" title="<?php echo htmlspecialchars(t('database.restore_title', 'ຟື້ນຟູຖານຂໍ້ມູນ')); ?>">
                                        <i class="fas fa-undo mr-1"></i> <?php echo htmlspecialchars(t('database.restore', 'ຟື້ນຟູ')); ?>
                                    </button>
                                    <!-- Delete Button -->
                                    <button type="button" class="btn-backup-action delete"
                                            onclick="deleteBackup('<?php echo htmlspecialchars($bf['name']); ?>')" title="<?php echo htmlspecialchars(t('database.delete_title', 'ລົບໄຟລ໌')); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
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
]); ?>;

// 1. Create Backup Action
function createBackup() {
    Swal.fire({
        title: I18N_DATABASE['database.creating_backup_title'],
        text: I18N_DATABASE['database.please_wait'],
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.post('<?php echo $base_path; ?>api/database_backend.php', { action: 'create_backup' }, function(res) {
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
            Swal.fire(I18N_DATABASE['database.error_title'], res.message, 'error');
        }
    }, 'json').fail(function() {
        Swal.fire(I18N_DATABASE['database.error_title'], I18N_DATABASE['database.err_connect'], 'error');
    });
}

// 2. Restore Backup Action
function restoreBackup(filename) {
    Swal.fire({
        title: I18N_DATABASE['database.confirm_restore_title'],
        text: I18N_DATABASE['database.confirm_restore_text'].replace('%s', filename),
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
                    Swal.fire(I18N_DATABASE['database.error_title'], res.message, 'error');
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
                    Swal.fire(I18N_DATABASE['database.error_title'], res.message, 'error');
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
                Swal.fire(I18N_DATABASE['database.error_title'], res.message, 'error');
            }
        },
        error: function() {
            Swal.fire(I18N_DATABASE['database.error_title'], I18N_DATABASE['database.err_upload'], 'error');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
