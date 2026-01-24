<?php
session_start();

// If already logged in, redirect to home
if(isset($_SESSION['user_id'])){
    header("Location: Home.php");
    exit();
}

// Check if form was submitted
$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])){
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if(empty($email) || empty($password)){
        $error = 'Please enter both email and password.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = 'Please enter a valid email address.';
    } else {
        // Demo credentials
        $valid_email = 'admin@apex.com';
        $valid_password = 'admin123';
        
        if($email === $valid_email && $password === $valid_password){
            $_SESSION['user_id'] = 1;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = 'Admin';
            $_SESSION['last_name'] = 'User';
            $_SESSION['is_admin'] = true;
            $_SESSION['login_time'] = time();
            
            // Handle "Remember me"
            if(isset($_POST['remember'])){
                setcookie('user_id', '1', time() + (30 * 24 * 60 * 60), '/');
            }
            
            header("Location: Home.php");
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$page_title = 'Apex Fuel — Log In';
$additional_css = ['LogIn.css'];
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main>
    <section class="auth-card" role="main">
        <img class="brand-logo" src="../Images/Logo.png" alt="Apex Fuel logo">

        <h1 class="title">Log in to Apex Fuel</h1>

        <?php if(!empty($error)): ?>
            <div id="form-error" class="error" style="margin-bottom:15px; padding:10px; background:#ffe0e0; color:#d32f2f; border-radius:4px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="" novalidate>
            <div class="form-row">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <div class="form-row helper-row">
                <label class="remember">
                    <input id="remember" name="remember" type="checkbox"> Remember me
                </label>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Log in</button>
                <a class="link-muted" href="Home.php">Back to shop</a>
            </div>
        </form>

        <p class="signup-cta">Don't have an account? <a class="link-muted" href="Register.php">Sign up</a></p>
    </section>
</main>

<script src="../JS/Home.js"></script>
<script>
// Frontend validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!email || !password) {
                e.preventDefault();
                let errorDiv = document.getElementById('form-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.id = 'form-error';
                    errorDiv.className = 'error';
                    errorDiv.style.cssText = 'margin-bottom:15px; padding:10px; background:#ffe0e0; color:#d32f2f; border-radius:4px;';
                    form.parentNode.insertBefore(errorDiv, form);
                }
                errorDiv.textContent = 'Please enter both email and password.';
                return false;
            }
            
            const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            if (!emailValid) {
                e.preventDefault();
                let errorDiv = document.getElementById('form-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.id = 'form-error';
                    errorDiv.className = 'error';
                    errorDiv.style.cssText = 'margin-bottom:15px; padding:10px; background:#ffe0e0; color:#d32f2f; border-radius:4px;';
                    form.parentNode.insertBefore(errorDiv, form);
                }
                errorDiv.textContent = 'Please enter a valid email address.';
                return false;
            }
        });
    }
});
</script>
</body>
</html>