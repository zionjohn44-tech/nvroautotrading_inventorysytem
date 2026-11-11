'use strict';

// A decorative light behind the workspace; animation sleeps when the mouse stops.
(() => {
  const workspace = document.querySelector('.main-shell');
  const light = document.querySelector('.ambient-light');
  const glow = light?.querySelector('.ambient-light-glow');
  if (!workspace || !glow) return;
  const enabled = matchMedia('(min-width: 701px) and (hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)');
  let frame = 0;
  let visible = false;
  let x = 0, y = 0, targetX = 0, targetY = 0, previousTime = 0;

  function paint() {
    glow.style.transform = `translate3d(${x}px, ${y}px, 0) translate(-50%, -50%)`;
  }
  function follow(time) {
    const ease = 1 - Math.exp(-Math.min(time - previousTime, 64) / 95);
    previousTime = time;
    x += (targetX - x) * ease;
    y += (targetY - y) * ease;
    if (Math.abs(targetX - x) + Math.abs(targetY - y) < 0.5) {
      x = targetX;
      y = targetY;
      frame = 0;
    } else {
      frame = requestAnimationFrame(follow);
    }
    paint();
  }
  function hide() {
    cancelAnimationFrame(frame);
    frame = 0;
    visible = false;
    light.classList.remove('is-visible');
  }
  workspace.addEventListener('pointermove', event => {
    if (!enabled.matches || event.pointerType !== 'mouse' || document.hidden) return;
    targetX = event.clientX;
    targetY = event.clientY;
    if (!visible) {
      x = targetX;
      y = targetY;
      paint();
      visible = true;
      light.classList.add('is-visible');
    } else if (!frame) {
      previousTime = performance.now();
      frame = requestAnimationFrame(follow);
    }
  }, { passive: true });
  workspace.addEventListener('pointerleave', hide);
  window.addEventListener('blur', hide);
  document.addEventListener('visibilitychange', () => { if (document.hidden) hide(); });
  enabled.addEventListener('change', hide);
})();

const themeToggle = document.querySelector('#theme-toggle');
function updateThemeToggle() {
  if (!themeToggle) return;
  const dark = document.documentElement.dataset.theme === 'dark';
  const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
  themeToggle.setAttribute('aria-label', label);
  themeToggle.setAttribute('aria-pressed', String(dark));
  themeToggle.title = label;
  themeToggle.querySelector('[data-theme-icon]').textContent = dark ? '\u2600' : '\u263e';
  themeToggle.querySelector('.theme-label').textContent = dark ? 'Light' : 'Dark';
}
updateThemeToggle();
themeToggle?.addEventListener('click', () => {
  const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
  document.documentElement.dataset.theme = theme;
  try { localStorage.setItem('nvro-theme', theme); } catch { /* Toggle still works without storage. */ }
  updateThemeToggle();
});

const brandSelect = document.querySelector('#Existing_Brand');
const brandInput = document.querySelector('#Brand_Name');
let newBrandDraft = brandSelect?.value ? '' : brandInput?.value || '';
function updateBrand() {
  if (!brandSelect) return;
  const existing = brandSelect.value !== '';
  document.querySelector('#new-brand-field').hidden = existing;
  brandInput.required = !existing;
  brandInput.value = existing ? brandSelect.value : newBrandDraft;
}
brandInput?.addEventListener('input', () => { newBrandDraft = brandInput.value; });
brandSelect?.addEventListener('change', updateBrand);
updateBrand();

const customerSelect = document.querySelector('.inquiry-customer-picker #Customer_ID');
const customerKeys = ['First_Name', 'Last_Name', 'Email', 'Contact_Number', 'Address'];
let newCustomerDraft = {};
function readCustomerFields() {
  return Object.fromEntries(customerKeys.map(key => [key, document.getElementById(key).value]));
}
if (customerSelect && !customerSelect.value) newCustomerDraft = readCustomerFields();
customerKeys.forEach(key => {
  if (!customerSelect) return;
  document.getElementById(key).addEventListener('input', () => {
    if (!customerSelect.value) newCustomerDraft = readCustomerFields();
  });
});
customerSelect?.addEventListener('change', () => {
  const details = customerSelect.value ? JSON.parse(customerSelect.selectedOptions[0].dataset.details) : newCustomerDraft;
  customerKeys.forEach(key => { document.getElementById(key).value = details[key] || ''; });
});

const today = document.querySelector('[data-today]');
function updateToday() {
  if (!today) return;
  const now = new Date();
  today.textContent = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }).format(now);
  today.dateTime = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit' }).format(now);
}
updateToday();
if (today) setInterval(updateToday, 60000);

