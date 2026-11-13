<?php
/**
 * Halaman 404: tampil untuk alamat yang tidak ada.
 * Dipanggil oleh .htaccess (ErrorDocument) atau oleh not_found() dari halaman lain.
 */

require_once __DIR__ . '/includes/functions.php';

http_response_code(404);

// Bahasa: dari awal alamat (/en/...) kalau ada, kalau tidak pakai cookie/bahasa browser.
$firstSegment = explode('/', trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'))[0] ?? '';
set_lang(is_valid_lang($firstSegment) ? $firstSegment : detect_lang(), false);

// Kalau database bermasalah, halaman 404 tetap harus tampil.
try {
    $profile = profile();
} catch (Throwable $e) {
    error_log('[portofolio] 404 tanpa profil: ' . $e->getMessage());
    $profile = [];
}

$pagePath        = '';
$pageTitle       = t('page.not_found');
$pageDescription = '';
$noIndex         = true;

require ROOT_PATH . '/includes/header.php';
?>
<section class="wrap nf">
  <p class="nf-code" aria-hidden="true">4<span>0</span>4</p>
  <h1 class="nf-title"><?= e(t('404.title')) ?></h1>
  <p class="nf-body"><?= e(t('404.body')) ?></p>
  <a class="btn bg-blue" href="<?= e(lang_url('')) ?>"><?= icon('home') ?><?= e(t('404.back')) ?></a>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
