<?php
session_start();

if(!isset($_SESSION['user']) && isset($_COOKIE['user'])){
    $_SESSION['user'] = $_COOKIE['user'];
    header("Location: ../HTML/Home.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])){
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if(empty($email) || empty($password)){
        $error = 'Please enter both email and password.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = 'Please enter a valid email address.';
    } else {
        // Demo credentials - replace with database query in production
        $valid_email = 'admin@apex.com';
        $valid_password = 'admin123';
        
        if($email === $valid_email && $password === $valid_password){
            $_SESSION['user'] = $email;
            $_SESSION['login_time'] = time();
            
            // Handle "Remember me"
            if(isset($_POST['remember'])){
                setcookie('user', $email, time() + (30 * 24 * 60 * 60), '/');
            }
            
            header("Location: ../HTML/Home.html");
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// If form was submitted with error, return to form with error message
if(isset($error)){
    $form_error = $error;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../CSS/Home.css" />
  <link rel="stylesheet" href="../CSS/LogIn.css" />
  <title>Apex Fuel — Log In</title>
</head>
<body>
  <!-- Navigation bar (same as home) -->
  <header>
    <div class="nav">
      <a href="./Home.php"><img id="logo" src="../Images/Logo.png" alt="Apex Fuel logo"></a>
      <form class="srch" action="./Search.php" method="GET">
        <input type="text" name="q" id="SearchBar" placeholder="Search products...">
        <button id="search" type="submit"><img src="../Images/search_24dp_000000_FILL0_wght400_GRAD0_opsz24.png" alt="search"></button>
      </form>
      <div class="buttons">
        <button type="button">Protein</button>
        <button type="button">Pre Workout</button>
        <button type="button">Vitamins</button>
        <button type="button">Supplements</button>

        <button id="favorites" type="button"><img src="../Images/favorite_24dp_000000_FILL0_wght400_GRAD0_opsz24.png" alt="favorites"></button>
        <button id="cart" type="button"><img src="../Images/shopping_cart_24dp_000000_FILL0_wght400_GRAD0_opsz24.png" alt="cart"></button>
        <a id="account" href="#" data-target="./Account.php" class="icon-link" aria-label="Account"><img src="../Images/account_circle_24dp_000000_FILL0_wght400_GRAD0_opsz24.png" alt="account"></a>
      </div>
    </div>
  </header>
  <main>
    <section class="auth-card" role="main">
      <img class="brand-logo" src="../Images/Logo.png" alt="Apex Fuel logo">

      <h1 class="title">Log in to Apex Fuel</h1>

      <form id=\"login-form\" action=\"./login.php\" method=\"post\" novalidate>
        <?php if(isset($form_error)): ?>
          <div id="form-error" class="error" style="margin-bottom:15px; padding:10px; background:#ffe0e0; color:#d32f2f; border-radius:4px;">
            <?php echo htmlspecialchars($form_error); ?>
          </div>
        <?php endif; ?>
        
        <div class="form-row">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" autocomplete="email" required>
        </div>

        <div class="form-row">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>

        <div class="form-row helper-row">
          <label class="remember"><input id="remember" name="remember" type="checkbox"> Remember me</label>
        </div>

        <div class="actions">
          <button class="btn" type="submit">Log in</button>
          <a class="link-muted" href="./Home.php">Back to shop</a>
        </div>
      </form>

      <p class="signup-cta">Don't have an account? <a class="link-muted" href="./Register.php">Sign up</a></p>
    </section>
  </main>



  <script src="../JS/Home.js"></script>
  <script src="../JS/Login.js"></script>


</body>
</html>