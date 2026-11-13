/*
 * Animasi halaman publik (Tahap 7). JavaScript biasa, tanpa library, di bawah 10 KB.
 *
 * 1. Animasi masuk beranda. Gerakannya ditulis di CSS (kelas .intro, dipasang skrip kecil
 *    di <head> sekali per kunjungan). Di sini: angka ringkas menghitung naik dari nol,
 *    lalu kelas .intro dibersihkan setelah selesai.
 * 2. Efek ketik pada peran di hero (daftar peran dari atribut data-roles).
 * 3. Animasi scroll: tiap elemen muncul sekali saat masuk layar, judul lebih dulu lalu isinya.
 * 4. Navbar menandai bagian yang sedang dibaca (beranda).
 *
 * "Kurangi gerakan" aktif → 1–3 tidak dijalankan: isi langsung tampil, hanya peran pertama.
 * Skrip gagal dimuat → tidak ada yang disembunyikan, karena kelas .rv dipasang dari sini.
 */
(function () {
  'use strict';

  var root = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var intro = root.classList.contains('intro');
  var INTRO_MS = 1500;                                   // batas PRD untuk animasi masuk
  var introStart = parseFloat(root.getAttribute('data-intro-t')) || 0;
  function sinceIntro() { return performance.now() - introStart; }

  /* ---------- 1. Animasi masuk: angka menghitung naik ---------- */
  if (intro) {
    var COUNT_BEGIN = 750, COUNT_MS = 650;               // mulai setelah kotak angka muncul
    document.querySelectorAll('.stats [data-count]').forEach(function (el) {
      var target = parseInt(el.getAttribute('data-count'), 10) || 0;
      if (sinceIntro() >= COUNT_BEGIN + COUNT_MS) return; // skrip terlambat dimuat: biarkan angka akhir
      el.textContent = '0';
      (function step() {
        var p = Math.min(Math.max((sinceIntro() - COUNT_BEGIN) / COUNT_MS, 0), 1);
        el.textContent = String(Math.round(target * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(step);
      })();
    });
    setTimeout(function () { root.classList.remove('intro'); }, Math.max(0, INTRO_MS + 150 - sinceIntro()));
  }

  /* ---------- 2. Efek ketik pada peran ---------- */
  var role = document.querySelector('.hero-role[data-roles]');
  var typed = role && role.querySelector('.typed');
  if (typed && !reduce) {
    var roles = [];
    try { roles = JSON.parse(role.getAttribute('data-roles')) || []; } catch (e) { roles = []; }
    roles = roles.map(function (r) { return Array.from(String(r)); }).filter(function (r) { return r.length; });
    if (roles.length > 1) {
      var r = 0, i = roles[0].length, deleting = false;
      var tick = function () {
        if (!deleting && i >= roles[r].length) { deleting = true; setTimeout(tick, 1800); return; }
        if (deleting && i <= 0) { deleting = false; r = (r + 1) % roles.length; setTimeout(tick, 300); return; }
        i += deleting ? -1 : 1;
        typed.textContent = roles[r].slice(0, i).join('') || '​'; // spasi tak terlihat: tinggi kotak tetap
        setTimeout(tick, deleting ? 35 : 70);
      };
      setTimeout(tick, intro ? Math.max(0, INTRO_MS - sinceIntro()) + 300 : 1200);
    }
  }

  /* ---------- 3. Animasi scroll ---------- */
  // [elemen, arah]  '' = naik dari bawah, rv-l = dari kiri, rv-r = dari kanan
  var REVEAL = [
    ['.sec .head', ''], ['.head-row > .btn', 'rv-r'],
    ['.about > .card', 'rv-l'], ['.about > .term', 'rv-r'],
    ['.skill', ''], ['.projects > li', ''],
    ['.jtitle', 'rv-l'], ['.tl > li', 'rv-l'],
    ['.certs > li', ''], ['#achievements .center', ''],
    ['.contact-l', 'rv-l'], ['.c-form', 'rv-r'],
    ['.filters', ''], ['.empty-note', ''],
    ['.pd-cover', ''], ['.pd-body > *', ''], ['.pd-gallery h2', ''], ['.gallery > li', ''],
    ['.foot-top', ''], ['.foot-big', ''], ['.foot-bottom', '']
  ];

  if (!reduce && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      // Elemen yang masuk layar bersamaan diberi jeda berurutan (atas → bawah, kiri → kanan)
      var shown = entries.filter(function (e) { return e.isIntersecting; });
      shown.sort(function (a, b) {
        return (a.boundingClientRect.top - b.boundingClientRect.top) || (a.boundingClientRect.left - b.boundingClientRect.left);
      });
      shown.forEach(function (e, n) {
        io.unobserve(e.target);                          // sekali saja, tidak diulang saat digulir naik-turun
        e.target.style.setProperty('--i', String(Math.min(n, 6)));
        e.target.classList.add('in');
      });
    }, { rootMargin: '0px 0px -8% 0px' });

    var seen = [];
    REVEAL.forEach(function (item) {
      document.querySelectorAll(item[0]).forEach(function (el) {
        if (seen.indexOf(el) > -1) return;
        seen.push(el);
        var box = el.getBoundingClientRect();
        // Yang sudah terlihat saat halaman dibuka tidak disembunyikan (tidak berkedip)
        if (box.height > 0 && box.top < window.innerHeight) return;
        el.classList.add('rv');
        if (item[1]) el.classList.add(item[1]);
        io.observe(el);
      });
    });
  }

  /* ---------- 4. Navbar menandai bagian yang sedang dibaca ---------- */
  var links = Array.prototype.slice.call(document.querySelectorAll('#nav-links a[href^="#"]'));
  if (links.length && 'IntersectionObserver' in window) {
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        links.forEach(function (a) {
          if (a.getAttribute('href') === '#' + e.target.id) a.setAttribute('aria-current', 'true');
          else a.removeAttribute('aria-current');
        });
      });
    }, { rootMargin: '-45% 0px -50% 0px' });         // garis tipis di tengah layar
    document.querySelectorAll('#hero, main section[id]').forEach(function (sec) { spy.observe(sec); });
  }
})();
