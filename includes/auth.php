<?php
/**
 * Login admin: sesi, proteksi halaman, pengunci gagal login, dan pesan singkat (flash).
 *
 * Aturan dari PRD:
 * - Hanya satu akun admin, dibuat lewat data awal database.
 * - 5 kali gagal login dari alamat IP yang sama → login dikunci 15 menit.
 * - Sesi berakhir sendiri setelah 2 jam tidak aktif.
 * - ID sesi dibuat ulang setelah login. Cookie sesi: HttpOnly, SameSite, dan Secure di HTTPS.
 * - Setiap halaman dan setiap aksi admin memeriksa login (pakai require_admin()).
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

const SESSION_IDLE_SECONDS = 7200; // 2 jam
const LOGIN_MAX_ATTEMPTS   = 5;
const LOGIN_LOCK_MINUTES   = 15;
const PASSWORD_MIN_LENGTH  = 10;
const PASSWORD_MAX_LENGTH  = 72;   // batas aman bcrypt

/* ------------------------------------------------------------------
 * Sesi
 * ------------------------------------------------------------------ */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_IDLE_SECONDS);

    session_name('porto_sess');
    session_set_cookie_params([
        'lifetime' => 0,          // hilang saat browser ditutup
        'path'     => '/',
        'secure'   => is_https(), // otomatis aktif di hosting HTTPS
        'httponly' => true,       // tidak bisa dibaca JavaScript
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Hapus sesi sepenuhnya (dipakai saat keluar atau sesi kedaluwarsa).
 * $restart = true → langsung buka sesi baru dengan ID baru (untuk menyimpan pesan flash).
 */
function destroy_session(bool $restart = false): void
{
    start_session();
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);
    session_destroy();

    if ($restart) {
        session_id(session_create_id());
        start_session();
    }
}

/* ------------------------------------------------------------------
 * Pesan singkat setelah simpan/hapus/login ("flash")
 * ------------------------------------------------------------------ */

/** $type: success, error, atau info */
function flash(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    start_session();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/* ------------------------------------------------------------------
 * Proteksi halaman admin
 * ------------------------------------------------------------------ */

/** Larang browser dan mesin pencari menyimpan halaman admin. */
function admin_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');
}

/**
 * Wajib dipanggil di baris paling atas setiap halaman/aksi admin (kecuali login).
 * Kalau belum login atau sesi sudah 2 jam tidak aktif → diarahkan ke halaman login.
 * Mengembalikan data admin yang sedang login.
 */
function require_admin(): array
{
    start_session();
    admin_headers();

    $loginUrl = '/admin/login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/admin/');

    if (empty($_SESSION['admin_id'])) {
        redirect($loginUrl);
    }

    if (time() - (int) ($_SESSION['last_activity'] ?? 0) > SESSION_IDLE_SECONDS) {
        destroy_session(true);
        flash('info', 'Sesi berakhir karena 2 jam tidak aktif. Silakan masuk lagi.');
        redirect($loginUrl);
    }

    $stmt = db()->prepare('SELECT id, username, last_login_at FROM admins WHERE id = ?');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin) {
        destroy_session();
        redirect('/admin/login.php');
    }

    $_SESSION['last_activity'] = time();
    return $admin;
}

/** Sudah login dan sesinya masih berlaku? (untuk halaman login) */
function is_logged_in(): bool
{
    start_session();
    return !empty($_SESSION['admin_id'])
        && time() - (int) ($_SESSION['last_activity'] ?? 0) <= SESSION_IDLE_SECONDS;
}

/**
 * Alamat tujuan setelah login. Hanya boleh halaman di dalam /admin/,
 * supaya tautan login tidak bisa dipakai untuk mengarahkan ke website lain.
 */
function safe_admin_next(mixed $next): string
{
    if (is_string($next)
        && str_starts_with($next, '/admin/')
        && !str_contains($next, '//')
        && !str_contains($next, '\\')
        && !str_starts_with($next, '/admin/login.php')
        && !str_starts_with($next, '/admin/logout.php')
        && preg_match('#^/admin/[A-Za-z0-9/_.\-?=&%]*$#', $next)
    ) {
        return $next;
    }
    return '/admin/';
}

/* ------------------------------------------------------------------
 * Login & pengunci gagal login
 * ------------------------------------------------------------------ */

/** Alamat IP pengunjung (tidak memakai header yang bisa dipalsukan). */
function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Berapa detik lagi login masih terkunci untuk IP ini (0 = tidak terkunci).
 * Terkunci kalau ada 5 kegagalan dalam 15 menit terakhir; kunci berlaku 15 menit sejak kegagalan terakhir.
 */
function login_lock_seconds(string $ip): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) AS total,
                TIMESTAMPDIFF(SECOND, NOW(), MAX(attempted_at) + INTERVAL ' . LOGIN_LOCK_MINUTES . ' MINUTE) AS remaining
           FROM login_attempts
          WHERE ip_address = ?
            AND attempted_at > NOW() - INTERVAL ' . LOGIN_LOCK_MINUTES . ' MINUTE'
    );
    $stmt->execute([$ip]);
    $row = $stmt->fetch();
    if ((int) $row['total'] >= LOGIN_MAX_ATTEMPTS) {
        return max(1, (int) $row['remaining']);
    }
    return 0;
}

/** Jumlah gagal login dari IP ini dalam 15 menit terakhir. */
function failed_login_count(string $ip): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
          WHERE ip_address = ? AND attempted_at > NOW() - INTERVAL ' . LOGIN_LOCK_MINUTES . ' MINUTE'
    );
    $stmt->execute([$ip]);
    return (int) $stmt->fetchColumn();
}

function record_failed_login(string $ip): void
{
    db()->prepare('INSERT INTO login_attempts (ip_address) VALUES (?)')->execute([$ip]);
    // Bersihkan catatan lama supaya tabel tidak membengkak
    db()->prepare('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY')->execute();
}

function clear_failed_logins(string $ip): void
{
    db()->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
}

/** Cek username & password. Mengembalikan data admin kalau cocok, null kalau tidak. */
function check_credentials(string $username, string $password): ?array
{
    $stmt = db()->prepare('SELECT id, username, password_hash, last_login_at FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // Tetap jalankan password_verify supaya waktu respons sama (username tidak bisa ditebak)
        password_verify($password, '$2y$10$CUYPlRUVHCuFsfOUKO6/FOThXOPKKc7z//EYV4w1Mww1aL2W0EXW6');
        return null;
    }
    if (!password_verify($password, $admin['password_hash'])) {
        return null;
    }

    // Perbarui hash kalau standar keamanan PHP sudah naik
    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
    }
    return $admin;
}

/** Tandai admin sudah login: ID sesi dibuat ulang (mencegah pembajakan sesi). */
function login_admin(array $admin): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['admin_id']          = (int) $admin['id'];
    $_SESSION['admin_username']    = $admin['username'];
    $_SESSION['last_activity']     = time();
    $_SESSION['previous_login_at'] = $admin['last_login_at'];
    unset($_SESSION['csrf_token']); // token formulir baru untuk sesi baru

    db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$admin['id']]);
}
