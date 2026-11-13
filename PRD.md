# PRD Website Portofolio Zulian

Nov 13, 2026 · @zulian

## Ringkasan

Website portofolio pribadi Zulian terdiri dari halaman publik untuk
memajang karya dan panel admin untuk menambah, mengubah, dan menghapus
isinya tanpa menyentuh kode. Website dibangun dengan PHP Native dan
MySQL, dengan foto diri di hero, animasi masuk, dan animasi scroll.

Keputusan yang sudah diambil:

| Hal                               | Keputusan                                                                                                       |
|-----------------------------------|-----------------------------------------------------------------------------------------------------------------|
| Backend                           | PHP Native + MySQL, tanpa framework                                                                             |
| Konten yang dikelola dari admin   | Project, skill, pengalaman & pendidikan, sertifikat & lomba                                                     |
| Hero dan animasi                  | Foto diri di hero, animasi masuk saat website dibuka, dan animasi scroll; tanpa model 3D                        |
| Bahasa                            | Indonesia dan Inggris, dengan tombol ganti bahasa                                                               |
| Tampilan                          | Tema terang dan gelap, dengan tombol ganti tema                                                                 |
| Pembaca sasaran                   | Perekrut, calon klien freelance, penilai tugas, dan pengunjung umum                                             |
| Syarat utama                      | Tetap ringan saat dibuka orang lain, termasuk di HP                                                             |
| Nama yang ditampilkan             | Zulian Dwi Saputra                                                                                              |
| Media sosial yang sudah diketahui | Instagram @juliee.an; LinkedIn linkedin.com/in/zulian-dwi-saputra-08323544a; GitHub github.com/zuliandwisaputra |

## Tujuan dan ukuran keberhasilan

Website berhasil kalau pengunjung cepat menemukan karya terbaik Zulian,
dan Zulian bisa memperbarui isinya sendiri dalam hitungan menit. Angka
target di bawah adalah usulan awal dan boleh disesuaikan.

| Tujuan                                               | Ukuran keberhasilan                                                                                                                                                                        |
|------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Perekrut cepat menilai kemampuan                     | Project unggulan dan tombol unduh CV bisa dicapai dalam 1 klik dari beranda                                                                                                                |
| Calon klien mudah menghubungi                        | Form kontak dan tautan WhatsApp bisa dicapai dalam 1 klik dari halaman mana pun                                                                                                            |
| Isi mudah diperbarui                                 | Menambah satu project lengkap (teks dua bahasa + gambar) selesai dalam 5 menit atau kurang, tanpa mengubah kode                                                                            |
| Layak dinilai sebagai tugas                          | CRUD lengkap untuk 4 jenis konten, login aman, dan semua butir di bagian Kriteria selesai terpenuhi                                                                                        |
| Kesan pertama yang kuat tanpa mengorbankan kecepatan | Teks dan foto hero tampil dalam 2,5 detik atau kurang di HP kelas menengah dengan jaringan 4G, dan animasi masuk selesai dalam 1,5 detik; skor Lighthouse Performance mobile 85 atau lebih |
| Nyaman untuk semua pembaca                           | Semua halaman publik tersedia dalam dua bahasa dan dua tema; skor Lighthouse Accessibility 90 atau lebih                                                                                   |

## Pengguna

Ada lima jenis pengguna: empat jenis pengunjung yang hanya membaca, dan
satu admin yang mengelola isi.

| Pengguna                   | Yang dicari                                          | Yang disediakan website                                                              |
|----------------------------|------------------------------------------------------|--------------------------------------------------------------------------------------|
| Perekrut / HRD             | Bukti kemampuan, riwayat, dan CV dalam waktu singkat | Project unggulan, daftar skill, timeline pengalaman, tombol unduh CV                 |
| Calon klien freelance      | Contoh hasil kerja dan cara menghubungi              | Detail project dengan gambar dan tautan demo, form kontak, tautan WhatsApp dan email |
| Penilai tugas (guru/dosen) | Kelengkapan fitur dan kerapian kode                  | CRUD lengkap, login aman, struktur folder rapi, dokumentasi cara menjalankan         |
| Pengunjung umum            | Gambaran siapa Zulian dan apa yang ia kerjakan       | Bagian Tentang, sertifikat & lomba, tautan media sosial                              |
| Admin (Zulian)             | Memperbarui isi tanpa membuka kode                   | Panel admin dengan form tambah, ubah, hapus, dan unggah gambar                       |

