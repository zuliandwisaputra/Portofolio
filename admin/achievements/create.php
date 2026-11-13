<?php
/**
 * Tambah sertifikat / lomba.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';
require __DIR__ . '/form.php';

$admin  = require_admin();
$data   = achievement_defaults();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data   = achievement_input();
    $image  = file_was_sent($_FILES['image'] ?? null) ? $_FILES['image'] : null;
    $errors = achievement_validate($data, $image);

    if (!$errors) {
        try {
            achievement_save($data, null, $image);
            flash('success', '"' . $data['title_ind'] . '" berhasil ditambahkan.');
            redirect('/admin/achievements/');
        } catch (Throwable $e) {
            $errors['_save'] = save_error('Data gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }
    }
}

$pageTitle  = 'Tambah sertifikat & lomba';
$activeMenu = 'achievements';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/achievements/">Sertifikat & lomba</a> <span aria-hidden="true">/</span> Tambah</p>
<?= error_summary($errors, 'Data belum tersimpan.') ?>
<?php achievement_form($data, $errors, '/admin/achievements/create.php', 'Simpan', null); ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
