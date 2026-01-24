<?php
// Get current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Navigation bar -->
<header>
    <div class="nav">
        <a href="./Home.php"><img id="logo" src="../Images/Logo.png" alt="Apex Fuel logo"></a>
        <form class="srch" action="./Search.php" method="GET">
            <input type="text" name="q" id="SearchBar" placeholder="Search products..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button id="search" type="submit"><img src="../Images/search_24dp_000000_FILL0_wght400_GRAD0_opsz24.png" alt="search"></button>
        </form>
        <div class="buttons">
            <a class="nav-link" href="./Home.php#Proteins">Protein</a>
            <a class="nav-link" href="./Home.php#Pre">Pre Workout</a>
            <a class="nav-link" href="./Home.php#Vitamins">Vitamins</a>
            <a class="nav-link" href="./Home.php#Supplements">Supplements</a>

            <button id="favorites" type="button"><img src="../Images/favorite_24dp_000000_FILL0_wght400_GRAD0_opsz24.png" alt="favorites"></button>
            <button id="cart" type="button"><img src="../Images/shopping_cart_24dp_000000_FILL0_wght400_GRAD0_opsz24.png" alt="cart"></button>
            <a id="account" href="#" class="icon-link" aria-label="Account"><img src="../Images/account_circle_24dp_000000_FILL0_wght400_GRAD0_opsz24.png" alt="account"></a>
        </div>
    </div>
</header>