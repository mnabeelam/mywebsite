<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';

site_page(
    'shop',
    'IT Shop',
    'Browse recommended IT accessories and equipment. Place an order online — availability confirmed by email.',
    'shop.php',
    function () {
        site_page_intro(
            'Store',
            'IT Gadgets Shop',
            'Browse recommended IT accessories and equipment. Place an order online — I will confirm availability, price, and payment by email.'
        );
        ?>
<section class="section-block shop-section reveal theme-emerald">
  <div class="shop-topbar">
    <div id="shopStatus" class="shop-status" role="status" aria-live="polite">Loading products...</div>
    <button type="button" id="shopCartBtn" class="shop-cart-btn" aria-expanded="false" aria-controls="shopCartPanel" aria-label="Open shopping cart">
      <span class="shop-cart-icon" aria-hidden="true">🛒</span>
      Cart
      <span id="shopCartBadge" class="shop-cart-badge" hidden>0</span>
    </button>
  </div>
  <aside id="shopCartPanel" class="shop-cart-panel" hidden aria-label="Shopping cart">
    <div class="shop-cart-header">
      <h3>Your Cart</h3>
      <button type="button" id="shopCartClose" class="shop-cart-close" aria-label="Close cart">&times;</button>
    </div>
    <div id="shopCartItems" class="shop-cart-items">
      <p class="shop-cart-empty">Your cart is empty. Add products from the list below.</p>
    </div>
    <div class="shop-cart-footer">
      <p class="shop-cart-total">Total: <strong id="shopCartTotal">PKR 0</strong></p>
      <button type="button" id="shopCartCheckout" class="shop-cart-checkout" disabled>Checkout</button>
    </div>
  </aside>
  <div id="shopGrid" class="shop-grid"></div>
  <p class="shop-note">Orders are saved on this website. Payment is arranged after confirmation (bank transfer or cash on delivery). No card payment on the site.</p>
</section>

<div id="shopOrderModal" class="shop-modal" hidden aria-hidden="true">
  <div class="shop-modal-backdrop" data-shop-close></div>
  <div class="shop-modal-panel" role="dialog" aria-modal="true" aria-labelledby="shopModalTitle">
    <button type="button" class="shop-modal-close" data-shop-close aria-label="Close order form">&times;</button>
    <h3 id="shopModalTitle">Checkout</h3>
    <div id="shopModalProduct" class="shop-modal-product"></div>
    <form id="shopOrderForm" class="shop-order-form">
      <input type="hidden" id="shopProductId" name="product_id" value="">
      <input type="hidden" id="shopOrderItems" name="items" value="">
      <div id="shopSingleQtyWrap">
        <label for="shopQuantity">Quantity</label>
        <input id="shopQuantity" name="quantity" type="number" min="1" max="99" value="1" required>
      </div>
      <div>
        <label for="shopName">Your name</label>
        <input id="shopName" name="name" type="text" autocomplete="name" required maxlength="120">
      </div>
      <div>
        <label for="shopEmail">Your email</label>
        <input id="shopEmail" name="email" type="email" autocomplete="email" required maxlength="180">
      </div>
      <div>
        <label for="shopPhone">Phone / WhatsApp</label>
        <input id="shopPhone" name="phone" type="tel" autocomplete="tel" required maxlength="40">
      </div>
      <div>
        <label for="shopAddress">Delivery address</label>
        <textarea id="shopAddress" name="address" required maxlength="500"></textarea>
      </div>
      <div>
        <label for="shopNotes">Notes (optional)</label>
        <textarea id="shopNotes" name="notes" maxlength="1000"></textarea>
      </div>
      <button id="shopSubmit" type="submit">Submit Order</button>
      <p id="shopOrderFeedback" class="shop-order-feedback" role="status" aria-live="polite"></p>
    </form>
  </div>
</div>
        <?php
    }
);
