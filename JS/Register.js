document.addEventListener('DOMContentLoaded', () => {
  console.log('Register.js loaded');
  
  const form = document.getElementById('register-form');
  if (!form) return;

  const errEl = document.getElementById('form-error');

  // Show error message
  function showError(msg) {
    if (!errEl) return;
    errEl.textContent = msg;
    errEl.style.display = 'block';
  }

  // Clear error message
  function clearError() {
    if (!errEl) return;
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  // Validate email format
  function validEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // Validate password strength
  function validatePassword(password) {
    if (password.length < 6) {
      return 'Password must be at least 6 characters long';
    }
    return null;
  }

  // Handle form submission
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearError();

    // Collect form data
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

    // Validation
    if (!data.fullname) {
      showError('Please enter your full name.');
      return;
    }

    if (!data.email) {
      showError('Please enter your email address.');
      return;
    }

    if (!validEmail(data.email)) {
      showError('Please enter a valid email address.');
      return;
    }

    if (!data.password) {
      showError('Please enter a password.');
      return;
    }

    const passwordError = validatePassword(data.password);
    if (passwordError) {
      showError(passwordError);
      return;
    }

    if (data.password !== data.confirm) {
      showError('Passwords do not match.');
      return;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Creating Account...';
    submitBtn.disabled = true;

    try {
      // Send registration request
      const response = await fetch('../PHP/register.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();

      if (result.success) {
        // Registration successful
        showError(''); // Clear any errors
        submitBtn.textContent = 'Account Created!';
        submitBtn.style.background = '#4CAF50';
        
        // Store user data in localStorage for immediate use
        try {
          localStorage.setItem('apexProfile', JSON.stringify({
            firstName: data.fullname.split(' ')[0],
            lastName: data.fullname.split(' ').slice(1).join(' '),
            email: data.email,
            phone: data.phone,
            address: `${data.street}, ${data.city}, ${data.region} ${data.postal}`
          }));
        } catch (e) {
          console.error('Error storing profile:', e);
        }
        
        // Redirect to home page after delay
        setTimeout(() => {
          window.location.href = 'Home.php';
        }, 1500);
        
      } else {
        // Registration failed
        showError(result.message || 'Registration failed. Please try again.');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      }
      
    } catch (error) {
      console.error('Registration error:', error);
      showError('An error occurred during registration. Please try again.');
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  });

  // Real-time password validation
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('confirm');
  
  if (passwordInput && confirmInput) {
    let passwordTimeout;
    
    function validatePasswords() {
      const password = passwordInput.value;
      const confirm = confirmInput.value;
      
      if (password && confirm && password !== confirm) {
        showError('Passwords do not match.');
      } else {
        clearError();
      }
    }
    
    passwordInput.addEventListener('input', () => {
      clearTimeout(passwordTimeout);
      passwordTimeout = setTimeout(validatePasswords, 300);
    });
    
    confirmInput.addEventListener('input', () => {
      clearTimeout(passwordTimeout);
      passwordTimeout = setTimeout(validatePasswords, 300);
    });
  }

  // Real-time email validation
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

  // Clear error when user starts typing in any field
  const formInputs = form.querySelectorAll('input, select, textarea');
  formInputs.forEach(input => {
    input.addEventListener('input', () => {
      clearError();
    });
  });
});