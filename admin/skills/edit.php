<?php
/**
 * Ubah skill.
 */

require dirname(__DIR__, 2) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';
require ROOT_PATH . '/includes/form.php';
require __DIR__ . '/form.php';

$admin = require_admin();
$id    = id_from_query('/admin/skills/', 'skill');

$stmt = db()->prepare('SELECT * FROM skills WHERE id = ?');
$stmt->execute([$id]);
$skill = $stmt->fetch();
if (!$skill) {
    flash('error', 'Skill tidak ditemukan. Mungkin sudah dihapus.');
    redirect('/admin/skills/');
}

$data = [
    'name'       => $skill['name'],
    'category'   => $skill['category'],
    'level'      => $skill['level'] === null ? '' : (string) $skill['level'],
    'status'     => $skill['status'],
    'sort_order' => (string) $skill['sort_order'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data   = skill_input();
    $errors = skill_validate($data, $id);

    if (!$errors) {
        try {
            db()->prepare('UPDATE skills SET name = ?, category = ?, level = ?, status = ?, sort_order = ? WHERE id = ?')
                ->execute([...skill_values($data), $id]);
            flash('success', 'Perubahan skill "' . $data['name'] . '" berhasil disimpan.');
            redirect('/admin/skills/');
        } catch (Throwable $e) {
            $errors['_save'] = save_error('Perubahan gagal disimpan karena kesalahan server. Coba lagi.', $e);
        }
    }
}

$pageTitle  = 'Ubah skill';
$activeMenu = 'skills';
require ROOT_PATH . '/includes/admin_header.php';
?>
<p class="crumbs"><a href="/admin/skills/">Skill</a> <span aria-hidden="true">/</span> Ubah "<?= e($skill['name']) ?>"</p>
<?= error_summary($errors, 'Perubahan belum tersimpan.') ?>
<?php skill_form($data, $errors, '/admin/skills/edit.php?id=' . $id, 'Simpan perubahan'); ?>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
