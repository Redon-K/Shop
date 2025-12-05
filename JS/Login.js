document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('login-form');
  if (!form) return;

  function showError(msg) {
    let err = document.getElementById('form-error');
    if (!err) {
      err = document.createElement('div');
      err.id = 'form-error';
      err.className = 'error';
      err.setAttribute('role', 'alert');
      err.setAttribute('aria-live', 'polite');
      form.parentNode.insertBefore(err, form.nextSibling);
    }
    err.textContent = msg;
    err.style.display = 'block';
  }

  function clearError() {
    const err = document.getElementById('form-error');
    if (err) err.style.display = 'none';
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearError();

    const email = (form.email?.value || '').trim();
    const password = (form.password?.value || '').trim();

    if (!email || !password) {
      showError('Please enter both email and password.');
      return;
    }

    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    if (!emailValid) {
      showError('Please enter a valid email address.');
      return;
    }

    const remember = !!form.remember?.checked;
    const user = { email, remembered: remember };




    window.location.href = 'Home.html';
  });
});