<?php
/**
 * Ubah sertifikat / lomba.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';
require __DIR__ . '/form.php';

$admin = require_admin();
$id    = id_from_query('/admin/achievements/', 'data');

$stmt = db()->prepare('SELECT * FROM achievements WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    flash('error', 'Data tidak ditemukan. Mungkin sudah dihapus.');
    redirect('/admin/achievements/');
}

$data   = achievement_from_row($row);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data   = achievement_input();
    $image  = file_was_sent($_FILES['image'] ?? null) ? $_FILES['image'] : null;
    $errors = achievement_validate($data, $image);

    if (!$errors) {
        try {
            achievement_save($data, $row, $image);
            flash('success', 'Perubahan "' . $data['title_ind'] . '" berhasil disimpan.');
            redirect('/admin/achievements/');
        } catch (Throwable $e) {
            $errors['_save'] = save_error('Perubahan gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }
    }
}

$pageTitle  = 'Ubah sertifikat & lomba';
$activeMenu = 'achievements';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/achievements/">Sertifikat & lomba</a> <span aria-hidden="true">/</span> Ubah "<?= e($row['title_ind']) ?>"</p>
<?= error_summary($errors, 'Perubahan belum tersimpan.') ?>
<?php achievement_form($data, $errors, '/admin/achievements/edit.php?id=' . $id, 'Simpan perubahan', $row); ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
