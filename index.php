<?php
/**
 * Beranda: /id/ atau /en/
 * Alamat tanpa kode bahasa (/) diarahkan otomatis sesuai cookie atau bahasa browser.
 *
 * Urutan bagian sesuai PRD: hero, tentang, skill, project unggulan, pengalaman & pendidikan,
 * sertifikat & lomba, kontak. Bagian yang belum punya isi (semua Draf/kosong) disembunyikan.
 * Atribut data-roles dan data-count dipakai animasi di Tahap 7.
 */

require __DIR__ . '/includes/functions.php';
require ROOT_PATH . '/includes/upload.php';
require ROOT_PATH . '/includes/contact.php';

lang_from_url('');
$profile  = profile();
$sections = home_sections();

$fullName = trim((string) ($profile['full_name'] ?? '')) ?: 'Portofolio';
$roles    = role_list($profile);
$socials  = social_links($profile);
$cv       = cv_url($profile);
$email    = filter_var((string) ($profile['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$whatsapp = whatsapp_url($profile['whatsapp'] ?? ''); // kosong di Profil = tombol WhatsApp tidak tampil

// Form kontak: hasil kiriman terakhir (sekali tampil) dan token CSRF.
// Harus disiapkan sebelum HTML dikirim karena memakai sesi (cookie).
$contact    = contact_take_result();
$cErrors    = $contact['errors'] ?? [];
$cOld       = $contact['old'] ?? [];
$csrfField  = csrf_field();

// ---------- Ambil isi dari database (hanya yang berstatus Tampil) ----------
$stmt = db()->prepare(
    "SELECT (SELECT COUNT(*) FROM projects     WHERE status = 'published')                       AS projects,
            (SELECT COUNT(*) FROM achievements WHERE status = 'published' AND type = 'sertifikat') AS certificates,
            (SELECT COUNT(*) FROM achievements WHERE status = 'published' AND type = 'lomba')      AS competitions"
);
$stmt->execute();
$counts = array_map('intval', $stmt->fetch());

$skills = [];
if ($sections['skills']) {
    $stmt = db()->prepare("SELECT name, category FROM skills WHERE status = 'published' ORDER BY sort_order, name");
    $stmt->execute();
    $skills = $stmt->fetchAll();
}
// Kelompokkan per kategori, urut sesuai config/app.php (kategori lain di akhir)
$skillGroups = array_fill_keys(array_keys(config('skill_categories', [])), []);
foreach ($skills as $s) {
    $skillGroups[$s['category']][] = $s['name'];
}
$skillGroups = array_filter($skillGroups);
$skillNames  = array_merge(...array_values($skillGroups ?: [[]]));

$featured = $sections['projects'] ? published_projects(true, null, 6) : [];

$education = $journey = [];
if ($sections['experience']) {
    $stmt = db()->prepare("SELECT * FROM experiences WHERE status = 'published' ORDER BY start_date DESC, id DESC");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        if ($row['type'] === 'pendidikan') {
            $education[] = $row;
        } else {
            $journey[] = $row;
        }
    }
}

$achievements = [];
if ($sections['achievements']) {
    $stmt = db()->prepare("SELECT * FROM achievements WHERE status = 'published' ORDER BY issued_date DESC, id DESC");
    $stmt->execute();
    $achievements = $stmt->fetchAll();
}
const ACHIEVEMENTS_VISIBLE = 6; // sisanya muncul setelah tombol "Tampilkan lebih banyak"

// ---------- Hal kecil untuk tampilan ----------
$nameWords = preg_split('/\s+/', $fullName) ?: [$fullName];
$nameLast  = array_pop($nameWords);
$nameFirst = implode(' ', $nameWords);

$statusText = !empty($profile['status_enabled']) ? tr($profile, 'status_text') : '';
$projectsHref = $sections['projects'] ? '#projects' : ($sections['all_projects'] ? lang_url('projects') : '');

$photo = img_set('profile', $profile['photo'] ?? null);
$photoSizes = '(max-width: 767px) 280px, 390px';

$secNum = 0;
$secLabel = function (string $key) use (&$secNum): string {
    return sprintf('%02d / %s', ++$secNum, mb_strtoupper(t($key)));
};

/**
 * Huruf nama dibungkus satu per satu untuk animasi masuk (muncul bergantian).
 * Tiap kata dibungkus .w supaya tidak terpotong di tengah kata. Pembaca layar membaca
 * nama utuh dari <span class="sr-only">, bagian huruf ini disembunyikan (aria-hidden).
 */
function name_letters(string $text): string
{
    $html = [];
    $k = 0;
    foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
        $letters = '';
        foreach (mb_str_split($word) as $ch) {
            $letters .= '<span class="ch" style="--k:' . min($k++, 20) . '">' . e($ch) . '</span>';
        }
        $html[] = '<span class="w">' . $letters . '</span>';
        $k++;
    }
    return implode(' ', $html);
}

// Satu entri pengalaman/pendidikan
function journey_item(array $row, int $i): string
{
    $title  = tr($row, 'title');
    $start  = format_month($row['start_date']);
    $end    = $row['is_current'] ? t('experience.now') : format_month($row['end_date']);
    $when   = ($end === '' || $end === $start) ? $start : $start . ' — ' . $end;
    $color  = cycle_color($i, ['yellow', 'orange', 'pink', 'mint', 'lav', 'blue']);
    $orgIco = ['pendidikan' => 'school', 'organisasi' => 'users', 'kerja' => 'briefcase'][$row['type']] ?? 'briefcase';

    ob_start(); ?>
<li style="--c:var(--<?= $color ?>)">
  <div class="card box">
    <?php if ($img = responsive_img('experiences', $row['image'], t('experience.photo_alt', ['title' => $title]), '(max-width: 1023px) calc(100vw - 120px), 460px')): ?>
    <a class="shot-wrap" href="<?= e(upload_url('experiences', $row['image'])) ?>" target="_blank" rel="noopener">
      <span class="shot"><?= $img ?><span class="zoom"><?= icon('arrow-ne') ?><?= e(t('experience.photo')) ?></span></span>
    </a>
    <?php endif; ?>
    <div class="row">
      <span class="when"><?= e($when) ?></span>
      <?php if ($row['type'] !== 'pendidikan'): ?>
      <span class="sticker type bg-<?= $row['type'] === 'kerja' ? 'blue' : 'pink' ?>"><?= e(t('type.' . $row['type'])) ?></span>
      <?php elseif ($row['is_current']): ?>
      <span class="sticker type bg-mint"><?= e(t('experience.current')) ?></span>
      <?php endif; ?>
    </div>
    <h4><?= e($title) ?></h4>
    <p class="org"><?= icon($orgIco) ?><?= e($row['organization']) ?>
      <?php if (trim((string) $row['location']) !== ''): ?>
      <span class="loc"><?= icon('pin') ?><?= e($row['location']) ?></span>
      <?php endif; ?>
    </p>
    <?php if (($desc = paragraphs(tr($row, 'description'))) !== ''): ?>
    <div class="desc"><?= $desc ?></div>
    <?php endif; ?>
  </div>
</li>
<?php
    return (string) ob_get_clean();
}

// ---------- Untuk <head> ----------
$pagePath = '';
$isHome   = true;
$ogImage  = og_image('profile', $profile['photo'] ?? null);
if ($photo) {
    $preloadImage = ['src' => $photo['src'], 'srcset' => $photo['srcset'], 'sizes' => $photoSizes];
}

require ROOT_PATH . '/includes/header.php';
?>

<!-- ===================== HERO ===================== -->
<div class="wrap">
  <section class="hero" id="hero" aria-labelledby="hero-name">
    <svg class="deco d1" viewBox="0 0 70 22" aria-hidden="true"><path d="M3 11 Q 11 2, 19 11 T 35 11 T 51 11 T 67 11"/></svg>
    <svg class="deco d2" viewBox="0 0 100 100" aria-hidden="true"><use href="#i-sparkle"/></svg>
    <svg class="deco d3" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg>

    <div class="hero-text">
      <?php if ($statusText !== ''): ?>
      <p class="sticker badge-status bg-mint" style="--r:-2deg"><span class="dot" aria-hidden="true"></span><?= e($statusText) ?></p>
      <?php endif; ?>
      <p class="hero-hello"><?= e(t('hero.hello')) ?></p>
      <h1 class="hero-name" id="hero-name"><span class="sr-only"><?= e($fullName) ?></span><span aria-hidden="true"><?php if ($nameFirst !== ''): ?><span class="l1"><?= name_letters($nameFirst) ?></span> <?php endif; ?><span class="blk"><?= e($nameLast) ?></span></span></h1>

      <?php if ($roles): ?>
      <p class="hero-role" data-roles="<?= e(json_encode($roles, JSON_UNESCAPED_UNICODE)) ?>">
        <span class="sr-only"><?= e(implode(', ', $roles)) ?></span>
        <span class="tbox" aria-hidden="true"><?= icon('term') ?><span class="typed"><?= e($roles[0]) ?></span><span class="cur"></span></span>
      </p>
      <?php endif; ?>

      <?php if (($tagline = tr($profile, 'tagline')) !== ''): ?>
      <p class="hero-tagline"><?= e($tagline) ?></p>
      <?php endif; ?>

      <?php if ($projectsHref !== '' || $cv !== ''): ?>
      <div class="hero-cta">
        <?php if ($projectsHref !== ''): ?>
        <a class="btn bg-blue" href="<?= e($projectsHref) ?>"><?= e(t('hero.see_projects')) ?><?= icon('arrow', 'i nudge') ?></a>
        <?php endif; ?>
        <?php if ($cv !== ''): ?>
        <a class="btn white" href="<?= e($cv) ?>" download="<?= e('CV-' . trim(preg_replace('/[^A-Za-z0-9]+/', '-', $fullName), '-') . '-' . strtoupper(lang()) . '.pdf') ?>" type="application/pdf"><?= e(t('hero.download_cv')) ?><?= icon('dl', 'i nudge dn') ?></a>
        <?php endif; ?>
        <div class="doodle" aria-hidden="true">
          <svg viewBox="0 0 120 60"><path d="M112 48 C 92 56, 54 52, 30 22"/><path d="M26 34 L 29 20 L 42 24"/></svg>
          <span><?= e(t('hero.start_here')) ?></span>
        </div>
      </div>
      <?php endif; ?>

      <?php $statItems = array_filter(['projects' => 'yellow', 'certificates' => 'pink', 'competitions' => 'mint'], fn ($k) => $counts[$k] > 0, ARRAY_FILTER_USE_KEY); ?>
      <?php if ($socials || $statItems): ?>
      <div class="hero-meta">
        <?php if ($socials): ?>
        <ul class="social">
          <?php foreach ($socials as $n => $s): ?>
          <li><a class="ibtn bg-<?= cycle_color($n, ['blue', 'pink', 'yellow', 'mint']) ?>" href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>"><?= icon($s['key']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if ($statItems): ?>
        <dl class="stats box" aria-label="<?= e(t('hero.stats')) ?>">
          <?php foreach ($statItems as $key => $color): ?>
          <div style="--c:var(--<?= $color ?>)"><dt><?= e(t('stat.' . $key)) ?></dt><dd data-count="<?= $counts[$key] ?>"><?= $counts[$key] ?></dd></div>
          <?php endforeach; ?>
        </dl>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="hero-visual">
      <svg class="spark" viewBox="0 0 100 100" aria-hidden="true"><use href="#i-sparkle"/></svg>
      <div class="window-back" aria-hidden="true"></div>
      <div class="window">
        <div class="win-bar" aria-hidden="true"><i></i><i></i><i></i><span><?= e(mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', $nameWords[0] ?? $nameLast) ?: 'foto')) ?>.webp</span></div>
        <div class="win-body">
          <?php if ($photo): ?>
          <img src="<?= e($photo['src']) ?>" srcset="<?= e($photo['srcset']) ?>" sizes="<?= e($photoSizes) ?>" width="<?= $photo['w'] ?>" height="<?= $photo['h'] ?>" alt="<?= e(t('hero.photo_alt', ['name' => $fullName])) ?>" fetchpriority="high" decoding="async">
          <?php else: ?>
          <span class="ini" aria-hidden="true"><?= e(initials($fullName)) ?></span>
          <span class="lbl" aria-hidden="true"><?= icon('cam') ?><?= e(t('hero.photo_soon')) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="code-chip" aria-hidden="true">
        <span class="cc-bar"><i></i><i></i><i></i><b><?= e(lang() === 'en' ? 'hello.php' : 'halo.php') ?></b></span>
<pre><span class="k">&lt;?php</span>
<span class="f">echo</span> <span class="s">"<?= e(t('hero.code_hello')) ?>"</span>;</pre>
      </div>
      <?php if ($skillNames): ?>
      <span class="sticker float bg-orange" aria-hidden="true"><?= icon(tech_icon($skillNames[0]) ?? 'code') ?><?= e(implode(' · ', array_slice($skillNames, 0, 2))) ?></span>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php if ($skillNames): ?>
<!-- Pita skill (hiasan; daftar lengkapnya ada di bagian Skill) -->
<div class="tape" aria-hidden="true">
  <div class="tape-back"></div>
  <div class="ticker"><div class="ticker-in">
    <?php foreach ($skillNames as $n => $name): ?>
    <?php if ($n > 0): ?><svg viewBox="0 0 100 100"><use href="#i-sparkle"/></svg><?php endif; ?><span><?= e($name) ?></span>
    <?php endforeach; ?>
  </div></div>
</div>
<?php endif; ?>

<div class="wrap">

  <!-- ===================== TENTANG ===================== -->
  <section class="sec" id="about" aria-labelledby="about-title">
    <div class="head"><span class="num"><?= e($secLabel('about.label')) ?></span><h2 id="about-title"><?= hl_title(t('about.title'), 'pink') ?></h2></div>
    <div class="about">
      <div class="card box about-text">
        <?= paragraphs(tr($profile, 'about')) ?>
        <?php if ($socials): ?>
        <ul class="social">
          <?php foreach ($socials as $n => $s): ?>
          <li><a class="ibtn bg-<?= cycle_color($n, ['blue', 'pink', 'yellow', 'mint']) ?>" href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>"><?= icon($s['key']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <!-- Kartu terminal: hiasan, isinya sama dengan data di atas -->
      <div class="term" aria-hidden="true">
        <div class="win-bar"><i></i><i></i><i></i><span>~/<?= e(mb_strtolower($nameWords[0] ?? $nameLast)) ?></span></div>
<pre><span class="p">$</span> whoami
<span class="y"><?= e($fullName) ?></span>
<?php if ($roles): ?><span class="p">$</span> cat <?= lang() === 'en' ? 'role.txt' : 'peran.txt' ?>

<span class="d"><?= e(implode(' · ', $roles)) ?></span>
<?php endif; ?><?php if ($email !== ''): ?><span class="p">$</span> echo $EMAIL
<span class="d"><?= e($email) ?></span>
<?php endif; ?><span class="p">$</span> <span class="cur"></span></pre>
      </div>
    </div>
  </section>

  <?php if ($skillGroups): ?>
  <!-- ===================== SKILL ===================== -->
  <section class="sec" id="skills" aria-labelledby="skills-title">
    <div class="head"><span class="num"><?= e($secLabel('skills.label')) ?></span><h2 id="skills-title"><?= hl_title(t('skills.title'), 'mint') ?></h2></div>
    <div class="skills">
      <?php $n = 0; foreach ($skillGroups as $category => $names): $ico = skill_category_icon($category); ?>
      <div class="skill box bg-<?= cycle_color($n++, ['yellow', 'pink', 'mint', 'lav', 'blue', 'orange']) ?>">
        <?= icon($ico, 'i wm') ?>
        <div class="skill-top">
          <span class="skill-ico"><?= icon($ico) ?></span>
          <span class="skill-count"><?= e(t('skills.count', ['n' => count($names)])) ?></span>
        </div>
        <h3><?= e(category_label('skill_categories', (string) $category)) ?></h3>
        <ul class="chips">
          <?php foreach ($names as $name): ?>
          <li><?php if ($ti = tech_icon($name)): ?><?= icon($ti) ?><?php endif; ?><?= e($name) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($featured): ?>
  <!-- ===================== PROJECT UNGGULAN ===================== -->
  <section class="sec" id="projects" aria-labelledby="projects-title">
    <div class="head-row">
      <div class="head"><span class="num"><?= e($secLabel('projects.label')) ?></span><h2 id="projects-title"><?= hl_title(t('projects.title'), 'yellow') ?></h2></div>
      <a class="btn white" href="<?= e(lang_url('projects')) ?>"><?= e(t('projects.all')) ?><?= icon('arrow', 'i nudge') ?></a>
    </div>
    <ul class="projects">
      <?php foreach ($featured as $i => $p): ?>
      <?php require ROOT_PATH . '/includes/project_card.php'; ?>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <?php if ($education || $journey): ?>
  <!-- ===================== PENGALAMAN & PENDIDIKAN ===================== -->
  <section class="sec" id="experience" aria-labelledby="experience-title">
    <div class="head"><span class="num"><?= e($secLabel('experience.label')) ?></span><h2 id="experience-title"><?= hl_title(t('experience.title'), 'lav') ?></h2></div>
    <div class="journey<?= ($education && $journey) ? '' : ' single' ?>">
      <?php foreach ([['edu', 'school', 'yellow', $education], ['work', 'briefcase', 'pink', $journey]] as [$key, $ico, $color, $items]): ?>
      <?php if ($items): ?>
      <div class="jcol">
        <h3 class="jtitle"><span class="jico bg-<?= $color ?>"><?= icon($ico) ?></span><?= e(t('experience.' . $key)) ?> <span class="jcount"><?= count($items) ?></span></h3>
        <ol class="tl">
          <?php foreach ($items as $i => $row): ?>
          <?= journey_item($row, $key === 'edu' ? $i : $i + 2) ?>
          <?php endforeach; ?>
        </ol>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($achievements): ?>
  <!-- ===================== SERTIFIKAT & LOMBA ===================== -->
  <section class="sec" id="achievements" aria-labelledby="achievements-title">
    <div class="head"><span class="num"><?= e($secLabel('achievements.label')) ?></span><h2 id="achievements-title"><?= hl_title(t('achievements.title'), 'orange') ?></h2></div>
    <ul class="certs" id="cert-list">
      <?php foreach ($achievements as $i => $a):
          $title = tr($a, 'title');
          $typeIcon = ['sertifikat' => 'cert', 'lomba' => 'trophy', 'penghargaan' => 'award'][$a['type']] ?? 'award';
          $img = responsive_img('achievements', $a['image'], t('type.' . $a['type']) . ': ' . $title,
              '(max-width: 767px) calc(100vw - 32px), (max-width: 1023px) 50vw, 360px');
          $proof = safe_url($a['credential_url'] ?? '');
          $result = tr($a, 'result');
      ?>
      <li<?= $i >= ACHIEVEMENTS_VISIBLE ? ' class="is-extra"' : '' ?>>
        <article class="cert"<?= $i === ACHIEVEMENTS_VISIBLE ? ' tabindex="-1" data-first-extra' : '' ?>>
          <?php if ($img !== ''): ?>
          <a class="cert-img" href="<?= e(upload_url('achievements', $a['image'])) ?>" target="_blank" rel="noopener" style="--c:var(--<?= cycle_color($i, ['lav', 'blue', 'orange', 'mint', 'pink', 'yellow']) ?>)">
            <span class="sticker bg-<?= cycle_color($i, ['pink', 'yellow', 'mint', 'lav', 'orange', 'blue']) ?>"><?= e(t('type.' . $a['type'])) ?></span>
            <?= $img ?>
            <span class="zoom"><?= icon('arrow-ne') ?><?= e(t('achievements.view')) ?></span>
          </a>
          <?php else: ?>
          <div class="cert-img" style="--c:var(--<?= cycle_color($i, ['lav', 'blue', 'orange', 'mint', 'pink', 'yellow']) ?>)">
            <span class="sticker bg-<?= cycle_color($i, ['pink', 'yellow', 'mint', 'lav', 'orange', 'blue']) ?>"><?= e(t('type.' . $a['type'])) ?></span>
            <div class="paper" aria-hidden="true"><?= icon($typeIcon) ?></div>
          </div>
          <?php endif; ?>
          <div class="cert-b">
            <h3><?= e($title) ?></h3>
            <p class="by"><?= e($a['issuer']) ?> · <?= e(format_month($a['issued_date'])) ?></p>
            <?php if ($result !== ''): ?>
            <span class="res"><?= e($result) ?></span>
            <?php endif; ?>
            <?php if ($proof !== ''): ?>
            <a class="proof" href="<?= e($proof) ?>" target="_blank" rel="noopener"><?= e(t('achievements.proof')) ?><span class="sr-only">: <?= e($title) ?></span><?= icon('external') ?></a>
            <?php endif; ?>
          </div>
        </article>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if (count($achievements) > ACHIEVEMENTS_VISIBLE): ?>
    <div class="center">
      <button class="btn white" type="button" data-more="cert-list" aria-controls="cert-list" aria-expanded="false" hidden><?= e(t('achievements.more')) ?><?= icon('down', 'i nudge dn') ?></button>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ===================== KONTAK ===================== -->
  <section class="sec" id="contact" aria-labelledby="contact-title">
    <div class="contact">
      <div class="contact-l">
        <span class="sticker bg-pink" style="--r:-3deg"><?= e($secLabel('contact.label')) ?></span>
        <h2 id="contact-title"><?= hl_title(t('contact.title'), 'yellow') ?></h2>
        <p><?= e(t('contact.body')) ?></p>
        <?php if ($email !== ''): ?>
        <a class="mail" href="mailto:<?= e($email) ?>"><?= icon('mail') ?><span><span class="sr-only"><?= e(t('contact.email')) ?>: </span><?= e($email) ?></span></a>
        <?php endif; ?>
        <?php if ($whatsapp !== ''): ?>
        <a class="mail" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?><span><?= e(t('contact.whatsapp')) ?></span></a>
        <?php endif; ?>
        <?php if ($socials): ?>
        <ul class="social">
          <?php foreach ($socials as $s): ?>
          <li><a class="ibtn" href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>"><?= icon($s['key']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <?php
      // Atribut untuk isian yang salah: ditandai dan dihubungkan ke pesan kesalahannya
      $cAttr = function (string $key, string $hintId = '') use ($cErrors): string {
          $ids = trim($hintId . (isset($cErrors[$key]) ? ' c-' . $key . '-err' : ''));
          return (isset($cErrors[$key]) ? ' aria-invalid="true"' : '') . ($ids !== '' ? ' aria-describedby="' . $ids . '"' : '');
      };
      $cError = fn (string $key): string => isset($cErrors[$key])
          ? '<p class="c-err" id="c-' . $key . '-err">' . icon('alert') . e($cErrors[$key]) . '</p>' : '';
      ?>
      <form class="c-form" method="post" action="/contact_send.php" novalidate aria-labelledby="c-form-title">
        <h3 class="sr-only" id="c-form-title"><?= e(t('contact.form_title')) ?></h3>
        <?= $csrfField ?>
        <input type="hidden" name="lang" value="<?= e(lang()) ?>">

        <?php if ($contact): ?>
        <div class="c-alert <?= $contact['type'] === 'success' ? 'ok' : 'bad' ?>" role="<?= $contact['type'] === 'success' ? 'status' : 'alert' ?>" tabindex="-1" data-autofocus>
          <?= icon($contact['type'] === 'success' ? 'circle-check' : 'alert') ?>
          <p><?= e($contact['message']) ?></p>
        </div>
        <?php endif; ?>

        <div class="c-field">
          <label for="c-name"><?= e(t('contact.name')) ?></label>
          <input class="c-input" id="c-name" name="name" type="text" autocomplete="name" required maxlength="<?= CONTACT_NAME_MAX ?>"
                 placeholder="<?= e(t('contact.name_ph')) ?>" value="<?= e($cOld['name'] ?? '') ?>"<?= $cAttr('name') ?>>
          <?= $cError('name') ?>
        </div>
        <div class="c-field">
          <label for="c-email"><?= e(t('contact.email_label')) ?></label>
          <input class="c-input" id="c-email" name="email" type="email" autocomplete="email" inputmode="email" required maxlength="<?= CONTACT_EMAIL_MAX ?>"
                 placeholder="<?= e(t('contact.email_ph')) ?>" value="<?= e($cOld['email'] ?? '') ?>"<?= $cAttr('email') ?>>
          <?= $cError('email') ?>
        </div>
        <div class="c-field">
          <label for="c-message"><?= e(t('contact.message')) ?></label>
          <textarea class="c-input" id="c-message" name="message" rows="5" required maxlength="<?= CONTACT_MESSAGE_MAX ?>"
                    placeholder="<?= e(t('contact.message_ph')) ?>"<?= $cAttr('message', 'c-message-hint') ?>><?= e($cOld['message'] ?? '') ?></textarea>
          <p class="c-hint" id="c-message-hint"><?= e(t('contact.message_hint', ['min' => CONTACT_MESSAGE_MIN, 'max' => CONTACT_MESSAGE_MAX])) ?></p>
          <?= $cError('message') ?>
        </div>

        <!-- Isian jebakan untuk bot: disembunyikan dari pengunjung dan pembaca layar -->
        <div class="c-trap" aria-hidden="true">
          <label for="c-<?= CONTACT_HONEYPOT ?>"><?= e(t('contact.trap')) ?></label>
          <input id="c-<?= CONTACT_HONEYPOT ?>" name="<?= CONTACT_HONEYPOT ?>" type="text" tabindex="-1" autocomplete="off" value="">
        </div>

        <button class="btn bg-blue" type="submit"><?= e(t('contact.send')) ?><?= icon('send', 'i nudge') ?></button>
      </form>
    </div>
  </section>

</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
