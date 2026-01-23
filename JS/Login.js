document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('login-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    const email = (form.email?.value || '').trim();
    const password = (form.password?.value || '').trim();

    // Basic validation before submit
    if (!email || !password) {
      e.preventDefault();
      let err = document.getElementById('form-error');
      if (!err) {
        err = document.createElement('div');
        err.id = 'form-error';
        err.className = 'error';
        form.parentNode.insertBefore(err, form.nextSibling);
      }
      err.textContent = 'Please enter both email and password.';
      err.style.display = 'block';
      err.style.cssText = 'margin-bottom:15px; padding:10px; background:#ffe0e0; color:#d32f2f; border-radius:4px;';
      return false;
    }

    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    if (!emailValid) {
      e.preventDefault();
      let err = document.getElementById('form-error');
      if (!err) {
        err = document.createElement('div');
        err.id = 'form-error';
        err.className = 'error';
        form.parentNode.insertBefore(err, form.nextSibling);
      }
      err.textContent = 'Please enter a valid email address.';
      err.style.display = 'block';
      err.style.cssText = 'margin-bottom:15px; padding:10px; background:#ffe0e0; color:#d32f2f; border-radius:4px;';
      return false;
    }
  });
});