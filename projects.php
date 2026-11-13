<?php
/**
 * Daftar semua project: /id/projects atau /en/projects
 * Saring kategori lewat ?category=web (berupa tautan biasa, jadi tetap jalan tanpa JavaScript).
 */

require __DIR__ . '/includes/functions.php';
require ROOT_PATH . '/includes/upload.php';

// Kategori yang dipilih harus salah satu kunci di config/app.php; selain itu → 404
$categories = config('project_categories', []);
$category   = $_GET['category'] ?? null;
if ($category !== null && (!is_string($category) || !isset($categories[$category]))) {
    not_found();
}
$pagePath = 'projects' . ($category !== null ? '?category=' . $category : '');

lang_from_url($pagePath);
$profile = profile();

// Jumlah project terbit per kategori (untuk tombol saring; kategori kosong tidak ditampilkan)
$stmt = db()->prepare("SELECT category, COUNT(*) AS n FROM projects WHERE status = 'published' GROUP BY category");
$stmt->execute();
$perCategory = array_map('intval', array_column($stmt->fetchAll(), 'n', 'category'));
$total       = array_sum($perCategory);

$projects = published_projects(false, $category);

$pageTitle       = str_replace(['[', ']'], '', t('projects.page_title'))
                 . ($category !== null ? ' · ' . category_label('project_categories', $category) : '');
$pageDescription = t('page.projects_desc', ['name' => $profile['full_name'] ?? '']);
$navActive       = 'projects';

require ROOT_PATH . '/includes/header.php';
?>
<div class="wrap">
  <section class="sec" aria-labelledby="page-title">
    <div class="head">
      <span class="num"><?= e(mb_strtoupper(t('projects.label'))) ?> · <?= e(t('projects.count', ['n' => count($projects)])) ?></span>
      <h1 id="page-title"><?= hl_title(t('projects.page_title'), 'yellow') ?></h1>
      <p><?= e(t('page.projects_desc', ['name' => $profile['full_name'] ?? ''])) ?></p>
    </div>

    <?php if ($total > 0): ?>
    <nav aria-label="<?= e(t('projects.filter')) ?>">
      <ul class="filters">
        <li><a href="<?= e(lang_url('projects')) ?>"<?= $category === null ? ' aria-current="page"' : '' ?>><?= e(t('projects.filter_all')) ?> <span class="n"><?= $total ?></span></a></li>
        <?php foreach ($categories as $key => $label): ?>
        <?php if (!empty($perCategory[$key])): ?>
        <li><a href="<?= e(lang_url('projects') . '?category=' . rawurlencode($key)) ?>"<?= $category === $key ? ' aria-current="page"' : '' ?>><?= e(category_label('project_categories', $key)) ?> <span class="n"><?= $perCategory[$key] ?></span></a></li>
        <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <?php if ($projects): ?>
    <ul class="projects">
      <?php foreach ($projects as $i => $p): ?>
      <?php require ROOT_PATH . '/includes/project_card.php'; ?>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <div class="card box empty-note">
      <p><?= e($total > 0 ? t('projects.empty') : t('projects.none')) ?></p>
      <a class="btn white" href="<?= e($total > 0 ? lang_url('projects') : lang_url('')) ?>"><?= e($total > 0 ? t('projects.filter_all') : t('projects.back_home')) ?><?= icon('arrow', 'i nudge') ?></a>
    </div>
    <?php endif; ?>
  </section>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
