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

/* ------------------------------------------------------------------
 * Konten halaman publik
 * ------------------------------------------------------------------ */

/** Judul bagian: kata di dalam [kurung siku] jadi balok warna. Teks tetap diamankan dulu. */
function hl_title(string $text, string $color = 'yellow'): string
{
    $color = in_array($color, ['yellow', 'pink', 'mint', 'blue', 'lav', 'orange'], true) ? $color : 'yellow';
    return preg_replace('/\[(.+?)\]/', '<span class="hl" style="--c:var(--' . $color . ')">$1</span>', e($text)) ?? e($text);
}

/** Teks panjang → beberapa <p> (dipisah baris kosong); baris baru di dalamnya jadi <br>. */
function paragraphs(?string $text): string
{
    $html = '';
    foreach (preg_split('/\R\s*\R/', trim((string) $text)) ?: [] as $part) {
        $part = trim($part);
        if ($part !== '') {
            $html .= '<p>' . nl2br(e($part), false) . '</p>';
        }
    }
    return $html;
}

/**
 * Bagian beranda yang punya isi. Bagian kosong tidak ditampilkan
 * (termasuk tautannya di navbar), sesuai PRD.
 */
function home_sections(): array
{
    static $sections = null;
    if ($sections !== null) {
        return $sections;
    }
    $stmt = db()->prepare(
        "SELECT (SELECT COUNT(*) FROM skills       WHERE status = 'published')                    AS skills,
                (SELECT COUNT(*) FROM projects     WHERE status = 'published' AND is_featured = 1) AS featured,
                (SELECT COUNT(*) FROM projects     WHERE status = 'published')                    AS projects,
                (SELECT COUNT(*) FROM experiences  WHERE status = 'published')                    AS experience,
                (SELECT COUNT(*) FROM achievements WHERE status = 'published')                    AS achievements"
    );
    $stmt->execute();
    $c = $stmt->fetch();
    $sections = [
        'about'        => true,
        'skills'       => (int) $c['skills'] > 0,
        'projects'     => (int) $c['featured'] > 0,
        'all_projects' => (int) $c['projects'] > 0,
        'experience'   => (int) $c['experience'] > 0,
        'achievements' => (int) $c['achievements'] > 0,
        'contact'      => true,
    ];
    return $sections;
}

/** Daftar peran sesuai bahasa (bahasa Inggris kosong → pakai Indonesia). */
function role_list(array $profile): array
{
    $text = tr($profile, 'roles');
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), fn ($r) => $r !== ''));
}

/** Alamat CV sesuai bahasa aktif; CV Inggris kosong → CV Indonesia; tidak ada → ''. */
function cv_url(array $profile): string
{
    foreach ([lang() === 'en' ? 'cv_eng' : 'cv_ind', 'cv_ind'] as $column) {
        $file = (string) ($profile[$column] ?? '');
        if (preg_match('/^[a-f0-9]{32}\.pdf$/', $file) && is_file(ROOT_PATH . '/uploads/cv/' . $file)) {
            return '/uploads/cv/' . $file;
        }
    }
    return '';
}

/** Ikon logo untuk nama teknologi yang dikenal (selain itu tanpa ikon). */
function tech_icon(string $name): ?string
{
    $map = [
        'html' => 'html', 'html5' => 'html', 'css' => 'css', 'css3' => 'css',
        'javascript' => 'js', 'js' => 'js', 'php' => 'php', 'mysql' => 'mysql', 'mariadb' => 'db',
        'sql' => 'db', 'git' => 'git', 'github' => 'github', 'figma' => 'figma', 'laravel' => 'laravel',
        'python' => 'python', 'react' => 'react', 'bootstrap' => 'bootstrap', 'tailwind' => 'tailwind',
        'tailwind css' => 'tailwind', 'node.js' => 'nodejs', 'nodejs' => 'nodejs', 'vs code' => 'vscode', 'vscode' => 'vscode',
    ];
    return $map[mb_strtolower(trim($name))] ?? null;
}

