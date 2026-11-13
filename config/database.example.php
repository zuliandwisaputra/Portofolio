<?php
/**
 * CONTOH pengaturan database.
 *
 * Cara pakai:
 * 1. Salin file ini dan beri nama database.php (di folder yang sama).
 * 2. Sesuaikan isinya. Untuk Laragon, pengaturan bawaan di bawah biasanya sudah benar.
 *
 * File database.php TIDAK ikut diunggah ke GitHub (sudah diatur di .gitignore),
 * jadi password database kamu aman.
 */

return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'name'     => 'portofolio',   // nama database yang kamu buat di HeidiSQL
    'user'     => 'root',         // Laragon bawaan: root
    'password' => '',             // Laragon bawaan: kosong
    'charset'  => 'utf8mb4',
];
