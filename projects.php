<?php
/**
 * Daftar semua project: /id/projects atau /en/projects
 *
 * TAHAP 1: kerangka saja. Daftar dan filter kategori dibuat di Tahap 5.
 */

require __DIR__ . '/includes/functions.php';

lang_from_url('projects');
$profile = profile();

$pagePath        = 'projects';
$pageTitle       = t('page.projects');
$pageDescription = t('page.projects_desc', ['name' => $profile['full_name'] ?? '']);

require ROOT_PATH . '/includes/header.php';
?>
<section class="wrap wip">
  <span class="sticker bg-yellow" style="--r:-2deg"><?= e(t('wip.badge')) ?></span>
  <h1 class="wip-title"><?= e(t('page.projects')) ?></h1>
  <p class="wip-lead"><?= e(t('wip.projects')) ?></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