Hanya ada satu akun admin. Pengunjung tidak perlu mendaftar atau login.

## Ruang lingkup

Versi pertama berisi semua yang dibutuhkan untuk memajang dan mengelola
portofolio; fitur di luar itu sengaja ditunda supaya website cepat
selesai.

**Masuk versi pertama**

- Halaman publik: beranda, daftar project, detail project, dan halaman
  404

- Hero dengan foto diri, animasi masuk saat website dibuka, dan animasi
  scroll di tiap bagian

- Panel admin dengan login untuk satu akun

- CRUD untuk project, skill, pengalaman & pendidikan, sertifikat & lomba

- Pengaturan profil: nama, peran, teks Tentang, foto hero, file CV,
  tautan media sosial

- Form kontak, dengan pesan masuk yang bisa dibaca di panel admin

- Dua bahasa (Indonesia dan Inggris) dan dua tema (terang dan gelap)

- Tampilan responsif untuk HP, tablet, dan desktop

**Ditunda ke versi berikutnya**

- Blog atau artikel

- Lebih dari satu akun admin dan pengaturan hak akses

- Notifikasi email saat ada pesan masuk

- Model dan efek 3D dengan Three.js

- Animasi scroll yang rumit, seperti bagian yang menempel atau parallax
  berlapis

- Editor teks kaya (bold, daftar, gambar di dalam deskripsi)

- Mengurutkan konten dengan seret-dan-lepas

- Halaman layanan dan harga, testimoni klien

- Statistik pengunjung di dalam panel admin

## Halaman publik

Website publik terdiri dari tiga halaman utama dan satu halaman 404.
Beranda memuat semua bagian penting dalam satu halaman panjang.

| Halaman        | Alamat                   | Isi                                                                                         |
|----------------|--------------------------|---------------------------------------------------------------------------------------------|
| Beranda        | /id/ atau /en/           | Hero, Tentang, Skill, Project unggulan, Pengalaman & pendidikan, Sertifikat & lomba, Kontak |
| Daftar project | /id/projects             | Semua project yang sudah terbit, dengan filter kategori                                     |
| Detail project | /id/project/nama-project | Galeri gambar, deskripsi, peran, tahun, teknologi, tautan demo dan repo                     |
| 404            | Alamat yang tidak ada    | Pesan singkat dan tombol kembali ke beranda                                                 |

**Urutan bagian di beranda**

1.  Hero: nama lengkap Zulian Dwi Saputra, badge status, peran yang
    berganti, satu kalimat tagline, tombol "Lihat project" dan "Unduh
    CV", ikon media sosial, angka ringkas, serta foto diri.

2.  Tentang: dua sampai tiga paragraf perkenalan dan tautan media
    sosial.

3.  Skill: dikelompokkan per kategori, misalnya Frontend, Backend,
    Database, dan Tools.

4.  Project unggulan: paling banyak 6 kartu project bertanda unggulan,
    dengan tombol "Lihat semua project".

5.  Pengalaman & pendidikan: timeline, yang terbaru di atas.

6.  Sertifikat & lomba: kartu, yang terbaru di atas. Enam pertama
    langsung tampil, sisanya lewat tombol "Tampilkan lebih banyak".

7.  Kontak: form (nama, email, pesan) dan tautan email, WhatsApp,
    GitHub, LinkedIn, Instagram.

**Elemen di semua halaman**

- Navbar menempel di atas, berisi tautan ke tiap bagian, tombol ganti
  bahasa, dan tombol ganti tema.

- Footer berisi nama, tahun, dan tautan media sosial.

