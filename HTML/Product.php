<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../CSS/Home.css" />
  <link rel="stylesheet" href="../CSS/Product.css" />
  <title>Product — Apex Fuel</title>
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

  <main class="product-page">
    <div class="product-container">
      <section class="product-gallery">
        <div class="gallery-viewport">
          <img id="main-product-img" src="../Images/Prote+na+whey+OPTIMUN+NUTRITION+Gold+Standard+chocolate+908+g-1159974388.jpg" alt="Product image">
        </div>
        <!-- thumbnails removed for cleaner product presentation -->
      </section>

      <aside class="product-info">
        <h2 class="prod-brand">Apex</h2>
        <h1 class="prod-title">Premium Whey Protein — Chocolate</h1>
        

        <div class="size-select">
          <div class="size-label">Select Size</div>
          <div class="sizes">
            <button class="size">1 kg</button>
            <button class="size">2 kg</button>
            <button class="size">5 kg</button>
          </div>
        </div>

        <ul class="quick-links">
          <li>Free Shipping & Returns</li>
          <li>In stock</li>
        </ul>
        <div class="price-row">
          <button class="btn add">ADD TO CART</button>
          <button class="btn buy">BUY NOW</button>
          <div class="price">$49</div>
        </div>
      </aside>
    </div>

    <div class="product-tabs">
      <nav class="tabs">
        <button class="tab active">Description</button>
        <button class="tab">Size & Fit</button>
        <button class="tab">Reviews</button>
      </nav>
      <section class="tab-panel">
        <div class="tab-content">
          <p>This premium whey protein is crafted for fast recovery and clean gains. Delicious chocolate flavor with 24g protein per serving.</p>
        </div>
      </section>
    </div>
  </main>

  <script src="../JS/Home.js"></script>
  <script src="../JS/Product.js"></script>
</body>
</html>
