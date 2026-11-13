<?php
/**
 * Tambah skill.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require __DIR__ . '/form.php';

$admin  = require_admin();
$data   = skill_defaults();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data   = skill_input();
    $errors = skill_validate($data);

    if (!$errors) {
        try {
            db()->prepare('INSERT INTO skills (name, category, level, status, sort_order) VALUES (?, ?, ?, ?, ?)')
                ->execute(skill_values($data));
            flash('success', 'Skill "' . $data['name'] . '" berhasil ditambahkan.');
            redirect('/admin/skills/');
        } catch (Throwable $e) {
            $errors['_save'] = save_error('Skill gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }
    }
}

$pageTitle  = 'Tambah skill';
$activeMenu = 'skills';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/skills/">Skill</a> <span aria-hidden="true">/</span> Tambah</p>
<?= error_summary($errors, 'Skill belum tersimpan.') ?>
<?php skill_form($data, $errors, '/admin/skills/create.php', 'Simpan skill'); ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
