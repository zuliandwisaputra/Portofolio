<?php
/**
 * Bagian bersama untuk tambah & ubah project: nilai awal, validasi, penyimpanan, dan formulir.
 * File ini tidak bisa dibuka langsung; dipanggil dari create.php dan edit.php.
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

const GALLERY_MAX = 8;
const UPLOAD_FOLDER_PROJECTS = 'projects';

function project_defaults(): array
{
    return [
        'title_ind' => '', 'title_eng' => '',
        'summary_ind' => '', 'summary_eng' => '',
        'description_ind' => '', 'description_eng' => '',
        'my_role_ind' => '', 'my_role_eng' => '',
        'category' => (string) array_key_first(config('project_categories', [])),
        'year' => date('Y'),
        'demo_url' => '', 'repo_url' => '',
        'is_featured' => 0,
        'status' => 'draft',
        'sort_order' => (string) next_sort_order('projects'),
        'skills' => [],
        'gallery' => [],
        'remove_cover' => false,
    ];
}

/** Ubah baris database menjadi isian formulir. */
function project_from_row(array $row, array $skillIds, array $gallery): array
{
    $d = project_defaults();
    foreach ($d as $key => $value) {
        if (array_key_exists($key, $row)) {
            $d[$key] = $row[$key] === null ? '' : (string) $row[$key];
        }
    }
    $d['is_featured'] = (int) $row['is_featured'];
    $d['skills'] = array_map('intval', $skillIds);
    foreach ($gallery as $g) {
        $d['gallery'][(int) $g['id']] = [
            'alt_ind' => $g['alt_ind'], 'alt_eng' => (string) $g['alt_eng'],
            'sort_order' => (string) $g['sort_order'], 'delete' => false,
        ];
    }
    return $d;
}

/** Ambil isian dari formulir yang dikirim. */
function project_input(): array
{
    $text = fn (string $key): string => trim((string) ($_POST[$key] ?? ''));

    $skills = [];
    foreach ((array) ($_POST['skills'] ?? []) as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $skills[$id] = $id;
        }
    }

    $gallery = [];
    foreach ((array) ($_POST['gallery'] ?? []) as $imageId => $g) {
        if (!is_array($g) || (int) $imageId < 1) {
            continue;
        }
        $gallery[(int) $imageId] = [
            'alt_ind'    => trim((string) ($g['alt_ind'] ?? '')),
            'alt_eng'    => trim((string) ($g['alt_eng'] ?? '')),
            'sort_order' => trim((string) ($g['sort_order'] ?? '0')),
            'delete'     => !empty($g['delete']),
        ];
    }

    return [
        'title_ind' => $text('title_ind'), 'title_eng' => $text('title_eng'),
        'summary_ind' => $text('summary_ind'), 'summary_eng' => $text('summary_eng'),
        'description_ind' => $text('description_ind'), 'description_eng' => $text('description_eng'),
        'my_role_ind' => $text('my_role_ind'), 'my_role_eng' => $text('my_role_eng'),
        'category' => $text('category'),
        'year' => $text('year'),
        'demo_url' => $text('demo_url'), 'repo_url' => $text('repo_url'),
        'is_featured' => empty($_POST['is_featured']) ? 0 : 1,
        'status' => $text('status'),
        'sort_order' => $text('sort_order'),
        'skills' => array_values($skills),
        'gallery' => $gallery,
        'remove_cover' => !empty($_POST['remove_cover']),
    ];
}

