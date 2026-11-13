<?php
/**
 * Fungsi bantu yang dipakai semua halaman.
 *
 * Setiap halaman cukup memanggil:
 *     require __DIR__ . '/includes/functions.php';
 * File ini sekaligus memuat pengaturan, fungsi bahasa, dan penanganan error.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

$GLOBALS['app_config'] = require ROOT_PATH . '/config/app.php';

/** Ambil satu nilai dari config/app.php. */
function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['app_config'][$key] ?? $default;
}

date_default_timezone_set((string) config('timezone', 'Asia/Jakarta'));

/* ------------------------------------------------------------------
 * Pesan error: tampil di Laragon, disembunyikan di hosting.
 * ------------------------------------------------------------------ */
error_reporting(E_ALL);
if (config('env') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
}

/**
 * Kalau terjadi error yang tidak tertangani (misalnya database mati),
 * tampilkan halaman sederhana. Di hosting, detail error hanya dicatat ke log.
 */
set_exception_handler(function (Throwable $e): void {
    error_log('[portofolio] ' . $e->getMessage() . ' di ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    $detail = config('env') === 'production'
        ? 'Terjadi kesalahan di server. Silakan coba lagi nanti.'
        : $e->getMessage();
    echo '<!doctype html><html lang="id"><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Terjadi kesalahan</title>'
        . '<body style="font-family:system-ui,sans-serif;max-width:640px;margin:10vh auto;padding:0 16px;line-height:1.6">'
        . '<h1>Terjadi kesalahan</h1><p>' . e($detail) . '</p></body></html>';
});

/* ------------------------------------------------------------------
 * Keamanan teks
 * ------------------------------------------------------------------ */

/** Amankan teks sebelum ditampilkan di HTML (wajib untuk semua data dari database). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Kembalikan tautan hanya kalau diawali http:// atau https://. Selain itu kosong. */
function safe_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '' || !preg_match('~^https?://~i', $url)) {
        return '';
    }
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

/* ------------------------------------------------------------------
 * Database (PDO + prepared statement)
 * ------------------------------------------------------------------ */

/** Koneksi PDO yang dipakai bersama. Dibuat sekali saat pertama dipanggil. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $file = ROOT_PATH . '/config/database.php';
    if (!is_file($file)) {
        throw new RuntimeException(
            'File config/database.php belum ada. Salin config/database.example.php menjadi config/database.php.'
        );
    }
    $c = require $file;

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $c['host'],
        (int) ($c['port'] ?? 3306),
        $c['name'],
        $c['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $c['user'], $c['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Samakan zona waktu database dengan PHP (WIB), supaya NOW() dan tanggal tersimpan cocok
    $pdo->exec("SET time_zone = '" . date('P') . "'");

    return $pdo;
}

/** Data profil (satu baris di tabel profile). Hasilnya disimpan supaya query hanya sekali per halaman. */
function profile(): array
{
    static $profile = null;
    if ($profile === null) {
        $stmt = db()->prepare('SELECT * FROM profile WHERE id = ?');
        $stmt->execute([1]);
        $profile = $stmt->fetch() ?: [];
    }
    return $profile;
}

/* ------------------------------------------------------------------
 * Alamat (URL)
 * ------------------------------------------------------------------ */

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Alamat lengkap, misalnya base_url('id/projects') → http://portofolio.test/id/projects */
function base_url(string $path = ''): string
{
    $base = rtrim((string) config('base_url', ''), '/');
    if ($base === '') {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (!preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) {
            $host = 'localhost';
        }
        $base = (is_https() ? 'https' : 'http') . '://' . $host;
    }
    return $base . '/' . ltrim($path, '/');
}

/** Alamat file di folder assets, ditambah nomor versi supaya browser memuat versi terbaru. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = ROOT_PATH . '/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return '/assets/' . $path . '?v=' . $version;
}

function redirect(string $url, int $code = 302): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

/** Tampilkan halaman 404 lalu berhenti. */
function not_found(): never
{
    http_response_code(404);
    require ROOT_PATH . '/404.php';
    exit;
}

/* ------------------------------------------------------------------
 * Tampilan
 * ------------------------------------------------------------------ */

/** Tanggal sesuai bahasa aktif, misalnya "13 Nov 2026" atau "13 Nov 2026, 09.15". */
function format_date(?string $value, bool $withTime = false): string
{
    $ts = $value ? strtotime($value) : false;
    if ($ts === false) {
        return '';
    }
    $months = lang() === 'en'
        ? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        : ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $text = date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    if ($withTime) {
        $text .= ', ' . date(lang() === 'en' ? 'H:i' : 'H.i', $ts);
    }
    return $text;
}

/** Ikon dari Tabler Icons (gambarnya ada di includes/icons.php). */
function icon(string $name, string $class = 'i'): string
{
    return '<svg class="' . e($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . e($name) . '"/></svg>';
}

/** Bulan & tahun sesuai bahasa aktif, misalnya "Jul 2023". */
function format_month(?string $value): string
{
    $ts = $value ? strtotime($value) : false;
    if ($ts === false) {
        return '';
    }
    $months = lang() === 'en'
        ? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        : ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/** Inisial nama untuk logo, misalnya "Zulian Dwi Saputra" → "ZDS". */
function initials(string $name): string
{
    $out = '';
    foreach (preg_split('/\s+/', trim($name)) ?: [] as $word) {
        if ($word !== '') {
            $out .= mb_strtoupper(mb_substr($word, 0, 1));
        }
    }
    return mb_substr($out, 0, 3);
}

/**
 * Daftar media sosial dari profil yang sudah diisi.
 * Hasil: [['key' => 'github', 'label' => 'GitHub', 'url' => '...'], ...]
 */
function social_links(array $profile): array
{
    $map = [
        'linkedin'  => ['LinkedIn',  'linkedin_url'],
        'github'    => ['GitHub',    'github_url'],
        'instagram' => ['Instagram', 'instagram_url'],
        'tiktok'    => ['TikTok',    'tiktok_url'],
    ];
    $links = [];
    foreach ($map as $key => [$label, $column]) {
        $url = safe_url($profile[$column] ?? '');
        if ($url !== '') {
            $links[] = ['key' => $key, 'label' => $label, 'url' => $url];
        }
    }
    return $links;
}

require ROOT_PATH . '/includes/lang.php';
