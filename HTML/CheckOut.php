<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../CSS/Home.css" />
  <link rel="stylesheet" href="../CSS/CheckOut.css" />
  <title>Checkout — Apex Fuel</title>
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

  <main class="checkout-page" style="max-width:1100px;margin:28px auto;padding:0 20px;">
    <div class="checkout-container">
      <section class="checkout-form">
        <h2>Shipping & Payment</h2>
        <form id="checkoutForm" novalidate>
          <fieldset class="group">
            <legend>Contact</legend>
            <label>Full name<input id="co-name" type="text" placeholder="Jane Doe" required></label>
            <label>Email<input id="co-email" type="email" placeholder="you@example.com" required></label>
            <label>Phone<input id="co-phone" type="tel" placeholder="+1 555 555 5555"></label>
          </fieldset>

          <fieldset class="group">
            <legend>Shipping address</legend>
            <label>Address<textarea id="co-address" rows="2" placeholder="Street, Apt / Suite" required></textarea></label>
            <div class="row">
              <label>City<input id="co-city" type="text" placeholder="City" required></label>
              <label>Postal code<input id="co-postal" type="text" placeholder="Postal / ZIP" required></label>
            </div>
            <label>Country<select id="co-country"><option>United States</option><option>United Kingdom</option><option>Germany</option><option>Other</option></select></label>
          </fieldset>

          <fieldset class="group">
            <legend>Payment</legend>
            <label>Method<select id="co-method"><option value="card">Card (demo)</option><option value="paypal">PayPal</option></select></label>
            <div id="cardFields">
              <label>Card number<input id="co-card" type="text" placeholder="1234 5678 9012 3456" inputmode="numeric"></label>
              <div class="row">
                <label>Expiry<input id="co-exp" type="text" placeholder="MM/YY"></label>
                <label>CVV<input id="co-cvv" type="text" placeholder="123" inputmode="numeric"></label>
              </div>
            </div>
          </fieldset>

          <div class="form-actions">
            <button id="placeOrder" class="btn btn-primary" type="button">Place Order</button>
            <button id="cancelOrder" class="btn btn-ghost" type="button">Cancel</button>
          </div>
          <div id="checkoutStatus" role="status" aria-live="polite" style="margin-top:12px"></div>
        </form>
      </section>

      <aside class="order-summary">
        <h2>Order Summary</h2>
        <div class="summary-inner">
          <ul class="order-items" id="orderItems"></ul>
          <div class="summary-row"><span>Subtotal</span><strong>$<span id="summarySubtotal">0.00</span></strong></div>
          <div class="summary-row"><span>Shipping</span><strong>$<span id="summaryShipping">0.00</span></strong></div>
          <div class="summary-row"><span>Tax</span><strong>$<span id="summaryTax">0.00</span></strong></div>
          <div class="summary-total"><span>Total</span><strong>$<span id="summaryTotal">0.00</span></strong></div>
          <small class="muted">Items are stored locally in your browser for this demo.</small>
        </div>
      </aside>
    </div>

    <!-- order confirmation overlay -->
    <div id="orderConfirmation" class="order-confirmation" aria-hidden="true">
      <div class="oc-card">
        <h3>Thank you — order placed!</h3>
        <p id="oc-message">Your order <strong id="oc-id">#</strong> has been received.</p>
        <div style="margin-top:12px;text-align:right"><button id="oc-close" class="btn btn-primary">Continue shopping</button></div>
      </div>
    </div>
  </main>

  <script src="../JS/Home.js"></script>
  <script src="../JS/CheckOut.js"></script>
</body>
</html>
