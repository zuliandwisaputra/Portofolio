<?php
/**
 * Bagian atas halaman panel admin: <head>, menu samping, dan bilah atas.
 *
 * Variabel yang diisi halaman sebelum memanggil file ini:
 *   $admin      array  hasil require_admin()
 *   $pageTitle  string judul halaman
 *   $activeMenu string kunci menu yang sedang dibuka (lihat $menu di bawah)
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

require_once ROOT_PATH . '/includes/csrf.php';

$pageTitle  = $pageTitle ?? 'Panel admin';
$activeMenu = $activeMenu ?? '';
$flash      = take_flash();

// Jumlah pesan belum dibaca, tampil sebagai angka di menu Pesan
$stmt = db()->prepare('SELECT COUNT(*) FROM messages WHERE is_read = 0');
$stmt->execute();
$unreadMessages = (int) $stmt->fetchColumn();

// [kunci, label, alamat, ikon, tahap selesai]  (alamat null = belum dibuat)
$menu = [
    ['dashboard',    'Dashboard',                '/admin/',                   'dashboard', null],
    ['projects',     'Project',                  '/admin/projects/',          'folder',    null],
    ['skills',       'Skill',                    '/admin/skills/',            'tools',     null],
    ['experiences',  'Pengalaman & pendidikan',  '/admin/experiences/',       'school',    null],
    ['achievements', 'Sertifikat & lomba',       '/admin/achievements/',      'award',     null],
    ['messages',     'Pesan',                    '/admin/messages/',          'inbox',     null],
    ['profile',      'Profil',                   '/admin/profile.php',        'user',      null],
    ['password',     'Ganti password',           '/admin/password.php',       'key',       null],
];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($pageTitle) ?> · Panel admin</title>
  <link rel="preload" href="/assets/fonts/public-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <script>
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
<body class="admin">
<?php require ROOT_PATH . '/includes/icons.php'; ?>
<a class="skip-link" href="#main">Langsung ke isi utama</a>

<div class="admin-shell">
  <aside class="side">
    <div class="side-top">
      <a class="brand" href="/admin/"><b aria-hidden="true">ZDS.</b><span>Panel admin</span></a>
      <button class="ibtn white menu-btn" type="button" aria-controls="nav-links" aria-expanded="false"
              aria-label="Buka menu admin" data-label-open="Buka menu admin" data-label-close="Tutup menu admin">
        <?= icon('menu', 'i i-menu') ?><?= icon('x', 'i i-close') ?>
      </button>
    </div>

    <nav class="side-nav" id="nav-links" aria-label="Menu admin">
      <ul>
        <?php foreach ($menu as [$key, $label, $url, $ico, $stage]): ?>
        <li>
          <?php if ($url !== null): ?>
          <a href="<?= e($url) ?>" <?= $key === $activeMenu ? 'aria-current="page"' : '' ?>><?= icon($ico) ?><span><?= e($label) ?></span><?php if ($key === 'messages' && $unreadMessages > 0): ?><em class="nav-badge"><?= e($unreadMessages) ?><span class="sr-only"> belum dibaca</span></em><?php endif; ?></a>
          <?php else: ?>
          <span class="soon" aria-disabled="true"><?= icon($ico) ?><span><?= e($label) ?></span><em><?= e($stage) ?></em></span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="side-bottom">
        <a class="side-site" href="/" target="_blank" rel="noopener"><?= icon('external') ?><span>Lihat website</span></a>
        <form method="post" action="/admin/logout.php">
          <?= csrf_field() ?>
          <button class="side-logout" type="submit"><?= icon('logout') ?><span>Keluar</span></button>
        </form>
      </div>
    </nav>
  </aside>

  <div class="admin-main">
    <header class="topbar">
      <h1><?= e($pageTitle) ?></h1>
      <div class="topbar-tools">
        <span class="who"><?= icon('user') ?><?= e($admin['username'] ?? '') ?></span>
        <button class="ibtn white theme-btn" type="button" aria-label="Ganti tema terang atau gelap" aria-pressed="false">
          <?= icon('moon', 'i i-moon') ?><?= icon('sun', 'i i-sun') ?>
        </button>
        <form method="post" action="/admin/logout.php" class="topbar-logout">
          <?= csrf_field() ?>
          <button class="btn white btn-sm" type="submit"><?= icon('logout') ?>Keluar</button>
        </form>
      </div>
    </header>

    <main id="main" tabindex="-1" class="admin-content">
      <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
        <?= icon($flash['type'] === 'success' ? 'circle-check' : ($flash['type'] === 'error' ? 'alert' : 'info')) ?>
        <p><?= e($flash['message']) ?></p>
      </div>
      <?php endif; ?>
