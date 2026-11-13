/*
 * Tombol ganti tema terang/gelap.
 * Tema awal sudah dipasang oleh skrip kecil di <head> (supaya tidak berkedip).
 * Di sini hanya mengurus klik tombol dan menyimpan pilihan di browser.
 */
(function () {
  var root = document.documentElement;
  var buttons = document.querySelectorAll('.theme-btn');

  function current() {
    return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }

  function sync() {
    var dark = current() === 'dark';
    buttons.forEach(function (btn) { btn.setAttribute('aria-pressed', dark ? 'true' : 'false'); });
  }

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var next = current() === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('theme', next); } catch (e) { /* mode privat: abaikan */ }
      sync();
    });
  });

  sync();
})();
