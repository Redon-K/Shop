document.addEventListener('DOMContentLoaded', () => {
  console.log('Login.js loaded');
  
  const form = document.getElementById('login-form') || document.querySelector('form[method="post"]');
  if (!form) return;

  let errEl = document.getElementById('form-error');
  if (!errEl) {
    errEl = document.createElement('div');
    errEl.id = 'form-error';
    errEl.className = 'error';
    errEl.style.display = 'none';
    form.parentNode.insertBefore(errEl, form);
  }

  function showError(msg) {
    errEl.textContent = msg;
    errEl.style.display = 'block';
    errEl.style.marginBottom = '15px';
    errEl.style.padding = '12px 16px';
    errEl.style.background = 'rgba(244,67,54,0.1)';
    errEl.style.color = '#f44336';
    errEl.style.borderRadius = '8px';
    errEl.style.border = '1px solid rgba(244,67,54,0.3)';
    errEl.style.fontSize = '14px';
  }

  function clearError() {
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  function validEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  form.addEventListener('submit', (e) => {
    const email = (form.email?.value || '').trim();
    const password = (form.password?.value || '').trim();

    clearError();

    if (!email || !password) {
      e.preventDefault();
      showError('Please enter both email and password.');
      return false;
    }

    if (!validEmail(email)) {
      e.preventDefault();
      showError('Please enter a valid email address.');
      return false;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.textContent = 'Logging in...';
      submitBtn.disabled = true;
    }
    
    return true;
  });

  const emailInput = document.getElementById('email');
  if (emailInput) {
    let emailTimeout;
    
    emailInput.addEventListener('input', () => {
      clearTimeout(emailTimeout);
      const email = emailInput.value.trim();
      
      if (!email) {
        clearError();
        return;
      }
      
      emailTimeout = setTimeout(() => {
        if (!validEmail(email)) {
          showError('Please enter a valid email address.');
        } else {
          clearError();
        }
      }, 500);
    });
  }

  const formInputs = form.querySelectorAll('input');
  formInputs.forEach(input => {
    input.addEventListener('input', () => {
      clearError();
      
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && submitBtn.disabled) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Log in';
      }
    });
  });

  const rememberCheckbox = document.getElementById('remember');
  if (rememberCheckbox) {
    const rememberLabel = rememberCheckbox.closest('.remember');
    if (rememberLabel) {
      rememberLabel.style.cursor = 'pointer';
      rememberLabel.style.display = 'flex';
      rememberLabel.style.alignItems = 'center';
      rememberLabel.style.gap = '8px';
    }
  }

  if (emailInput && !emailInput.value.trim()) {
    setTimeout(() => {
      emailInput.focus();
    }, 100);
  }
});