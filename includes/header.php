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
$fullName        = trim((string) ($profile['full_name'] ?? '')) ?: 'Portofolio';
$pageDescription = $pageDescription ?? tr($profile, 'meta_desc');

$siteLabel = lang() === 'en' ? 'Portfolio' : 'Portofolio';
$docTitle  = $pageTitle === '' ? $fullName . ' · ' . $siteLabel : $pageTitle . ' · ' . $fullName;

// Tautan navbar: di beranda cukup #bagian, di halaman lain menuju beranda#bagian
$home = $isHome ? '' : lang_url('');
$navItems = [
    ['about',        t('nav.about')],
    ['skills',       t('nav.skills')],
    ['projects',     t('nav.projects')],
    ['experience',   t('nav.experience')],
    ['achievements', t('nav.achievements')],
    ['contact',      t('nav.contact')],
];
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

  <meta name="theme-color" content="#F2F5FF" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#14111F" media="(prefers-color-scheme: dark)">

  <link rel="preload" href="/assets/fonts/public-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/lexend-mega-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
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
        <?php foreach ($navItems as [$id, $label]): ?>
        <a href="<?= e($home) ?>#<?= e($id) ?>"><?= e($label) ?></a>
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
