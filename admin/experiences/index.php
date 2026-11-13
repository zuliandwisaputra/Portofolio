<?php
/**
 * Daftar pengalaman & pendidikan (10 per halaman), terbaru di atas.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';
require __DIR__ . '/form.php';

$admin = require_admin();

$stmt = db()->prepare('SELECT COUNT(*) FROM experiences');
$stmt->execute();
$p = paginate((int) $stmt->fetchColumn());

$stmt = db()->prepare(
    'SELECT id, type, title_ind, organization, start_date, end_date, is_current, image, status, updated_at
       FROM experiences
      ORDER BY start_date DESC, id DESC
      LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $p['per'], PDO::PARAM_INT);
$stmt->bindValue(2, $p['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$pageTitle  = 'Pengalaman & pendidikan';
$activeMenu = 'experiences';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="toolbar">
  <p class="muted"><?= e($p['total']) ?> data · diurutkan otomatis dari yang terbaru.</p>
  <a class="btn bg-mint btn-sm" href="/admin/experiences/create.php"><?= icon('plus') ?>Tambah data</a>
</div>

<?php if (!$rows): ?>
<div class="empty card box">
  <p><strong>Belum ada data.</strong> Tambahkan riwayat pendidikan, organisasi, atau pekerjaanmu.</p>
  <a class="btn bg-mint btn-sm" href="/admin/experiences/create.php"><?= icon('plus') ?>Tambah data</a>
</div>
<?php else: ?>
<div class="table-wrap box">
  <table class="data">
    <caption class="sr-only">Daftar pengalaman & pendidikan, halaman <?= e($p['page']) ?> dari <?= e($p['pages']) ?></caption>
    <thead>
      <tr><th scope="col">Foto</th><th scope="col">Posisi / jurusan</th><th scope="col">Jenis</th><th scope="col">Periode</th><th scope="col">Status</th><th scope="col">Terakhir diubah</th><th scope="col"><span class="sr-only">Aksi</span></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r):
          [$w, $h] = image_size(UPLOAD_FOLDER_EXPERIENCES, $r['image'], true); ?>
      <tr>
        <td>
          <?php if ($r['image'] && $w): ?>
          <img class="thumb" src="<?= e(upload_url(UPLOAD_FOLDER_EXPERIENCES, $r['image'], true)) ?>" width="<?= e($w) ?>" height="<?= e($h) ?>" alt="" loading="lazy">
          <?php else: ?>
          <span class="thumb thumb-empty" title="Tanpa foto"><?= icon('photo') ?></span>
          <?php endif; ?>
        </td>
        <td class="col-title"><strong><?= e($r['title_ind']) ?></strong><span class="sub"><?= e($r['organization']) ?></span></td>
        <td><?= e(EXPERIENCE_TYPES[$r['type']] ?? $r['type']) ?></td>
        <td class="nowrap"><?= e(format_month($r['start_date'])) ?> – <?= $r['is_current'] ? 'sekarang' : e(format_month($r['end_date'])) ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td class="nowrap"><?= e(format_date($r['updated_at'], true)) ?></td>
        <td class="row-actions">
          <a class="act" href="/admin/experiences/edit.php?id=<?= e($r['id']) ?>"><?= icon('pencil') ?>Ubah<span class="sr-only"> <?= e($r['title_ind']) ?></span></a>
          <a class="act act-danger" href="/admin/experiences/delete.php?id=<?= e($r['id']) ?>"><?= icon('trash') ?>Hapus<span class="sr-only"> <?= e($r['title_ind']) ?></span></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= pagination_links($p, '/admin/experiences/') ?>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
