<?php
/**
 * Bagian bersama menu Pesan. File ini tidak bisa dibuka langsung.
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

/** Label status pesan untuk tabel dan halaman baca. */
function message_badge(bool $isRead): string
{
    return $isRead
        ? '<span class="status status-read">Sudah dibaca</span>'
        : '<span class="status status-new">Baru</span>';
}

/** Ambil satu pesan, atau kembali ke daftar dengan pesan error kalau tidak ada. */
function find_message(int $id): array
{
    $stmt = db()->prepare('SELECT * FROM messages WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('error', 'Pesan tidak ditemukan. Mungkin sudah dihapus.');
        redirect('/admin/messages/');
    }
    return $row;
}

/** Alamat "balas lewat email" (membuka aplikasi email dengan subjek terisi). */
function reply_link(array $row): string
{
    return 'mailto:' . $row['email'] . '?subject=' . rawurlencode('Balasan pesan dari website portofolio');
}
