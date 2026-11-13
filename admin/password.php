<?php
/**
 * Ganti password admin. Wajib memasukkan password lama lebih dulu.
 * Catatan: demi keamanan, isian password tidak diisi ulang setelah ada kesalahan.
 */

require dirname(__DIR__) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';

$admin  = require_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old     = (string) ($_POST['old_password'] ?? '');
    $new     = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $stmt->execute([$admin['id']]);
    $hash = (string) $stmt->fetchColumn();

    if ($old === '') {
        $errors['old_password'] = 'Isi password lama.';
    } elseif (!password_verify($old, $hash)) {
        $errors['old_password'] = 'Password lama salah.';
    }

    if ($new === '') {
        $errors['new_password'] = 'Isi password baru.';
    } elseif (mb_strlen($new) < PASSWORD_MIN_LENGTH) {
        $errors['new_password'] = 'Password baru minimal ' . PASSWORD_MIN_LENGTH . ' karakter.';
    } elseif (strlen($new) > PASSWORD_MAX_LENGTH) {
        $errors['new_password'] = 'Password baru terlalu panjang (maksimal ' . PASSWORD_MAX_LENGTH . ' karakter biasa).';
    } elseif ($new === $old) {
        $errors['new_password'] = 'Password baru harus berbeda dari password lama.';
    }

    if ($confirm === '') {
        $errors['confirm_password'] = 'Ulangi password baru.';
    } elseif (!isset($errors['new_password']) && $confirm !== $new) {
        $errors['confirm_password'] = 'Ulangan password tidak sama dengan password baru.';
    }

    if (!$errors) {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
        session_regenerate_id(true);
        flash('success', 'Password berhasil diganti. Pakai password baru saat login berikutnya.');
        redirect('/admin/password.php');
    }
}

$fields = [
    'old_password'     => ['Password lama', 'current-password', null],
    'new_password'     => ['Password baru', 'new-password', 'Minimal ' . PASSWORD_MIN_LENGTH . ' karakter. Gabungkan huruf, angka, dan simbol supaya sulit ditebak.'],
    'confirm_password' => ['Ulangi password baru', 'new-password', null],
];

$pageTitle  = 'Ganti password';
$activeMenu = 'password';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="narrow">
  <?php if ($errors): ?>
  <div class="alert alert-error error-summary" role="alert" tabindex="-1">
    <?= icon('alert') ?>
    <div>
      <p><strong>Password belum diganti.</strong> Perbaiki <?= count($errors) ?> isian berikut:</p>
      <ul>
        <?php foreach ($errors as $name => $msg): ?>
        <li><a href="#<?= e($name) ?>"><?= e($msg) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <form method="post" action="/admin/password.php" class="form card box" novalidate>
    <?= csrf_field() ?>
    <?php foreach ($fields as $name => [$label, $autocomplete, $hint]): ?>
    <?php
      $describedBy = [];
      if ($hint) { $describedBy[] = $name . '-hint'; }
      if (isset($errors[$name])) { $describedBy[] = $name . '-err'; }
    ?>
    <div class="field">
      <label for="<?= e($name) ?>"><?= e($label) ?> <span class="req" aria-hidden="true">*</span></label>
      <div class="pw">
        <input class="input" id="<?= e($name) ?>" name="<?= e($name) ?>" type="password" required
               autocomplete="<?= e($autocomplete) ?>"
               <?= $describedBy ? 'aria-describedby="' . e(implode(' ', $describedBy)) . '"' : '' ?>
               <?= isset($errors[$name]) ? 'aria-invalid="true"' : '' ?>>
        <button class="pw-toggle" type="button" data-target="<?= e($name) ?>" aria-pressed="false" aria-label="Tampilkan password">
          <?= icon('eye', 'i i-eye') ?><?= icon('eye-off', 'i i-eye-off') ?>
        </button>
      </div>
      <?php if ($hint): ?><p class="hint" id="<?= e($name) ?>-hint"><?= e($hint) ?></p><?php endif; ?>
      <?php if (isset($errors[$name])): ?>
      <p class="err" id="<?= e($name) ?>-err"><?= icon('alert') ?><?= e($errors[$name]) ?></p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <button class="btn bg-blue" type="submit"><?= icon('key') ?>Simpan password baru</button>
  </form>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
