<?php
/**
 * Hapus skill — selalu lewat halaman konfirmasi dulu.
 * Project yang memakai skill ini TIDAK ikut terhapus; hanya penghubungnya yang hilang.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';

$admin = require_admin();
$id    = id_from_query('/admin/skills/', 'skill');

$stmt = db()->prepare(
    'SELECT s.*, (SELECT COUNT(*) FROM project_skill ps WHERE ps.skill_id = s.id) AS used_in FROM skills s WHERE s.id = ?'
);
$stmt->execute([$id]);
$skill = $stmt->fetch();
if (!$skill) {
    flash('error', 'Skill tidak ditemukan. Mungkin sudah dihapus.');
    redirect('/admin/skills/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        db()->prepare('DELETE FROM skills WHERE id = ?')->execute([$id]);
        flash('success', 'Skill "' . $skill['name'] . '" berhasil dihapus.');
    } catch (Throwable $e) {
        error_log('[portofolio] gagal hapus skill: ' . $e->getMessage());
        flash('error', 'Skill "' . $skill['name'] . '" gagal dihapus karena kesalahan server.');
    }
    redirect('/admin/skills/');
}

$pageTitle  = 'Hapus skill';
$activeMenu = 'skills';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/skills/">Skill</a> <span aria-hidden="true">/</span> Hapus</p>
<div class="confirm card box">
  <span class="confirm-ico"><?= icon('trash') ?></span>
  <h2>Hapus skill "<?= e($skill['name']) ?>"?</h2>
  <?php if ((int) $skill['used_in'] > 0): ?>
  <p>Skill ini dipakai di <strong><?= e($skill['used_in']) ?> project</strong>. Project tersebut tetap ada; hanya skill ini yang dilepas dari daftar teknologinya.</p>
  <?php else: ?>
  <p>Skill ini belum dipakai di project mana pun.</p>
  <?php endif; ?>
  <p class="muted">Tindakan ini tidak bisa dibatalkan.</p>
  <form method="post" action="/admin/skills/delete.php?id=<?= e($id) ?>" class="form-actions">
    <?= csrf_field() ?>
    <a class="btn white" href="/admin/skills/">Batal</a>
    <button class="btn btn-danger" type="submit"><?= icon('trash') ?>Ya, hapus skill</button>
  </form>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
