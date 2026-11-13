-- =====================================================================
-- Database Website Portofolio Zulian
-- MySQL 8 / MariaDB 10.4 ke atas · InnoDB · utf8mb4
--
-- Cara impor (Laragon):
--   1. Buka HeidiSQL (tombol "Database" di Laragon).
--   2. Buat database baru bernama "portofolio" dengan collation utf8mb4_unicode_ci.
--   3. Pilih database itu, lalu File → Run SQL file → pilih file ini.
--
-- PERHATIAN: file ini MENGHAPUS tabel lama lalu membuatnya ulang.
-- Jangan impor ulang kalau sudah mengisi data asli lewat panel admin.
--
-- Data bertanda [CONTOH] hanya untuk menguji tampilan. Hapus atau ganti
-- lewat panel admin (mulai Tahap 3 dan 4).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS project_skill;
DROP TABLE IF EXISTS project_images;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS experiences;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS profile;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. admins: akun admin (hanya satu). Akun dibuat di Tahap 2.
-- ---------------------------------------------------------------------
CREATE TABLE admins (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  last_login_at DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. profile: data diri dan pengaturan website (selalu satu baris, id = 1)
--    Akhiran _ind = Bahasa Indonesia (wajib), _eng = Bahasa Inggris (opsional)
--    roles_ind / roles_eng: satu peran per baris (untuk efek ketik)
-- ---------------------------------------------------------------------
CREATE TABLE profile (
  id                      TINYINT UNSIGNED NOT NULL,
  full_name               VARCHAR(100) NOT NULL,
  roles_ind               TEXT         NOT NULL,
  roles_eng               TEXT         NULL,
  tagline_ind             VARCHAR(255) NOT NULL,
  tagline_eng             VARCHAR(255) NULL,
  status_text_ind         VARCHAR(120) NULL,
  status_text_eng         VARCHAR(120) NULL,
  status_enabled          TINYINT(1)   NOT NULL DEFAULT 1,
  about_ind               TEXT         NOT NULL,
  about_eng               TEXT         NULL,
  photo                   VARCHAR(255) NULL,
  cv_ind                  VARCHAR(255) NULL,
  cv_eng                  VARCHAR(255) NULL,
  email                   VARCHAR(150) NULL,
  whatsapp                VARCHAR(30)  NULL,
  github_url              VARCHAR(255) NULL,
  linkedin_url            VARCHAR(255) NULL,
  instagram_url           VARCHAR(255) NULL,
  tiktok_url              VARCHAR(255) NULL,   -- tambahan atas permintaan Zulian
  meta_desc_ind           VARCHAR(160) NULL,
  meta_desc_eng           VARCHAR(160) NULL,
  intro_animation_enabled TINYINT(1)   NOT NULL DEFAULT 1,
  created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. projects
-- ---------------------------------------------------------------------
CREATE TABLE projects (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug            VARCHAR(120) NOT NULL,
  title_ind       VARCHAR(150) NOT NULL,
  title_eng       VARCHAR(150) NULL,
  summary_ind     VARCHAR(255) NOT NULL,
  summary_eng     VARCHAR(255) NULL,
  description_ind TEXT         NOT NULL,
  description_eng TEXT         NULL,
  my_role_ind     VARCHAR(150) NULL,
  my_role_eng     VARCHAR(150) NULL,
  category        VARCHAR(30)  NOT NULL,      -- kunci dari config/app.php
  year            SMALLINT UNSIGNED NOT NULL,
  cover_image     VARCHAR(255) NULL,
  demo_url        VARCHAR(255) NULL,
  repo_url        VARCHAR(255) NULL,
  is_featured     TINYINT(1)   NOT NULL DEFAULT 0,
  status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
  sort_order      INT          NOT NULL DEFAULT 0,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_projects_slug (slug),
  KEY idx_projects_public (status, is_featured, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. project_images: galeri milik project (terhapus otomatis bersama project)
-- ---------------------------------------------------------------------
CREATE TABLE project_images (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  file_name  VARCHAR(255) NOT NULL,
  alt_ind    VARCHAR(200) NOT NULL,
  alt_eng    VARCHAR(200) NULL,
  sort_order INT          NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_project_images_project (project_id, sort_order),
  CONSTRAINT fk_project_images_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. skills (sekaligus daftar teknologi untuk project)
--    level: 1 = dasar, 2 = menengah, 3 = mahir (opsional, tidak ditampilkan)
-- ---------------------------------------------------------------------
CREATE TABLE skills (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(60)  NOT NULL,
  category   VARCHAR(30)  NOT NULL,           -- kunci dari config/app.php
  level      TINYINT UNSIGNED NULL,
  status     ENUM('draft','published') NOT NULL DEFAULT 'draft',
  sort_order INT          NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_skills_public (status, category, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. project_skill: penghubung project ↔ skill
--    Hapus project → penghubungnya ikut terhapus.
--    Hapus skill   → hanya penghubungnya yang terhapus, project tetap ada.
-- ---------------------------------------------------------------------
CREATE TABLE project_skill (
  project_id INT UNSIGNED NOT NULL,
  skill_id   INT UNSIGNED NOT NULL,
  PRIMARY KEY (project_id, skill_id),
  KEY idx_project_skill_skill (skill_id),
  CONSTRAINT fk_project_skill_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT fk_project_skill_skill   FOREIGN KEY (skill_id)   REFERENCES skills (id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. experiences: pengalaman & pendidikan (urut otomatis dari tanggal terbaru)
-- ---------------------------------------------------------------------
CREATE TABLE experiences (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  type            ENUM('kerja','organisasi','pendidikan') NOT NULL,
  title_ind       VARCHAR(150) NOT NULL,      -- posisi atau jurusan
  title_eng       VARCHAR(150) NULL,
  organization    VARCHAR(150) NOT NULL,      -- nama tempat / sekolah / kampus
  location        VARCHAR(100) NULL,
  start_date      DATE         NOT NULL,
  end_date        DATE         NULL,
  is_current      TINYINT(1)   NOT NULL DEFAULT 0,
  description_ind TEXT         NULL,
  description_eng TEXT         NULL,
  image           VARCHAR(255) NULL,          -- satu foto opsional (tambahan atas permintaan Zulian)
  status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_experiences_public (status, type, start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. achievements: sertifikat, lomba, penghargaan (urut dari tanggal terbaru)
-- ---------------------------------------------------------------------
CREATE TABLE achievements (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  type           ENUM('sertifikat','lomba','penghargaan') NOT NULL,
  title_ind      VARCHAR(150) NOT NULL,
  title_eng      VARCHAR(150) NULL,
  issuer         VARCHAR(150) NOT NULL,
  issued_date    DATE         NOT NULL,
  result_ind     VARCHAR(100) NULL,
  result_eng     VARCHAR(100) NULL,
  image          VARCHAR(255) NULL,
  credential_url VARCHAR(255) NULL,
  status         ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_achievements_public (status, issued_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. messages: pesan dari form kontak (ip_hash = alamat IP yang disamarkan)
-- ---------------------------------------------------------------------
CREATE TABLE messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  body       TEXT         NOT NULL,
  ip_hash    CHAR(64)     NOT NULL,
  is_read    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_messages_rate (ip_hash, created_at),
  KEY idx_messages_unread (is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. login_attempts: catatan gagal login untuk pengunci (5x gagal → 15 menit)
-- ---------------------------------------------------------------------
CREATE TABLE login_attempts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_address   VARCHAR(45)  NOT NULL,
  attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- DATA AWAL
-- =====================================================================

-- Profil: nama, email, dan media sosial adalah data asli.
-- Peran, tagline, teks Tentang, dan deskripsi masih CONTOH — ubah lewat menu Profil.
INSERT INTO profile (
  id, full_name, roles_ind, roles_eng, tagline_ind, tagline_eng,
  status_text_ind, status_text_eng, status_enabled, about_ind, about_eng,
  photo, cv_ind, cv_eng, email, whatsapp,
  github_url, linkedin_url, instagram_url, tiktok_url,
  meta_desc_ind, meta_desc_eng, intro_animation_enabled
) VALUES (
  1,
  'Zulian Dwi Saputra',
  'Web Developer\nBackend Developer PHP\nUI Enthusiast',
  'Web Developer\nPHP Backend Developer\nUI Enthusiast',
  'Saya membangun website yang cepat dan rapi, dari database sampai tampilan yang enak dipakai.',
  'I build fast, tidy websites, from the database to an interface that feels good to use.',
  'Terbuka untuk magang & freelance',
  'Open to internships & freelance',
  1,
  'Saya pelajar yang senang membangun website dari nol sampai bisa dibuka orang lain.\n\nSaya suka memecahkan masalah dengan kode: merancang database, menulis PHP yang aman, lalu merapikan tampilan supaya nyaman dibuka di HP.\n\nSekarang saya fokus belajar pengembangan web dan terbuka untuk magang maupun project freelance.',
  'I am a student who enjoys building websites from scratch until other people can use them.\n\nI like solving problems with code: designing databases, writing secure PHP, and polishing interfaces so they feel comfortable on phones.\n\nRight now I am focusing on web development and I am open to internships and freelance projects.',
  NULL, NULL, NULL,
  'zuliandwisaputra17@gmail.com',
  NULL,
  'https://github.com/zuliandwisaputra',
  'https://www.linkedin.com/in/zulian-dwi-saputra-08323544a',
  'https://www.instagram.com/juliee.an',
  'https://www.tiktok.com/@julie.4n',
  'Portofolio Zulian Dwi Saputra, web developer: project, skill, pengalaman, dan sertifikat.',
  'Portfolio of Zulian Dwi Saputra, web developer: projects, skills, experience, and certificates.',
  1
);

-- Skill dasar (silakan sesuaikan lewat panel admin)
INSERT INTO skills (id, name, category, level, status, sort_order) VALUES
  (1, 'HTML',       'frontend', NULL, 'published', 1),
  (2, 'CSS',        'frontend', NULL, 'published', 2),
  (3, 'JavaScript', 'frontend', NULL, 'published', 3),
  (4, 'PHP',        'backend',  NULL, 'published', 4),
  (5, 'MySQL',      'database', NULL, 'published', 5),
  (6, 'Git',        'tools',    NULL, 'published', 6);

-- Project CONTOH
INSERT INTO projects (id, slug, title_ind, title_eng, summary_ind, summary_eng, description_ind, description_eng,
  my_role_ind, my_role_eng, category, year, cover_image, demo_url, repo_url, is_featured, status, sort_order) VALUES
  (1, 'contoh-sistem-informasi-perpustakaan',
   '[CONTOH] Sistem Informasi Perpustakaan', '[SAMPLE] Library Information System',
   'Peminjaman buku dengan login petugas, denda otomatis, dan laporan bulanan.',
   'Book lending with staff login, automatic fines, and monthly reports.',
   'Ini project contoh untuk menguji tampilan. Ganti atau hapus lewat panel admin.',
   'This is a sample project for testing the layout. Replace or delete it from the admin panel.',
   'Pembuat tunggal', 'Solo developer', 'web', 2025, NULL, NULL, NULL, 1, 'published', 1),
  (2, 'contoh-aplikasi-kasir-kantin',
   '[CONTOH] Aplikasi Kasir Kantin', '[SAMPLE] Canteen Cashier App',
   'Pencatatan penjualan harian dan stok sederhana untuk kantin sekolah.',
   'Daily sales and simple stock records for a school canteen.',
   'Ini project contoh untuk menguji tampilan. Ganti atau hapus lewat panel admin.',
   'This is a sample project for testing the layout. Replace or delete it from the admin panel.',
   'Backend developer', 'Backend developer', 'web', 2024, NULL, NULL, NULL, 1, 'published', 2),
  (3, 'contoh-landing-page-umkm',
   '[CONTOH] Landing Page UMKM', '[SAMPLE] Small Business Landing Page',
   'Halaman promosi produk lokal yang ringan dibuka di HP.',
   'A lightweight promo page for a local product that loads fast on phones.',
   'Ini project contoh untuk menguji tampilan. Ganti atau hapus lewat panel admin.',
   'This is a sample project for testing the layout. Replace or delete it from the admin panel.',
   'Desain dan frontend', 'Design and frontend', 'uiux', 2024, NULL, NULL, NULL, 1, 'published', 3);

INSERT INTO project_skill (project_id, skill_id) VALUES
  (1, 4), (1, 5), (1, 2),
  (2, 4), (2, 3),
  (3, 1), (3, 2);

-- Pengalaman & pendidikan CONTOH
INSERT INTO experiences (type, title_ind, title_eng, organization, location, start_date, end_date, is_current,
  description_ind, description_eng, image, status) VALUES
  ('pendidikan', '[CONTOH] Nama jurusan', '[SAMPLE] Major', 'Nama sekolah/kampus', 'Kota',
   '2023-07-01', NULL, 1,
   'Isi dengan data aslimu lewat panel admin: nilai, prestasi, atau kegiatan yang menonjol.',
   'Fill in your real data from the admin panel: grades, achievements, or notable activities.', NULL, 'published'),
  ('pendidikan', '[CONTOH] Nama jurusan / kelas', '[SAMPLE] Major / class', 'Nama sekolah sebelumnya', 'Kota',
   '2020-07-01', '2023-06-30', 0, NULL, NULL, NULL, 'published'),
  ('organisasi', '[CONTOH] Anggota Divisi IT', '[SAMPLE] IT Division Member', 'Organisasi contoh', NULL,
   '2024-01-01', '2025-06-30', 0,
   'Mengelola website organisasi dan membuat formulir pendaftaran anggota.',
   'Managed the organization website and built the member registration form.', NULL, 'published'),
  ('kerja', '[CONTOH] Freelance Web Developer', '[SAMPLE] Freelance Web Developer', 'Klien UMKM contoh', NULL,
   '2024-03-01', '2024-12-31', 0,
   'Membuat landing page dan katalog produk untuk usaha lokal.',
   'Built landing pages and product catalogs for local businesses.', NULL, 'published');

-- Sertifikat & lomba CONTOH
INSERT INTO achievements (type, title_ind, title_eng, issuer, issued_date, result_ind, result_eng, image, credential_url, status) VALUES
  ('lomba',       '[CONTOH] Lomba Desain Web Tingkat Kota', '[SAMPLE] City Web Design Competition', 'Penyelenggara contoh', '2025-05-20', 'Juara 2', '2nd Place', NULL, NULL, 'published'),
  ('sertifikat',  '[CONTOH] Dasar Pemrograman Web', '[SAMPLE] Web Programming Fundamentals', 'Lembaga kursus contoh', '2025-02-10', 'Lulus', 'Passed', NULL, NULL, 'published'),
  ('penghargaan', '[CONTOH] Siswa Berprestasi Bidang TIK', '[SAMPLE] Outstanding ICT Student', 'Sekolah contoh', '2024-12-15', 'Terbaik 1', 'Top 1', NULL, NULL, 'published');
