(function () {
  var storageKey = 'tf-portal-theme';
  var root = document.documentElement;
  var transitionTimer = null;

  function detectTheme() {
    try {
      var storedTheme = localStorage.getItem(storageKey);
      if (storedTheme === 'light' || storedTheme === 'dark') {
        return storedTheme;
      }
    } catch (error) {
      return 'light';
    }

    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(theme, animate) {
    var activeTheme = theme === 'dark' ? 'dark' : 'light';

    if (animate) {
      root.classList.add('theme-animating');
      window.clearTimeout(transitionTimer);
      transitionTimer = window.setTimeout(function () {
        root.classList.remove('theme-animating');
      }, 650);
    }

    root.setAttribute('data-theme', activeTheme);

    if (document.body) {
      document.body.setAttribute('data-theme', activeTheme);
    }

    try {
      localStorage.setItem(storageKey, activeTheme);
    } catch (error) {
      // Ignore storage failures and keep the current session theme only.
    }

    document.querySelectorAll('[data-theme-label]').forEach(function (label) {
      label.textContent = activeTheme === 'dark' ? 'Dark mode' : 'Light mode';
    });

    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.setAttribute('aria-pressed', activeTheme === 'dark' ? 'true' : 'false');
      button.setAttribute('title', activeTheme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    });
  }

  function bindToggle() {
    var currentTheme = root.getAttribute('data-theme') || detectTheme();
    applyTheme(currentTheme, false);

    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      if (button.dataset.themeBound === '1') {
        return;
      }

      button.dataset.themeBound = '1';
      button.addEventListener('click', function () {
        var nextTheme = (root.getAttribute('data-theme') || 'light') === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme, true);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindToggle);
  } else {
    bindToggle();
  }
})();