/** Ikon untuk kategori skill. */
function skill_category_icon(string $category): string
{
    return ['frontend' => 'code', 'backend' => 'term', 'database' => 'db', 'tools' => 'tool'][$category] ?? 'tools';
}

/**
 * Project yang sudah terbit, lengkap dengan daftar nama teknologinya (kunci 'skills').
 * $featured = hanya unggulan; $category = saring kategori; $limit = batas jumlah.
 */
function published_projects(bool $featured = false, ?string $category = null, ?int $limit = null): array
{
    $sql = "SELECT id, slug, title_ind, title_eng, summary_ind, summary_eng, category, year, cover_image
              FROM projects WHERE status = 'published'";
    $params = [];
    if ($featured) {
        $sql .= ' AND is_featured = 1';
    }
    if ($category !== null) {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    $sql .= ' ORDER BY sort_order, id';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, $limit);
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
    if (!$projects) {
        return [];
    }

    $ids = array_column($projects, 'id');
    $stmt = db()->prepare(
        "SELECT ps.project_id, s.name
           FROM project_skill ps JOIN skills s ON s.id = ps.skill_id
          WHERE s.status = 'published' AND ps.project_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')
          ORDER BY s.sort_order, s.name'
    );
    $stmt->execute($ids);
    $skills = [];
    foreach ($stmt->fetchAll() as $row) {
        $skills[(int) $row['project_id']][] = $row['name'];
    }
    foreach ($projects as &$project) {
        $project['skills'] = $skills[(int) $project['id']] ?? [];
    }
    return $projects;
}

/**
 * Data gambar unggahan untuk srcset: versi kecil + besar, lebar & tinggi versi kecil.
 * Hasil: ['src' => ..., 'srcset' => ..., 'w' => ..., 'h' => ...] atau null kalau file tidak ada.
 * Butuh includes/upload.php.
 */
function img_set(string $folder, ?string $file): ?array
{
    if (!is_upload_name($file)) {
        return null;
    }
    [$w, $h] = image_size($folder, $file, true);
    [$bigW]  = image_size($folder, $file, false);
    if (!$w) {
        return null;
    }
    $src    = upload_url($folder, $file, true);
    $srcset = $src . ' ' . $w . 'w';
    if ($bigW > $w) {
        $srcset .= ', ' . upload_url($folder, $file, false) . ' ' . $bigW . 'w';
    }
    return ['src' => $src, 'srcset' => $srcset, 'w' => $w, 'h' => $h];
}

/**
 * <img> responsif untuk gambar unggahan: versi kecil + besar (srcset), lebar & tinggi tetap
 * (mencegah tata letak bergeser), dan dimuat belakangan kecuali $priority.
 * Butuh includes/upload.php.
 */
function responsive_img(string $folder, ?string $file, string $alt, string $sizes, string $class = '', bool $priority = false): string
{
    $img = img_set($folder, $file);
    if (!$img) {
        return '';
    }
    return '<img src="' . e($img['src']) . '" srcset="' . e($img['srcset']) . '" sizes="' . e($sizes) . '"'
        . ' width="' . $img['w'] . '" height="' . $img['h'] . '" alt="' . e($alt) . '"'
        . ($class !== '' ? ' class="' . e($class) . '"' : '')
        . ($priority ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">';
}

/** Gambar pratinjau media sosial (og:image) dari gambar unggahan versi besar, alamat lengkap. */
function og_image(string $folder, ?string $file): ?array
{
    [$w, $h] = image_size($folder, $file, false);
    return $w ? ['url' => base_url(ltrim(upload_url($folder, $file), '/')), 'w' => $w, 'h' => $h] : null;
}

/** Warna kartu bergiliran. */
function cycle_color(int $i, array $colors = ['yellow', 'pink', 'mint', 'lav', 'blue', 'orange']): string
{
    return $colors[$i % count($colors)];
}

require ROOT_PATH . '/includes/lang.php';