- Bagian yang belum punya isi di database tidak ditampilkan, jadi tidak
  ada judul bagian yang kosong.

**Aturan bahasa**

- Kode bahasa ada di alamat (/id/ dan /en/), supaya tiap bahasa bisa
  dibagikan dan dibaca mesin pencari.

- Kunjungan pertama tanpa kode bahasa memakai bahasa browser: Indonesia
  kalau browsernya berbahasa Indonesia, selain itu Inggris.

- Pilihan pengunjung disimpan di cookie dan dipakai lagi pada kunjungan
  berikutnya.

- Kalau teks Inggris suatu konten belum diisi, yang tampil teks
  Indonesianya.

**Aturan tema**

- Kunjungan pertama mengikuti pengaturan terang/gelap perangkat.

- Pilihan pengunjung disimpan di browser dan langsung diterapkan saat
  halaman dimuat, tanpa kedipan warna.

- Kedua tema memakai kumpulan variabel warna yang sama.

## Panel admin

Panel admin berada di /admin, hanya bisa dibuka setelah login, dan punya
satu menu untuk tiap jenis konten.

**Login dan akun**

- Login memakai username dan password. Akun admin dibuat lewat data awal
  database, jadi tidak ada halaman daftar.

- Setelah 5 kali gagal login dari alamat IP yang sama, login dikunci
  selama 15 menit.

- Admin bisa mengganti password dari panel, dengan memasukkan password
  lama lebih dulu.

- Sesi berakhir sendiri setelah 2 jam tidak aktif. Tombol Keluar ada di
  semua halaman admin.

**Menu dan fitur**

| Menu                    | Yang bisa dilakukan                                                 | Isian utama                                                                                                                                                    |
|-------------------------|---------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Dashboard               | Melihat jumlah tiap jenis konten dan jumlah pesan yang belum dibaca | Hanya tampilan                                                                                                                                                 |
| Project                 | Tambah, ubah, hapus, tandai unggulan                                | Judul, ringkasan, deskripsi, peran, kategori, tahun, teknologi yang dipakai, gambar sampul, galeri, tautan website (opsional), tautan repo GitHub (opsional)   |
| Skill                   | Tambah, ubah, hapus                                                 | Nama, kategori, tingkat (opsional)                                                                                                                             |
| Pengalaman & pendidikan | Tambah, ubah, hapus                                                 | Jenis (kerja, organisasi, pendidikan), posisi atau jurusan, nama tempat, tanggal mulai dan selesai, deskripsi                                                  |
| Sertifikat & lomba      | Tambah, ubah, hapus                                                 | Jenis (sertifikat, lomba, penghargaan), judul, penyelenggara, tanggal, hasil, gambar, tautan bukti                                                             |
| Pesan                   | Membaca, menandai sudah dibaca, menghapus                           | Hanya tampilan                                                                                                                                                 |
| Profil                  | Mengubah data diri dan pengaturan website                           | Nama, daftar peran, tagline, badge status, teks Tentang, foto hero, file CV, tautan media sosial, deskripsi untuk mesin pencari, animasi masuk aktif atau mati |

**Aturan di semua form**

- Isian teks yang tampil ke pengunjung punya dua kolom: Indonesia
  (wajib) dan Inggris (opsional).

- Setiap konten berstatus Draf atau Terbit. Hanya yang Terbit yang
  tampil di halaman publik.

- Project dan skill diurutkan dengan angka urutan; angka kecil tampil
  lebih dulu. Pengalaman dan sertifikat diurutkan otomatis dari tanggal
  terbaru.

- Teknologi pada project dipilih dari daftar skill, sehingga tidak perlu
  diketik ulang.

- Menghapus selalu meminta konfirmasi. Gambar milik konten yang dihapus
  ikut dihapus dari server.

- Validasi dilakukan di server. Pesan kesalahan muncul di dekat isian
  yang salah, dan isian lain tidak hilang.

- Setiap simpan atau hapus diikuti pesan berhasil atau gagal.

