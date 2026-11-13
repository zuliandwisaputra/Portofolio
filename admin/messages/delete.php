<?php
/**
 * Hapus pesan — lewat halaman konfirmasi (POST + token CSRF).
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require __DIR__ . '/common.php';

$admin = require_admin();
$id    = id_from_query('/admin/messages/', 'pesan');
$row   = find_message($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        db()->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
        flash('success', 'Pesan dari ' . $row['name'] . ' berhasil dihapus.');
    } catch (Throwable $e) {
        error_log('[portofolio] gagal hapus pesan: ' . $e->getMessage());
        flash('error', 'Pesan gagal dihapus karena kesalahan server.');
    }
    redirect('/admin/messages/');
}

$pageTitle  = 'Hapus pesan';
$activeMenu = 'messages';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/messages/">Pesan</a> <span aria-hidden="true">/</span> Hapus</p>
<div class="confirm card box">
  <span class="confirm-ico"><?= icon('trash') ?></span>
  <h2>Hapus pesan dari "<?= e($row['name']) ?>"?</h2>
  <p>Dikirim <?= e(format_date($row['created_at'], true)) ?> WIB dari <strong><?= e($row['email']) ?></strong>:</p>
  <blockquote class="msg-quote"><?= e(mb_strimwidth(preg_replace('/\s+/u', ' ', $row['body']) ?? '', 0, 200, '…')) ?></blockquote>
  <p class="muted">Tindakan ini tidak bisa dibatalkan.</p>
  <form method="post" action="/admin/messages/delete.php?id=<?= e($id) ?>" class="form-actions">
    <?= csrf_field() ?>
    <a class="btn white" href="/admin/messages/">Batal</a>
    <button class="btn btn-danger" type="submit"><?= icon('trash') ?>Ya, hapus</button>
  </form>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
