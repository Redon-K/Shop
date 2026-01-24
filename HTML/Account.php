<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Get admin status from database
require_once '../PHP/config.php';
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

$is_admin = isset($user['is_admin']) && $user['is_admin'] ? true : false;

$page_title = 'My Account — Apex Fuel';
$additional_css = ['Account.css'];
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<?php if($is_admin): ?>
<div class="admin-banner">
    <a href="./admin/index.php" class="btn btn-primary">Go to Admin Panel</a>
</div>
<?php endif; ?>

<div class="profile-container">
    <div class="profile-card">
        <div class="form-section">
            <div class="compact-grid">
                <label>
                    <span class="label-text">First Name</span>
                    <input type="text" id="firstName" placeholder="John">
                </label>
                <label>
                    <span class="label-text">Last Name</span>
                    <input type="text" id="lastName" placeholder="Doe">
                </label>
                <label>
                    <span class="label-text">Email</span>
                    <input type="email" id="email" placeholder="john@example.com">
                </label>
                <label>
                    <span class="label-text">Phone</span>
                    <input type="tel" id="phone" placeholder="+1 (555) 123-4567">
                </label>
                <label>
                    <span class="label-text">Date of Birth</span>
                    <input type="date" id="dob">
                </label>
                <label>
                    <span class="label-text">Contact Preference</span>
                    <select id="contactPref">
                        <option value="email">Email</option>
                        <option value="phone">Phone</option>
                    </select>
                </label>
            </div>

            <label>
                <span class="label-text">Address</span>
                <textarea id="address" placeholder="123 Main St, City, State, ZIP"></textarea>
            </label>

            <div class="form-row">
                <label class="inline-checkbox">
                    <input type="checkbox" id="newsletter">
                    <span class="label-text">Subscribe to newsletter</span>
                </label>
            </div>

            <div id="status" role="status" aria-live="polite"></div>

            <div class="form-actions">
                <button id="editBtn" class="btn btn-ghost">Change Information</button>
                <button id="saveBtn" class="btn btn-primary" disabled>Save Changes</button>
                <button id="logoutBtn" class="btn btn-danger">Sign Out</button>
            </div>
        </div>
    </div>
</div>

<script src="../JS/Home.js"></script>
<script src="../JS/Account.js"></script>
</body>
</html>