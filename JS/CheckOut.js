document.addEventListener('DOMContentLoaded', () => {
  console.log('CheckOut.js loaded');
  
  const orderItemsEl = document.getElementById('orderItems');
  const subtotalEl = document.getElementById('summarySubtotal');
  const shippingEl = document.getElementById('summaryShipping');
  const taxEl = document.getElementById('summaryTax');
  const totalEl = document.getElementById('summaryTotal');
  const statusEl = document.getElementById('checkoutStatus');
  const confirmation = document.getElementById('orderConfirmation');
  const ocClose = document.getElementById('oc-close');
  const placeOrderBtn = document.getElementById('placeOrder');
  const cancelOrderBtn = document.getElementById('cancelOrder');
  const ocId = document.getElementById('oc-id');
  const ocMessage = document.getElementById('oc-message');

  // Read cart from localStorage
  function readCart() {
    try { 
      return JSON.parse(localStorage.getItem('cart') || '[]'); 
    } catch(e) { 
      console.error('Error reading cart:', e);
      return []; 
    }
  }

  // Calculate totals
  function calcTotals(items) {
    const subtotal = items.reduce((sum, item) => sum + (Number(item.price) || 0), 0);
    const shipping = subtotal > 0 ? 4.99 : 0;
    const tax = parseFloat((subtotal * 0.07).toFixed(2));
    const total = parseFloat((subtotal + shipping + tax).toFixed(2));
    
    return { subtotal, shipping, tax, total };
  }

  // Render order items and totals
  function render() {
    const items = readCart();
    orderItemsEl.innerHTML = '';
    
    if (!items || items.length === 0) {
      orderItemsEl.innerHTML = '<li class="muted" style="text-align:center;padding:20px;">Your cart is empty.</li>';
      // Disable place order button
      if (placeOrderBtn) placeOrderBtn.disabled = true;
    } else {
      items.forEach((item, index) => {
        const li = document.createElement('li');
        li.innerHTML = `
          <div class="oi-left">
            <div class="oi-name">${escapeHtml(item.name || 'Product')}</div>
            <div class="muted">$${Number(item.price || 0).toFixed(2)} × ${item.quantity || 1}</div>
          </div>
          <div class="oi-right">$${(Number(item.price || 0) * (item.quantity || 1)).toFixed(2)}</div>
        `;
        orderItemsEl.appendChild(li);
      });
      // Enable place order button
      if (placeOrderBtn) placeOrderBtn.disabled = false;
    }
    
    const totals = calcTotals(items);
    if (subtotalEl) subtotalEl.textContent = totals.subtotal.toFixed(2);
    if (shippingEl) shippingEl.textContent = totals.shipping.toFixed(2);
    if (taxEl) taxEl.textContent = totals.tax.toFixed(2);
    if (totalEl) totalEl.textContent = totals.total.toFixed(2);
  }

  // HTML escape function
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Prefill form with user data from localStorage
  function prefillForm() {
    try {
      const profile = JSON.parse(localStorage.getItem('apexProfile') || '{}');
      if (profile) {
        const nameInput = document.getElementById('co-name');
        const emailInput = document.getElementById('co-email');
        const phoneInput = document.getElementById('co-phone');
        const addressInput = document.getElementById('co-address');
        
        if (nameInput && profile.firstName && profile.lastName) {
          nameInput.value = `${profile.firstName} ${profile.lastName}`.trim();
        }
        if (emailInput && profile.email) {
          emailInput.value = profile.email;
        }
        if (phoneInput && profile.phone) {
          phoneInput.value = profile.phone;
        }
        if (addressInput && profile.address) {
          addressInput.value = profile.address;
        }
      }
    } catch (e) {
      console.error('Error pre-filling form:', e);
    }
  }

  // Validate form
  function validateForm() {
    const name = document.getElementById('co-name')?.value.trim();
    const email = document.getElementById('co-email')?.value.trim();
    const address = document.getElementById('co-address')?.value.trim();
    const city = document.getElementById('co-city')?.value.trim();
    const postal = document.getElementById('co-postal')?.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!name) {
      showStatus('Please enter your full name', true);
      return false;
    }
    
    if (!email || !emailRegex.test(email)) {
      showStatus('Please enter a valid email address', true);
      return false;
    }
    
    if (!address) {
      showStatus('Please enter your shipping address', true);
      return false;
    }
    
    if (!city) {
      showStatus('Please enter your city', true);
      return false;
    }
    
    if (!postal) {
      showStatus('Please enter your postal code', true);
      return false;
    }
    
    return true;
  }

  // Show status message
  function showStatus(msg, isError = false) {
    if (!statusEl) return;
    
    statusEl.textContent = msg;
    statusEl.style.color = isError ? '#f44336' : '#4CAF50';
    statusEl.style.background = isError ? 'rgba(244,67,54,0.1)' : 'rgba(76,175,80,0.1)';
    statusEl.style.borderColor = isError ? 'rgba(244,67,54,0.3)' : 'rgba(76,175,80,0.3)';
    statusEl.style.padding = '10px';
    statusEl.style.borderRadius = '4px';
    statusEl.style.margin = '10px 0';
    
    setTimeout(() => {
      if (statusEl.textContent === msg) {
        statusEl.textContent = '';
        statusEl.style.background = '';
        statusEl.style.borderColor = '';
        statusEl.style.padding = '';
        statusEl.style.margin = '';
      }
    }, 5000);
  }

  // Show order confirmation
  function showOrderConfirmation(orderNumber, message) {
    if (ocId) ocId.textContent = orderNumber;
    if (ocMessage) ocMessage.textContent = message;
    if (confirmation) {
      confirmation.setAttribute('aria-hidden', 'false');
      confirmation.style.display = 'flex';
    }
  }

  // Hide order confirmation
  function hideOrderConfirmation() {
    if (confirmation) {
      confirmation.setAttribute('aria-hidden', 'true');
      confirmation.style.display = 'none';
    }
  }

  // Place order
  async function placeOrder() {
    if (!validateForm()) return;
    
    const items = readCart();
    if (items.length === 0) {
        showStatus('Your cart is empty', true);
        return;
    }
    
    // Show processing status
    showStatus('Processing your order...', false);
    if (placeOrderBtn) placeOrderBtn.disabled = true;
    
    // Collect form data
    const formData = {
        shipping: {
            name: document.getElementById('co-name')?.value.trim(),
            email: document.getElementById('co-email')?.value.trim(),
            phone: document.getElementById('co-phone')?.value.trim(),
            address: document.getElementById('co-address')?.value.trim(),
            city: document.getElementById('co-city')?.value.trim(),
            postal: document.getElementById('co-postal')?.value.trim(),
            country: document.getElementById('co-country')?.value
        },
        payment: {
            method: document.getElementById('co-method')?.value
        },
        cart_items: items.map(item => ({
            product_id: item.id || 0,
            name: item.name,
            price: parseFloat(item.price),
            quantity: item.quantity || 1
        }))
    };
    
    console.log('Sending order data:', formData);
    
    try {
        // Use absolute URL to ensure correct path
        const baseUrl = window.location.origin;
        const shopPath = '/Shop'; // Your shop directory
        const endpoint = `${baseUrl}${shopPath}/PHP/checkout_process.php`;
        
        console.log('Calling endpoint:', endpoint);
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Response data:', result);
        
        if (result.success) {
            // Clear cart
            localStorage.removeItem('cart');
            
            // Show success message
            showOrderConfirmation(result.order_number, result.message);
            
            // Update UI
            render();
            
            // Clear form
            document.getElementById('checkoutForm')?.reset();
        } else {
            showStatus(result.message || 'Order failed. Please try again.', true);
            if (placeOrderBtn) placeOrderBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error details:', error);
        showStatus(`Error: ${error.message}. Please check the console for details.`, true);
        if (placeOrderBtn) placeOrderBtn.disabled = false;
    }
}

  // Event Listeners
  if (placeOrderBtn) {
    placeOrderBtn.addEventListener('click', placeOrder);
  }
  
  if (cancelOrderBtn) {
    cancelOrderBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to cancel this order?')) {
        window.location.href = '../HTML/Home.php';
      }
    });
  }
  
  if (ocClose) {
    ocClose.addEventListener('click', () => {
      hideOrderConfirmation();
      window.location.href = '../HTML/Home.php';
    });
  }

  // Close confirmation when clicking outside
  if (confirmation) {
    confirmation.addEventListener('click', (e) => {
      if (e.target === confirmation) {
        hideOrderConfirmation();
        window.location.href = '../HTML/Home.php';
      }
    });
  }

  // Payment method toggle
  const paymentMethod = document.getElementById('co-method');
  const cardFields = document.getElementById('cardFields');
  
  if (paymentMethod && cardFields) {
    paymentMethod.addEventListener('change', function() {
      if (this.value === 'card') {
        cardFields.style.display = 'block';
      } else {
        cardFields.style.display = 'none';
      }
    });
    
    // Initial state
    if (paymentMethod.value === 'card') {
      cardFields.style.display = 'block';
    } else {
      cardFields.style.display = 'none';
    }
  }

  // Initialize
  render();
  prefillForm();
  
  // Auto-fill city based on postal code (example)
  const postalInput = document.getElementById('co-postal');
  if (postalInput) {
    postalInput.addEventListener('blur', function() {
      // This is a simple example - in production, you'd use a postal code API
      const cityInput = document.getElementById('co-city');
      if (cityInput && !cityInput.value) {
        // Simple mapping for demo
        const postalMappings = {
          '10001': 'New York',
          '90001': 'Los Angeles',
          '60601': 'Chicago',
          '77001': 'Houston'
        };
        
        if (postalMappings[this.value]) {
          cityInput.value = postalMappings[this.value];
        }
      }
    });
  }
});