<?php
/**
 * Ubah project. Alamat (slug) tidak berubah walaupun judul diganti,
 * supaya tautan yang sudah dibagikan tetap jalan.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';
require __DIR__ . '/form.php';

$admin = require_admin();
$id    = id_from_query('/admin/projects/', 'project');

$stmt = db()->prepare('SELECT * FROM projects WHERE id = ?');
$stmt->execute([$id]);
$project = $stmt->fetch();
if (!$project) {
    flash('error', 'Project tidak ditemukan. Mungkin sudah dihapus.');
    redirect('/admin/projects/');
}

$stmt = db()->prepare('SELECT skill_id FROM project_skill WHERE project_id = ?');
$stmt->execute([$id]);
$skillIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = db()->prepare('SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order, id');
$stmt->execute([$id]);
$gallery = [];
foreach ($stmt->fetchAll() as $row) {
    $gallery[(int) $row['id']] = $row;
}

$data   = project_from_row($project, $skillIds, $gallery);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data     = project_input();
    $cover    = file_was_sent($_FILES['cover_image'] ?? null) ? $_FILES['cover_image'] : null;
    $newFiles = uploaded_files('gallery_new');
    $errors   = project_validate($data, $gallery, $cover, $newFiles);

    if (!$errors) {
        try {
            project_save($data, $project, $cover, $newFiles, $gallery);
            flash('success', 'Perubahan project "' . $data['title_ind'] . '" berhasil disimpan.');
            redirect('/admin/projects/edit.php?id=' . $id);
        } catch (Throwable $e) {
            $errors['_save'] = save_error('Perubahan gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }
    }
}

$publicUrl = lang_url('project/' . $project['slug'], 'id');

$pageTitle  = 'Ubah project';
$activeMenu = 'projects';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/projects/">Project</a> <span aria-hidden="true">/</span> Ubah "<?= e($project['title_ind']) ?>"</p>
<div class="slug-box card box">
  <p><span class="muted">Alamat halaman:</span> <code><?= e('/id/project/' . $project['slug']) ?></code></p>
  <?php if ($project['status'] === 'published'): ?>
  <a class="btn white btn-sm" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"><?= icon('external') ?>Lihat di website</a>
  <?php else: ?>
  <span class="muted">Belum tampil di website karena masih Draf.</span>
  <?php endif; ?>
</div>
<?= error_summary($errors, 'Perubahan belum tersimpan.') ?>
<?php project_form($data, $errors, '/admin/projects/edit.php?id=' . $id, 'Simpan perubahan', $project, $gallery); ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
