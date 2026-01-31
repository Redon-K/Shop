<?php
$page_title = 'Checkout — Apex Fuel';
$additional_css = ['CheckOut.css'];
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

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
                    <label>Method<select id="co-method"><option value="card">Credit Card</option><option value="paypal">PayPal</option><option value="cash">Cash on Delivery</option></select></label>
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
                <div class="summary-row"><span>Shipping</span><strong>$<span id="summaryShipping">4.99</span></strong></div>
                <div class="summary-row"><span>Tax (7%)</span><strong>$<span id="summaryTax">0.00</span></strong></div>
                <div class="summary-total"><span>Total</span><strong>$<span id="summaryTotal">0.00</span></strong></div>
                <small class="muted">Free shipping on orders over $50</small>
            </div>
        </aside>
    </div>

    <!-- Order confirmation overlay -->
    <div id="orderConfirmation" class="order-confirmation" aria-hidden="true">
        <div class="oc-card">
            <h3>🎉 Order Confirmed!</h3>
            <p id="oc-message">Your order has been placed successfully.</p>
            <p>Order Number: <strong id="oc-id">#</strong></p>
            <p>A confirmation email has been sent to your email address.</p>
            <div style="margin-top:20px;text-align:center">
                <button id="oc-close" class="btn btn-primary">Continue Shopping</button>
                <a href="orders.php" class="btn btn-secondary" style="margin-left:10px">View Orders</a>
            </div>
        </div>
    </div>
</main>

<script src="../JS/Home.js"></script>
<script src="../JS/CheckOut.js"></script>
</body>
</html>