- Daftar konten tampil sebagai tabel berisi judul, status, dan tanggal
  terakhir diubah, 10 baris per halaman.

**Aturan unggah file**

| Jenis file                                     | Format         | Ukuran maksimum      | Catatan                                                                   |
|------------------------------------------------|----------------|----------------------|---------------------------------------------------------------------------|
| Gambar sampul, galeri, sertifikat, foto profil | JPG, PNG, WebP | 2 MB per file        | Diperkecil otomatis ke lebar 1600 px, plus versi kecil 600 px untuk kartu |
| Galeri project                                 | JPG, PNG, WebP | 8 gambar per project | Tiap gambar punya teks alt dua bahasa                                     |
| CV                                             | PDF            | 5 MB per file        | Satu file untuk tiap bahasa                                               |

## Hero dan animasi

Hero menampilkan foto Zulian di samping nama dan perannya, tanpa model
3D. Kesan pertama dibangun lewat animasi masuk saat website dibuka dan
animasi scroll di tiap bagian.

**Isi hero**

- Badge status di atas nama, misalnya "Terbuka untuk magang dan
  freelance". Teksnya diisi dari menu Profil dan bisa dimatikan.

- Nama lengkap: Zulian Dwi Saputra.

- Peran yang berganti-ganti dengan efek ketik. Daftar perannya diisi
  dari menu Profil.

- Satu kalimat tagline.

- Tombol "Lihat project" dan "Unduh CV", diikuti ikon LinkedIn,
  Instagram, dan GitHub.

- Angka ringkas: jumlah project, sertifikat, dan lomba yang berstatus
  Terbit, dihitung otomatis dari database.

- Foto diri dengan bentuk dekoratif dari CSS di belakangnya. Fotonya
  diunggah dari menu Profil.

- Di desktop teks berada di kiri dan foto di kanan. Di HP foto berada di
  atas teks.

**Animasi masuk**

- Berjalan saat website pertama kali dibuka: badge, nama, peran, tombol,
  dan foto muncul bergantian, lalu angka ringkas menghitung naik dari
  nol.

- Seluruh urutan selesai dalam 1,5 detik atau kurang. Elemen pertama
  langsung bergerak, tanpa layar tunggu.

- Hanya diputar sekali per kunjungan. Berpindah halaman lalu kembali ke
  beranda tidak mengulangnya.

- Bisa dimatikan dari menu Profil.

**Animasi scroll**

- Tiap bagian muncul dengan gerakan halus saat masuk ke layar: judul
  lebih dulu, lalu isinya.

- Kartu project, skill, dan sertifikat muncul berurutan dengan jeda
  singkat.

- Kartu project sedikit terangkat saat disentuh kursor.

- Navbar menandai bagian yang sedang dibaca.

- Tiap elemen hanya dianimasikan sekali, tidak berulang saat halaman
  digulir naik-turun.

**Batas supaya tetap ringan**

| Hal                 | Batas                                                                    |
|---------------------|--------------------------------------------------------------------------|
| Cara membuat        | CSS dan JavaScript biasa, tanpa Three.js dan tanpa library animasi       |
| Yang dianimasikan   | Hanya posisi dan transparansi, supaya tata letak tidak bergeser          |
| Durasi tiap gerakan | 0,3 sampai 0,6 detik                                                     |
| Foto hero           | Format WebP, lebar 800 px, target 200 KB atau kurang, dimuat paling awal |
| Skrip animasi       | 10 KB atau kurang                                                        |

**Kapan animasi tidak dijalankan**

- Pengunjung menyalakan "kurangi gerakan" di perangkatnya. Semua isi
  langsung tampil tanpa gerakan, dan hanya peran pertama yang
  ditampilkan.

- JavaScript gagal dimuat. Semua isi tetap terlihat, tidak ada yang
  tersembunyi menunggu animasi.

- Admin mematikan animasi masuk dari menu Profil. Animasi scroll tetap
  berjalan.

