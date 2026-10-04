// Applies the saved theme (or the system one) before the first paint; external file so the CSP needs no inline script.
(function () {
  var saved;
  try {
    saved = localStorage.getItem('lexican-theme');
  } catch {
    saved = null;
  }
  var dark =
    saved === 'dark' ||
    (saved !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
  document.documentElement.dataset.theme = dark ? 'dark' : 'light';
})();
