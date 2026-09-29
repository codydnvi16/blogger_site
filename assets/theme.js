// theme.js — handles dark/light mode across all pages

// apply saved theme on page load
(function() {
  var theme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', theme);
})();

// toggle theme function
function toggleTheme() {
  var current = document.documentElement.getAttribute('data-theme');
  var next    = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  updateThemeIcon();
}

// update icon based on current theme
function updateThemeIcon() {
  var theme = document.documentElement.getAttribute('data-theme');
  var btn   = document.getElementById('themeToggle');
  if (btn) {
    btn.querySelector('.material-icons').textContent =
      theme === 'dark' ? 'light_mode' : 'dark_mode';
  }
}

// run on page load
document.addEventListener('DOMContentLoaded', updateThemeIcon);