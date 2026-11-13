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
| 4. CRUD lainnya & profil | Pengalaman & pendidikan (+ foto), sertifikat & lomba, menu Profil, unggah CV | Selesai |
| 5. Halaman publik | Beranda lengkap, daftar project + saring kategori, detail project + galeri, SEO dasar, sitemap & robots | Selesai |
| 6. Kontak | Form kontak (CSRF, isian jebakan bot, batas 3 pesan/10 menit), menu Pesan di admin | Selesai |
| 7. Animasi | Tirai lima warna + animasi masuk hero, angka menghitung naik, efek ketik peran, animasi scroll, navbar menandai bagian aktif | Selesai |
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
index.php, projects.php, project.php, 404.php   halaman publik (beranda, daftar project, detail project, 404)
sitemap.php, robots.php                         /sitemap.xml dan /robots.txt untuk mesin pencari (dibuat otomatis)
contact_send.php                                pemroses form kontak di beranda
admin/       login.php, logout.php, index.php (dashboard), password.php,
             projects/, skills/, experiences/, achievements/ (index, create, edit, delete, form),
             messages/ (index = daftar pesan, view = baca, mark = tandai dibaca, delete = hapus),
             profile.php (data diri, foto hero, CV, media sosial, pengaturan)
.htaccess                                       alamat rapi (/id/, /en/) dan aturan keamanan
config/      app.php (pengaturan umum), database.example.php (contoh pengaturan database)
includes/    functions.php, lang.php, header.php, footer.php, icons.php,
             auth.php (login & sesi), csrf.php (token formulir), admin_header.php, admin_footer.php,
             upload.php (unggah & pengecilan gambar), form.php (bantuan formulir & tabel admin),
             project_card.php (kartu project di beranda & daftar project),
             contact.php (aturan form kontak & pembatas pesan)