Gaya animasi masuk dan foto yang dipakai belum dipilih. Keduanya
tercatat di bagian Risiko dan pertanyaan terbuka.

## Rancangan database

Database MySQL berisi 10 tabel. Hanya projects yang punya relasi: satu
project punya banyak gambar galeri, dan terhubung ke banyak skill
sebagai teknologi yang dipakai.

| Tabel          | Fungsi                                             | Kolom utama                                                                                                                                                                                                                                                               |
|----------------|----------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| admins         | Akun admin                                         | id, username, password_hash, last_login_at                                                                                                                                                                                                                                |
| profile        | Data diri dan pengaturan website, hanya satu baris | full_name, roles_ind, roles_eng, tagline_ind, tagline_eng, status_text_ind, status_text_eng, status_enabled, about_ind, about_eng, photo, cv_ind, cv_eng, email, whatsapp, github_url, linkedin_url, instagram_url, meta_desc_ind, meta_desc_eng, intro_animation_enabled |
| projects       | Project                                            | id, slug, title_ind, title_eng, summary_ind, summary_eng, description_ind, description_eng, my_role_ind, my_role_eng, category, year, cover_image, demo_url, repo_url, is_featured, status, sort_order                                                                    |
| project_images | Galeri milik project                               | id, project_id, file_name, alt_ind, alt_eng, sort_order                                                                                                                                                                                                                   |
| skills         | Skill, sekaligus daftar teknologi untuk project    | id, name, category, level, status, sort_order                                                                                                                                                                                                                             |
| project_skill  | Penghubung project dan skill                       | project_id, skill_id                                                                                                                                                                                                                                                      |
| experiences    | Pengalaman & pendidikan                            | id, type, title_ind, title_eng, organization, location, start_date, end_date, is_current, description_ind, description_eng, status                                                                                                                                        |
| achievements   | Sertifikat & lomba                                 | id, type, title_ind, title_eng, issuer, issued_date, result_ind, result_eng, image, credential_url, status                                                                                                                                                                |
| messages       | Pesan dari form kontak                             | id, name, email, body, ip_hash, is_read, created_at                                                                                                                                                                                                                       |
| login_attempts | Catatan gagal login untuk pengunci                 | id, ip_address, attempted_at                                                                                                                                                                                                                                              |

**Aturan**

- Akhiran \_ind dan \_eng menandai versi bahasa. Akhiran \_id sengaja
  tidak dipakai untuk bahasa, karena biasanya berarti kunci relasi.

- Kolom status berisi draft atau published.

- Tabel konten punya created_at dan updated_at.

- slug unik, dibuat otomatis dari judul Indonesia, dan dipakai di alamat
  detail project.

- Menghapus project ikut menghapus baris galeri dan penghubungnya.
  Menghapus skill hanya menghapus penghubungnya, project tetap ada.

- Semua tabel memakai mesin InnoDB dan karakter utf8mb4.

- Daftar kategori project dan kategori skill disimpan di file
  konfigurasi, bukan tabel tersendiri.

## Teknologi dan struktur folder

PHP menyusun halaman dari data MySQL, sedangkan animasi berjalan di
browser pengunjung dengan CSS dan JavaScript biasa. Panel admin dan
halaman publik memakai database dan folder unggahan yang sama.

*(Diagram arsitektur "2 pintu masuk, 1 sumber data" ada di file Word asli: halaman publik dan panel admin sama-sama memakai database MySQL dan folder uploads/ yang sama.)*

Pengunjung hanya membaca lewat halaman publik. Perubahan isi hanya bisa
masuk lewat panel admin, yang butuh login.

