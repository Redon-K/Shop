const saveBtn = document.getElementById("saveBtn");
const statusEl = document.getElementById("status");
const avatarInput = document.getElementById("avatarInput");
const avatar = document.getElementById("avatar");

saveBtn.addEventListener("click", () => {
  statusEl.textContent = "Profile saved successfully!";
});

avatarInput.addEventListener("change", () => {
  const file = avatarInput.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = () => {
      avatar.src = reader.result;
    };
    reader.readAsDataURL(file);
  }
});

