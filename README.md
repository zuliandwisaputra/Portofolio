# Website Portofolio Zulian Dwi Saputra

Website portofolio pribadi dengan halaman publik dua bahasa (Indonesia/Inggris), tema terang/gelap,
dan panel admin untuk mengelola isi tanpa menyentuh kode. Dibangun dengan **PHP Native 8.3+** dan
**MySQL/MariaDB** (PDO + prepared statement), tanpa framework PHP, tanpa Bootstrap/Tailwind,
tanpa library animasi. Rincian kebutuhan ada di [PRD.md](PRD.md).

## Status pengerjaan

| Tahap | Isi | Status |
|---|---|---|
| 1. Fondasi | Struktur folder, database, koneksi PDO, dua bahasa, dua tema, header & footer | Selesai |
| 2. Login admin | Login, logout, proteksi halaman, CSRF, pengunci gagal login, ganti password | Selesai |
| 3. CRUD project & skill | Tambah/ubah/hapus project & skill, unggah + pengecilan gambar, galeri, pilihan teknologi | Selesai |
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

## Panel admin

- Alamat: **http://portofolio.test/admin/**
- Username bawaan: `admin`. Password sementaranya diberikan terpisah (tidak ditulis di repo ini).
- **Segera ganti password** lewat menu *Ganti password* setelah login pertama.
- 5 kali salah login dari komputer yang sama → login dikunci 15 menit.
- Sesi berakhir sendiri setelah 2 jam tidak aktif.

**Lupa password?** Buka Terminal Laragon di folder project, lalu jalankan (ganti teksnya dengan password baru):

```bash
php -r "echo password_hash('PasswordBaruKamu', PASSWORD_DEFAULT);"
```

Salin hasilnya (diawali `$2y$`), buka HeidiSQL → tabel `admins` → tempel di kolom `password_hash` → simpan.

> Mengimpor ulang `database/portofolio.sql` akan **menghapus semua isi** dan mengembalikan password ke password sementara.

## Struktur folder

```
index.php, projects.php, project.php, 404.php   halaman publik
admin/       login.php, logout.php, index.php (dashboard), password.php,
             projects/ dan skills/ (index, create, edit, delete, form)
.htaccess                                       alamat rapi (/id/, /en/) dan aturan keamanan
config/      app.php (pengaturan umum), database.example.php (contoh pengaturan database)
includes/    functions.php, lang.php, header.php, footer.php, icons.php,
             auth.php (login & sesi), csrf.php (token formulir), admin_header.php, admin_footer.php,
             upload.php (unggah & pengecilan gambar), form.php (bantuan formulir & tabel admin)
lang/        id.php, en.php (teks tombol dan judul bagian)
assets/      css/, js/, fonts/ (font disimpan sendiri), img/
uploads/     file unggahan dari panel admin (PHP tidak bisa dijalankan di sini)
database/    portofolio.sql (tabel + data awal)
```

## Unggah gambar

- Format JPG, PNG, atau WebP, maksimal 2 MB per file. Isi file diperiksa, bukan hanya namanya.
- Gambar disimpan ulang sebagai WebP dengan nama acak: versi besar (lebar maks. 1600 px) dan versi kecil 600 px.
- Galeri project maksimal 8 gambar; teks alt dibuat otomatis dari judul dan bisa diubah.
- Batas unggah PHP dinaikkan lewat `.htaccess` (Laragon/Apache) dan `.user.ini` (hosting PHP-FPM).
- Kalau muncul pesan "folder tidak bisa ditulis" di hosting, beri izin tulis (755) ke folder `uploads/`.

## Catatan keamanan

- `config/database.php` tidak ikut diunggah ke GitHub (lihat `.gitignore`).
- Folder `config/`, `includes/`, `lang/`, `database/`, serta file tersembunyi tidak bisa dibuka dari browser.
- Semua query memakai prepared statement, dan semua teks dari database melewati `htmlspecialchars()`.
- Password admin disimpan dengan `password_hash()`; semua formulir POST memakai token CSRF.
- Setiap halaman admin memanggil `require_admin()` sehingga tidak bisa dibuka tanpa login.

## Lisensi pihak ketiga

- Font **Public Sans** dan **Lexend Mega**: SIL Open Font License 1.1 (`assets/fonts/OFL-*.txt`).
- Ikon **Tabler Icons**: MIT (`assets/img/LICENSE-tabler-icons.txt`).
