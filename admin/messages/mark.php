<?php
/**
 * Tandai pesan sudah dibaca / belum dibaca (hanya POST + token CSRF).
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require __DIR__ . '/common.php';

$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/messages/');
}
csrf_verify();

$id   = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$mark = $_POST['mark'] ?? '';
if (!$id || !in_array($mark, ['read', 'unread'], true)) {
    flash('error', 'Permintaan tidak dikenali.');
    redirect('/admin/messages/');
}

$row = find_message((int) $id);
db()->prepare('UPDATE messages SET is_read = ? WHERE id = ?')->execute([$mark === 'read' ? 1 : 0, $row['id']]);

if ($mark === 'read') {
    flash('success', 'Pesan dari ' . $row['name'] . ' ditandai sudah dibaca.');
    redirect('/admin/messages/');
}
flash('success', 'Pesan dari ' . $row['name'] . ' ditandai belum dibaca.');
redirect('/admin/messages/view.php?id=' . $row['id']);
