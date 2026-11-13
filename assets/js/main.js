/*
 * Skrip utama (halaman publik dan panel admin).
 * - Menu lipat di HP
 * - Tombol tampilkan/sembunyikan password
 * - Fokus ke ringkasan error setelah formulir gagal dikirim
 * - Tombol "Tampilkan lebih banyak" (sertifikat)
 */

/* Tombol tampilkan/sembunyikan password */
(function () {
  document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });
  });

  var summary = document.querySelector('.error-summary');
  if (summary) summary.focus();
})();

/* Menu lipat di HP */
(function () {
  var btn = document.querySelector('.menu-btn');
  var menu = document.getElementById('nav-links');
  if (!btn || !menu) return;

  function setOpen(open) {
    menu.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? btn.dataset.labelClose : btn.dataset.labelOpen);
  }

  btn.addEventListener('click', function () {
    setOpen(btn.getAttribute('aria-expanded') !== 'true');
  });

  // Tutup menu setelah memilih tautan, menekan Esc, atau klik di luar menu
  menu.addEventListener('click', function (e) {
    if (e.target.closest('a')) setOpen(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      btn.focus();
    }
  });
  document.addEventListener('click', function (e) {
    if (btn.getAttribute('aria-expanded') === 'true' && !menu.contains(e.target) && !btn.contains(e.target)) {
      setOpen(false);
    }
  });
})();

/* Tombol "Tampilkan lebih banyak": tanpa JavaScript semua kartu sudah tampil,
   jadi tombol ini disembunyikan (atribut hidden) dan baru dimunculkan di sini. */
(function () {
  document.querySelectorAll('[data-more]').forEach(function (btn) {
    var list = document.getElementById(btn.dataset.more);
    if (!list) return;
    btn.hidden = false;
    btn.addEventListener('click', function () {
      list.querySelectorAll('.is-extra').forEach(function (item) { item.classList.add('show'); });
      btn.setAttribute('aria-expanded', 'true');
      btn.hidden = true;
      // Pindahkan fokus ke kartu pertama yang baru muncul supaya pengguna keyboard tidak tersesat
      var first = list.querySelector('[data-first-extra]');
      if (first) first.focus();
    });
  });
})();
