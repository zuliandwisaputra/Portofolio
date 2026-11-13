# Website Portofolio Zulian Dwi Saputra

Website portofolio pribadi dengan halaman publik dua bahasa (Indonesia/Inggris), tema terang/gelap,
dan panel admin untuk mengelola isi tanpa menyentuh kode. Dibangun dengan **PHP Native 8.3+** dan
**MySQL/MariaDB** (PDO + prepared statement), tanpa framework PHP, tanpa Bootstrap/Tailwind,
tanpa library animasi. Rincian kebutuhan ada di [PRD.md](PRD.md).

## Status pengerjaan

| Tahap | Isi | Status |
|---|---|---|
| 1. Fondasi | Struktur folder, database, koneksi PDO, dua bahasa, dua tema, header & footer | Selesai |
| 2. Login admin | Login, logout, proteksi halaman, CSRF, pengunci gagal login, ganti password | Belum |
| 3. CRUD project & skill | | Belum |
| 4. CRUD lainnya & profil | | Belum |
| 5. Halaman publik | | Belum |
| 6. Kontak | | Belum |
| 7. Animasi | | Belum |
| 8. Uji & rilis | | Belum |

## Cara menjalankan di Laragon (Windows)

Kebutuhan: Laragon dengan PHP 8.3 atau lebih baru dan MySQL/MariaDB (bawaan Laragon sudah cukup).

1. **Ambil kode.** Buka Laragon → klik **Terminal**, lalu jalankan:
   ```bash
   cd C:\laragon\www
   git clone https://github.com/zuliandwisaputra/Portofolio.git portofolio
   cd portofolio
   git checkout claude/wizardly-euler-kpxsvn
   ```
   Nama folder harus `portofolio` supaya alamatnya menjadi `http://portofolio.test`.
2. **Buat alamat portofolio.test.** Di Laragon klik **Stop**, lalu **Start All** (atau menu → Apache → Reload).
   Laragon otomatis membuat alamat `portofolio.test` dari nama folder.
3. **Buat database.** Klik **Database** di Laragon (membuka HeidiSQL) → sambungkan →
   klik kanan nama koneksi → **Create new → Database** → nama `portofolio`, collation `utf8mb4_unicode_ci`.
4. **Impor tabel dan data awal.** Pilih database `portofolio` → menu **File → Run SQL file...** →
   pilih `database/portofolio.sql`.
5. **Pengaturan database.** Salin `config/database.example.php` menjadi `config/database.php`.
   Untuk Laragon bawaan (user `root`, password kosong) isinya tidak perlu diubah.
6. Buka **http://portofolio.test** di browser.

## Struktur folder

```
index.php, projects.php, project.php, 404.php   halaman publik
.htaccess                                       alamat rapi (/id/, /en/) dan aturan keamanan
config/      app.php (pengaturan umum), database.example.php (contoh pengaturan database)
includes/    functions.php, lang.php, header.php, footer.php, icons.php
lang/        id.php, en.php (teks tombol dan judul bagian)
assets/      css/, js/, fonts/ (font disimpan sendiri), img/
uploads/     file unggahan dari panel admin (PHP tidak bisa dijalankan di sini)
database/    portofolio.sql (tabel + data awal)
```

## Catatan keamanan

- `config/database.php` tidak ikut diunggah ke GitHub (lihat `.gitignore`).
- Folder `config/`, `includes/`, `lang/`, `database/`, serta file tersembunyi tidak bisa dibuka dari browser.
- Semua query memakai prepared statement, dan semua teks dari database melewati `htmlspecialchars()`.

## Lisensi pihak ketiga

- Font **Public Sans** dan **Lexend Mega**: SIL Open Font License 1.1 (`assets/fonts/OFL-*.txt`).
- Ikon **Tabler Icons**: MIT (`assets/img/LICENSE-tabler-icons.txt`).
