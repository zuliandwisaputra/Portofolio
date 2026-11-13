<?php
/**
 * Bagian bersama untuk tambah & ubah skill: nilai awal, validasi, dan tampilan formulir.
 * File ini tidak bisa dibuka langsung; dipanggil dari create.php dan edit.php.
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

/** Tingkat skill (opsional, tidak ditampilkan di website). */
const SKILL_LEVELS = ['' => 'Tidak diisi', '1' => 'Dasar', '2' => 'Menengah', '3' => 'Mahir'];

function skill_defaults(): array
{
    $categories = config('skill_categories', []);
    return [
        'name'       => '',
        'category'   => (string) array_key_first($categories),
        'level'      => '',
        'status'     => 'published',
        'sort_order' => (string) next_sort_order('skills'),
    ];
}

/** Ambil isian dari formulir yang dikirim. */
function skill_input(): array
{
    return [
        'name'       => trim((string) ($_POST['name'] ?? '')),
        'category'   => (string) ($_POST['category'] ?? ''),
        'level'      => (string) ($_POST['level'] ?? ''),
        'status'     => (string) ($_POST['status'] ?? ''),
        'sort_order' => trim((string) ($_POST['sort_order'] ?? '')),
    ];
}

/** Periksa isian. Mengembalikan daftar error [nama isian => pesan]. */
function skill_validate(array $d, ?int $id = null): array
{
    $errors = [];

    if ($msg = check_text($d['name'], 'nama skill', 60, true)) {
        $errors['name'] = $msg;
    } else {
        $stmt = db()->prepare('SELECT COUNT(*) FROM skills WHERE name = ? AND id <> ?');
        $stmt->execute([$d['name'], $id ?? 0]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errors['name'] = 'Skill "' . $d['name'] . '" sudah ada.';
        }
    }
    if (!array_key_exists($d['category'], config('skill_categories', []))) {
        $errors['category'] = 'Pilih kategori skill.';
    }
    if (!array_key_exists($d['level'], SKILL_LEVELS)) {
        $errors['level'] = 'Pilih tingkat yang tersedia.';
    }
    if (!isset(STATUS_LABELS[$d['status']])) {
        $errors['status'] = 'Pilih status Draf atau Terbit.';
    }
    if (filter_var($d['sort_order'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]) === false) {
        $errors['sort_order'] = 'Urutan harus berupa angka 0 sampai 9999.';
    }
    return $errors;
}

/** Nilai siap simpan ke database (urutan sesuai kolom di query). */
function skill_values(array $d): array
{
    return [
        $d['name'],
        $d['category'],
        $d['level'] === '' ? null : (int) $d['level'],
        $d['status'],
        (int) $d['sort_order'],
    ];
}

/** Tampilkan formulir skill. */
function skill_form(array $d, array $errors, string $action, string $submitLabel): void
{
    ?>
    <form method="post" action="<?= e($action) ?>" class="form card box form-narrow" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="name">Nama skill <span class="req" aria-hidden="true">*</span></label>
        <input class="input" id="name" name="name" type="text" maxlength="60" required
               value="<?= e($d['name']) ?>"<?= field_attrs($errors, 'name', 'name-hint') ?>>
        <p class="hint" id="name-hint">Contoh: PHP, MySQL, Figma. Nama teknologi sama di dua bahasa.</p>
        <?= field_error($errors, 'name') ?>
      </div>

      <div class="grid-2">
        <div class="field">
          <label for="category">Kategori <span class="req" aria-hidden="true">*</span></label>
          <select class="input" id="category" name="category" required<?= field_attrs($errors, 'category') ?>>
            <?php foreach (config('skill_categories', []) as $key => $labels): ?>
            <option value="<?= e($key) ?>" <?= $d['category'] === $key ? 'selected' : '' ?>><?= e($labels['id']) ?></option>
            <?php endforeach; ?>
          </select>
          <?= field_error($errors, 'category') ?>
        </div>
        <div class="field">
          <label for="level">Tingkat <span class="opt">(opsional)</span></label>
          <select class="input" id="level" name="level"<?= field_attrs($errors, 'level', 'level-hint') ?>>
            <?php foreach (SKILL_LEVELS as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= (string) $d['level'] === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="hint" id="level-hint">Hanya untuk catatanmu, tidak tampil di website.</p>
          <?= field_error($errors, 'level') ?>
        </div>
      </div>

      <div class="grid-2">
        <fieldset class="field" id="status"<?= isset($errors['status']) ? ' aria-describedby="status-err"' : '' ?>>
          <legend>Status <span class="req" aria-hidden="true">*</span></legend>
          <div class="choices">
            <?php foreach (STATUS_LABELS as $value => $label): ?>
            <label class="choice"><input type="radio" name="status" value="<?= e($value) ?>" <?= $d['status'] === $value ? 'checked' : '' ?>> <?= e($label) ?></label>
            <?php endforeach; ?>
          </div>
          <?= field_error($errors, 'status') ?>
        </fieldset>
        <div class="field">
          <label for="sort_order">Urutan <span class="req" aria-hidden="true">*</span></label>
          <input class="input" id="sort_order" name="sort_order" type="number" min="0" max="9999" inputmode="numeric" required
                 value="<?= e($d['sort_order']) ?>"<?= field_attrs($errors, 'sort_order', 'sort-hint') ?>>
          <p class="hint" id="sort-hint">Angka kecil tampil lebih dulu.</p>
          <?= field_error($errors, 'sort_order') ?>
        </div>
      </div>

      <div class="form-actions">
        <a class="btn white" href="/admin/skills/">Batal</a>
        <button class="btn bg-blue" type="submit"><?= icon('check') ?><?= e($submitLabel) ?></button>
      </div>
    </form>
    <?php
}
