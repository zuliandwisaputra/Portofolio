<?php
/**
 * Satu kartu project (dipakai di beranda dan halaman daftar project).
 *
 * Variabel yang harus ada sebelum file ini dipanggil:
 *   $p  array  satu project dari published_projects()
 *   $i  int    urutan kartu (0, 1, 2, ...) untuk warna bergantian
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

$coverColor   = cycle_color($i, ['yellow', 'pink', 'mint', 'lav', 'blue', 'orange']);
$stickerColor = cycle_color($i, ['pink', 'mint', 'lav', 'yellow', 'orange', 'blue']);
// Sampul tidak diberi teks alt karena judul project sudah ada di kartu (menghindari dibaca dua kali).
$coverImg = responsive_img('projects', $p['cover_image'] ?? null, '',
    '(max-width: 767px) calc(100vw - 32px), (max-width: 1023px) 50vw, 360px');
?>
<li>
  <article class="proj">
    <div class="cover bg-<?= $coverColor ?>">
      <?php if ($coverImg !== ''): ?>
      <?= $coverImg ?>
      <?php else: ?>
      <div class="mini" aria-hidden="true"><b><i></i><i></i><i></i></b><u><i></i><i></i><i></i></u></div>
      <?php endif; ?>
    </div>
    <div class="proj-b">
      <span class="sticker bg-<?= $stickerColor ?>"><?= e(category_label('project_categories', (string) $p['category'])) ?></span>
      <span class="yr"><?= (int) $p['year'] ?></span>
      <h3><a href="<?= e(lang_url('project/' . $p['slug'])) ?>"><?= e(tr($p, 'title')) ?></a></h3>
      <p><?= e(tr($p, 'summary')) ?></p>
      <?php if (!empty($p['skills'])): ?>
      <ul class="tech" aria-label="<?= e(t('project.tech')) ?>">
        <?php foreach ($p['skills'] as $skillName): ?>
        <li><?= e($skillName) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <div class="proj-foot" aria-hidden="true">
        <span><?= e(t('projects.detail')) ?></span>
        <span class="go"><?= icon('arrow-ne') ?></span>
      </div>
    </div>
  </article>
</li>
