<?php
/**
 * Detail project: /id/project/nama-project atau /en/project/nama-project
 * Project yang tidak ada atau masih Draf → halaman 404.
 */

require __DIR__ . '/includes/functions.php';
require ROOT_PATH . '/includes/upload.php';

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

// Teknologi (hanya skill yang berstatus Tampil)
$stmt = db()->prepare(
    "SELECT s.name FROM project_skill ps JOIN skills s ON s.id = ps.skill_id
      WHERE ps.project_id = ? AND s.status = 'published' ORDER BY s.sort_order, s.name"
);
$stmt->execute([$project['id']]);
$tech = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Galeri
$stmt = db()->prepare('SELECT file_name, alt_ind, alt_eng FROM project_images WHERE project_id = ? ORDER BY sort_order, id');
$stmt->execute([$project['id']]);
$gallery = array_filter($stmt->fetchAll(), fn ($g) => is_upload_name($g['file_name']));

$title = tr($project, 'title');
$role  = tr($project, 'my_role');
$demo  = safe_url($project['demo_url'] ?? '');
$repo  = safe_url($project['repo_url'] ?? '');
$cover = img_set('projects', $project['cover_image']);
$coverSizes = '(max-width: 1023px) calc(100vw - 56px), 1040px';

$pagePath        = 'project/' . $slug;
$pageTitle       = $title;
$pageDescription = tr($project, 'summary');
$navActive       = 'projects';
$ogImage         = og_image('projects', $project['cover_image']) ?? og_image('profile', $profile['photo'] ?? null);
if ($cover) {
    $preloadImage = ['src' => $cover['src'], 'srcset' => $cover['srcset'], 'sizes' => $coverSizes];
}

require ROOT_PATH . '/includes/header.php';
?>
<div class="wrap">
  <a class="back" href="<?= e(lang_url('projects')) ?>"><?= icon('arrow') ?><?= e(t('project.back')) ?></a>

  <article aria-labelledby="project-title">
    <header class="pd-head">
      <span class="sticker bg-pink" style="--r:-2deg"><?= e(category_label('project_categories', (string) $project['category'])) ?></span>
      <h1 id="project-title"><?= e($title) ?></h1>
      <p class="pd-lead"><?= e(tr($project, 'summary')) ?></p>
      <?php if ($demo !== '' || $repo !== ''): ?>
      <div class="pd-actions">
        <?php if ($demo !== ''): ?>
        <a class="btn bg-blue" href="<?= e($demo) ?>" target="_blank" rel="noopener"><?= e(t('project.demo')) ?><?= icon('external') ?></a>
        <?php endif; ?>
        <?php if ($repo !== ''): ?>
        <a class="btn white" href="<?= e($repo) ?>" target="_blank" rel="noopener"><?= icon('github') ?><?= e(t('project.repo')) ?></a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </header>

    <figure class="pd-cover bg-yellow">
      <?php if ($cover): ?>
      <img src="<?= e($cover['src']) ?>" srcset="<?= e($cover['srcset']) ?>" sizes="<?= e($coverSizes) ?>" width="<?= $cover['w'] ?>" height="<?= $cover['h'] ?>" alt="<?= e(t('project.cover_alt', ['title' => $title])) ?>" fetchpriority="high" decoding="async">
      <?php else: ?>
      <div class="mini" aria-hidden="true"><b><i></i><i></i><i></i></b><u><i></i><i></i><i></i></u></div>
      <?php endif; ?>
    </figure>

    <div class="pd-body">
      <section class="card box pd-desc" aria-labelledby="about-project">
        <h2 id="about-project"><?= e(t('project.about')) ?></h2>
        <?= paragraphs(tr($project, 'description')) ?>
      </section>

      <div class="card box pd-info">
        <dl>
          <?php if ($role !== ''): ?>
          <div><dt><?= e(t('project.role')) ?></dt><dd><?= e($role) ?></dd></div>
          <?php endif; ?>
          <div><dt><?= e(t('project.year')) ?></dt><dd><?= (int) $project['year'] ?></dd></div>
          <div><dt><?= e(t('project.category')) ?></dt><dd><?= e(category_label('project_categories', (string) $project['category'])) ?></dd></div>
          <?php if ($tech): ?>
          <div><dt><?= e(t('project.tech')) ?></dt><dd>
            <ul class="tech">
              <?php foreach ($tech as $name): ?>
              <li><?= e($name) ?></li>
              <?php endforeach; ?>
            </ul>
          </dd></div>
          <?php endif; ?>
        </dl>
      </div>
    </div>

    <?php if ($gallery): ?>
    <section class="pd-gallery" aria-labelledby="gallery-title">
      <h2 id="gallery-title"><?= e(t('project.gallery')) ?></h2>
      <ul class="gallery">
        <?php foreach ($gallery as $g): $alt = tr($g, 'alt'); ?>
        <li>
          <a href="<?= e(upload_url('projects', $g['file_name'])) ?>" target="_blank" rel="noopener">
            <?= responsive_img('projects', $g['file_name'], $alt, '(max-width: 767px) 50vw, 340px') ?>
            <span class="sr-only"> — <?= e(t('project.open_image')) ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>
  </article>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
