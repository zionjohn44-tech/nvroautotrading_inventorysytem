'use strict';

// Apply before the stylesheet renders to avoid a flash of the wrong theme.
(() => {
  let theme;
  try { theme = localStorage.getItem('nvro-theme'); } catch { /* Storage may be disabled. */ }
  if (!['light', 'dark'].includes(theme)) {
    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  document.documentElement.dataset.theme = theme;
  let navigation = 'compact';
  try { navigation = localStorage.getItem('nvro-navigation') || navigation; } catch { /* Keep the default. */ }
  document.documentElement.dataset.navigation = navigation === 'expanded' ? 'expanded' : 'compact';
})();
