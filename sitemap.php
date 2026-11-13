<?php
/**
 * Peta situs untuk mesin pencari: /sitemap.xml
 * Berisi beranda, daftar project, dan setiap project yang berstatus Tampil, dalam dua bahasa.
 * Draf tidak ikut. Alamatnya dibuat otomatis dari database, jadi tidak perlu diubah manual.
 */

require __DIR__ . '/includes/functions.php';

$pages = [
    ['path' => '',         'lastmod' => profile()['updated_at'] ?? null],
    ['path' => 'projects', 'lastmod' => null],
];

$stmt = db()->prepare("SELECT slug, updated_at FROM projects WHERE status = 'published' ORDER BY sort_order, id");
$stmt->execute();
$latest = null;
foreach ($stmt->fetchAll() as $row) {
    $pages[] = ['path' => 'project/' . $row['slug'], 'lastmod' => $row['updated_at']];
    $latest  = max($latest ?? '', (string) $row['updated_at']);
}
$pages[1]['lastmod'] = $latest;

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($pages as $page): ?>
<?php foreach (languages() as $code): ?>
  <url>
    <loc><?= e(lang_url($page['path'], $code)) ?></loc>
<?php foreach (languages() as $alt): ?>
    <xhtml:link rel="alternate" hreflang="<?= e($alt) ?>" href="<?= e(lang_url($page['path'], $alt)) ?>"/>
<?php endforeach; ?>
<?php if (!empty($page['lastmod']) && ($ts = strtotime($page['lastmod'])) !== false): ?>
    <lastmod><?= date('Y-m-d', $ts) ?></lastmod>
<?php endif; ?>
  </url>
<?php endforeach; ?>
<?php endforeach; ?>
</urlset>
