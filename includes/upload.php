<?php
/**
 * Unggah file dari panel admin.
 *
 * Aturan dari PRD:
 * - Gambar: JPG, PNG, atau WebP, maksimal 2 MB per file.
 * - Gambar diperkecil otomatis ke lebar 1600 px, ditambah versi kecil 600 px untuk kartu.
 * - Isi file diperiksa (bukan hanya nama/ekstensinya), lalu disimpan dengan nama acak.
 * - Folder uploads/ tidak bisa menjalankan PHP (lihat uploads/.htaccess).
 *
 * Semua gambar disimpan ulang sebagai WebP supaya ringan:
 *   uploads/<folder>/<nama-acak>.webp      → versi besar
 *   uploads/<folder>/<nama-acak>-sm.webp   → versi kecil
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

const IMAGE_MAX_BYTES  = 2 * 1024 * 1024;   // 2 MB
const IMAGE_MAX_SIDE   = 6000;              // piksel
const IMAGE_MAX_PIXELS = 25_000_000;        // 25 megapiksel
const IMAGE_QUALITY    = 82;                // kualitas WebP (0–100)
const IMAGE_TYPES      = [
    IMAGETYPE_JPEG => 'image/jpeg',
    IMAGETYPE_PNG  => 'image/png',
    IMAGETYPE_WEBP => 'image/webp',
];

/** Ukuran file dalam teks, misalnya 2,4 MB. */
function format_bytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return str_replace('.', ',', (string) round($bytes / 1024 / 1024, 1)) . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}

/** Apakah isian file ini benar-benar diisi (bukan dibiarkan kosong)? */
function file_was_sent(?array $file): bool
{
    return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/**
 * Ubah isian file "multiple" (name[]...) menjadi daftar file satu per satu.
 * File yang tidak dipilih dilewati.
 */
function uploaded_files(string $field): array
{
    $raw = $_FILES[$field] ?? null;
    if (!is_array($raw) || !is_array($raw['name'] ?? null)) {
        return [];
    }
    $files = [];
    foreach ($raw['name'] as $i => $name) {
        $file = [
            'name'     => $name,
            'type'     => $raw['type'][$i] ?? '',
            'tmp_name' => $raw['tmp_name'][$i] ?? '',
            'error'    => $raw['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $raw['size'][$i] ?? 0,
        ];
        if (file_was_sent($file)) {
            $files[] = $file;
        }
    }
    return $files;
}

/**
 * Periksa file gambar. Mengembalikan pesan kesalahan, atau null kalau aman.
 */
function image_upload_error(array $file): ?string
{
    $name  = '"' . mb_strimwidth((string) ($file['name'] ?? 'file'), 0, 60, '…') . '"';
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return "File $name terlalu besar. Maksimal " . format_bytes(IMAGE_MAX_BYTES) . '.';
    }
    if ($error !== UPLOAD_ERR_OK) {
        return "File $name gagal diunggah. Coba pilih ulang.";
    }
    if (!is_uploaded_file((string) $file['tmp_name'])) {
        return "File $name tidak valid.";
    }
    if ((int) $file['size'] > IMAGE_MAX_BYTES) {
        return "File $name berukuran " . format_bytes((int) $file['size']) . '. Maksimal ' . format_bytes(IMAGE_MAX_BYTES) . '.';
    }

    // Periksa ISI file, bukan nama/ekstensinya
    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!in_array($mime, IMAGE_TYPES, true)) {
            return "File $name bukan gambar JPG, PNG, atau WebP.";
        }
    }
    $info = @getimagesize((string) $file['tmp_name']);
    if ($info === false || !isset(IMAGE_TYPES[$info[2]])) {
        return "File $name bukan gambar JPG, PNG, atau WebP.";
    }
    [$w, $h] = $info;
    if ($w < 1 || $h < 1 || $w > IMAGE_MAX_SIDE || $h > IMAGE_MAX_SIDE || $w * $h > IMAGE_MAX_PIXELS) {
        return "Dimensi gambar $name terlalu besar (maksimal " . IMAGE_MAX_SIDE . ' × ' . IMAGE_MAX_SIDE . ' piksel).';
    }
    return null;
}

