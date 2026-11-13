<?php
/**
 * Hapus sertifikat / lomba — lewat halaman konfirmasi. Fotonya ikut dihapus dari server.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        db()->prepare('DELETE FROM achievements WHERE id = ?')->execute([$id]);
        delete_image(UPLOAD_FOLDER_ACHIEVEMENTS, $row['image']);
        flash('success', '"' . $row['title_ind'] . '" berhasil dihapus.');
    } catch (Throwable $e) {
        error_log('[portofolio] gagal hapus sertifikat: ' . $e->getMessage());
        flash('error', '"' . $row['title_ind'] . '" gagal dihapus karena kesalahan server.');
    }
    redirect('/admin/achievements/');
}

$pageTitle  = 'Hapus sertifikat & lomba';
$activeMenu = 'achievements';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/achievements/">Sertifikat & lomba</a> <span aria-hidden="true">/</span> Hapus</p>
<div class="confirm card box">
  <span class="confirm-ico"><?= icon('trash') ?></span>
  <h2>Hapus "<?= e($row['title_ind']) ?>"?</h2>
  <p><?= e(ACHIEVEMENT_TYPES[$row['type']] ?? '') ?> dari <strong><?= e($row['issuer']) ?></strong> (<?= e(format_month($row['issued_date'])) ?>).
    <?= $row['image'] ? 'Gambarnya ikut dihapus dari server.' : '' ?></p>
  <p class="muted">Tindakan ini tidak bisa dibatalkan.</p>
  <form method="post" action="/admin/achievements/delete.php?id=<?= e($id) ?>" class="form-actions">
    <?= csrf_field() ?>
    <a class="btn white" href="/admin/achievements/">Batal</a>
    <button class="btn btn-danger" type="submit"><?= icon('trash') ?>Ya, hapus</button>
  </form>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