| Lapisan           | Pilihan                                                                                    |
|-------------------|--------------------------------------------------------------------------------------------|
| Backend           | PHP Native 8.3 atau lebih baru, tanpa framework                                            |
| Database          | MySQL atau MariaDB, diakses lewat PDO dengan prepared statement                            |
| Tampilan          | HTML, CSS dengan variabel warna, dan JavaScript biasa, tanpa Bootstrap atau Tailwind       |
| Animasi           | CSS dan JavaScript biasa, tanpa Three.js dan tanpa library animasi                         |
| Pengolahan gambar | Ekstensi GD bawaan PHP                                                                     |
| Alamat rapi       | Aturan .htaccess yang mengubah /id/projects menjadi projects.php?lang=id                   |
| Server lokal      | Laragon di Windows                                                                         |
| Hosting           | Shared hosting dengan PHP, MySQL, HTTPS, dan dukungan .htaccess; penyedianya belum dipilih |
| Penyimpanan kode  | Git dan GitHub                                                                             |

**Struktur folder**

portofolio/  
├── index.php beranda  
├── projects.php daftar project  
├── project.php detail project  
├── contact_send.php proses form kontak  
├── 404.php  
├── .htaccess alamat rapi dan aturan keamanan  
├── admin/  
│ ├── login.php, logout.php, index.php  
│ ├── projects/ index, create, edit, delete  
│ ├── skills/  
│ ├── experiences/  
│ ├── achievements/  
│ ├── messages/  
│ ├── profile.php  
│ └── password.php  
├── config/ database.php, app.php  
├── includes/ header, footer, auth, csrf, lang, upload, functions  
├── lang/ id.php, en.php (teks tombol dan judul bagian)  
├── assets/  
│ ├── css/ style.css, admin.css  
│ ├── js/ main.js, theme.js, animations.js  
│ └── img/ ikon dan gambar tetap  
├── uploads/ projects/, achievements/, profile/, cv/  
└── database/ portofolio.sql

Teks tetap seperti label tombol dan judul bagian disimpan di folder
lang/. Teks konten seperti judul project disimpan di database dalam dua
kolom bahasa.

## Kebutuhan non-fungsional

Tiga syarat yang paling menentukan: halaman cepat dibuka di HP, panel
admin aman, dan semua isi terbaca di kedua bahasa dan kedua tema.

**Performa**

| Ukuran                                       | Target                                                        |
|----------------------------------------------|---------------------------------------------------------------|
| Waktu sampai teks dan foto hero tampil (LCP) | 2,5 detik atau kurang di HP kelas menengah dengan jaringan 4G |
| Unduhan beranda saat pertama dibuka          | 500 KB atau kurang                                            |
| Durasi animasi masuk                         | 1,5 detik atau kurang                                         |
| Pergeseran tata letak (CLS)                  | Di bawah 0,1                                                  |
| Skor Lighthouse mobile                       | Performance 85, Accessibility 90, SEO 90, atau lebih          |

- Gambar di bawah layar pertama dimuat belakangan, dan semua gambar
  punya lebar dan tinggi yang ditetapkan.

- Halaman publik memakai satu file CSS dan satu file JavaScript utama,
  dikecilkan saat rilis.

- Paling banyak dua keluarga font, dengan teks tetap tampil selama font
  dimuat.

**Keamanan**

- Semua query memakai PDO dengan prepared statement.

- Password disimpan dengan password_hash() dan dicek dengan
  password_verify().

- ID sesi dibuat ulang setelah login. Cookie sesi diberi tanda HttpOnly,
  SameSite, dan Secure di hosting.

- Semua form POST, baik di admin maupun form kontak, memakai token CSRF.

- Semua teks dari database dilewatkan htmlspecialchars() sebelum
  ditampilkan.

- Setiap halaman dan setiap aksi admin memeriksa login, bukan hanya
  menyembunyikan menu.

- File unggahan diperiksa isi tipenya, diberi nama acak, dan folder
  uploads/ tidak boleh menjalankan PHP.

- Tautan yang diisi admin hanya boleh diawali http:// atau https://.

- Form kontak punya isian jebakan untuk bot dan batas 3 pesan per 10
  menit per alamat IP.

- Di hosting, pesan error PHP tidak tampil ke pengunjung dan hanya
  dicatat ke log.

- File konfigurasi database tidak ikut diunggah ke GitHub.

- Website hanya dilayani lewat HTTPS.

**Responsif**

