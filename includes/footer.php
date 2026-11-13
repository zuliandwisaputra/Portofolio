<?php
/**
 * Bagian bawah setiap halaman publik: footer dan skrip.
 * Butuh variabel $profile (sudah diisi halaman sebelum header.php).
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

$profile  = $profile ?? [];
$fullName = trim((string) ($profile['full_name'] ?? '')) ?: 'Portofolio';
$firstName = mb_strtoupper(explode(' ', $fullName)[0]);
$socials  = social_links($profile);
?>
</main>

<footer class="foot">
  <div class="wrap">
    <div class="foot-top">
      <span class="sticker bg-pink" style="--r:-3deg"><?= e(t('footer.thanks')) ?></span>
      <a class="foot-up" href="#top"><?= icon('arrow-up') ?><?= e(t('footer.back_top')) ?></a>
    </div>
    <p class="foot-big" aria-hidden="true"><?php foreach (mb_str_split($firstName) as $k => $ch): ?><span class="ch" style="--k:<?= $k ?>"><?= e($ch) ?></span><?php endforeach; ?><span class="ch dot" style="--k:<?= mb_strlen($firstName) ?>">.</span></p>
    <div class="foot-bottom">
      <span>© <?= date('Y') ?> <?= e($fullName) ?> · <?= e(t('footer.made_with')) ?></span>
      <?php if ($socials): ?>
      <ul class="foot-social" aria-label="<?= e(t('footer.social')) ?>">
        <?php foreach ($socials as $s): ?>
        <li><a href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>"><?= icon($s['key']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</footer>

<script src="<?= e(asset('js/theme.js')) ?>" defer></script>
<script src="<?= e(asset('js/anim.js')) ?>" defer></script>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
