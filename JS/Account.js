// Lightweight instrumentation to help diagnose issues when the account page
// appears to not be working. This adds a console log and surfaces any
// initialization errors into the #status element so they're visible in the UI.
console.log("Account.js loaded");

document.addEventListener("DOMContentLoaded", () => {
  const saveBtn = document.getElementById("saveBtn");
  const statusEl = document.getElementById("status");
  const avatarInput = document.getElementById("avatarInput");
  const avatar = document.getElementById("avatar");
  const avatarFileName = document.getElementById("avatarFileName");
  const firstName = document.getElementById("firstName");
  const lastName = document.getElementById("lastName");
  const email = document.getElementById("email");
  const phone = document.getElementById("phone");
  const address = document.getElementById("address");
  const dob = document.getElementById("dob");
  const contactPref = document.getElementById("contactPref");
  const newsletter = document.getElementById("newsletter");
  const editBtn = document.getElementById("editBtn");

  const STORAGE_KEY = "apexProfile";

  // small helper to show a temporary status message
  function showStatus(msg, timeout = 3000) {
    if (!statusEl) return;
    statusEl.textContent = msg;
    // keep message for `timeout` ms then clear (if unchanged)
    if (timeout > 0) {
      setTimeout(() => {
        if (statusEl.textContent === msg) statusEl.textContent = "";
      }, timeout);
    }
  }

  function saveProfile() {
    const payload = {
      firstName: firstName ? firstName.value : "",
      lastName: lastName ? lastName.value : "",
      email: email ? email.value : "",
      phone: phone ? phone.value : "",
      dob: dob ? dob.value : "",
      contactPref: contactPref ? contactPref.value : "email",
      newsletter: newsletter ? !!newsletter.checked : false,
      address: address ? address.value : "",
      avatar: avatar ? avatar.src : ""
    };
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
      showStatus("Profile saved successfully!");
    } catch (e) {
      console.error("Failed to save profile:", e);
      showStatus("Failed to save profile");
    }
  }

  // Toggle edit mode: when not editing, inputs are disabled and Save is disabled.
  function setEditing(enabled) {
    const controls = [firstName, lastName, email, phone, dob, contactPref, address, newsletter, avatarInput];
    controls.forEach(c => {
      if (!c) return;
      try { c.disabled = !enabled; } catch (e) {}
    });
    // style the file label when disabled
    const fileBtn = document.querySelector('.file-btn');
    if (fileBtn) {
      fileBtn.classList.toggle('disabled', !enabled);
    }
    if (saveBtn) saveBtn.disabled = !enabled;
    if (editBtn) editBtn.textContent = enabled ? 'Cancel' : 'Change Information';
  }

  function loadProfile() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const data = JSON.parse(raw);
  // Backwards compatibility: older profiles used `shopName` for a single name value
  if (firstName && data.firstName) firstName.value = data.firstName;
  else if (firstName && data.shopName) firstName.value = data.shopName;
  if (lastName && data.lastName) lastName.value = data.lastName;
  if (email && data.email) email.value = data.email;
  if (phone && data.phone) phone.value = data.phone;
  if (dob && data.dob) dob.value = data.dob;
  if (contactPref && data.contactPref) contactPref.value = data.contactPref;
  if (newsletter && typeof data.newsletter !== 'undefined') newsletter.checked = !!data.newsletter;
  if (address && data.address) address.value = data.address;
  if (avatar && data.avatar) avatar.src = data.avatar;
    } catch (e) {
      console.error("Failed to load profile:", e);
    }
  }

  // smooth fade when changing avatar src
  if (avatar) {
    avatar.style.transition = avatar.style.transition || "opacity 0.25s ease";
    avatar.style.opacity = "1";
  }

  if (avatarInput) {
    avatarInput.addEventListener("change", () => {
      const file = avatarInput.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = () => {
          // fade out, change src, fade in
          try {
            avatar.style.opacity = "0";
          } catch (e) {}
          setTimeout(() => {
            avatar.src = reader.result;
            // show selected filename in the UI instead of the browser default text
            try {
              if (avatarFileName) avatarFileName.textContent = file.name || 'Selected file';
            } catch (e) {}
            try {
              avatar.style.opacity = "1";
            } catch (e) {}
          }, 150);
        };
        reader.readAsDataURL(file);
      }
    });
  }

  if (saveBtn) saveBtn.addEventListener("click", saveProfile);

  if (editBtn) {
    editBtn.addEventListener('click', () => {
      const nowEditing = editBtn.textContent !== 'Change Information';
      // If currently 'Cancel' -> disable editing; otherwise enable
      setEditing(nowEditing ? false : true);
      // If we just cancelled, reload profile to discard changes in the form
      if (nowEditing) loadProfile();
    });
  }

  // load saved profile (if any)
  try {
    loadProfile();
    // Initialize in non-editing mode: inputs disabled and Save disabled
    setEditing(false);
  } catch (initErr) {
    console.error('Account initialization error:', initErr);
    // surface a friendly message to the user
    try {
      if (statusEl) statusEl.textContent = 'Failed to initialize profile UI: ' + (initErr.message || initErr);
    } catch (e) {
      // ignore
    }
  }
});