- Tampilan didukung mulai lebar layar 360 px, dengan perubahan tata
  letak di 768 px dan 1024 px.

- Panel admin dirancang untuk desktop, tetapi tetap bisa dipakai di HP
  dengan tabel yang bisa digulir.

- Browser yang didukung: dua versi terbaru Chrome, Edge, Firefox, dan
  Safari.

**SEO**

- Judul dan deskripsi berbeda untuk tiap halaman dan tiap bahasa.

- Tag hreflang menghubungkan versi Indonesia dan Inggris, dan atribut
  lang halaman sesuai bahasanya.

- Beranda dan tiap project punya gambar pratinjau untuk dibagikan di
  media sosial.

- sitemap.xml memuat semua halaman terbit dalam kedua bahasa.

**Aksesibilitas**

- Kontras teks memenuhi WCAG AA di kedua tema.

- Semua fungsi bisa dipakai dengan keyboard, dan penanda fokus terlihat
  jelas.

- Semua gambar punya teks alt.

- Animasi berhenti kalau pengunjung memilih "kurangi gerakan".

## Tahapan pengerjaan

Pengerjaan dibagi menjadi 8 tahap berurutan. Satu tahap diuji sampai
berjalan dulu, baru lanjut ke tahap berikutnya. Animasi dikerjakan
setelah semua halaman jadi, supaya isi dan tata letaknya sudah tetap
lebih dulu.

| Tahap                       | Yang dikerjakan                                                                                         | Hasil yang bisa dicek                                                         |
|-----------------------------|---------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------|
| 1\. Fondasi                 | Struktur folder, file SQL, koneksi PDO, fungsi bahasa, variabel warna tema, kerangka header dan footer  | Database terimpor dan halaman kosong tampil dalam dua bahasa dan dua tema     |
| 2\. Login admin             | Login, logout, proteksi halaman, token CSRF, pengunci gagal login, ganti password                       | Halaman admin tidak bisa dibuka tanpa login                                   |
| 3\. CRUD project dan skill  | Form tambah, ubah, hapus; unggah dan pengecilan gambar; galeri; pilihan teknologi                       | Project dan skill bisa dikelola penuh dari admin                              |
| 4\. CRUD lainnya dan profil | Pengalaman & pendidikan, sertifikat & lomba, menu Profil, unggah CV                                     | Semua jenis konten bisa dikelola dari admin                                   |
| 5\. Halaman publik          | Beranda dengan semua bagian, daftar dan detail project, 404, tombol bahasa dan tema, tampilan responsif | Isi dari admin tampil di halaman publik, masih tanpa animasi                  |
| 6\. Kontak                  | Form kontak, validasi, pembatas pesan, menu Pesan di admin                                              | Pesan dari form muncul di admin                                               |
| 7\. Animasi                 | Animasi masuk di hero, animasi scroll tiap bagian, efek hover kartu, aturan kurangi gerakan             | Animasi lancar di HP dan desktop, dan isi tetap tampil saat animasi dimatikan |
| 8\. Uji dan rilis           | Uji di HP dan desktop, Lighthouse, SEO, perbaikan, unggah ke hosting, sambungkan domain                 | Semua butir di Kriteria selesai terpenuhi dan website bisa dibuka publik      |

## Kriteria selesai

Website siap rilis kalau semua butir di bawah sudah dicentang.

**Halaman publik**

- [ ] Beranda menampilkan ketujuh bagian dengan isi dari database

- [ ] Daftar project bisa difilter per kategori, dan detail project
  terbuka lewat alamat slug

- [ ] Konten berstatus Draf tidak tampil di halaman publik mana pun

- [ ] Tombol bahasa mengganti semua teks, dan pilihan tetap setelah
  halaman dimuat ulang

- [ ] Tombol tema mengganti warna tanpa kedipan, dan pilihan tetap
  setelah halaman dimuat ulang

- [ ] Tombol unduh CV memberi file sesuai bahasa yang aktif

- [ ] Alamat yang salah menampilkan halaman 404

**Panel admin**

