<?php
/**
 * Halaman login admin: /admin/login.php
 * - Username & password (akun dibuat lewat data awal database, tidak ada halaman daftar).
 * - 5 kali gagal dari IP yang sama → dikunci 15 menit.
 * - Formulir memakai token CSRF.
 */

require dirname(__DIR__) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';

start_session();
admin_headers();

$next = safe_admin_next($_POST['next'] ?? $_GET['next'] ?? null);
if (is_logged_in()) {
    redirect($next);
}

$ip       = client_ip();
$lock     = login_lock_seconds($ip);
$errors   = [];
$general  = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($lock === 0) {
        if ($username === '') {
            $errors['username'] = 'Isi username.';
        }
        if ($password === '') {
            $errors['password'] = 'Isi password.';
        }

        if (!$errors) {
            $admin = check_credentials($username, $password);
            if ($admin) {
                clear_failed_logins($ip);
                login_admin($admin);
                flash('success', 'Selamat datang kembali, ' . $admin['username'] . '!');
                redirect($next);
            }

            record_failed_login($ip);
            $lock = login_lock_seconds($ip);
            if ($lock === 0) {
                $left = LOGIN_MAX_ATTEMPTS - failed_login_count($ip);
                $general = 'Username atau password salah. Sisa percobaan sebelum dikunci: ' . $left . '.';
            }
        }
    }
}

$flash = take_flash();
$lockMinutes = (int) ceil($lock / 60);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Masuk · Panel admin</title>
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
<main class="auth" id="main">
  <div class="auth-card">
    <div class="auth-head">
      <a class="brand" href="/"><b aria-hidden="true">ZDS.</b><span>Panel admin</span></a>
      <span class="sticker bg-yellow" style="--r:3deg"><?= icon('lock') ?>Khusus admin</span>
    </div>

    <div>
      <h1>Masuk ke panel admin</h1>
      <p class="muted">Kelola isi website tanpa menyentuh kode.</p>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="status">
      <?= icon($flash['type'] === 'success' ? 'circle-check' : 'info') ?><p><?= e($flash['message']) ?></p>
    </div>
    <?php endif; ?>

    <?php if ($lock > 0): ?>
    <div class="alert alert-error" role="alert">
      <?= icon('lock') ?>
      <p>Terlalu banyak percobaan gagal. Login dikunci, coba lagi dalam <strong><?= e($lockMinutes) ?> menit</strong>.</p>
    </div>
    <?php elseif ($general): ?>
    <div class="alert alert-error" role="alert"><?= icon('alert') ?><p><?= e($general) ?></p></div>
    <?php endif; ?>

    <form method="post" action="/admin/login.php" class="form" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">

      <div class="field">
        <label for="username">Username</label>
        <input class="input" id="username" name="username" type="text" autocomplete="username" required
               autocapitalize="none" spellcheck="false" value="<?= e($username) ?>"
               <?= isset($errors['username']) ? 'aria-invalid="true" aria-describedby="username-err"' : '' ?>
               <?= $lock > 0 ? 'disabled' : '' ?>>
        <?php if (isset($errors['username'])): ?>
        <p class="err" id="username-err"><?= icon('alert') ?><?= e($errors['username']) ?></p>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="pw">
          <input class="input" id="password" name="password" type="password" autocomplete="current-password" required
                 <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-err"' : '' ?>
                 <?= $lock > 0 ? 'disabled' : '' ?>>
          <button class="pw-toggle" type="button" data-target="password" aria-pressed="false" aria-label="Tampilkan password">
            <?= icon('eye', 'i i-eye') ?><?= icon('eye-off', 'i i-eye-off') ?>
          </button>
        </div>
        <?php if (isset($errors['password'])): ?>
        <p class="err" id="password-err"><?= icon('alert') ?><?= e($errors['password']) ?></p>
        <?php endif; ?>
      </div>

      <button class="btn bg-blue" type="submit" <?= $lock > 0 ? 'disabled' : '' ?>><?= icon('lock') ?>Masuk</button>
    </form>

    <a class="auth-back" href="/"><?= icon('arrow') ?>Kembali ke website</a>
  </div>
</main>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