lang/        id.php, en.php (teks tombol dan judul bagian)
assets/      css/, js/ (main.js, theme.js, anim.js = animasi), fonts/ (font disimpan sendiri), img/
uploads/     file unggahan dari panel admin (PHP tidak bisa dijalankan di sini)
database/    portofolio.sql (tabel + data awal)
```

## Halaman publik

| Alamat | Isi |
|---|---|
| `/id/` dan `/en/` | Beranda: hero, tentang, skill, project unggulan (maks. 6), pengalaman & pendidikan, sertifikat & lomba, kontak |
| `/id/projects` | Semua project yang berstatus Tampil, bisa disaring per kategori (`?category=web`) |
| `/id/project/nama-project` | Detail project: sampul, deskripsi, peran, tahun, teknologi, tautan demo & kode, galeri |
| `/sitemap.xml`, `/robots.txt` | Untuk mesin pencari; Draf dan panel admin tidak ikut |

- Hanya data berstatus **Tampil** yang muncul. Bagian yang belum punya isi (misalnya belum ada sertifikat) disembunyikan
  beserta menunya, dan nomor bagian menyesuaikan.
- Sertifikat: 6 pertama tampil, sisanya muncul lewat tombol *Tampilkan lebih banyak*.
- Statistik hero dihitung otomatis: jumlah project, sertifikat, dan lomba (penghargaan tidak dihitung).
- Tombol *Unduh CV* hanya muncul kalau CV sudah diunggah. Di versi Inggris, kalau CV Inggris kosong dipakai CV Indonesia.
- Tombol WhatsApp hanya muncul kalau nomor diisi di menu Profil. Kosongkan supaya tidak tampil (saat ini kosong).

## Animasi

| Animasi | Kapan | Keterangan |
|---|---|---|
| Animasi masuk | Beranda, sekali per kunjungan | Tirai lima warna tersingkap, lalu badge, nama (huruf demi huruf), peran, tombol, foto, dan angka muncul bergantian. Selesai dalam ±1,4 detik. Angka ringkas menghitung naik dari 0. |
| Efek ketik | Beranda | Peran di hero berganti-ganti seperti diketik. Daftarnya dari menu Profil → Daftar peran. |
| Animasi scroll | Semua halaman publik | Tiap bagian muncul saat digulir ke layar: judul lebih dulu, lalu kartu berurutan. Tiap elemen hanya sekali. |
| Navbar aktif | Beranda | Menu di navbar menandai bagian yang sedang dibaca. |
| Efek hover | Semua halaman | Kartu dan tombol sedikit terangkat saat disentuh kursor. |

- Yang dianimasikan hanya posisi dan transparansi, jadi tata letak tidak bergeser. Durasi tiap gerakan 0,45–0,6 detik.
- **Kurangi gerakan** (pengaturan perangkat) aktif: tidak ada animasi, semua isi langsung tampil, hanya peran pertama.
- **JavaScript mati**: tidak ada yang disembunyikan, semua isi tetap terlihat.
- **Mematikan animasi masuk**: menu Profil → hilangkan centang *Animasi masuk aktif*. Animasi scroll dan efek ketik tetap berjalan.
- Ingin melihat animasi masuk lagi saat mengetes? Tutup tab lalu buka beranda di tab baru (atau jendela penyamaran),
  karena animasi ini hanya diputar sekali per kunjungan.

## Form kontak & menu Pesan

- Form di bagian Kontak beranda: nama, email, pesan (10–3000 karakter). Kesalahan ditampilkan di dekat isian
  dan isian lain tidak hilang. Setelah terkirim muncul ucapan terima kasih; menekan F5 tidak mengirim dua kali.
- Pengaman: token CSRF, isian jebakan untuk bot (bot dianggap terkirim tapi tidak disimpan), dan
  batas **3 pesan per 10 menit** dari alamat IP yang sama.
- Alamat IP tidak disimpan mentah, hanya sidik acaknya (`ip_hash`) yang dibuat dengan kunci dari `config/database.php`.
- Panel admin → **Pesan**: daftar 10 per halaman (yang belum dibaca bertanda *Baru*), baca, balas lewat email,
  tandai sudah/belum dibaca, dan hapus (dengan konfirmasi). Angka pesan belum dibaca tampil di menu dan Dashboard.
- Notifikasi email saat ada pesan masuk belum ada (ditunda ke versi berikutnya sesuai PRD); cek menu Pesan secara berkala.

### Gambar yang perlu kamu isi sendiri (lewat panel admin)

Selama gambar belum diunggah, website menampilkan placeholder bergambar (bukan foto palsu):

| Gambar | Menu admin | Disimpan di folder | Placeholder |
|---|---|---|---|
| Foto hero (foto diri) | Profil → Foto hero | `uploads/profile/` | kotak ungu berisi inisial "ZDS" |
| Sampul project | Project → Ubah → Gambar sampul | `uploads/projects/` | gambar jendela browser warna-warni |
| Galeri project (maks. 8) | Project → Ubah → Galeri | `uploads/projects/` | galeri tidak ditampilkan |
| Foto pengalaman/pendidikan | Pengalaman → Ubah → Foto | `uploads/experiences/` | tidak ada foto |
| Gambar sertifikat/lomba | Sertifikat → Ubah → Gambar | `uploads/achievements/` | kertas dengan ikon |
| CV (PDF) | Profil → CV | `uploads/cv/` | tombol Unduh CV disembunyikan |

Nama file dibuat acak oleh sistem, jadi nama file aslimu bebas.

## Unggah gambar

- Format JPG, PNG, atau WebP, maksimal 2 MB per file. Isi file diperiksa, bukan hanya namanya.
- Gambar disimpan ulang sebagai WebP dengan nama acak: versi besar (lebar maks. 1600 px) dan versi kecil 600 px.
- Galeri project maksimal 8 gambar; teks alt dibuat otomatis dari judul dan bisa diubah.
- Foto hero disimpan 800 px + 400 px. Foto pengalaman dan gambar sertifikat 1600 px + 600 px.
- CV: PDF maksimal 5 MB, satu file per bahasa (isi file harus benar-benar PDF).
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