- [ ] Semua halaman dan aksi admin menolak akses tanpa login

- [ ] Tambah, ubah, dan hapus berjalan untuk project, skill, pengalaman
  & pendidikan, serta sertifikat & lomba

- [ ] Unggahan dengan format atau ukuran yang salah ditolak dengan pesan
  yang jelas

- [ ] Menghapus konten ikut menghapus gambarnya dari server

- [ ] Login terkunci setelah 5 kali gagal

- [ ] Pesan dari form kontak muncul di menu Pesan

**Hero dan animasi**

- [ ] Hero menampilkan nama Zulian Dwi Saputra, badge status, peran
  berganti, foto, dua tombol, ikon media sosial, dan angka ringkas yang
  sesuai isi database

- [ ] Animasi masuk selesai dalam 1,5 detik dan hanya diputar sekali per
  kunjungan

- [ ] Tiap bagian muncul dengan animasi saat digulir ke layar, tanpa
  menggeser tata letak

- [ ] Dengan "kurangi gerakan" aktif atau JavaScript mati, semua isi
  tetap tampil

- [ ] Animasi masuk bisa dimatikan dari menu Profil

**Mutu**

- [ ] Skor Lighthouse mobile: Performance 85, Accessibility 90, SEO 90,
  atau lebih

- [ ] Tampilan rapi di lebar 360 px, 768 px, dan 1280 px

- [ ] Sudah diuji di Chrome, Firefox, dan Safari, termasuk satu HP
  Android

- [ ] Tidak ada pesan error PHP yang tampil di halaman

- [ ] Website berjalan di hosting lewat HTTPS, dan password admin bawaan
  sudah diganti

## Risiko dan pertanyaan terbuka

Risiko terbesar ada di animasi: kalau berlebihan, website terasa lambat
dan kurang profesional. Delapan hal di bawah tabel masih perlu
diputuskan Zulian.

| Risiko                                        | Dampak                                                                          | Cara mengatasi                                                                                    |
|-----------------------------------------------|---------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------|
| Animasi terlalu banyak atau terlalu lama      | Pengunjung harus menunggu sebelum bisa membaca, dan kesan profesional berkurang | Animasi masuk dibatasi 1,5 detik, tiap gerakan singkat, dan tiap elemen hanya dianimasikan sekali |
| Foto hero berukuran besar                     | Halaman pertama lambat tampil di HP                                             | Foto dikecilkan otomatis, memakai WebP, dan dimuat paling awal                                    |
| Isi dua bahasa menggandakan pekerjaan menulis | Versi Inggris tertinggal                                                        | Teks Inggris opsional dan otomatis memakai teks Indonesia kalau kosong                            |
| Kode PHP buatan sendiri punya celah keamanan  | Panel admin bisa disalahgunakan orang lain                                      | Aturan keamanan di PRD ini diuji satu per satu di tahap 8                                         |
| Hosting membatasi fitur                       | Unggah gambar atau alamat rapi gagal setelah rilis                              | Cek versi PHP, ekstensi GD, batas unggah, dan dukungan .htaccess sebelum memilih hosting          |

**Yang masih perlu diputuskan**

- [ ] Gaya animasi masuknya seperti apa, misalnya muncul bergantian dari
  bawah, efek ketik pada nama, atau tirai yang terbuka?

- [ ] Foto mana yang dipakai di hero, dan apakah latarnya dihapus atau
  dibiarkan?

- [ ] Apa alamat email dan nomor WhatsApp yang ingin ditampilkan?

- [ ] Hosting dan nama domain apa yang dipakai?

- [ ] Apakah sudah ada rancangan tampilan (Figma atau website acuan) dan
  warna utama?

- [ ] Kategori project apa saja yang dipakai, misalnya Web, AI, dan
  Keamanan?

- [ ] Project, skill, pengalaman, dan sertifikat mana yang masuk sebagai
  isi awal saat rilis?

- [ ] Apakah tingkat skill perlu ditampilkan, atau cukup nama skill
  saja?
