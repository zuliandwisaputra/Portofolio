<?php
/**
 * Daftar pesan dari form kontak (10 per halaman), terbaru di atas.
 * Pesan yang belum dibaca ditandai "Baru".
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require __DIR__ . '/common.php';

$admin = require_admin();

$stmt = db()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(is_read = 0), 0) AS unread FROM messages');
$stmt->execute();
$count = $stmt->fetch();
$p = paginate((int) $count['total']);

$stmt = db()->prepare(
    'SELECT id, name, email, body, is_read, created_at
       FROM messages
      ORDER BY created_at DESC, id DESC
      LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $p['per'], PDO::PARAM_INT);
$stmt->bindValue(2, $p['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$pageTitle  = 'Pesan';
$activeMenu = 'messages';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="toolbar">
  <p class="muted"><?= e($count['total']) ?> pesan · <strong><?= e($count['unread']) ?> belum dibaca</strong> · terbaru di atas.</p>
</div>

<?php if (!$rows): ?>
<div class="empty card box">
  <p><strong>Belum ada pesan.</strong> Pesan yang dikirim lewat form kontak di beranda akan muncul di sini.</p>
  <a class="btn white btn-sm" href="/#contact" target="_blank" rel="noopener"><?= icon('external') ?>Lihat form kontak</a>
</div>
<?php else: ?>
<div class="table-wrap box">
  <table class="data">
    <caption class="sr-only">Daftar pesan, halaman <?= e($p['page']) ?> dari <?= e($p['pages']) ?></caption>
    <thead>
      <tr><th scope="col">Status</th><th scope="col">Pengirim</th><th scope="col">Pesan</th><th scope="col">Diterima</th><th scope="col"><span class="sr-only">Aksi</span></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r):
          $snippet = mb_strimwidth(preg_replace('/\s+/u', ' ', $r['body']) ?? '', 0, 90, '…'); ?>
      <tr<?= $r['is_read'] ? '' : ' class="unread"' ?>>
        <td><?= message_badge((bool) $r['is_read']) ?></td>
        <td class="col-from"><strong><?= e($r['name']) ?></strong><span class="sub"><?= e($r['email']) ?></span></td>
        <td class="col-msg"><?= e($snippet) ?></td>
        <td class="nowrap"><?= e(format_date($r['created_at'], true)) ?></td>
        <td class="row-actions">
          <a class="act" href="/admin/messages/view.php?id=<?= e($r['id']) ?>"><?= icon('mail-opened') ?>Baca<span class="sr-only"> pesan dari <?= e($r['name']) ?></span></a>
          <a class="act act-danger" href="/admin/messages/delete.php?id=<?= e($r['id']) ?>"><?= icon('trash') ?>Hapus<span class="sr-only"> pesan dari <?= e($r['name']) ?></span></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= pagination_links($p, '/admin/messages/') ?>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
