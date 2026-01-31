document.addEventListener("DOMContentLoaded", () => {
  console.log('Account.js loaded');
  
  const saveBtn = document.getElementById("saveBtn");
  const statusEl = document.getElementById("status");
  const avatar = document.getElementById("avatar");
  const firstName = document.getElementById("firstName");
  const lastName = document.getElementById("lastName");
  const email = document.getElementById("email");
  const phone = document.getElementById("phone");
  const address = document.getElementById("address");
  const dob = document.getElementById("dob");
  const contactPref = document.getElementById("contactPref");
  const newsletter = document.getElementById("newsletter");
  const editBtn = document.getElementById("editBtn");
  const changeAvatar = document.getElementById("changeAvatar");
  const logoutBtn = document.getElementById("logoutBtn");
  const avatarInput = document.createElement("input");
  
  avatarInput.type = "file";
  avatarInput.accept = "image/*";
  avatarInput.style.display = "none";
  document.body.appendChild(avatarInput);

  function showStatus(msg, isError = false) {
    if (!statusEl) return;
    statusEl.textContent = msg;
    statusEl.style.color = isError ? '#f44336' : '#4CAF50';
    statusEl.style.background = isError ? 'rgba(244,67,54,0.1)' : 'rgba(76,175,80,0.1)';
    statusEl.style.borderColor = isError ? 'rgba(244,67,54,0.3)' : 'rgba(76,175,80,0.3)';
    
    setTimeout(() => {
      if (statusEl.textContent === msg) {
        statusEl.textContent = "";
        statusEl.style.background = '';
        statusEl.style.borderColor = '';
      }
    }, 3000);
  }

  async function loadProfile() {
    try {
      const response = await fetch('../PHP/account_update.php');
      const result = await response.json();
      
      if (result.success && result.user) {
        const user = result.user;
        if (firstName) firstName.value = user.first_name || '';
        if (lastName) lastName.value = user.last_name || '';
        if (email) email.value = user.email || '';
        if (phone) phone.value = user.phone || '';
        if (address) address.value = user.street_address || '';
        if (dob) dob.value = user.date_of_birth || '';
        if (contactPref) contactPref.value = user.contact_preference || 'email';
        if (newsletter) newsletter.checked = user.newsletter_subscribed || false;
        
        if (avatar && user.avatar_url) {
          const img = avatar.querySelector('img');
          if (img) img.src = user.avatar_url;
        }
      }
    } catch (error) {
      console.error('Error loading profile:', error);
      showStatus('Error loading profile. Please refresh the page.', true);
    }
  }

  async function saveProfile() {
    const payload = {
      firstName: firstName ? firstName.value : "",
      lastName: lastName ? lastName.value : "",
      email: email ? email.value : "",
      phone: phone ? phone.value : "",
      address: address ? address.value : "",
      dob: dob ? dob.value : "",
      contactPref: contactPref ? contactPref.value : "email",
      newsletter: newsletter ? newsletter.checked : false
    };
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (payload.email && !emailRegex.test(payload.email)) {
      showStatus("Please enter a valid email address", true);
      return;
    }
    
    try {
      const response = await fetch('../PHP/account_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const result = await response.json();
      
      if (result.success) {
        showStatus(result.message || "Profile saved successfully");
        setEditing(false);
        
        try {
          let profile = JSON.parse(localStorage.getItem('apexProfile') || '{}');
          profile = { ...profile, ...payload };
          localStorage.setItem('apexProfile', JSON.stringify(profile));
        } catch(e) { /* ignore */ }
      } else {
        showStatus(result.message || "Error saving profile", true);
      }
    } catch (error) {
      console.error('Error saving profile:', error);
      showStatus("Error saving profile. Please try again.", true);
    }
  }

  async function logout() {
    if (!confirm('Are you sure you want to sign out?')) {
        return;
    }
    
    try {
        if (logoutBtn) {
            logoutBtn.textContent = 'Signing Out...';
            logoutBtn.disabled = true;
        }
        
        // Try the AJAX endpoint first
        const response = await fetch('../PHP/logout_ajax.php');
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/json')) {
            const result = await response.json();
            
            if (result.success) {
                // Clear local storage
                localStorage.removeItem('apexProfile');
                localStorage.removeItem('cart');
                localStorage.removeItem('wishlist');
                
                showStatus('Signed out successfully. Redirecting...');
                
                // Wait a moment then redirect
                setTimeout(() => {
                    window.location.href = '../HTML/Home.php';
                }, 1500);
            } else {
                showStatus(result.message || 'Error signing out', true);
                if (logoutBtn) {
                    logoutBtn.textContent = 'Sign Out';
                    logoutBtn.disabled = false;
                }
            }
        } else {
            // If not JSON, just redirect to regular logout
            window.location.href = '../PHP/logout.php';
        }
    } catch (error) {
        console.error('Logout error:', error);
        // Fallback to regular logout
        window.location.href = '../PHP/logout.php';
    }
}


  if (editBtn) {
    editBtn.addEventListener('click', () => {
      const nowEditing = editBtn.textContent !== 'Change Information';
      setEditing(!nowEditing);
      
      if (!nowEditing) {
        setEditing(true);
      } else {
        loadProfile();
      }
    });
  }

  if (saveBtn) {
    saveBtn.addEventListener('click', saveProfile);
  }

  if (logoutBtn) {
    logoutBtn.addEventListener('click', logout);
  }

  loadProfile();
  setEditing(false);
});