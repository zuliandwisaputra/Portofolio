<?php
/**
 * Fungsi bahasa (Indonesia dan Inggris).
 *
 * - Kode bahasa ada di alamat: /id/... dan /en/...
 * - Kunjungan pertama tanpa kode bahasa memakai bahasa browser.
 * - Pilihan pengunjung disimpan di cookie "lang" selama 1 tahun.
 * - Teks tetap (tombol, judul bagian) ada di folder lang/.
 * - Teks konten dari database punya kolom _ind dan _eng; kalau _eng kosong, tampil _ind.
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

$GLOBALS['current_lang'] = 'id';
$GLOBALS['lang_strings'] = [];

/** Daftar kode bahasa yang didukung, misalnya ['id', 'en']. */
function languages(): array
{
    return config('languages', ['id', 'en']);
}

function is_valid_lang(?string $lang): bool
{
    return is_string($lang) && in_array($lang, languages(), true);
}

/** Bahasa yang sedang aktif. */
function lang(): string
{
    return $GLOBALS['current_lang'];
}

/**
 * Tebak bahasa pengunjung: pakai cookie kalau ada,
 * kalau belum ada pakai bahasa browser (Indonesia → id, selain itu → en).
 */
function detect_lang(): string
{
    $cookie = $_COOKIE['lang'] ?? null;
    if (is_valid_lang($cookie)) {
        return $cookie;
    }
    $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    $first = trim(explode(',', $accept)[0] ?? '');
    if (str_starts_with($first, 'id') || str_starts_with($first, 'in')) {
        return 'id';
    }
    return 'en';
}

/**
 * Aktifkan bahasa untuk halaman ini.
 * $remember = true → simpan juga di cookie (dipakai saat bahasa datang dari alamat).
 */
function set_lang(string $lang, bool $remember = true): void
{
    if (!is_valid_lang($lang)) {
        $lang = languages()[0];
    }
    $GLOBALS['current_lang'] = $lang;

    if ($remember && ($_COOKIE['lang'] ?? null) !== $lang && !headers_sent()) {
        setcookie('lang', $lang, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['lang'] = $lang;
    }
}

/**
 * Ambil bahasa dari alamat (?lang=id dari .htaccess).
 * Kalau tidak ada atau salah, arahkan ke alamat dengan kode bahasa yang tepat.
 */
function lang_from_url(string $path = ''): string
{
    $lang = $_GET['lang'] ?? null;
    if (!is_valid_lang($lang)) {
        redirect(lang_url($path, detect_lang()));
    }
    set_lang($lang);
    return $lang;
}

/** Teks tetap dari lang/id.php atau lang/en.php. Contoh: t('nav.about') */
function t(string $key, array $replace = []): string
{
    $lang = lang();
    if (!isset($GLOBALS['lang_strings'][$lang])) {
        $file = ROOT_PATH . '/lang/' . $lang . '.php';
        $GLOBALS['lang_strings'][$lang] = is_file($file) ? require $file : [];
    }
    if (!isset($GLOBALS['lang_strings']['id'])) {
        $GLOBALS['lang_strings']['id'] = require ROOT_PATH . '/lang/id.php';
    }

    $text = $GLOBALS['lang_strings'][$lang][$key]
        ?? $GLOBALS['lang_strings']['id'][$key]
        ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }
    return $text;
}

/**
 * Teks konten dari database sesuai bahasa aktif.
 * Contoh: tr($project, 'title') → title_eng kalau bahasa Inggris dan sudah diisi, kalau tidak title_ind.
 */
function tr(array $row, string $field): string
{
    $eng = trim((string) ($row[$field . '_eng'] ?? ''));
    if (lang() === 'en' && $eng !== '') {
        return $eng;
    }
    return (string) ($row[$field . '_ind'] ?? '');
}

/** Alamat halaman dalam bahasa tertentu. Contoh: lang_url('projects', 'en') → http://.../en/projects */
function lang_url(string $path = '', ?string $lang = null): string
{
    $lang = $lang ?? lang();
    $path = ltrim($path, '/');
    return base_url($lang . '/' . $path);
}

/** Label kategori dari config/app.php sesuai bahasa aktif. */
function category_label(string $group, string $key): string
{
    $list = config($group, []);
    return $list[$key][lang()] ?? $list[$key]['id'] ?? $key;
}
