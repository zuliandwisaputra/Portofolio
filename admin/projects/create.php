<?php
/**
 * Tambah project.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';
require __DIR__ . '/form.php';

$admin  = require_admin();
$data   = project_defaults();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data     = project_input();
    $cover    = file_was_sent($_FILES['cover_image'] ?? null) ? $_FILES['cover_image'] : null;
    $newFiles = uploaded_files('gallery_new');
    $errors   = project_validate($data, [], $cover, $newFiles);

    if (!$errors) {
        try {
            project_save($data, null, $cover, $newFiles, []);
            flash('success', 'Project "' . $data['title_ind'] . '" berhasil ditambahkan.');
            redirect('/admin/projects/');
        } catch (Throwable $e) {
            $errors['_save'] = save_error('Project gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }
    }
}

$pageTitle  = 'Tambah project';
$activeMenu = 'projects';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/projects/">Project</a> <span aria-hidden="true">/</span> Tambah</p>
<?= error_summary($errors, 'Project belum tersimpan.') ?>
<?php project_form($data, $errors, '/admin/projects/create.php', 'Simpan project', null, []); ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
