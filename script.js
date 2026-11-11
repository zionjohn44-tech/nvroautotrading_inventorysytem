const form = document.querySelector('#login-form');
const username = document.querySelector('#username');
const password = document.querySelector('#password');
const toggle = document.querySelector('#password-toggle');
const message = document.querySelector('#form-message');
const remember = document.querySelector('#remember');

// Store only the username when explicitly selected; never store a password or session token.
try {
  const saved = localStorage.getItem('nvro.rememberedUsername');
  if (saved && !username.value) username.value = saved;
  remember.checked = Boolean(saved);
} catch { /* Login still works when browser storage is unavailable. */ }
remember.addEventListener('change', () => {
  if (!remember.checked) {
    try { localStorage.removeItem('nvro.rememberedUsername'); } catch { /* Optional preference. */ }
  }
});

document.querySelector('#year').textContent = new Date().getFullYear();

toggle.addEventListener('click', () => {
  const visible = password.type === 'password';
  password.type = visible ? 'text' : 'password';
  toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
  toggle.setAttribute('aria-pressed', String(visible));
});

for (const input of [username, password]) {
  input.addEventListener('input', () => {
    input.removeAttribute('aria-invalid');
    document.querySelector(`#${input.id}-error`).textContent = '';
    message.textContent = '';
  });
}

form.addEventListener('submit', (event) => {
  message.textContent = '';
  let firstInvalid = null;
  for (const input of [username, password]) {
    const invalid = input === username ? !input.value.trim() : !input.value;
    input.setAttribute('aria-invalid', String(invalid));
    document.querySelector(`#${input.id}-error`).textContent = invalid ? `Please enter your ${input.id}.` : '';
    if (invalid && !firstInvalid) firstInvalid = input;
  }
  if (firstInvalid) {
    event.preventDefault();
    firstInvalid.focus();
    return;
  }
  try {
    if (remember.checked) localStorage.setItem('nvro.rememberedUsername', username.value.trim());
    else localStorage.removeItem('nvro.rememberedUsername');
  } catch { /* Optional preference; continue with server authentication. */ }
});

document.querySelector('#forgot-password').addEventListener('click', () => {
  message.textContent = 'Ask your NVRO administrator to reset your password in Staff accounts. You will choose a new password at your next login.';
});
document.querySelector('#contact-admin').addEventListener('click', () => {
  message.textContent = 'Please ask your manager or NVRO system administrator to set up your account.';
});
