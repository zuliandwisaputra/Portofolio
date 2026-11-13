<?php
/**
 * Menu Profil: data diri dan pengaturan website (tabel profile, satu baris id = 1).
 * Foto hero disimpan 800 px + 400 px (WebP). CV berupa PDF, satu file per bahasa.
 */

require dirname(__DIR__) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require ROOT_PATH . '/includes/upload.php';

const PROFILE_PHOTO_FOLDER = 'profile';
const PROFILE_CV_FOLDER    = 'cv';
const ROLES_MAX            = 6;

$admin   = require_admin();
$profile = profile();
if (!$profile) {
    // Baris profil hilang (misalnya terhapus manual): buat ulang yang kosong
    db()->prepare("INSERT INTO profile (id, full_name, roles_ind, tagline_ind, about_ind) VALUES (1, 'Nama Kamu', 'Web Developer', 'Tagline', 'Tentang saya')")->execute();
    redirect('/admin/profile.php');
}

/** Ubah teks "satu peran per baris" menjadi daftar peran yang bersih. */
function role_lines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), fn ($r) => $r !== ''));
}

$text = fn (string $key): string => trim((string) ($_POST[$key] ?? ''));
$fields = ['full_name', 'roles_ind', 'roles_eng', 'tagline_ind', 'tagline_eng', 'status_text_ind', 'status_text_eng',
    'about_ind', 'about_eng', 'email', 'whatsapp', 'github_url', 'linkedin_url', 'instagram_url', 'tiktok_url',
    'meta_desc_ind', 'meta_desc_eng'];

