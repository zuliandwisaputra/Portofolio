<?php
/**
 * Token CSRF: memastikan formulir benar-benar dikirim dari website ini,
 * bukan dari website lain yang mencoba "menumpang" sesi login admin.
 *
 * Cara pakai:
 *   - Di dalam <form method="post">  : echo csrf_field();
 *   - Di awal pemrosesan POST        : csrf_verify();
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

require_once ROOT_PATH . '/includes/auth.php';

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Isian tersembunyi berisi token, ditaruh di dalam setiap formulir POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_is_valid(): bool
{
    start_session();
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}

/** Hentikan permintaan kalau token tidak cocok. */
function csrf_verify(): void
{
    if (csrf_is_valid()) {
        return;
    }
    http_response_code(400);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="id"><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Formulir kedaluwarsa</title>'
        . '<body style="font-family:system-ui,sans-serif;max-width:640px;margin:10vh auto;padding:0 16px;line-height:1.6">'
        . '<h1>Formulir kedaluwarsa</h1>'
        . '<p>Formulir ini sudah terlalu lama dibuka atau dikirim dari tempat lain. '
        . 'Kembali ke halaman sebelumnya, muat ulang (F5), lalu coba lagi.</p></body></html>';
    exit;
}
