<?php
/**
 * Dashboard admin: jumlah tiap jenis konten dan pesan yang belum dibaca.
 */

require dirname(__DIR__) . '/includes/functions.php';
require ROOT_PATH . '/includes/csrf.php';

$admin = require_admin();

$stmt = db()->prepare(
    "SELECT
        (SELECT COUNT(*) FROM projects     WHERE status = 'published') AS projects_published,
        (SELECT COUNT(*) FROM projects     WHERE status = 'draft')     AS projects_draft,
        (SELECT COUNT(*) FROM skills)                                  AS skills,
        (SELECT COUNT(*) FROM experiences)                             AS experiences,
        (SELECT COUNT(*) FROM achievements)                            AS achievements,
        (SELECT COUNT(*) FROM messages     WHERE is_read = 0)          AS messages_unread,
        (SELECT COUNT(*) FROM messages)                                AS messages_total"
);
$stmt->execute();
$count = $stmt->fetch();

// [judul, angka, keterangan, warna, ikon, alamat kelola, tahap]
$cards = [
    ['Project', $count['projects_published'] + $count['projects_draft'],
        $count['projects_published'] . ' terbit · ' . $count['projects_draft'] . ' draf', 'yellow', 'folder', '/admin/projects/', null],
    ['Skill', $count['skills'], 'dikelompokkan per kategori', 'mint', 'tools', '/admin/skills/', null],
    ['Pengalaman & pendidikan', $count['experiences'], 'kerja, organisasi, pendidikan', 'lav', 'school', '/admin/experiences/', null],
    ['Sertifikat & lomba', $count['achievements'], 'sertifikat, lomba, penghargaan', 'orange', 'award', '/admin/achievements/', null],
    ['Pesan belum dibaca', $count['messages_unread'], 'dari ' . $count['messages_total'] . ' pesan masuk', 'pink', 'inbox', null, 'Tahap 6'],
];

$previousLogin = $_SESSION['previous_login_at'] ?? null;

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
require ROOT_PATH . '/includes/admin_header.php';
?>
<section class="hello card box">
  <div>
    <p class="hello-title">Halo, <?= e($admin['username']) ?>!</p>
    <p class="muted">
      <?php if ($previousLogin): ?>
        Login sebelumnya: <?= e(format_date($previousLogin, true)) ?> WIB.
      <?php else: ?>
        Ini login pertamamu. Segera ganti password bawaan lewat menu <a href="/admin/password.php">Ganti password</a>, lalu lengkapi <a href="/admin/profile.php">Profil</a>.
      <?php endif; ?>
    </p>
  </div>
  <a class="btn white btn-sm" href="/" target="_blank" rel="noopener"><?= icon('external') ?>Lihat website</a>
</section>

<section aria-labelledby="ringkasan">
  <h2 id="ringkasan" class="section-title">Ringkasan isi website</h2>
  <ul class="stat-grid">
    <?php foreach ($cards as [$title, $number, $caption, $color, $ico, $url, $stage]): ?>
    <li class="stat box bg-<?= e($color) ?>">
      <span class="stat-ico"><?= icon($ico) ?></span>
      <p class="stat-num"><?= e($number) ?></p>
      <p class="stat-title"><?= e($title) ?></p>
      <p class="stat-cap"><?= e($caption) ?></p>
      <?php if ($url): ?>
      <a class="stat-link" href="<?= e($url) ?>">Kelola <?= icon('arrow') ?></a>
      <?php else: ?>
      <span class="stat-soon">Dikelola mulai <?= e($stage) ?></span>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
