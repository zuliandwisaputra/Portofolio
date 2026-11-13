<?php
/**
 * Fungsi bantu formulir dan tabel di panel admin.
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

/** Pilihan status konten. */
const STATUS_LABELS = ['draft' => 'Draf', 'published' => 'Terbit'];

/** Atribut aria untuk isian: menghubungkan petunjuk & pesan error ke isian. */
function field_attrs(array $errors, string $name, ?string $hintId = null): string
{
    $ids = [];
    if ($hintId !== null) {
        $ids[] = $hintId;
    }
    if (isset($errors[$name])) {
        $ids[] = $name . '-err';
    }
    $out = $ids ? ' aria-describedby="' . e(implode(' ', $ids)) . '"' : '';
    if (isset($errors[$name])) {
        $out .= ' aria-invalid="true"';
    }
    return $out;
}

/** Pesan error tepat di bawah isian. */
function field_error(array $errors, string $name): string
{
    if (!isset($errors[$name])) {
        return '';
    }
    return '<p class="err" id="' . e($name) . '-err">' . icon('alert') . e($errors[$name]) . '</p>';
}

/** Kotak ringkasan error di atas formulir, berisi tautan ke tiap isian yang salah. */
function error_summary(array $errors, string $title): string
{
    if (!$errors) {
        return '';
    }
    $items = '';
    foreach ($errors as $name => $message) {
        $items .= str_starts_with((string) $name, '_')
            ? '<li>' . e($message) . '</li>'
            : '<li><a href="#' . e($name) . '">' . e($message) . '</a></li>';
    }
    return '<div class="alert alert-error error-summary" role="alert" tabindex="-1">' . icon('alert')
        . '<div><p><strong>' . e($title) . '</strong> Perbaiki ' . count($errors) . ' hal berikut:</p><ul>' . $items . '</ul></div></div>';
}

/** Label status (Draf/Terbit) berwarna untuk tabel. */
function status_badge(string $status): string
{
    $label = STATUS_LABELS[$status] ?? $status;
    return '<span class="status status-' . e($status) . '">' . e($label) . '</span>';
}

/** Validasi panjang teks. Mengembalikan pesan error atau null. */
function check_text(string $value, string $label, int $max, bool $required): ?string
{
    if ($required && $value === '') {
        return "Isi $label.";
    }
    if (mb_strlen($value) > $max) {
        return "$label maksimal $max karakter (sekarang " . mb_strlen($value) . ').';
    }
    return null;
}

/** Validasi tautan opsional: kosong boleh, kalau diisi harus http:// atau https://. */
function check_url(string $value, string $label): ?string
{
    if ($value === '') {
        return null;
    }
    if (mb_strlen($value) > 255) {
        return "$label terlalu panjang (maksimal 255 karakter).";
    }
    if (safe_url($value) === '') {
        return "$label harus diawali http:// atau https:// dan berupa alamat yang benar.";
    }
    return null;
}

/**
 * Satu pasang isian Indonesia (wajib/opsional) + Inggris (selalu opsional), berdampingan.
 * Nama isian: <base>_ind dan <base>_eng (sesuai kolom database).
 */
