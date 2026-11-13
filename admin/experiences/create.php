<?php
/**
 * Tambah pengalaman / pendidikan.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';
require __DIR__ . '/form.php';

$admin  = require_admin();
$data   = experience_defaults();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data   = experience_input();
    $image  = file_was_sent($_FILES['image'] ?? null) ? $_FILES['image'] : null;
    $errors = experience_validate($data, $image);

    if (!$errors) {
        try {
            experience_save($data, null, $image);
            flash('success', '"' . $data['title_ind'] . '" berhasil ditambahkan.');
            redirect('/admin/experiences/');
        } catch (Throwable $e) {
            $errors['_save'] = save_error('Data gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }
    }
}

$pageTitle  = 'Tambah pengalaman & pendidikan';
$activeMenu = 'experiences';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/experiences/">Pengalaman & pendidikan</a> <span aria-hidden="true">/</span> Tambah</p>
<?= error_summary($errors, 'Data belum tersimpan.') ?>
<?php experience_form($data, $errors, '/admin/experiences/create.php', 'Simpan', null); ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