const queueStrip = document.querySelector('#pending-queue');
if (queueStrip) {
  const navigation = document.querySelector('.queue-navigation');
  const buttons = Array.from(navigation.querySelectorAll('[data-queue-direction]'));
  function updateQueueNavigation() {
    const maxScroll = queueStrip.scrollWidth - queueStrip.clientWidth;
    navigation.hidden = maxScroll <= 2;
    buttons[0].disabled = queueStrip.scrollLeft <= 2;
    buttons[1].disabled = queueStrip.scrollLeft >= maxScroll - 2;
  }
  buttons.forEach(button => button.addEventListener('click', () => {
    queueStrip.scrollBy({ left: Number(button.dataset.queueDirection) * queueStrip.clientWidth, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
  }));
  queueStrip.addEventListener('scroll', updateQueueNavigation, { passive: true });
  window.addEventListener('resize', updateQueueNavigation);
  updateQueueNavigation();
}

const photos = document.querySelector('#Photos');
const preview = document.querySelector('#photo-preview');
const photoError = document.querySelector('#Photos-error');
const savedPreview = preview?.innerHTML || '';
photos?.addEventListener('change', () => {
  const files = Array.from(photos.files);
  let error = '';
  if (files.length > 3) error = 'Select up to three vehicle photos.';
  else if (files.some(file => file.size > 5 * 1024 * 1024)) error = 'Each photo must be 5 MB or smaller.';
  else if (files.some(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type))) error = 'Use JPG, PNG, or WebP images.';
  photos.setCustomValidity(error);
  photos.setAttribute('aria-invalid', String(Boolean(error)));
  photoError.textContent = error;
  preview.innerHTML = savedPreview;
  if (error || !files.length) return;
  preview.replaceChildren();
  files.forEach((file, index) => {
    const figure = document.createElement('figure');
    const img = document.createElement('img');
    const caption = document.createElement('figcaption');
    img.alt = 'Selected vehicle photo ' + (index + 1);
    caption.textContent = index === 0 ? 'Cover photo' : 'Photo ' + (index + 1);
    figure.append(img, caption);
    preview.append(figure);
    const reader = new FileReader();
    reader.addEventListener('load', () => { img.src = reader.result; });
    reader.readAsDataURL(file);
  });
});

const menu = document.querySelector('#menu-toggle');
const sidebar = document.querySelector('#sidebar');
const navBackdrop = document.querySelector('#nav-backdrop');
function setMobileMenu(open) {
  menu?.setAttribute('aria-expanded', String(open));
  sidebar?.classList.toggle('is-open', open);
  if (navBackdrop) navBackdrop.hidden = !open;
}
menu?.addEventListener('click', () => {
  const open = menu.getAttribute('aria-expanded') !== 'true';
  setMobileMenu(open);
});
navBackdrop?.addEventListener('click', () => { setMobileMenu(false); menu.focus(); });
window.addEventListener('resize', () => { if (window.innerWidth > 700) setMobileMenu(false); });

const sidebarToggle = document.querySelector('#sidebar-toggle');
function updateSidebarToggle() {
  if (!sidebarToggle) return;
  const expanded = document.documentElement.dataset.navigation === 'expanded';
  sidebarToggle.setAttribute('aria-expanded', String(expanded));
  sidebarToggle.setAttribute('aria-label', expanded ? 'Collapse navigation' : 'Expand navigation');
  sidebarToggle.title = expanded ? 'Collapse navigation' : 'Expand navigation';
}
updateSidebarToggle();
sidebarToggle?.addEventListener('click', () => {
  const navigation = document.documentElement.dataset.navigation === 'expanded' ? 'compact' : 'expanded';
  document.documentElement.dataset.navigation = navigation;
  try { localStorage.setItem('nvro-navigation', navigation); } catch { /* Works without storage. */ }
  updateSidebarToggle();
  window.dispatchEvent(new Event('resize'));
});
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && menu?.getAttribute('aria-expanded') === 'true') {
    setMobileMenu(false);
    menu.focus();
  }
});

const dialog = document.querySelector('#confirm-dialog');
let pendingForm = null;
let pendingSubmitter = null;
document.querySelectorAll('form').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (form.dataset.confirmed === 'true') return;
    const message = event.submitter?.dataset.confirm || form.dataset.confirm;
    if (!message) return;
    event.preventDefault();
    if (!dialog || typeof dialog.showModal !== 'function') {
      if (window.confirm(message)) {
        form.dataset.confirmed = 'true';
        form.requestSubmit(event.submitter);
      }
      return;
    }
    pendingForm = form;
    pendingSubmitter = event.submitter;
    document.querySelector('#confirm-message').textContent = message;
    dialog.showModal();
  });
});
dialog?.addEventListener('close', () => {
  const form = pendingForm;
  const submitter = pendingSubmitter;
  pendingForm = null;
  pendingSubmitter = null;
  if (dialog.returnValue === 'confirm' && form) {
    form.dataset.confirmed = 'true';
    form.requestSubmit(submitter);
  }
});

document.querySelector('#validation-summary')?.focus();
document.querySelectorAll('form[data-dirty-form]').forEach((form) => {
  let dirty = false;
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('change', () => { dirty = true; });
  form.addEventListener('submit', (event) => { if (!event.defaultPrevented) dirty = false; });
  window.addEventListener('beforeunload', (event) => {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });
});
