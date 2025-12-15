// Fake logged-in user data
const user = {
  name: "John Doe",
  email: "john@proteinshop.com"
};

// Load user data on page load
window.onload = () => {
  const savedUser = JSON.parse(localStorage.getItem("user")) || user;
  document.getElementById("name").value = savedUser.name;
  document.getElementById("email").value = savedUser.email;
};

// Save changes
document.getElementById("saveBtn").addEventListener("click", () => {
  const updatedName = document.getElementById("name").value;

  if (updatedName.trim() === "") {
    alert("Name cannot be empty");
    return;
  }

  const updatedUser = {
    name: updatedName,
    email: document.getElementById("email").value
  };

  localStorage.setItem("user", JSON.stringify(updatedUser));
  alert("Profile updated successfully");
});

// Logout
document.getElementById("logoutBtn").addEventListener("click", () => {
  localStorage.removeItem("user");
  alert("Logged out");
  window.location.reload();
});
