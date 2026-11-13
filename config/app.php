<?php
/**
 * Pengaturan umum website.
 *
 * File ini boleh ikut diunggah ke GitHub karena tidak berisi password.
 * Data rahasia (database) ada di config/database.php yang tidak diunggah.
 */

return [
    // 'local' untuk Laragon (pesan error tampil di layar),
    // 'production' untuk hosting (pesan error hanya dicatat ke log).
    'env' => 'local',

    // Alamat utama website tanpa garis miring di akhir.
    // Kosongkan untuk dideteksi otomatis (cocok untuk Laragon: http://portofolio.test).
    // Di hosting isi lengkap, misalnya 'https://zulian.my.id'.
    'base_url' => '',

    'timezone' => 'Asia/Jakarta',

    // Bahasa yang didukung. Bahasa pertama dipakai kalau bahasa lain tidak dikenali.
    'languages' => ['id', 'en'],

    // Kategori project (dipakai di form admin dan filter halaman daftar project).
    // Kunci kiri disimpan di database, jadi jangan diganti setelah ada project yang memakainya.
    'project_categories' => [
        'web'      => ['id' => 'Web',       'en' => 'Web'],
        'mobile'   => ['id' => 'Mobile',    'en' => 'Mobile'],
        'uiux'     => ['id' => 'UI/UX',     'en' => 'UI/UX'],
        'ai'       => ['id' => 'AI',        'en' => 'AI'],
        'keamanan' => ['id' => 'Keamanan',  'en' => 'Security'],
        'lainnya'  => ['id' => 'Lainnya',   'en' => 'Other'],
    ],

    // Kategori skill (urutan di sini = urutan tampil di beranda).
    'skill_categories' => [
        'frontend' => ['id' => 'Frontend', 'en' => 'Frontend'],
        'backend'  => ['id' => 'Backend',  'en' => 'Backend'],
        'database' => ['id' => 'Database', 'en' => 'Database'],
        'tools'    => ['id' => 'Tools',    'en' => 'Tools'],
    ],
];
