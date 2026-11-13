<?php
/**
 * Pemroses form kontak di beranda (POST /contact_send.php).
 *
 * Urutan pemeriksaan:
 *   1. Hanya menerima POST.
 *   2. Isian jebakan terisi → anggap bot: tampilkan "terkirim" tapi tidak disimpan.
 *   3. Token CSRF harus cocok.
 *   4. Batas 3 pesan per 10 menit per alamat IP.
 *   5. Validasi nama, email, pesan.
 *   6. Simpan ke tabel messages (prepared statement).
 * Setelah itu pengunjung dialihkan kembali ke bagian Kontak di beranda (pola Post/Redirect/Get),
 * sehingga menekan F5 tidak mengirim pesan dua kali.
 */

require __DIR__ . '/includes/functions.php';
require ROOT_PATH . '/includes/contact.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url());
}

// Bahasa mengikuti halaman tempat form dikirim
$lang = $_POST['lang'] ?? null;
set_lang(is_string($lang) && is_valid_lang($lang) ? $lang : detect_lang(), false);
$back = lang_url('') . '#contact';

$data = contact_input();

// 2. Bot mengisi isian jebakan → pura-pura berhasil supaya bot tidak mencoba cara lain
$trap = $_POST[CONTACT_HONEYPOT] ?? '';
if (!is_string($trap) || trim($trap) !== '') {
    contact_set_result('success', t('contact.ok', ['name' => $data['name'] ?: '']));
    redirect($back);
}

// 3. Token CSRF
if (!csrf_is_valid()) {
    contact_set_result('error', t('contact.err.expired'), [], $data);
    redirect($back);
}

try {
    // 4. Pembatas pesan
    $ipHash = contact_ip_hash();
    $wait = contact_wait_minutes($ipHash);
    if ($wait > 0) {
        contact_set_result('error', t('contact.err.rate', ['max' => CONTACT_RATE_MAX, 'minutes' => CONTACT_RATE_MINUTES, 'm' => $wait]), [], $data);
        redirect($back);
    }

    // 5. Validasi
    $errors = contact_validate($data);
    if ($errors) {
        contact_set_result('error', t('contact.err.summary'), $errors, $data);
        redirect($back);
    }

    // 6. Simpan
    $stmt = db()->prepare('INSERT INTO messages (name, email, body, ip_hash) VALUES (?, ?, ?, ?)');
    $stmt->execute([$data['name'], $data['email'], $data['message'], $ipHash]);
} catch (PDOException $e) {
    error_log('[portofolio] pesan kontak gagal disimpan: ' . $e->getMessage());
    contact_set_result('error', t('contact.err.server'), [], $data);
    redirect($back);
}

contact_set_result('success', t('contact.ok', ['name' => $data['name']]));
redirect($back);
