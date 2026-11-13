<?php
/**
 * Bagian bersama untuk tambah & ubah sertifikat/lomba/penghargaan.
 * File ini tidak bisa dibuka langsung; dipanggil dari create.php dan edit.php.
 */

if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

const ACHIEVEMENT_TYPES = ['sertifikat' => 'Sertifikat', 'lomba' => 'Lomba', 'penghargaan' => 'Penghargaan'];
const UPLOAD_FOLDER_ACHIEVEMENTS = 'achievements';

function achievement_defaults(): array
{
    return [
        'type' => 'sertifikat',
        'title_ind' => '', 'title_eng' => '',
        'issuer' => '', 'issued' => '',
        'result_ind' => '', 'result_eng' => '',
        'credential_url' => '',
        'status' => 'published',
        'remove_image' => false,
    ];
}

function achievement_from_row(array $row): array
{
    return [
        'type' => $row['type'],
        'title_ind' => $row['title_ind'], 'title_eng' => (string) $row['title_eng'],
        'issuer' => $row['issuer'], 'issued' => month_value($row['issued_date']),
        'result_ind' => (string) $row['result_ind'], 'result_eng' => (string) $row['result_eng'],
        'credential_url' => (string) $row['credential_url'],
        'status' => $row['status'],
        'remove_image' => false,
    ];
}

function achievement_input(): array
{
    $text = fn (string $key): string => trim((string) ($_POST[$key] ?? ''));
    return [
        'type' => $text('type'),
        'title_ind' => $text('title_ind'), 'title_eng' => $text('title_eng'),
        'issuer' => $text('issuer'), 'issued' => $text('issued'),
        'result_ind' => $text('result_ind'), 'result_eng' => $text('result_eng'),
        'credential_url' => $text('credential_url'),
        'status' => $text('status'),
        'remove_image' => !empty($_POST['remove_image']),
    ];
}

function achievement_validate(array $d, ?array $image): array
{
    $errors = [];
    if (!isset(ACHIEVEMENT_TYPES[$d['type']])) {
        $errors['type'] = 'Pilih jenis: sertifikat, lomba, atau penghargaan.';
    }
    $rules = [
        'title_ind'  => ['judul (Indonesia)', 150, true],
        'title_eng'  => ['Judul (Inggris)', 150, false],
        'issuer'     => ['penyelenggara', 150, true],
        'result_ind' => ['Hasil (Indonesia)', 100, false],
        'result_eng' => ['Hasil (Inggris)', 100, false],
    ];
    foreach ($rules as $field => [$label, $max, $required]) {
        if ($msg = check_text($d[$field], $label, $max, $required)) {
            $errors[$field] = $msg;
        }
    }
    if ($d['issued'] === '') {
        $errors['issued'] = 'Isi bulan & tahun diterbitkan (contoh: 2025-05).';
    } elseif (parse_month($d['issued']) === null) {
        $errors['issued'] = 'Tanggal tidak valid. Gunakan format tahun-bulan, contoh: 2025-05.';
    }
    if ($msg = check_url($d['credential_url'], 'Tautan bukti')) {
        $errors['credential_url'] = $msg;
    }
    if (!isset(STATUS_LABELS[$d['status']])) {
        $errors['status'] = 'Pilih status Draf atau Terbit.';
    }
    if ($image !== null && ($msg = image_upload_error($image))) {
        $errors['image'] = $msg . ' Pilih ulang gambar.';
    }
    return $errors;
}