function bilingual_field(array $d, array $errors, string $base, string $labelId, string $labelEn, int $max, bool $required, bool $textarea = false, ?string $hint = null): void
{
    ?>
    <div class="pair">
      <?php foreach (['ind' => [$labelId, 'ID', $required], 'eng' => [$labelEn, 'EN', false]] as $suffix => [$label, $tag, $req]):
          $name = $base . '_' . $suffix;
          $hintId = $hint && $suffix === 'ind' ? $name . '-hint' : null; ?>
      <div class="field">
        <label for="<?= e($name) ?>"><span class="lang-tag lang-<?= e($suffix) ?>"><?= e($tag) ?></span><?= e($label) ?>
          <?= $req ? '<span class="req" aria-hidden="true">*</span>' : '<span class="opt">(opsional)</span>' ?></label>
        <?php if ($textarea): ?>
        <textarea class="input" id="<?= e($name) ?>" name="<?= e($name) ?>" rows="7" maxlength="<?= e($max) ?>"
                  <?= $req ? 'required' : '' ?><?= field_attrs($errors, $name, $hintId) ?>><?= e($d[$name]) ?></textarea>
        <?php else: ?>
        <input class="input" id="<?= e($name) ?>" name="<?= e($name) ?>" type="text" maxlength="<?= e($max) ?>"
               value="<?= e($d[$name]) ?>" <?= $req ? 'required' : '' ?><?= field_attrs($errors, $name, $hintId) ?>>
        <?php endif; ?>
        <?php if ($hintId): ?><p class="hint" id="<?= e($hintId) ?>"><?= e($hint) ?></p><?php endif; ?>
        <?= field_error($errors, $name) ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Isian satu gambar: pratinjau gambar saat ini + centang "hapus" + pilih file baru.
 */
function image_field(array $errors, string $name, string $label, string $folder, ?string $current, bool $removeChecked, string $hint): void
{
    [$w, $h] = image_size($folder, $current, true);
    ?>
    <?php if ($current && $w): ?>
    <div class="cover-current">
      <img src="<?= e(upload_url($folder, $current, true)) ?>" width="<?= e($w) ?>" height="<?= e($h) ?>" alt="<?= e($label) ?> saat ini">
      <label class="choice"><input type="checkbox" name="remove_<?= e($name) ?>" value="1" <?= $removeChecked ? 'checked' : '' ?>> Hapus gambar ini</label>
    </div>
    <?php endif; ?>
    <div class="field">
      <label for="<?= e($name) ?>"><?= $current ? 'Ganti ' . e(mb_strtolower($label)) : e($label) ?> <span class="opt">(opsional)</span></label>
      <input class="input file" id="<?= e($name) ?>" name="<?= e($name) ?>" type="file" accept="image/jpeg,image/png,image/webp"<?= field_attrs($errors, $name, $name . '-hint') ?>>
      <p class="hint" id="<?= e($name) ?>-hint"><?= e($hint) ?></p>
      <?= field_error($errors, $name) ?>
    </div>
    <?php
}

/** Pilihan jenis (radio) sebagai fieldset. */
function choice_field(array $errors, string $name, string $legend, array $options, string $selected): void
{
    ?>
    <fieldset class="field" id="<?= e($name) ?>"<?= isset($errors[$name]) ? ' aria-describedby="' . e($name) . '-err"' : '' ?>>
      <legend><?= e($legend) ?> <span class="req" aria-hidden="true">*</span></legend>
      <div class="choices">
        <?php foreach ($options as $value => $label): ?>
        <label class="choice"><input type="radio" name="<?= e($name) ?>" value="<?= e($value) ?>" <?= $selected === (string) $value ? 'checked' : '' ?>> <?= e($label) ?></label>
        <?php endforeach; ?>
      </div>
      <?= field_error($errors, $name) ?>
    </fieldset>
    <?php
}

/* ------------------------------------------------------------------
 * Bulan & tahun (isian <input type="month">, disimpan sebagai tanggal 1)
 * ------------------------------------------------------------------ */

/** '2023-07-01' → '2023-07' untuk isian bulan. */
function month_value(?string $date): string
{
    return $date ? substr($date, 0, 7) : '';
}

/** '2023-07' atau '2023-07-15' → '2023-07-01'. Null kalau tidak valid. */
function parse_month(string $value): ?string
{
    if (!preg_match('/^(\d{4})-(\d{2})(?:-(\d{2}))?$/', trim($value), $m)) {
        return null;
    }
    $year = (int) $m[1];
    $month = (int) $m[2];
    if ($year < 1990 || $year > (int) date('Y') + 1 || $month < 1 || $month > 12) {
        return null;
    }
    return sprintf('%04d-%02d-01', $year, $month);
}

/* ------------------------------------------------------------------
 * Halaman tabel (10 baris per halaman)
 * ------------------------------------------------------------------ */

function paginate(int $total, int $perPage = 10): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
    return ['page' => $page, 'pages' => $pages, 'per' => $perPage, 'offset' => ($page - 1) * $perPage, 'total' => $total];
}

/** Tombol halaman sebelumnya / berikutnya + nomor halaman. */
function pagination_links(array $p, string $baseUrl): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $link = fn (int $n) => $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $n;
    $html = '<nav class="pager" aria-label="Halaman tabel"><ul>';
    $html .= $p['page'] > 1
        ? '<li><a href="' . e($link($p['page'] - 1)) . '" rel="prev">' . icon('arrow', 'i flip') . '<span>Sebelumnya</span></a></li>'
        : '';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $html .= $i === $p['page']
            ? '<li><span aria-current="page">' . $i . '</span></li>'
            : '<li><a href="' . e($link($i)) . '">' . $i . '</a></li>';
    }
    $html .= $p['page'] < $p['pages']
        ? '<li><a href="' . e($link($p['page'] + 1)) . '" rel="next"><span>Berikutnya</span>' . icon('arrow') . '</a></li>'
        : '';
    return $html . '</ul></nav>';
}

/* ------------------------------------------------------------------
 * Lain-lain
 * ------------------------------------------------------------------ */

/** Ubah judul jadi slug alamat, misalnya "Sistem Informasi (v2)" → "sistem-informasi-v2". */
function make_slug(string $text, string $fallback = 'project'): string
{
    $slug = trim($text);
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        if ($ascii !== false) {
            $slug = $ascii;
        }
    }
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim(substr(trim($slug, '-'), 0, 100), '-');
    return $slug !== '' ? $slug : $fallback;
}

/** Nomor urut berikutnya (angka terbesar + 1) untuk tabel projects atau skills. */
function next_sort_order(string $table): int
{
    if (!in_array($table, ['projects', 'skills'], true)) {
        return 1;
    }
    $stmt = db()->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM $table");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

/**
 * Pesan saat penyimpanan gagal. Di Laragon (mode lokal) ditambah detail penyebabnya
 * supaya mudah dicari; di hosting detailnya hanya dicatat ke log.
 */
function save_error(string $message, Throwable $e): string
{
    error_log('[portofolio] ' . $message . ' ' . $e->getMessage());
    return config('env') === 'production' ? $message : $message . ' Detail: ' . $e->getMessage();
}

/** Ambil id angka dari alamat (?id=5). Kalau tidak valid, kembali ke daftar dengan pesan. */
function id_from_query(string $backUrl, string $what): int
{
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        flash('error', ucfirst($what) . ' tidak ditemukan.');
        redirect($backUrl);
    }
    return (int) $id;
}