/** Semua skill untuk pilihan teknologi, dikelompokkan per kategori. */
function all_skills(): array
{
    $stmt = db()->prepare('SELECT id, name, category, status FROM skills ORDER BY sort_order, name');
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Periksa isian. $existingGallery = gambar galeri yang sudah ada [id => baris].
 * Mengembalikan daftar error [id isian => pesan].
 */
function project_validate(array $d, array $existingGallery, ?array $cover, array $newFiles): array
{
    $errors = [];
    $rules = [
        'title_ind'       => ['judul (Indonesia)', 150, true],
        'title_eng'       => ['Judul (Inggris)', 150, false],
        'summary_ind'     => ['ringkasan (Indonesia)', 255, true],
        'summary_eng'     => ['Ringkasan (Inggris)', 255, false],
        'description_ind' => ['deskripsi (Indonesia)', 20000, true],
        'description_eng' => ['Deskripsi (Inggris)', 20000, false],
        'my_role_ind'     => ['Peran (Indonesia)', 150, false],
        'my_role_eng'     => ['Peran (Inggris)', 150, false],
    ];
    foreach ($rules as $field => [$label, $max, $required]) {
        if ($msg = check_text($d[$field], $label, $max, $required)) {
            $errors[$field] = $msg;
        }
    }

    if (!array_key_exists($d['category'], config('project_categories', []))) {
        $errors['category'] = 'Pilih kategori project.';
    }
    $maxYear = (int) date('Y') + 1;
    if (filter_var($d['year'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => $maxYear]]) === false) {
        $errors['year'] = "Tahun harus angka antara 2000 dan $maxYear.";
    }
    if ($msg = check_url($d['demo_url'], 'Tautan website')) {
        $errors['demo_url'] = $msg;
    }
    if ($msg = check_url($d['repo_url'], 'Tautan repo GitHub')) {
        $errors['repo_url'] = $msg;
    }
    if (!isset(STATUS_LABELS[$d['status']])) {
        $errors['status'] = 'Pilih status Draf atau Terbit.';
    }
    if (filter_var($d['sort_order'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]) === false) {
        $errors['sort_order'] = 'Urutan harus berupa angka 0 sampai 9999.';
    }

    if ($cover !== null && ($msg = image_upload_error($cover))) {
        $errors['cover_image'] = $msg . ' Pilih ulang gambar sampul.';
    }

    // Galeri yang sudah ada: teks alt Indonesia wajib
    $kept = 0;
    foreach ($existingGallery as $imageId => $row) {
        $g = $d['gallery'][$imageId] ?? null;
        if ($g === null || $g['delete']) {
            continue;
        }
        $kept++;
        if ($msg = check_text($g['alt_ind'], 'teks alt (Indonesia) gambar #' . $kept, 200, true)) {
            $errors["gallery_{$imageId}_alt_ind"] = $msg;
        }
        if ($msg = check_text($g['alt_eng'], 'Teks alt (Inggris) gambar #' . $kept, 200, false)) {
            $errors["gallery_{$imageId}_alt_eng"] = $msg;
        }
        if (filter_var($g['sort_order'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99]]) === false) {
            $errors["gallery_{$imageId}_sort_order"] = 'Urutan gambar #' . $kept . ' harus angka 0 sampai 99.';
        }
    }

    // Gambar galeri baru
    $fileErrors = [];
    foreach ($newFiles as $file) {
        if ($msg = image_upload_error($file)) {
            $fileErrors[] = $msg;
        }
    }
    if ($kept + count($newFiles) > GALLERY_MAX) {
        $fileErrors[] = 'Galeri maksimal ' . GALLERY_MAX . ' gambar. Sekarang ada ' . $kept
            . ', kamu menambah ' . count($newFiles) . '. Hapus sebagian atau pilih lebih sedikit gambar.';
    }
    if ($fileErrors) {
        $errors['gallery_new'] = implode(' ', $fileErrors) . ' Pilih ulang gambar galeri.';
    }

    return $errors;
}