$data = [];
foreach ($fields as $f) {
    $data[$f] = (string) ($profile[$f] ?? '');
}
$data['status_enabled']          = (int) $profile['status_enabled'];
$data['intro_animation_enabled'] = (int) $profile['intro_animation_enabled'];
$remove = ['photo' => false, 'cv_ind' => false, 'cv_eng' => false];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($fields as $f) {
        $data[$f] = $text($f);
    }
    $data['status_enabled']          = empty($_POST['status_enabled']) ? 0 : 1;
    $data['intro_animation_enabled'] = empty($_POST['intro_animation_enabled']) ? 0 : 1;
    foreach ($remove as $key => $_) {
        $remove[$key] = !empty($_POST['remove_' . $key]);
    }
    $files = [];
    foreach (['photo', 'cv_ind', 'cv_eng'] as $key) {
        $files[$key] = file_was_sent($_FILES[$key] ?? null) ? $_FILES[$key] : null;
    }

    // ---- Validasi ----
    $rules = [
        'full_name'       => ['nama lengkap', 100, true],
        'tagline_ind'     => ['tagline (Indonesia)', 255, true],
        'tagline_eng'     => ['Tagline (Inggris)', 255, false],
        'status_text_ind' => ['teks badge status (Indonesia)', 120, (bool) $data['status_enabled']],
        'status_text_eng' => ['Teks badge status (Inggris)', 120, false],
        'about_ind'       => ['teks Tentang (Indonesia)', 5000, true],
        'about_eng'       => ['Teks Tentang (Inggris)', 5000, false],
        'meta_desc_ind'   => ['deskripsi mesin pencari (Indonesia)', 160, true],
        'meta_desc_eng'   => ['Deskripsi mesin pencari (Inggris)', 160, false],
    ];
    foreach ($rules as $field => [$label, $max, $required]) {
        if ($msg = check_text($data[$field], $label, $max, $required)) {
            $errors[$field] = $msg;
        }
    }
    foreach (['roles_ind' => [true, 'Indonesia'], 'roles_eng' => [false, 'Inggris']] as $field => [$required, $langName]) {
        $roles = role_lines($data[$field]);
        if ($required && !$roles) {
            $errors[$field] = 'Isi minimal satu peran (bahasa ' . $langName . ').';
        } elseif (count($roles) > ROLES_MAX) {
            $errors[$field] = 'Peran (bahasa ' . $langName . ') maksimal ' . ROLES_MAX . ' baris.';
        } else {
            foreach ($roles as $role) {
                if (mb_strlen($role) > 60) {
                    $errors[$field] = 'Peran "' . mb_strimwidth($role, 0, 30, '…') . '" terlalu panjang (maksimal 60 karakter).';
                    break;
                }
            }
        }
        $data[$field] = implode("\n", $roles);
    }
    if ($data['email'] !== '' && (mb_strlen($data['email']) > 150 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
        $errors['email'] = 'Format email belum benar. Contoh: nama@gmail.com';
    }
    if ($data['whatsapp'] !== '' && !preg_match('/^\+?[0-9][0-9 \-]{7,19}$/', $data['whatsapp'])) {
        $errors['whatsapp'] = 'Nomor WhatsApp hanya boleh angka, spasi, tanda -, dan + di depan. Contoh: 0812-3456-7890';
    }
    foreach (['github_url' => 'Tautan GitHub', 'linkedin_url' => 'Tautan LinkedIn', 'instagram_url' => 'Tautan Instagram', 'tiktok_url' => 'Tautan TikTok'] as $field => $label) {
        if ($msg = check_url($data[$field], $label)) {
            $errors[$field] = $msg;
        }
    }
    if ($files['photo'] && ($msg = image_upload_error($files['photo']))) {
        $errors['photo'] = $msg . ' Pilih ulang foto.';
    }
    foreach (['cv_ind' => 'Indonesia', 'cv_eng' => 'Inggris'] as $key => $langName) {
        if ($files[$key] && ($msg = pdf_upload_error($files[$key]))) {
            $errors[$key] = $msg . ' Pilih ulang CV bahasa ' . $langName . '.';
        }
    }

    // ---- Simpan ----
    if (!$errors) {
        $savedNow = [];
        $deleteAfter = [];
        $newValues = ['photo' => $profile['photo'], 'cv_ind' => $profile['cv_ind'], 'cv_eng' => $profile['cv_eng']];
        try {
            if ($files['photo']) {
                $savedNow[] = ['photo', $newValues['photo'] = save_image($files['photo'], PROFILE_PHOTO_FOLDER, 800, 400)];
                $deleteAfter[] = ['photo', $profile['photo']];
            } elseif ($remove['photo']) {
                $deleteAfter[] = ['photo', $profile['photo']];
                $newValues['photo'] = null;
            }
            foreach (['cv_ind', 'cv_eng'] as $key) {
                if ($files[$key]) {
                    $savedNow[] = [$key, $newValues[$key] = save_pdf($files[$key], PROFILE_CV_FOLDER)];
                    $deleteAfter[] = [$key, $profile[$key]];
                } elseif ($remove[$key]) {
                    $deleteAfter[] = [$key, $profile[$key]];
                    $newValues[$key] = null;
                }
            }

            $nullable = fn (string $v): ?string => $v === '' ? null : $v;
            db()->prepare(
                'UPDATE profile SET full_name = ?, roles_ind = ?, roles_eng = ?, tagline_ind = ?, tagline_eng = ?,
                        status_text_ind = ?, status_text_eng = ?, status_enabled = ?, about_ind = ?, about_eng = ?,
                        photo = ?, cv_ind = ?, cv_eng = ?, email = ?, whatsapp = ?,
                        github_url = ?, linkedin_url = ?, instagram_url = ?, tiktok_url = ?,
                        meta_desc_ind = ?, meta_desc_eng = ?, intro_animation_enabled = ?
                  WHERE id = 1'
            )->execute([
                $data['full_name'], $data['roles_ind'], $nullable($data['roles_eng']), $data['tagline_ind'], $nullable($data['tagline_eng']),
                $nullable($data['status_text_ind']), $nullable($data['status_text_eng']), $data['status_enabled'],
                $data['about_ind'], $nullable($data['about_eng']),
                $newValues['photo'], $newValues['cv_ind'], $newValues['cv_eng'],
                $nullable($data['email']), $nullable($data['whatsapp']),
                $nullable($data['github_url']), $nullable($data['linkedin_url']), $nullable($data['instagram_url']), $nullable($data['tiktok_url']),
                $data['meta_desc_ind'], $nullable($data['meta_desc_eng']), $data['intro_animation_enabled'],
            ]);
        } catch (Throwable $e) {
            foreach ($savedNow as [$key, $name]) {
                $key === 'photo' ? delete_image(PROFILE_PHOTO_FOLDER, $name) : delete_pdf(PROFILE_CV_FOLDER, $name);
            }
            $errors['_save'] = save_error('Profil gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }

        if (!$errors) {
            foreach ($deleteAfter as [$key, $name]) {
                $key === 'photo' ? delete_image(PROFILE_PHOTO_FOLDER, $name) : delete_pdf(PROFILE_CV_FOLDER, $name);
            }
            flash('success', 'Profil berhasil disimpan.');
            redirect('/admin/profile.php');
        }
    }
}

/** Isian teks satu baris sederhana. */
function profile_input(array $data, array $errors, string $name, string $label, string $type = 'text', int $max = 255, string $placeholder = '', ?string $hint = null, bool $required = false): void
{
    ?>
    <div class="field">
      <label for="<?= e($name) ?>"><?= e($label) ?> <?= $required ? '<span class="req" aria-hidden="true">*</span>' : '<span class="opt">(opsional)</span>' ?></label>
      <input class="input" id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" maxlength="<?= e($max) ?>"
             placeholder="<?= e($placeholder) ?>" value="<?= e($data[$name]) ?>"<?= $required ? ' required' : '' ?><?= field_attrs($errors, $name, $hint ? $name . '-hint' : null) ?>>
      <?php if ($hint): ?><p class="hint" id="<?= e($name) ?>-hint"><?= e($hint) ?></p><?php endif; ?>
      <?= field_error($errors, $name) ?>
    </div>
    <?php
}

/** Isian CV: tautan file saat ini + centang hapus + pilih file baru. */
function cv_field(array $errors, array $profile, array $remove, string $key, string $tag, string $label): void
{
    $current = $profile[$key] ?? null;
    $path = $current ? ROOT_PATH . '/uploads/' . PROFILE_CV_FOLDER . '/' . $current : '';
    ?>
    <div class="cv-field">
      <p class="label"><span class="lang-tag lang-<?= $key === 'cv_ind' ? 'ind' : 'eng' ?>"><?= e($tag) ?></span><?= e($label) ?></p>
      <?php if ($current && is_file($path)): ?>
      <div class="cv-current">
        <a href="/uploads/<?= e(PROFILE_CV_FOLDER . '/' . $current) ?>" target="_blank" rel="noopener"><?= icon('external') ?>Lihat CV saat ini (<?= e(format_bytes((int) filesize($path))) ?>)</a>
        <label class="choice choice-danger"><input type="checkbox" name="remove_<?= e($key) ?>" value="1" <?= $remove[$key] ? 'checked' : '' ?>> Hapus CV ini</label>
      </div>
      <?php else: ?>
      <p class="hint"><?= $key === 'cv_ind'
          ? 'Belum ada CV. Tombol "Unduh CV" baru tampil setelah CV Indonesia diunggah.'
          : 'Belum ada CV Inggris. Pengunjung versi Inggris akan mengunduh CV Indonesia.' ?></p>
      <?php endif; ?>
      <div class="field">
        <label for="<?= e($key) ?>"><?= $current ? 'Ganti CV' : 'Pilih CV' ?> <span class="opt">(PDF, maks. 5 MB)</span></label>
        <input class="input file" id="<?= e($key) ?>" name="<?= e($key) ?>" type="file" accept="application/pdf,.pdf"<?= field_attrs($errors, $key) ?>>
        <?= field_error($errors, $key) ?>
      </div>
    </div>
    <?php
}

$pageTitle  = 'Profil';
$activeMenu = 'profile';
require ROOT_PATH . '/includes/admin_header.php';
?>
<?= error_summary($errors, 'Profil belum tersimpan.') ?>
<form method="post" action="/admin/profile.php" enctype="multipart/form-data" class="form project-form" novalidate>
  <?= csrf_field() ?>

  <section class="panel card box" aria-labelledby="sec-hero">
    <h2 class="panel-title" id="sec-hero"><?= icon('user') ?>Identitas & hero</h2>
    <?php profile_input($data, $errors, 'full_name', 'Nama lengkap', 'text', 100, '', null, true); ?>
    <div class="pair">
      <?php foreach (['roles_ind' => ['ID', 'Peran (satu per baris)', true], 'roles_eng' => ['EN', 'Roles (one per line)', false]] as $name => [$tag, $label, $req]): ?>
      <div class="field">
        <label for="<?= e($name) ?>"><span class="lang-tag lang-<?= $tag === 'ID' ? 'ind' : 'eng' ?>"><?= e($tag) ?></span><?= e($label) ?>
          <?= $req ? '<span class="req" aria-hidden="true">*</span>' : '<span class="opt">(opsional)</span>' ?></label>
        <textarea class="input textarea-sm" id="<?= e($name) ?>" name="<?= e($name) ?>" rows="4"<?= field_attrs($errors, $name, $req ? 'roles-hint' : null) ?>><?= e($data[$name]) ?></textarea>
        <?php if ($req): ?><p class="hint" id="roles-hint">Muncul bergantian dengan efek ketik di hero. Maksimal <?= ROLES_MAX ?> peran.</p><?php endif; ?>
        <?= field_error($errors, $name) ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php bilingual_field($data, $errors, 'tagline', 'Tagline', 'Tagline', 255, true, false, 'Satu kalimat di bawah peran.'); ?>
    <div class="field">
      <span class="label">Badge status</span>
      <label class="choice"><input type="checkbox" name="status_enabled" value="1" <?= $data['status_enabled'] ? 'checked' : '' ?>> Tampilkan badge di atas nama</label>
    </div>
    <?php bilingual_field($data, $errors, 'status_text', 'Teks badge', 'Badge text', 120, (bool) $data['status_enabled'], false, 'Contoh: Terbuka untuk magang & freelance.'); ?>
  </section>

  <section class="panel card box" aria-labelledby="sec-about">
    <h2 class="panel-title" id="sec-about"><?= icon('pencil') ?>Tentang</h2>
    <?php bilingual_field($data, $errors, 'about', 'Teks Tentang', 'About text', 5000, true, true, 'Dua sampai tiga paragraf. Pisahkan paragraf dengan baris kosong.'); ?>
  </section>

  <section class="panel card box" aria-labelledby="sec-photo">
    <h2 class="panel-title" id="sec-photo"><?= icon('photo') ?>Foto hero</h2>
    <?php image_field($errors, 'photo', 'Foto hero', PROFILE_PHOTO_FOLDER, $profile['photo'], $remove['photo'],
        'Foto setengah badan, tegak (rasio 4:5). Paling bagus PNG dengan latar dihapus. JPG, PNG, atau WebP, maksimal 2 MB; disimpan otomatis 800 px (WebP).'); ?>
  </section>

  <section class="panel card box" aria-labelledby="sec-cv">
    <h2 class="panel-title" id="sec-cv"><?= icon('upload') ?>CV</h2>
    <div class="pair">
      <?php cv_field($errors, $profile, $remove, 'cv_ind', 'ID', 'CV bahasa Indonesia'); ?>
      <?php cv_field($errors, $profile, $remove, 'cv_eng', 'EN', 'CV bahasa Inggris'); ?>
    </div>
  </section>

  <section class="panel card box" aria-labelledby="sec-contact">
    <h2 class="panel-title" id="sec-contact"><?= icon('mail') ?>Kontak & media sosial</h2>
    <div class="grid-2">
      <?php profile_input($data, $errors, 'email', 'Email', 'email', 150, 'nama@gmail.com', 'Tampil di bagian Kontak.'); ?>
      <?php profile_input($data, $errors, 'whatsapp', 'Nomor WhatsApp', 'tel', 30, '0812-3456-7890', 'Kosongkan supaya tombol WhatsApp tidak tampil.'); ?>
      <?php profile_input($data, $errors, 'linkedin_url', 'LinkedIn', 'url', 255, 'https://www.linkedin.com/in/...'); ?>
      <?php profile_input($data, $errors, 'github_url', 'GitHub', 'url', 255, 'https://github.com/...'); ?>
      <?php profile_input($data, $errors, 'instagram_url', 'Instagram', 'url', 255, 'https://www.instagram.com/...'); ?>
      <?php profile_input($data, $errors, 'tiktok_url', 'TikTok', 'url', 255, 'https://www.tiktok.com/@...'); ?>
    </div>
    <p class="hint">Media sosial yang dikosongkan tidak tampil di website.</p>
  </section>

  <section class="panel card box" aria-labelledby="sec-seo">
    <h2 class="panel-title" id="sec-seo"><?= icon('world') ?>Mesin pencari</h2>
    <?php bilingual_field($data, $errors, 'meta_desc', 'Deskripsi beranda', 'Home description', 160, true, false, 'Muncul di hasil Google di bawah judul. Maksimal 160 karakter.'); ?>
  </section>

  <section class="panel card box" aria-labelledby="sec-anim">
    <h2 class="panel-title" id="sec-anim"><?= icon('star') ?>Animasi</h2>
    <div class="field">
      <label class="choice"><input type="checkbox" name="intro_animation_enabled" value="1" <?= $data['intro_animation_enabled'] ? 'checked' : '' ?>> Putar animasi masuk (tirai warna) saat website dibuka</label>
      <p class="hint">Kalau dimatikan, beranda langsung tampil tanpa tirai. Animasi saat menggulir tetap berjalan.</p>
    </div>
  </section>

  <div class="form-actions form-actions-sticky">
    <a class="btn white" href="/admin/">Batal</a>
    <button class="btn bg-blue" type="submit"><?= icon('check') ?>Simpan profil</button>
  </div>
</form>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
