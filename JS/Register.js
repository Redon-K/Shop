document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('register-form');
  if (!form) return;

  const errEl = document.getElementById('form-error');

  function showError(msg) {
    if (!errEl) return;
    errEl.textContent = msg;
    errEl.style.display = 'block';
  }
  function clearError() {
    if (!errEl) return;
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  function validEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearError();

    const data = {
      fullname: (form.fullname?.value || '').trim(),
      email: (form.email?.value || '').trim(),
      password: (form.password?.value || ''),
      confirm: (form.confirm?.value || ''),
      phone: (form.phone?.value || '').trim(),
      street: (form.street?.value || '').trim(),
      city: (form.city?.value || '').trim(),
      region: (form.region?.value || '').trim(),
      postal: (form.postal?.value || '').trim(),
      country: (form.country?.value || '').trim(),
      notes: (form['delivery-notes']?.value || '').trim(),
      subscribe: !!form.subscribe?.checked
    };

    if (!data.fullname) { showError('Please enter your full name.'); return; }
    if (!data.email || !validEmail(data.email)) { showError('Please enter a valid email address.'); return; }
    if (!data.password) { showError('Please enter a password.'); return; }
    if (data.password !== data.confirm) { showError('Passwords do not match.'); return; }
    if (data.password.length < 6) { showError('Password should be at least 6 characters.'); return; }

    // Frontend-only demo: store user profile locally (do not store real passwords in production)
    const users = JSON.parse(localStorage.getItem('apex_users_demo') || '[]');
    users.push({
      fullname: data.fullname,
      email: data.email,
      // storing hashed password would be required in real app — demo stores plain text for simplicity
      password: data.password,
      phone: data.phone,
      address: { street: data.street, city: data.city, region: data.region, postal: data.postal, country: data.country },
      notes: data.notes,
      subscribe: data.subscribe,
      createdAt: new Date().toISOString()
    });
    localStorage.setItem('apex_users_demo', JSON.stringify(users));

    // mark session as logged in for demo and redirect to Home
    sessionStorage.setItem('apex_demo_user', JSON.stringify({ email: data.email, name: data.fullname }));
    window.location.href = 'Home.html';
  });
});