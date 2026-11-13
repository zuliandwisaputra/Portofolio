<?php
/**
 * Beranda: /id/ atau /en/
 * Alamat tanpa kode bahasa (/) diarahkan otomatis sesuai cookie atau bahasa browser.
 *
 * TAHAP 1: baru kerangka (header, footer, bahasa, tema, koneksi database).
 * Isi bagian beranda dibuat di Tahap 5.
 */

require __DIR__ . '/includes/functions.php';

lang_from_url('');
$profile = profile();

$pagePath = '';
$isHome   = true;

require ROOT_PATH . '/includes/header.php';
?>
<section class="wrap wip">
  <span class="sticker bg-mint" style="--r:-2deg"><?= e(t('wip.badge')) ?></span>
  <h1 class="wip-title"><?= e($profile['full_name'] ?? '') ?></h1>
  <p class="wip-lead"><?= e(t('wip.home')) ?></p>

  <ul class="wip-checks">
    <li><?= icon('check') ?><?= e(t('wip.db_ok')) ?></li>
    <li><?= icon('check') ?><?= e(t('wip.lang_now', ['lang' => t('lang.name')])) ?></li>
  </ul>

  <div class="card box wip-card">
    <p class="wip-label"><?= e(t('wip.fallback')) ?></p>
    <p><?= e(tr($profile, 'tagline')) ?></p>
  </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
