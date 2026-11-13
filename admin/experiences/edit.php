<?php
/**
 * Ubah pengalaman / pendidikan.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';
require __DIR__ . '/form.php';

$admin = require_admin();
$id    = id_from_query('/admin/experiences/', 'data');

$stmt = db()->prepare('SELECT * FROM experiences WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    flash('error', 'Data tidak ditemukan. Mungkin sudah dihapus.');
    redirect('/admin/experiences/');
}

$data   = experience_from_row($row);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data   = experience_input();
    $image  = file_was_sent($_FILES['image'] ?? null) ? $_FILES['image'] : null;
    $errors = experience_validate($data, $image);

    if (!$errors) {
        try {
            experience_save($data, $row, $image);
            flash('success', 'Perubahan "' . $data['title_ind'] . '" berhasil disimpan.');
            redirect('/admin/experiences/');
        } catch (Throwable $e) {
            $errors['_save'] = save_error('Perubahan gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }
    }
}

$pageTitle  = 'Ubah pengalaman & pendidikan';
$activeMenu = 'experiences';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/experiences/">Pengalaman & pendidikan</a> <span aria-hidden="true">/</span> Ubah "<?= e($row['title_ind']) ?>"</p>
<?= error_summary($errors, 'Perubahan belum tersimpan.') ?>
<?php experience_form($data, $errors, '/admin/experiences/edit.php?id=' . $id, 'Simpan perubahan', $row); ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
