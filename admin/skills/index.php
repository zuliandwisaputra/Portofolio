<?php
/**
 * Daftar skill (10 baris per halaman), diurutkan dari angka urutan terkecil.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';

$admin = require_admin();

$stmt = db()->prepare('SELECT COUNT(*) FROM skills');
$stmt->execute();
$p = paginate((int) $stmt->fetchColumn());

$stmt = db()->prepare(
    'SELECT s.*, (SELECT COUNT(*) FROM project_skill ps WHERE ps.skill_id = s.id) AS used_in
       FROM skills s
      ORDER BY s.sort_order, s.name
      LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $p['per'], PDO::PARAM_INT);
$stmt->bindValue(2, $p['offset'], PDO::PARAM_INT);
$stmt->execute();
$skills = $stmt->fetchAll();

$pageTitle  = 'Skill';
$activeMenu = 'skills';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="toolbar">
  <p class="muted"><?= e($p['total']) ?> skill. Skill juga dipakai sebagai pilihan teknologi di project.</p>
  <a class="btn bg-mint btn-sm" href="/admin/skills/create.php"><?= icon('plus') ?>Tambah skill</a>
</div>

<?php if (!$skills): ?>
<div class="empty card box">
  <p><strong>Belum ada skill.</strong> Tambahkan skill pertamamu, misalnya PHP atau Figma.</p>
  <a class="btn bg-mint btn-sm" href="/admin/skills/create.php"><?= icon('plus') ?>Tambah skill</a>
</div>
<?php else: ?>
<div class="table-wrap box">
  <table class="data">
    <caption class="sr-only">Daftar skill, halaman <?= e($p['page']) ?> dari <?= e($p['pages']) ?></caption>
    <thead>
      <tr><th scope="col">Urutan</th><th scope="col">Nama</th><th scope="col">Kategori</th><th scope="col">Dipakai di</th><th scope="col">Status</th><th scope="col">Terakhir diubah</th><th scope="col"><span class="sr-only">Aksi</span></th></tr>
    </thead>
    <tbody>
      <?php foreach ($skills as $s): ?>
      <tr>
        <td class="num"><?= e($s['sort_order']) ?></td>
        <td><strong><?= e($s['name']) ?></strong></td>
        <td><?= e(category_label('skill_categories', (string) $s['category'])) ?></td>
        <td><?= e($s['used_in']) ?> project</td>
        <td><?= status_badge($s['status']) ?></td>
        <td class="nowrap"><?= e(format_date($s['updated_at'], true)) ?></td>
        <td class="row-actions">
          <a class="act" href="/admin/skills/edit.php?id=<?= e($s['id']) ?>"><?= icon('pencil') ?>Ubah<span class="sr-only"> <?= e($s['name']) ?></span></a>
          <a class="act act-danger" href="/admin/skills/delete.php?id=<?= e($s['id']) ?>"><?= icon('trash') ?>Hapus<span class="sr-only"> <?= e($s['name']) ?></span></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= pagination_links($p, '/admin/skills/') ?>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
