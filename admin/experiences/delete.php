<?php
/**
 * Hapus pengalaman / pendidikan — lewat halaman konfirmasi. Fotonya ikut dihapus dari server.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        db()->prepare('DELETE FROM experiences WHERE id = ?')->execute([$id]);
        delete_image(UPLOAD_FOLDER_EXPERIENCES, $row['image']);
        flash('success', '"' . $row['title_ind'] . '" berhasil dihapus.');
    } catch (Throwable $e) {
        error_log('[portofolio] gagal hapus pengalaman: ' . $e->getMessage());
        flash('error', '"' . $row['title_ind'] . '" gagal dihapus karena kesalahan server.');
    }
    redirect('/admin/experiences/');
}

$pageTitle  = 'Hapus pengalaman & pendidikan';
$activeMenu = 'experiences';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/experiences/">Pengalaman & pendidikan</a> <span aria-hidden="true">/</span> Hapus</p>
<div class="confirm card box">
  <span class="confirm-ico"><?= icon('trash') ?></span>
  <h2>Hapus "<?= e($row['title_ind']) ?>"?</h2>
  <p><?= e(EXPERIENCE_TYPES[$row['type']] ?? '') ?> di <strong><?= e($row['organization']) ?></strong>
    (<?= e(format_month($row['start_date'])) ?> – <?= $row['is_current'] ? 'sekarang' : e(format_month($row['end_date'])) ?>).
    <?= $row['image'] ? 'Fotonya ikut dihapus dari server.' : '' ?></p>
  <p class="muted">Tindakan ini tidak bisa dibatalkan.</p>
  <form method="post" action="/admin/experiences/delete.php?id=<?= e($id) ?>" class="form-actions">
    <?= csrf_field() ?>
    <a class="btn white" href="/admin/experiences/">Batal</a>
    <button class="btn btn-danger" type="submit"><?= icon('trash') ?>Ya, hapus</button>
  </form>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
