<?php
/**
 * Bagian atas setiap halaman publik: <head>, tautan lewati, dan navbar.
 *
 * Variabel yang bisa diisi halaman sebelum memanggil file ini:
 *   $profile         array  data profil (wajib, dari profile())
 *   $pageTitle       string judul halaman; kosong = beranda
 *   $pageDescription string deskripsi untuk mesin pencari
 *   $pagePath        string alamat halaman tanpa kode bahasa, misalnya 'projects'
 *   $isHome          bool   true di beranda (tautan navbar jadi #bagian)
 *   $noIndex         bool   true supaya halaman tidak masuk mesin pencari (404)
 *   $navActive       string kunci menu yang ditandai aktif (misalnya 'projects')
 *   $ogImage         array  gambar pratinjau media sosial: ['url' => ..., 'w' => ..., 'h' => ...]
 *   $preloadImage    array  gambar yang dimuat paling awal: ['src' => ..., 'srcset' => ..., 'sizes' => ...]
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

$profile         = $profile ?? [];
$pageTitle       = $pageTitle ?? '';
$pagePath        = $pagePath ?? '';
$isHome          = $isHome ?? false;
$noIndex         = $noIndex ?? false;
$navActive       = $navActive ?? '';
$ogImage         = $ogImage ?? null;
$preloadImage    = $preloadImage ?? null;
$fullName        = trim((string) ($profile['full_name'] ?? '')) ?: 'Portofolio';
$pageDescription = $pageDescription ?? tr($profile, 'meta_desc');

$docTitle  = $pageTitle === '' ? $fullName . ' · ' . t('meta.site') : $pageTitle . ' · ' . $fullName;

// Bagian yang punya isi (kalau database bermasalah, misalnya di halaman 404, tampilkan semua)
try {
    $sections = home_sections();
} catch (Throwable $e) {
    $sections = array_fill_keys(['about', 'skills', 'projects', 'all_projects', 'experience', 'achievements', 'contact'], true);
}

// Tautan navbar: di beranda cukup #bagian, di halaman lain menuju beranda#bagian.
// Bagian yang kosong tidak ditampilkan. "Project" menuju halaman daftar kalau belum ada yang unggulan.
$home = $isHome ? '' : lang_url('');
$navItems = [];
foreach (['about' => 'nav.about', 'skills' => 'nav.skills', 'projects' => 'nav.projects',
          'experience' => 'nav.experience', 'achievements' => 'nav.achievements', 'contact' => 'nav.contact'] as $id => $key) {
    if (!empty($sections[$id])) {
        $navItems[] = [$id, t($key), $home . '#' . $id];
    } elseif ($id === 'projects' && !empty($sections['all_projects'])) {
        $navItems[] = [$id, t($key), lang_url('projects')];
    }
}
?>
<!doctype html>
<html lang="<?= e(lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($docTitle) ?></title>
  <?php if ($pageDescription !== ''): ?>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <?php endif; ?>
  <?php if ($noIndex): ?>
  <meta name="robots" content="noindex">
  <?php else: ?>
  <link rel="canonical" href="<?= e(lang_url($pagePath)) ?>">
  <?php foreach (languages() as $code): ?>
  <link rel="alternate" hreflang="<?= e($code) ?>" href="<?= e(lang_url($pagePath, $code)) ?>">
  <?php endforeach; ?>
  <link rel="alternate" hreflang="x-default" href="<?= e(base_url($pagePath === '' ? '' : 'id/' . $pagePath)) ?>">
  <?php endif; ?>

  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($docTitle) ?>">
  <?php if ($pageDescription !== ''): ?>
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <?php endif; ?>
  <meta property="og:url" content="<?= e(lang_url($pagePath)) ?>">
  <meta property="og:locale" content="<?= e(t('meta.locale')) ?>">
  <meta property="og:site_name" content="<?= e($fullName . ' · ' . t('meta.site')) ?>">
  <?php if ($ogImage): ?>
  <meta property="og:image" content="<?= e($ogImage['url']) ?>">
  <meta property="og:image:width" content="<?= e($ogImage['w']) ?>">
  <meta property="og:image:height" content="<?= e($ogImage['h']) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <?php endif; ?>

  <meta name="theme-color" content="#F2F5FF" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#14111F" media="(prefers-color-scheme: dark)">

  <link rel="preload" href="/assets/fonts/public-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/lexend-mega-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <?php if ($preloadImage): ?>
  <link rel="preload" as="image" href="<?= e($preloadImage['src']) ?>" imagesrcset="<?= e($preloadImage['srcset']) ?>" imagesizes="<?= e($preloadImage['sizes']) ?>" fetchpriority="high">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">

  <script>
    /* Pasang tema SEBELUM halaman tampil supaya warna tidak berkedip.
       Pilihan tersimpan di browser; kalau belum ada, ikut pengaturan perangkat. */
    (function () {
      var root = document.documentElement, theme = null;
      root.className += ' js';
      try { theme = localStorage.getItem('theme'); } catch (e) {}
      if (theme !== 'dark' && theme !== 'light') {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      }
      root.setAttribute('data-theme', theme);
    })();
  </script>
</head>
<body>
<?php require ROOT_PATH . '/includes/icons.php'; ?>
<a class="skip-link" href="#main"><?= e(t('skip')) ?></a>

<header class="nav" id="top">
  <div class="wrap">
    <div class="nav-in box">
      <a class="brand" href="<?= e(lang_url('')) ?>" aria-label="<?= e($fullName . ' — ' . t('nav.home')) ?>">
        <b aria-hidden="true"><?= e(initials($fullName)) ?>.</b>
        <span><?= e($fullName) ?></span>
      </a>

      <nav class="links" id="nav-links" aria-label="<?= e(t('nav.label')) ?>">
        <?php foreach ($navItems as [$id, $label, $href]): ?>
        <a href="<?= e($href) ?>"<?= $navActive === $id ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>

      <div class="tools">
        <div class="lang" role="group" aria-label="<?= e(t('lang.switch')) ?>">
          <?php foreach (languages() as $code): ?>
          <a href="<?= e(lang_url($pagePath, $code)) ?>" hreflang="<?= e($code) ?>" lang="<?= e($code) ?>"
             <?= $code === lang() ? 'aria-current="true"' : '' ?>><?= e(strtoupper($code)) ?></a>
          <?php endforeach; ?>
        </div>
        <button class="ibtn white theme-btn" type="button" aria-label="<?= e(t('theme.toggle')) ?>" aria-pressed="false">
          <?= icon('moon', 'i i-moon') ?><?= icon('sun', 'i i-sun') ?>
        </button>
        <button class="ibtn white menu-btn" type="button" aria-controls="nav-links" aria-expanded="false"
                aria-label="<?= e(t('nav.menu_open')) ?>"
                data-label-open="<?= e(t('nav.menu_open')) ?>" data-label-close="<?= e(t('nav.menu_close')) ?>">
          <?= icon('menu', 'i i-menu') ?><?= icon('x', 'i i-close') ?>
        </button>
      </div>
    </div>
  </div>
</header>

<main id="main" tabindex="-1">