/** Buat slug unik dari judul Indonesia (hanya saat project baru dibuat). */
function unique_project_slug(string $title): string
{
    $base = make_slug($title);
    $slug = $base;
    $stmt = db()->prepare('SELECT COUNT(*) FROM projects WHERE slug = ?');
    for ($n = 2; ; $n++) {
        $stmt->execute([$slug]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = substr($base, 0, 95) . '-' . $n;
    }
}

/**
 * Simpan project (baru atau ubah) beserta teknologi, sampul, dan galeri.
 * Semua perubahan database dalam satu transaksi: kalau ada yang gagal, semuanya dibatalkan
 * dan gambar yang telanjur tersimpan ikut dihapus.
 * Mengembalikan id project.
 */
function project_save(array $d, ?array $project, ?array $cover, array $newFiles, array $existingGallery): int
{
    $pdo       = db();
    $folder    = UPLOAD_FOLDER_PROJECTS;
    $savedNow  = [];   // file baru yang dibuat di permintaan ini
    $deleteAfter = []; // file lama yang dihapus setelah berhasil

    try {
        // 1. Gambar sampul
        $coverName = $project['cover_image'] ?? null;
        if ($cover !== null) {
            $newCover = save_image($cover, $folder);
            $savedNow[] = $newCover;
            if ($coverName) {
                $deleteAfter[] = $coverName;
            }
            $coverName = $newCover;
        } elseif ($d['remove_cover'] && $coverName) {
            $deleteAfter[] = $coverName;
            $coverName = null;
        }

        // 2. Gambar galeri baru
        $newGallery = [];
        foreach ($newFiles as $file) {
            $name = save_image($file, $folder);
            $savedNow[] = $name;
            $newGallery[] = $name;
        }

        $values = [
            $d['title_ind'], $d['title_eng'] ?: null,
            $d['summary_ind'], $d['summary_eng'] ?: null,
            $d['description_ind'], $d['description_eng'] ?: null,
            $d['my_role_ind'] ?: null, $d['my_role_eng'] ?: null,
            $d['category'], (int) $d['year'], $coverName,
            $d['demo_url'] ?: null, $d['repo_url'] ?: null,
            (int) $d['is_featured'], $d['status'], (int) $d['sort_order'],
        ];

        $pdo->beginTransaction();

        // 3. Data project
        if ($project) {
            $id = (int) $project['id'];
            $pdo->prepare(
                'UPDATE projects SET title_ind = ?, title_eng = ?, summary_ind = ?, summary_eng = ?,
                        description_ind = ?, description_eng = ?, my_role_ind = ?, my_role_eng = ?,
                        category = ?, year = ?, cover_image = ?, demo_url = ?, repo_url = ?,
                        is_featured = ?, status = ?, sort_order = ?
                  WHERE id = ?'
            )->execute([...$values, $id]);
        } else {
            $pdo->prepare(
                'INSERT INTO projects (title_ind, title_eng, summary_ind, summary_eng, description_ind, description_eng,
                        my_role_ind, my_role_eng, category, year, cover_image, demo_url, repo_url,
                        is_featured, status, sort_order, slug)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([...$values, unique_project_slug($d['title_ind'])]);
            $id = (int) $pdo->lastInsertId();
        }

        // 4. Teknologi (dipilih dari daftar skill yang memang ada)
        $pdo->prepare('DELETE FROM project_skill WHERE project_id = ?')->execute([$id]);
        if ($d['skills']) {
            $valid = $pdo->prepare('SELECT id FROM skills WHERE id IN (' . implode(',', array_fill(0, count($d['skills']), '?')) . ')');
            $valid->execute($d['skills']);
            $insert = $pdo->prepare('INSERT INTO project_skill (project_id, skill_id) VALUES (?, ?)');
            foreach ($valid->fetchAll(PDO::FETCH_COLUMN) as $skillId) {
                $insert->execute([$id, (int) $skillId]);
            }
        }

        // 5. Galeri lama: ubah teks alt/urutan, atau hapus
        $update = $pdo->prepare('UPDATE project_images SET alt_ind = ?, alt_eng = ?, sort_order = ? WHERE id = ? AND project_id = ?');
        $delete = $pdo->prepare('DELETE FROM project_images WHERE id = ? AND project_id = ?');
        $maxOrder = 0;
        foreach ($existingGallery as $imageId => $row) {
            $g = $d['gallery'][$imageId] ?? null;
            if ($g !== null && $g['delete']) {
                $delete->execute([$imageId, $id]);
                $deleteAfter[] = $row['file_name'];
            } elseif ($g !== null) {
                $update->execute([$g['alt_ind'], $g['alt_eng'] ?: null, (int) $g['sort_order'], $imageId, $id]);
                $maxOrder = max($maxOrder, (int) $g['sort_order']);
            } else {
                $maxOrder = max($maxOrder, (int) $row['sort_order']);
            }
        }

        // 6. Galeri baru: teks alt otomatis dari judul (bisa diubah setelah disimpan)
        $insertImage = $pdo->prepare(
            'INSERT INTO project_images (project_id, file_name, alt_ind, alt_eng, sort_order) VALUES (?, ?, ?, ?, ?)'
        );
        $titleEng = $d['title_eng'] ?: $d['title_ind'];
        foreach ($newGallery as $i => $name) {
            $n = $maxOrder + $i + 1;
            $insertImage->execute([
                $id, $name,
                mb_substr('Tampilan ' . $d['title_ind'] . ' ' . $n, 0, 200),
                mb_substr($titleEng . ' screenshot ' . $n, 0, 200),
                $n,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($savedNow as $name) {
            delete_image($folder, $name);
        }
        throw $e;
    }

    foreach ($deleteAfter as $name) {
        delete_image($folder, $name);
    }
    return $id;
}

/** Satu pasang isian Indonesia + Inggris. */
function bilingual_field(array $d, array $errors, string $base, string $labelId, string $labelEn, int $max, bool $required, bool $textarea = false, ?string $hint = null): void
{
    ?>
    <div class="pair">
      <?php foreach (['ind' => [$labelId, 'ID', $required], 'eng' => [$labelEn, 'EN', false]] as $suffix => [$label, $tag, $req]):
          $name = $base . '_' . $suffix;
          $hintId = $hint && $suffix === 'ind' ? $name . '-hint' : null; ?>
      <div class="field">
        <label for="<?= e($name) ?>"><span class="lang-tag lang-<?= e($suffix) ?>"><?= e($tag) ?></span><?= e($label) ?>
          <?= $req ? '<span class="req" aria-hidden="true">*</span>' : '<span class="opt">(opsional)</span>' ?></label>
        <?php if ($textarea): ?>
        <textarea class="input" id="<?= e($name) ?>" name="<?= e($name) ?>" rows="7" maxlength="<?= e($max) ?>"
                  <?= $req ? 'required' : '' ?><?= field_attrs($errors, $name, $hintId) ?>><?= e($d[$name]) ?></textarea>
        <?php else: ?>
        <input class="input" id="<?= e($name) ?>" name="<?= e($name) ?>" type="text" maxlength="<?= e($max) ?>"
               value="<?= e($d[$name]) ?>" <?= $req ? 'required' : '' ?><?= field_attrs($errors, $name, $hintId) ?>>
        <?php endif; ?>
        <?php if ($hintId): ?><p class="hint" id="<?= e($hintId) ?>"><?= e($hint) ?></p><?php endif; ?>
        <?= field_error($errors, $name) ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Tampilkan formulir project.
 * $project = baris project (saat ubah) atau null (saat tambah).
 * $gallery = gambar galeri yang sudah ada [id => baris].
 */
function project_form(array $d, array $errors, string $action, string $submitLabel, ?array $project, array $gallery): void
{
    $skillsByCategory = [];
    foreach (all_skills() as $skill) {
        $skillsByCategory[$skill['category']][] = $skill;
    }
    $kept = count(array_filter($gallery, fn ($row) => empty($d['gallery'][$row['id']]['delete'])));
    $slotsLeft = max(0, GALLERY_MAX - $kept);
    ?>
    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form project-form" novalidate>
      <?= csrf_field() ?>

      <section class="panel card box" aria-labelledby="sec-text">
        <h2 class="panel-title" id="sec-text"><?= icon('pencil') ?>Teks project</h2>
        <p class="hint">Kolom <b>Indonesia</b> wajib. Kolom <b>Inggris</b> boleh kosong; pengunjung versi Inggris akan melihat teks Indonesia.</p>
        <?php bilingual_field($d, $errors, 'title', 'Judul', 'Title', 150, true, false, 'Alamat halaman project dibuat otomatis dari judul ini saat pertama disimpan.'); ?>
        <?php bilingual_field($d, $errors, 'summary', 'Ringkasan', 'Summary', 255, true, false, 'Satu kalimat untuk kartu project di beranda.'); ?>
        <?php bilingual_field($d, $errors, 'description', 'Deskripsi', 'Description', 20000, true, true, 'Teks biasa. Pisahkan paragraf dengan baris kosong.'); ?>
        <?php bilingual_field($d, $errors, 'my_role', 'Peran saya', 'My role', 150, false); ?>
      </section>

      <section class="panel card box" aria-labelledby="sec-detail">
        <h2 class="panel-title" id="sec-detail"><?= icon('folder') ?>Detail</h2>
        <div class="grid-2">
          <div class="field">
            <label for="category">Kategori <span class="req" aria-hidden="true">*</span></label>
            <select class="input" id="category" name="category" required<?= field_attrs($errors, 'category') ?>>
              <?php foreach (config('project_categories', []) as $key => $labels): ?>
              <option value="<?= e($key) ?>" <?= $d['category'] === $key ? 'selected' : '' ?>><?= e($labels['id']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'category') ?>
          </div>
          <div class="field">
            <label for="year">Tahun <span class="req" aria-hidden="true">*</span></label>
            <input class="input" id="year" name="year" type="number" inputmode="numeric" min="2000" max="<?= e((int) date('Y') + 1) ?>"
                   required value="<?= e($d['year']) ?>"<?= field_attrs($errors, 'year') ?>>
            <?= field_error($errors, 'year') ?>
          </div>
          <div class="field">
            <label for="demo_url">Tautan website <span class="opt">(opsional)</span></label>
            <input class="input" id="demo_url" name="demo_url" type="url" inputmode="url" maxlength="255" placeholder="https://"
                   value="<?= e($d['demo_url']) ?>"<?= field_attrs($errors, 'demo_url') ?>>
            <?= field_error($errors, 'demo_url') ?>
          </div>
          <div class="field">
            <label for="repo_url">Tautan repo GitHub <span class="opt">(opsional)</span></label>
            <input class="input" id="repo_url" name="repo_url" type="url" inputmode="url" maxlength="255" placeholder="https://github.com/..."
                   value="<?= e($d['repo_url']) ?>"<?= field_attrs($errors, 'repo_url') ?>>
            <?= field_error($errors, 'repo_url') ?>
          </div>
        </div>
      </section>

      <section class="panel card box" aria-labelledby="sec-tech">
        <h2 class="panel-title" id="sec-tech"><?= icon('tools') ?>Teknologi yang dipakai</h2>
        <?php if (!$skillsByCategory): ?>
        <p class="hint">Belum ada skill. <a href="/admin/skills/create.php">Tambah skill dulu</a>, lalu pilih di sini.</p>
        <?php else: ?>
        <p class="hint">Dipilih dari daftar <a href="/admin/skills/">Skill</a>, jadi tidak perlu diketik ulang.</p>
        <div class="check-groups">
          <?php foreach (config('skill_categories', []) as $catKey => $catLabels):
              if (empty($skillsByCategory[$catKey])) { continue; } ?>
          <fieldset class="check-group">
            <legend><?= e($catLabels['id']) ?></legend>
            <div class="checks">
              <?php foreach ($skillsByCategory[$catKey] as $skill): ?>
              <label class="check-chip">
                <input type="checkbox" name="skills[]" value="<?= e($skill['id']) ?>" <?= in_array((int) $skill['id'], $d['skills'], true) ? 'checked' : '' ?>>
                <span><?= e($skill['name']) ?><?= $skill['status'] === 'draft' ? ' <em>(draf)</em>' : '' ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </section>

      <section class="panel card box" aria-labelledby="sec-cover">
        <h2 class="panel-title" id="sec-cover"><?= icon('photo') ?>Gambar sampul</h2>
        <?php if (!empty($project['cover_image'])):
            [$w, $h] = image_size(UPLOAD_FOLDER_PROJECTS, $project['cover_image'], true); ?>
        <div class="cover-current">
          <img src="<?= e(upload_url(UPLOAD_FOLDER_PROJECTS, $project['cover_image'], true)) ?>" width="<?= e($w) ?>" height="<?= e($h) ?>" alt="Sampul saat ini">
          <label class="choice"><input type="checkbox" name="remove_cover" value="1" <?= $d['remove_cover'] ? 'checked' : '' ?>> Hapus sampul ini</label>
        </div>
        <?php endif; ?>
        <div class="field">
          <label for="cover_image"><?= !empty($project['cover_image']) ? 'Ganti sampul' : 'Pilih sampul' ?> <span class="opt">(opsional)</span></label>
          <input class="input file" id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp"<?= field_attrs($errors, 'cover_image', 'cover-hint') ?>>
          <p class="hint" id="cover-hint">JPG, PNG, atau WebP, maksimal 2 MB. Ukuran ideal 1600 × 1000 piksel (16:10). Gambar diperkecil otomatis.</p>
          <?= field_error($errors, 'cover_image') ?>
        </div>
      </section>

      <section class="panel card box" aria-labelledby="sec-gallery">
        <h2 class="panel-title" id="sec-gallery"><?= icon('photo') ?>Galeri <span class="count-tag"><?= e($kept) ?>/<?= GALLERY_MAX ?></span></h2>
        <?php if ($gallery): ?>
        <ul class="gallery-list">
          <?php $n = 0; foreach ($gallery as $imageId => $row): $n++;
              $g = $d['gallery'][$imageId] ?? ['alt_ind' => $row['alt_ind'], 'alt_eng' => (string) $row['alt_eng'], 'sort_order' => (string) $row['sort_order'], 'delete' => false];
              [$w, $h] = image_size(UPLOAD_FOLDER_PROJECTS, $row['file_name'], true);
              $base = 'gallery_' . $imageId; ?>
          <li class="g-item">
            <img src="<?= e(upload_url(UPLOAD_FOLDER_PROJECTS, $row['file_name'], true)) ?>" width="<?= e($w) ?>" height="<?= e($h) ?>" alt="<?= e($g['alt_ind']) ?>" loading="lazy">
            <div class="g-fields">
              <div class="field">
                <label for="<?= e($base) ?>_alt_ind"><span class="lang-tag lang-ind">ID</span>Teks alt gambar #<?= e($n) ?> <span class="req" aria-hidden="true">*</span></label>
                <input class="input" id="<?= e($base) ?>_alt_ind" name="gallery[<?= e($imageId) ?>][alt_ind]" maxlength="200" value="<?= e($g['alt_ind']) ?>"<?= field_attrs($errors, $base . '_alt_ind') ?>>
                <?= field_error($errors, $base . '_alt_ind') ?>
              </div>
              <div class="field">
                <label for="<?= e($base) ?>_alt_eng"><span class="lang-tag lang-eng">EN</span>Alt text <span class="opt">(opsional)</span></label>
                <input class="input" id="<?= e($base) ?>_alt_eng" name="gallery[<?= e($imageId) ?>][alt_eng]" maxlength="200" value="<?= e($g['alt_eng']) ?>"<?= field_attrs($errors, $base . '_alt_eng') ?>>
                <?= field_error($errors, $base . '_alt_eng') ?>
              </div>
              <div class="g-row">
                <div class="field field-sm">
                  <label for="<?= e($base) ?>_sort_order">Urutan</label>
                  <input class="input" id="<?= e($base) ?>_sort_order" name="gallery[<?= e($imageId) ?>][sort_order]" type="number" min="0" max="99" value="<?= e($g['sort_order']) ?>"<?= field_attrs($errors, $base . '_sort_order') ?>>
                </div>
                <label class="choice choice-danger"><input type="checkbox" name="gallery[<?= e($imageId) ?>][delete]" value="1" <?= $g['delete'] ? 'checked' : '' ?>> Hapus gambar ini</label>
              </div>
              <?= field_error($errors, $base . '_sort_order') ?>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <div class="field">
          <label for="gallery_new">Tambah gambar galeri <span class="opt">(opsional, sisa <?= e($slotsLeft) ?> slot)</span></label>
          <input class="input file" id="gallery_new" name="gallery_new[]" type="file" multiple accept="image/jpeg,image/png,image/webp"<?= field_attrs($errors, 'gallery_new', 'gallery-hint') ?>>
          <p class="hint" id="gallery-hint">Bisa pilih beberapa sekaligus. JPG, PNG, atau WebP, maksimal 2 MB per gambar. Teks alt dibuat otomatis dari judul dan bisa kamu ubah setelah disimpan.</p>
          <?= field_error($errors, 'gallery_new') ?>
        </div>
      </section>

      <section class="panel card box" aria-labelledby="sec-show">
        <h2 class="panel-title" id="sec-show"><?= icon('star') ?>Tampilan</h2>
        <div class="grid-3">
          <fieldset class="field" id="status"<?= isset($errors['status']) ? ' aria-describedby="status-err"' : '' ?>>
            <legend>Status <span class="req" aria-hidden="true">*</span></legend>
            <div class="choices">
              <?php foreach (STATUS_LABELS as $value => $label): ?>
              <label class="choice"><input type="radio" name="status" value="<?= e($value) ?>" <?= $d['status'] === $value ? 'checked' : '' ?>> <?= e($label) ?></label>
              <?php endforeach; ?>
            </div>
            <?= field_error($errors, 'status') ?>
          </fieldset>
          <div class="field">
            <span class="label">Unggulan</span>
            <label class="choice"><input type="checkbox" name="is_featured" value="1" <?= $d['is_featured'] ? 'checked' : '' ?>> Tampilkan di beranda</label>
            <p class="hint">Beranda menampilkan paling banyak 6 project unggulan.</p>
          </div>
          <div class="field">
            <label for="sort_order">Urutan <span class="req" aria-hidden="true">*</span></label>
            <input class="input" id="sort_order" name="sort_order" type="number" min="0" max="9999" inputmode="numeric" required
                   value="<?= e($d['sort_order']) ?>"<?= field_attrs($errors, 'sort_order', 'sort-hint') ?>>
            <p class="hint" id="sort-hint">Angka kecil tampil lebih dulu.</p>
            <?= field_error($errors, 'sort_order') ?>
          </div>
        </div>
      </section>

      <div class="form-actions form-actions-sticky">
        <a class="btn white" href="/admin/projects/">Batal</a>
        <button class="btn bg-blue" type="submit"><?= icon('check') ?><?= e($submitLabel) ?></button>
      </div>
    </form>
    <?php
}