function achievement_save(array $d, ?array $row, ?array $image): int
{
    $folder = UPLOAD_FOLDER_ACHIEVEMENTS;
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
        $d['type'], $d['title_ind'], $d['title_eng'] ?: null, $d['issuer'], parse_month($d['issued']),
        $d['result_ind'] ?: null, $d['result_eng'] ?: null, $current, $d['credential_url'] ?: null, $d['status'],
    ];

    try {
        if ($row) {
            db()->prepare(
                'UPDATE achievements SET type = ?, title_ind = ?, title_eng = ?, issuer = ?, issued_date = ?,
                        result_ind = ?, result_eng = ?, image = ?, credential_url = ?, status = ?
                  WHERE id = ?'
            )->execute([...$values, $row['id']]);
            $id = (int) $row['id'];
        } else {
            db()->prepare(
                'INSERT INTO achievements (type, title_ind, title_eng, issuer, issued_date, result_ind, result_eng,
                        image, credential_url, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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

function achievement_form(array $d, array $errors, string $action, string $submitLabel, ?array $row): void
{
    ?>
    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form project-form" novalidate>
      <?= csrf_field() ?>

      <section class="panel card box" aria-labelledby="sec-main">
        <h2 class="panel-title" id="sec-main"><?= icon('award') ?>Data utama</h2>
        <?php choice_field($errors, 'type', 'Jenis', ACHIEVEMENT_TYPES, $d['type']); ?>
        <?php bilingual_field($d, $errors, 'title', 'Judul', 'Title', 150, true, false, 'Contoh: Lomba Desain Web Tingkat Kota.'); ?>
        <div class="grid-2">
          <div class="field">
            <label for="issuer">Penyelenggara <span class="req" aria-hidden="true">*</span></label>
            <input class="input" id="issuer" name="issuer" type="text" maxlength="150" required
                   value="<?= e($d['issuer']) ?>"<?= field_attrs($errors, 'issuer', 'issuer-hint') ?>>
            <p class="hint" id="issuer-hint">Lembaga atau panitia yang mengeluarkan. Tidak perlu diterjemahkan.</p>
            <?= field_error($errors, 'issuer') ?>
          </div>
          <div class="field">
            <label for="issued">Bulan & tahun <span class="req" aria-hidden="true">*</span></label>
            <input class="input" id="issued" name="issued" type="month" placeholder="2025-05" required
                   value="<?= e($d['issued']) ?>"<?= field_attrs($errors, 'issued', 'issued-hint') ?>>
            <p class="hint" id="issued-hint">Urutan di website otomatis: yang terbaru di atas.</p>
            <?= field_error($errors, 'issued') ?>
          </div>
        </div>
        <?php bilingual_field($d, $errors, 'result', 'Hasil', 'Result', 100, false, false, 'Contoh: Juara 2, Lulus, Finalis. Boleh kosong.'); ?>
        <div class="field">
          <label for="credential_url">Tautan bukti <span class="opt">(opsional)</span></label>
          <input class="input" id="credential_url" name="credential_url" type="url" inputmode="url" maxlength="255" placeholder="https://"
                 value="<?= e($d['credential_url']) ?>"<?= field_attrs($errors, 'credential_url', 'cred-hint') ?>>
          <p class="hint" id="cred-hint">Alamat halaman verifikasi sertifikat atau pengumuman lomba.</p>
          <?= field_error($errors, 'credential_url') ?>
        </div>
      </section>

      <section class="panel card box" aria-labelledby="sec-photo">
        <h2 class="panel-title" id="sec-photo"><?= icon('photo') ?>Gambar</h2>
        <?php image_field($errors, 'image', 'Gambar sertifikat', UPLOAD_FOLDER_ACHIEVEMENTS, $row['image'] ?? null, $d['remove_image'],
            'Foto atau pindaian sertifikat/piagam. JPG, PNG, atau WebP, maksimal 2 MB. Ukuran ideal 4:3.'); ?>
      </section>

      <section class="panel card box" aria-labelledby="sec-show">
        <h2 class="panel-title" id="sec-show"><?= icon('star') ?>Tampilan</h2>
        <?php choice_field($errors, 'status', 'Status', STATUS_LABELS, $d['status']); ?>
      </section>

      <div class="form-actions form-actions-sticky">
        <a class="btn white" href="/admin/achievements/">Batal</a>
        <button class="btn bg-blue" type="submit"><?= icon('check') ?><?= e($submitLabel) ?></button>
      </div>
    </form>
    <?php
}
