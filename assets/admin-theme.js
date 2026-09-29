// admin theme toggle
(function() {
  var theme = localStorage.getItem('admin-theme') || 'light';
  document.documentElement.setAttribute('data-theme', theme);
})();

function toggleAdminTheme() {
  var current = document.documentElement.getAttribute('data-theme');
  var next    = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('admin-theme', next);
  updateAdminThemeIcon();
}

function updateAdminThemeIcon() {
  var theme = document.documentElement.getAttribute('data-theme');
  var btn   = document.getElementById('themeToggle');
  if (btn) {
    btn.querySelector('.material-icons').textContent =
      theme === 'dark' ? 'light_mode' : 'dark_mode';
  }
}

document.addEventListener('DOMContentLoaded', updateAdminThemeIcon);