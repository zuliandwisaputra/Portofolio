<?php
/**
 * Detail project: /id/project/nama-project atau /en/project/nama-project
 *
 * TAHAP 1: sudah mencari project berdasarkan slug (yang tidak ada atau masih Draf → 404).
 * Tampilan lengkap (galeri, deskripsi, teknologi, tautan) dibuat di Tahap 5.
 */

require __DIR__ . '/includes/functions.php';

$slug = (string) ($_GET['slug'] ?? '');
if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    not_found();
}

lang_from_url('project/' . $slug);
$profile = profile();

$stmt = db()->prepare("SELECT * FROM projects WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$project = $stmt->fetch();
if (!$project) {
    not_found();
}

$pagePath        = 'project/' . $slug;
$pageTitle       = tr($project, 'title');
$pageDescription = tr($project, 'summary');

require ROOT_PATH . '/includes/header.php';
?>
<section class="wrap wip">
  <span class="sticker bg-pink" style="--r:-2deg"><?= e(category_label('project_categories', (string) $project['category'])) ?></span>
  <h1 class="wip-title"><?= e(tr($project, 'title')) ?></h1>
  <p class="wip-lead"><?= e(tr($project, 'summary')) ?></p>
  <p class="wip-note"><?= e(t('wip.project')) ?></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
