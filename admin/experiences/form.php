<?php
/**
 * Bagian bersama untuk tambah & ubah pengalaman/pendidikan.
 * File ini tidak bisa dibuka langsung; dipanggil dari create.php dan edit.php.
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

const EXPERIENCE_TYPES = ['kerja' => 'Kerja', 'organisasi' => 'Organisasi', 'pendidikan' => 'Pendidikan'];
const UPLOAD_FOLDER_EXPERIENCES = 'experiences';

function experience_defaults(): array
{
    return [
        'type' => 'kerja',
        'title_ind' => '', 'title_eng' => '',
        'organization' => '', 'location' => '',
        'start' => '', 'end' => '', 'is_current' => 0,
        'description_ind' => '', 'description_eng' => '',
        'status' => 'published',
        'remove_image' => false,
    ];
}

function experience_from_row(array $row): array
{
    return [
        'type' => $row['type'],
        'title_ind' => $row['title_ind'], 'title_eng' => (string) $row['title_eng'],
        'organization' => $row['organization'], 'location' => (string) $row['location'],
        'start' => month_value($row['start_date']), 'end' => month_value($row['end_date']),
        'is_current' => (int) $row['is_current'],
        'description_ind' => (string) $row['description_ind'], 'description_eng' => (string) $row['description_eng'],
        'status' => $row['status'],
        'remove_image' => false,
    ];
}

function experience_input(): array
{
    $text = fn (string $key): string => trim((string) ($_POST[$key] ?? ''));
    return [
        'type' => $text('type'),
        'title_ind' => $text('title_ind'), 'title_eng' => $text('title_eng'),
        'organization' => $text('organization'), 'location' => $text('location'),
        'start' => $text('start'), 'end' => $text('end'),
        'is_current' => empty($_POST['is_current']) ? 0 : 1,
        'description_ind' => $text('description_ind'), 'description_eng' => $text('description_eng'),
        'status' => $text('status'),
        'remove_image' => !empty($_POST['remove_image']),
    ];
}

function experience_validate(array $d, ?array $image): array
{
    $errors = [];
    if (!isset(EXPERIENCE_TYPES[$d['type']])) {
        $errors['type'] = 'Pilih jenis: kerja, organisasi, atau pendidikan.';
    }
    $rules = [
        'title_ind'       => ['posisi atau jurusan (Indonesia)', 150, true],
        'title_eng'       => ['Posisi atau jurusan (Inggris)', 150, false],
        'organization'    => ['nama tempat', 150, true],
        'location'        => ['Lokasi', 100, false],
        'description_ind' => ['Deskripsi (Indonesia)', 5000, false],
        'description_eng' => ['Deskripsi (Inggris)', 5000, false],
    ];
    foreach ($rules as $field => [$label, $max, $required]) {
        if ($msg = check_text($d[$field], $label, $max, $required)) {
            $errors[$field] = $msg;
        }
    }

    $start = parse_month($d['start']);
    if ($d['start'] === '') {
        $errors['start'] = 'Isi bulan & tahun mulai (contoh: 2023-07).';
    } elseif ($start === null) {
        $errors['start'] = 'Bulan mulai tidak valid. Gunakan format tahun-bulan, contoh: 2023-07.';
    }
    if (!$d['is_current']) {
        $end = parse_month($d['end']);
        if ($d['end'] === '') {
            $errors['end'] = 'Isi bulan & tahun selesai, atau centang "Masih berlangsung".';
        } elseif ($end === null) {
            $errors['end'] = 'Bulan selesai tidak valid. Gunakan format tahun-bulan, contoh: 2024-06.';
        } elseif ($start !== null && $end < $start) {
            $errors['end'] = 'Bulan selesai tidak boleh sebelum bulan mulai.';
        }
    }

    if (!isset(STATUS_LABELS[$d['status']])) {
        $errors['status'] = 'Pilih status Draf atau Terbit.';
    }
    if ($image !== null && ($msg = image_upload_error($image))) {
        $errors['image'] = $msg . ' Pilih ulang foto.';
    }
    return $errors;
}

/** Simpan (baru/ubah). Foto lama dihapus setelah database berhasil diperbarui. */
function experience_save(array $d, ?array $row, ?array $image): int
{
    $folder = UPLOAD_FOLDER_EXPERIENCES;
    $current = $row['image'] ?? null;
    $new = null;
    $old = null;

    if ($image !== null) {
        $new = save_image($image, $folder);
        $old = $current;
        $current = $new;
    } elseif ($d['remove_image'] && $current) {
        $old = $current;
        $current = null;
    }

    $values = [
        $d['type'], $d['title_ind'], $d['title_eng'] ?: null,
        $d['organization'], $d['location'] ?: null,
        parse_month($d['start']), $d['is_current'] ? null : parse_month($d['end']), (int) $d['is_current'],
        $d['description_ind'] ?: null, $d['description_eng'] ?: null,
        $current, $d['status'],
    ];

    try {
        if ($row) {
            db()->prepare(
                'UPDATE experiences SET type = ?, title_ind = ?, title_eng = ?, organization = ?, location = ?,
                        start_date = ?, end_date = ?, is_current = ?, description_ind = ?, description_eng = ?,
                        image = ?, status = ?
                  WHERE id = ?'
            )->execute([...$values, $row['id']]);
            $id = (int) $row['id'];
        } else {
            db()->prepare(
                'INSERT INTO experiences (type, title_ind, title_eng, organization, location, start_date, end_date,
                        is_current, description_ind, description_eng, image, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute($values);
            $id = (int) db()->lastInsertId();
        }
    } catch (Throwable $e) {
        delete_image($folder, $new);
        throw $e;
    }

    delete_image($folder, $old);
    return $id;
}

function experience_form(array $d, array $errors, string $action, string $submitLabel, ?array $row): void
{
    ?>
    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form project-form" novalidate>
      <?= csrf_field() ?>

      <section class="panel card box" aria-labelledby="sec-main">
        <h2 class="panel-title" id="sec-main"><?= icon('school') ?>Data utama</h2>
        <?php choice_field($errors, 'type', 'Jenis', EXPERIENCE_TYPES, $d['type']); ?>
        <?php bilingual_field($d, $errors, 'title', 'Posisi atau jurusan', 'Position or major', 150, true, false, 'Contoh: Anggota Divisi IT, atau Rekayasa Perangkat Lunak.'); ?>
        <div class="grid-2">
          <div class="field">
            <label for="organization">Nama tempat <span class="req" aria-hidden="true">*</span></label>
            <input class="input" id="organization" name="organization" type="text" maxlength="150" required
                   value="<?= e($d['organization']) ?>"<?= field_attrs($errors, 'organization', 'org-hint') ?>>
            <p class="hint" id="org-hint">Perusahaan, organisasi, atau sekolah/kampus. Tidak perlu diterjemahkan.</p>
            <?= field_error($errors, 'organization') ?>
          </div>
          <div class="field">
            <label for="location">Lokasi <span class="opt">(opsional)</span></label>
            <input class="input" id="location" name="location" type="text" maxlength="100" placeholder="Contoh: Jakarta"
                   value="<?= e($d['location']) ?>"<?= field_attrs($errors, 'location') ?>>
            <?= field_error($errors, 'location') ?>
          </div>
        </div>
      </section>

      <section class="panel card box" aria-labelledby="sec-time">
        <h2 class="panel-title" id="sec-time"><?= icon('briefcase') ?>Waktu</h2>
        <div class="grid-3">
          <div class="field">
            <label for="start">Mulai <span class="req" aria-hidden="true">*</span></label>
            <input class="input" id="start" name="start" type="month" placeholder="2023-07" required
                   value="<?= e($d['start']) ?>"<?= field_attrs($errors, 'start') ?>>
            <?= field_error($errors, 'start') ?>
          </div>
          <div class="field">
            <label for="end">Selesai</label>
            <input class="input" id="end" name="end" type="month" placeholder="2024-06"
                   value="<?= e($d['end']) ?>"<?= field_attrs($errors, 'end', 'end-hint') ?>>
            <p class="hint" id="end-hint">Kosongkan kalau masih berlangsung.</p>
            <?= field_error($errors, 'end') ?>
          </div>
          <div class="field">
            <span class="label">Masih berlangsung?</span>
            <label class="choice"><input type="checkbox" name="is_current" value="1" <?= $d['is_current'] ? 'checked' : '' ?>> Ya, sampai sekarang</label>
          </div>
        </div>
        <p class="hint">Urutan di website otomatis: yang mulainya paling baru tampil paling atas.</p>
      </section>

      <section class="panel card box" aria-labelledby="sec-desc">
        <h2 class="panel-title" id="sec-desc"><?= icon('pencil') ?>Keterangan <span class="opt">(opsional)</span></h2>
        <?php bilingual_field($d, $errors, 'description', 'Deskripsi', 'Description', 5000, false, true, 'Tugas, prestasi, atau nilai yang ingin ditonjolkan.'); ?>
      </section>

      <section class="panel card box" aria-labelledby="sec-photo">
        <h2 class="panel-title" id="sec-photo"><?= icon('photo') ?>Foto</h2>
        <?php image_field($errors, 'image', 'Foto kegiatan', UPLOAD_FOLDER_EXPERIENCES, $row['image'] ?? null, $d['remove_image'],
            'Satu foto, misalnya saat kegiatan atau wisuda. JPG, PNG, atau WebP, maksimal 2 MB. Di website tampil seperti ditempel selotip.'); ?>
      </section>

      <section class="panel card box" aria-labelledby="sec-show">
        <h2 class="panel-title" id="sec-show"><?= icon('star') ?>Tampilan</h2>
        <?php choice_field($errors, 'status', 'Status', STATUS_LABELS, $d['status']); ?>
      </section>

      <div class="form-actions form-actions-sticky">
        <a class="btn white" href="/admin/experiences/">Batal</a>
        <button class="btn bg-blue" type="submit"><?= icon('check') ?><?= e($submitLabel) ?></button>
      </div>
    </form>
    <?php
}