/**
 * Simpan gambar yang sudah lolos image_upload_error().
 * Mengembalikan nama file versi besar, misalnya "3f9a...c1.webp".
 *
 * $largeWidth / $smallWidth: lebar maksimal versi besar dan kecil.
 */
function save_image(array $file, string $folder, int $largeWidth = 1600, int $smallWidth = 600): string
{
    if (!function_exists('imagewebp')) {
        throw new RuntimeException('Ekstensi GD di PHP belum mendukung WebP.');
    }

    $info = getimagesize((string) $file['tmp_name']);
    $src = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg((string) $file['tmp_name']),
        IMAGETYPE_PNG  => @imagecreatefrompng((string) $file['tmp_name']),
        IMAGETYPE_WEBP => @imagecreatefromwebp((string) $file['tmp_name']),
        default        => false,
    };
    if (!$src) {
        throw new RuntimeException('Gambar tidak bisa dibaca.');
    }

    // Foto dari HP sering "miring": putar sesuai data EXIF
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data((string) $file['tmp_name']);
        $rotate = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($rotate !== 0) {
            $src = imagerotate($src, $rotate, 0);
        }
    }

    $dir = ROOT_PATH . '/uploads/' . $folder;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder uploads/' . $folder . ' tidak bisa dibuat.');
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('Folder uploads/' . $folder . ' tidak bisa ditulis oleh server. Periksa izin folder.');
    }

    $name = bin2hex(random_bytes(16));
    write_webp($src, $dir . '/' . $name . '.webp', $largeWidth);
    write_webp($src, $dir . '/' . $name . '-sm.webp', $smallWidth);

    return $name . '.webp';
}

/** Perkecil (tidak pernah memperbesar) lalu simpan sebagai WebP. */
function write_webp(GdImage $src, string $path, int $maxWidth): void
{
    $w = imagesx($src);
    $h = imagesy($src);
    $newW = min($maxWidth, $w);
    $newH = max(1, (int) round($h * $newW / $w));

    $dst = imagecreatetruecolor($newW, $newH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127)); // latar transparan untuk PNG/WebP
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

    if (!@imagewebp($dst, $path, IMAGE_QUALITY)) {
        throw new RuntimeException('Gambar gagal disimpan ke ' . basename(dirname($path)) . '/.');
    }
}

/** Nama file versi kecil: abc.webp → abc-sm.webp */
function small_image(string $file): string
{
    return preg_replace('/\.webp$/', '-sm.webp', $file) ?? $file;
}

/** Nama file hanya boleh hasil save_image() — mencegah penghapusan file lain. */
function is_upload_name(?string $file): bool
{
    return is_string($file) && preg_match('/^[a-f0-9]{32}\.(webp|pdf)$/', $file) === 1;
}

/** Hapus gambar (versi besar dan kecil) dari server. */
function delete_image(string $folder, ?string $file): void
{
    if (!is_upload_name($file)) {
        return;
    }
    foreach ([$file, small_image($file)] as $name) {
        $path = ROOT_PATH . '/uploads/' . $folder . '/' . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/** Alamat gambar untuk ditampilkan. $small = true → versi 600 px. */
function upload_url(string $folder, ?string $file, bool $small = false): string
{
    if (!is_upload_name($file)) {
        return '';
    }
    return '/uploads/' . $folder . '/' . ($small ? small_image($file) : $file);
}

/** Lebar & tinggi asli file yang sudah disimpan (untuk atribut width/height di <img>). */
function image_size(string $folder, ?string $file, bool $small = false): array
{
    if (!is_upload_name($file)) {
        return [0, 0];
    }
    $path = ROOT_PATH . '/uploads/' . $folder . '/' . ($small ? small_image($file) : $file);
    $info = is_file($path) ? @getimagesize($path) : false;
    return $info ? [$info[0], $info[1]] : [0, 0];
}
