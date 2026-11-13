<?php
/**
 * Form kontak: aturan isian, pembatas pesan, dan data sementara antara
 * contact_send.php (pemroses) dan beranda (tempat form ditampilkan).
 *
 * Aturan dari PRD:
 * - Isian: nama, email, pesan. Validasi di server, pesan kesalahan di dekat isian.
 * - Memakai token CSRF.
 * - Ada isian jebakan untuk bot (honeypot): manusia tidak melihatnya, bot biasanya mengisinya.
 * - Paling banyak 3 pesan per 10 menit dari alamat IP yang sama.
 * - Alamat IP tidak disimpan mentah, hanya sidik acaknya (ip_hash).
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

require_once ROOT_PATH . '/includes/csrf.php';

const CONTACT_NAME_MAX    = 100;
const CONTACT_EMAIL_MAX   = 150;
const CONTACT_MESSAGE_MIN = 10;
const CONTACT_MESSAGE_MAX = 3000;
const CONTACT_RATE_MAX     = 3;   // pesan
const CONTACT_RATE_MINUTES = 10;  // menit
const CONTACT_HONEYPOT     = 'website'; // nama isian jebakan

/** Ambil isian dari POST: dirapikan, baris baru diseragamkan, karakter kontrol dibuang. */
function contact_input(): array
{
    $text = function (string $key, bool $multiline = false): string {
        $value = $_POST[$key] ?? '';
        if (!is_string($value)) {
            return '';
        }
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace($multiline ? '/[^\P{Cc}\n\t]/u' : '/\p{Cc}/u', '', $value) ?? '';
        return trim($value);
    };
    return [
        'name'    => $text('name'),
        'email'   => $text('email'),
        'message' => $text('message', true),
    ];
}

/** Periksa isian. Hasil: [kunci isian => pesan kesalahan] (kosong = semua benar). */
function contact_validate(array $d): array
{
    $errors = [];
    if ($d['name'] === '') {
        $errors['name'] = t('contact.err.name_required');
    } elseif (mb_strlen($d['name']) > CONTACT_NAME_MAX) {
        $errors['name'] = t('contact.err.name_long', ['n' => CONTACT_NAME_MAX]);
    }

    if ($d['email'] === '') {
        $errors['email'] = t('contact.err.email_required');
    } elseif (mb_strlen($d['email']) > CONTACT_EMAIL_MAX || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = t('contact.err.email_invalid');
    }

    $length = mb_strlen($d['message']);
    if ($length === 0) {
        $errors['message'] = t('contact.err.message_required');
    } elseif ($length < CONTACT_MESSAGE_MIN) {
        $errors['message'] = t('contact.err.message_short', ['n' => CONTACT_MESSAGE_MIN]);
    } elseif ($length > CONTACT_MESSAGE_MAX) {
        $errors['message'] = t('contact.err.message_long', ['n' => CONTACT_MESSAGE_MAX]);
    }
    return $errors;
}

/**
 * Sidik alamat IP (64 karakter). Dibuat dengan kunci rahasia dari pengaturan database
 * (yang tidak ikut ke GitHub), jadi alamat IP asli tidak bisa ditebak dari database.
 */
function contact_ip_hash(): string
{
    $config = is_file(ROOT_PATH . '/config/database.php') ? (require ROOT_PATH . '/config/database.php') : [];
    $key = 'portofolio-kontak|' . json_encode($config);
    return hash_hmac('sha256', client_ip(), $key);
}

/**
 * Berapa menit lagi IP ini boleh mengirim pesan? 0 = boleh sekarang.
 * Dihitung dari pesan yang tersimpan dalam 10 menit terakhir.
 */
function contact_wait_minutes(string $ipHash): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) AS n, TIMESTAMPDIFF(SECOND, MIN(created_at), NOW()) AS age
           FROM messages
          WHERE ip_hash = ? AND created_at > NOW() - INTERVAL ' . CONTACT_RATE_MINUTES . ' MINUTE'
    );
    $stmt->execute([$ipHash]);
    $row = $stmt->fetch();
    if ((int) $row['n'] < CONTACT_RATE_MAX) {
        return 0;
    }
    $secondsLeft = CONTACT_RATE_MINUTES * 60 - (int) $row['age'];
    return max(1, (int) ceil($secondsLeft / 60));
}

/** Simpan hasil kiriman untuk ditampilkan sekali di beranda (setelah dialihkan). */
function contact_set_result(string $type, string $message, array $errors = [], array $old = []): void
{
    start_session();
    $_SESSION['contact'] = ['type' => $type, 'message' => $message, 'errors' => $errors, 'old' => $old];
}

/** Ambil (lalu hapus) hasil kiriman terakhir. */
function contact_take_result(): ?array
{
    start_session();
    $result = $_SESSION['contact'] ?? null;
    unset($_SESSION['contact']);
    return is_array($result) ? $result : null;
}

/** Alamat WhatsApp dari nomor di Profil (08xx → 628xx). Kosong kalau nomor tidak diisi. */
function whatsapp_url(?string $number): string
{
    $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits;
}
