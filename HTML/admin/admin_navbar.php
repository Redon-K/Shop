<?php
// admin_navbar.php
// This file should be included in all admin pages
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Navigation -->
<header>
    <div class="nav">
        <a href="../Home.php"><img id="logo" src="../../Images/Logo.png" alt="Apex Fuel logo"></a>
        <div class="buttons">
            <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="products.php" class="nav-link <?php echo in_array($current_page, ['products.php', 'add_product.php', 'edit_product.php']) ? 'active' : ''; ?>">Products</a>
            <a href="categories.php" class="nav-link <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">Categories</a>
            <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">Orders</a>
            <a href="customers.php" class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">Customers</a>
            <a href="inventory.php" class="nav-link <?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">Inventory</a>
            <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">Reports</a>
            <a href="../Home.php" class="nav-link">Back to Shop</a>
            <a href="../../PHP/logout.php" class="nav-link">Logout</a>
        </div>
    </div>
</header>

<style>
/* Navbar specific styles */
.nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #010409;
    padding: 0 20px;
    height: 70px;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.nav #logo {
    height: 50px;
    width: auto;
}

.nav .buttons {
    display: flex;
    gap: 10px;
}

.nav-link {
    padding: 10px 20px;
    text-decoration: none;
    color: #f8fafc;
    border-radius: 6px;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.nav-link:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.1);
}

.nav-link.active {
    background: rgba(87,87,243,0.3);
    border-color: rgba(87,87,243,0.5);
    color: #f8fafc;
}
</style>