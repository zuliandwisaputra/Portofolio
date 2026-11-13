<?php
/**
 * Daftar project (10 baris per halaman) + tombol cepat untuk menandai unggulan.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';

$admin = require_admin();

// Tombol bintang: tandai / lepas unggulan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id   = (int) ($_POST['id'] ?? 0);
    $page = max(1, (int) ($_POST['page'] ?? 1));
    $stmt = db()->prepare('SELECT title_ind, is_featured FROM projects WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('error', 'Project tidak ditemukan.');
    } else {
        $new = (int) $row['is_featured'] === 1 ? 0 : 1;
        db()->prepare('UPDATE projects SET is_featured = ? WHERE id = ?')->execute([$new, $id]);
        flash('success', 'Project "' . $row['title_ind'] . '" ' . ($new ? 'ditandai sebagai unggulan.' : 'tidak lagi unggulan.'));
    }
    redirect('/admin/projects/?page=' . $page);
}

$stmt = db()->prepare(
    "SELECT COUNT(*) AS total,
            SUM(is_featured = 1 AND status = 'published') AS featured_live
       FROM projects"
);
$stmt->execute();
$counts = $stmt->fetch();
$p = paginate((int) $counts['total']);

$stmt = db()->prepare(
    'SELECT id, slug, title_ind, category, year, cover_image, is_featured, status, sort_order, updated_at
       FROM projects
      ORDER BY sort_order, id
      LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $p['per'], PDO::PARAM_INT);
$stmt->bindValue(2, $p['offset'], PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

$pageTitle  = 'Project';
$activeMenu = 'projects';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="toolbar">
  <p class="muted"><?= e($p['total']) ?> project · <?= e((int) $counts['featured_live']) ?> unggulan yang terbit
    <?php if ((int) $counts['featured_live'] > 6): ?><br><strong>Beranda hanya menampilkan 6 project unggulan pertama (menurut urutan).</strong><?php endif; ?>
  </p>
  <a class="btn bg-mint btn-sm" href="/admin/projects/create.php"><?= icon('plus') ?>Tambah project</a>
</div>

<?php if (!$projects): ?>
<div class="empty card box">
  <p><strong>Belum ada project.</strong> Tambahkan karya pertamamu lengkap dengan gambar dan teknologinya.</p>
  <a class="btn bg-mint btn-sm" href="/admin/projects/create.php"><?= icon('plus') ?>Tambah project</a>
</div>
<?php else: ?>
<div class="table-wrap box">
  <table class="data">
    <caption class="sr-only">Daftar project, halaman <?= e($p['page']) ?> dari <?= e($p['pages']) ?></caption>
    <thead>
      <tr><th scope="col">Urutan</th><th scope="col">Sampul</th><th scope="col">Judul</th><th scope="col">Unggulan</th><th scope="col">Status</th><th scope="col">Terakhir diubah</th><th scope="col"><span class="sr-only">Aksi</span></th></tr>
    </thead>
    <tbody>
      <?php foreach ($projects as $pr):
          [$w, $h] = image_size('projects', $pr['cover_image'], true); ?>
      <tr>
        <td class="num"><?= e($pr['sort_order']) ?></td>
        <td>
          <?php if ($pr['cover_image'] && $w): ?>
          <img class="thumb" src="<?= e(upload_url('projects', $pr['cover_image'], true)) ?>" width="<?= e($w) ?>" height="<?= e($h) ?>" alt="" loading="lazy">
          <?php else: ?>
          <span class="thumb thumb-empty" title="Belum ada sampul"><?= icon('photo') ?></span>
          <?php endif; ?>
        </td>
        <td class="col-title">
          <strong><?= e($pr['title_ind']) ?></strong>
          <span class="sub"><?= e(category_label('project_categories', (string) $pr['category'])) ?> · <?= e($pr['year']) ?></span>
        </td>
        <td>
          <form method="post" action="/admin/projects/">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($pr['id']) ?>">
            <input type="hidden" name="page" value="<?= e($p['page']) ?>">
            <button class="star-btn" type="submit" aria-pressed="<?= $pr['is_featured'] ? 'true' : 'false' ?>"
                    aria-label="<?= $pr['is_featured'] ? 'Lepas unggulan: ' : 'Jadikan unggulan: ' ?><?= e($pr['title_ind']) ?>">
              <?= icon($pr['is_featured'] ? 'star-filled' : 'star') ?>
            </button>
          </form>
        </td>
        <td><?= status_badge($pr['status']) ?></td>
        <td class="nowrap"><?= e(format_date($pr['updated_at'], true)) ?></td>
        <td class="row-actions">
          <?php if ($pr['status'] === 'published'): ?>
          <a class="act" href="<?= e(lang_url('project/' . $pr['slug'], 'id')) ?>" target="_blank" rel="noopener"><?= icon('external') ?>Lihat<span class="sr-only"> <?= e($pr['title_ind']) ?> di website</span></a>
          <?php endif; ?>
          <a class="act" href="/admin/projects/edit.php?id=<?= e($pr['id']) ?>"><?= icon('pencil') ?>Ubah<span class="sr-only"> <?= e($pr['title_ind']) ?></span></a>
          <a class="act act-danger" href="/admin/projects/delete.php?id=<?= e($pr['id']) ?>"><?= icon('trash') ?>Hapus<span class="sr-only"> <?= e($pr['title_ind']) ?></span></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= pagination_links($p, '/admin/projects/') ?>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
