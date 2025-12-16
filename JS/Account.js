document.addEventListener("DOMContentLoaded", () => {
  const saveBtn = document.getElementById("saveBtn");
  const statusEl = document.getElementById("status");
  const avatarInput = document.getElementById("avatarInput");
  const avatar = document.getElementById("avatar");
  const shopName = document.getElementById("shopName");
  const email = document.getElementById("email");
  const phone = document.getElementById("phone");
  const address = document.getElementById("address");

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
      shopName: shopName ? shopName.value : "",
      email: email ? email.value : "",
      phone: phone ? phone.value : "",
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

  function loadProfile() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const data = JSON.parse(raw);
      if (shopName && data.shopName) shopName.value = data.shopName;
      if (email && data.email) email.value = data.email;
      if (phone && data.phone) phone.value = data.phone;
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

  // load saved profile (if any)
  loadProfile();
});

