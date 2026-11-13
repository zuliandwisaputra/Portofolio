<?php
/**
 * Baca satu pesan. Membuka halaman ini tidak mengubah status;
 * tandai sudah/belum dibaca lewat tombol (POST + token CSRF).
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require __DIR__ . '/common.php';

$admin = require_admin();
$id    = id_from_query('/admin/messages/', 'pesan');
$row   = find_message($id);

$pageTitle  = 'Baca pesan';
$activeMenu = 'messages';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/messages/">Pesan</a> <span aria-hidden="true">/</span> Dari <?= e($row['name']) ?></p>

<article class="msg card box" aria-labelledby="msg-from">
  <header class="msg-head">
    <div>
      <h2 id="msg-from"><?= e($row['name']) ?></h2>
      <p><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></p>
    </div>
    <div class="msg-meta">
      <?= message_badge((bool) $row['is_read']) ?>
      <p class="muted">Diterima <?= e(format_date($row['created_at'], true)) ?> WIB</p>
    </div>
  </header>

  <div class="msg-body"><?= nl2br(e($row['body']), false) ?></div>

  <div class="msg-actions">
    <a class="btn bg-mint btn-sm" href="<?= e(reply_link($row)) ?>"><?= icon('reply') ?>Balas lewat email</a>
    <form method="post" action="/admin/messages/mark.php">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= e($row['id']) ?>">
      <?php if ($row['is_read']): ?>
      <input type="hidden" name="mark" value="unread">
      <button class="btn white btn-sm" type="submit"><?= icon('mail') ?>Tandai belum dibaca</button>
      <?php else: ?>
      <input type="hidden" name="mark" value="read">
      <button class="btn bg-yellow btn-sm" type="submit"><?= icon('check') ?>Tandai sudah dibaca</button>
      <?php endif; ?>
    </form>
    <a class="btn btn-danger btn-sm" href="/admin/messages/delete.php?id=<?= e($row['id']) ?>"><?= icon('trash') ?>Hapus</a>
  </div>
</article>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
