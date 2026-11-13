<?php
/**
 * Aturan untuk robot mesin pencari: /robots.txt
 * Panel admin tidak boleh dijelajahi; alamat peta situs ditulis lengkap sesuai domain.
 */

require __DIR__ . '/includes/functions.php';

header('Content-Type: text/plain; charset=utf-8');

echo "User-agent: *\n";
echo "Disallow: /admin/\n";
echo "\n";
echo 'Sitemap: ' . base_url('sitemap.xml') . "\n";
