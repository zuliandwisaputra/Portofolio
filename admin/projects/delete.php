<?php
/**
 * Hapus project — selalu lewat halaman konfirmasi dulu.
 * Galeri dan pilihan teknologinya ikut terhapus dari database,
 * dan semua gambarnya (sampul + galeri) dihapus dari server.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';

$admin = require_admin();
$id    = id_from_query('/admin/projects/', 'project');

$stmt = db()->prepare('SELECT * FROM projects WHERE id = ?');
$stmt->execute([$id]);
$project = $stmt->fetch();
if (!$project) {
    flash('error', 'Project tidak ditemukan. Mungkin sudah dihapus.');
    redirect('/admin/projects/');
}

$stmt = db()->prepare('SELECT file_name FROM project_images WHERE project_id = ?');
$stmt->execute([$id]);
$galleryFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        // Galeri & penghubung skill ikut terhapus otomatis (ON DELETE CASCADE)
        db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
        delete_image('projects', $project['cover_image']);
        foreach ($galleryFiles as $file) {
            delete_image('projects', $file);
        }
        flash('success', 'Project "' . $project['title_ind'] . '" beserta gambarnya berhasil dihapus.');
    } catch (Throwable $e) {
        error_log('[portofolio] gagal hapus project: ' . $e->getMessage());
        flash('error', 'Project "' . $project['title_ind'] . '" gagal dihapus karena kesalahan server.');
    }
    redirect('/admin/projects/');
}

$imageCount = count($galleryFiles) + ($project['cover_image'] ? 1 : 0);

$pageTitle  = 'Hapus project';
$activeMenu = 'projects';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/projects/">Project</a> <span aria-hidden="true">/</span> Hapus</p>
<div class="confirm card box">
  <span class="confirm-ico"><?= icon('trash') ?></span>
  <h2>Hapus project "<?= e($project['title_ind']) ?>"?</h2>
  <p>Yang ikut terhapus: data project, pilihan teknologinya, dan <strong><?= e($imageCount) ?> gambar</strong> (sampul + galeri) dari server.</p>
  <p class="muted">Tindakan ini tidak bisa dibatalkan.</p>
  <form method="post" action="/admin/projects/delete.php?id=<?= e($id) ?>" class="form-actions">
    <?= csrf_field() ?>
    <a class="btn white" href="/admin/projects/">Batal</a>
    <button class="btn btn-danger" type="submit"><?= icon('trash') ?>Ya, hapus project</button>
  </form>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
