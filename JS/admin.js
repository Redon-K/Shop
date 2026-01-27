// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });

    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const mainContent = document.querySelector('.admin-main');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-show');
            mainContent.classList.toggle('sidebar-hidden');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('mobile-show');
                mainContent.classList.remove('sidebar-hidden');
            }
        }
    });

    // Auto-refresh dashboard stats every 60 seconds
    if (window.location.pathname.includes('index.php')) {
        setInterval(refreshDashboardStats, 60000);
    }

    // Initialize date pickers
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            const today = new Date().toISOString().split('T')[0];
            input.value = today;
        }
    });

    // Handle form validation
    const forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Add Bootstrap validation classes
                const inputs = this.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (!input.checkValidity()) {
                        input.classList.add('is-invalid');
                        
                        // Create or show error message
                        let errorDiv = input.nextElementSibling;
                        if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                            errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            input.parentNode.insertBefore(errorDiv, input.nextSibling);
                        }
                        
                        if (input.validity.valueMissing) {
                            errorDiv.textContent = 'This field is required';
                        } else if (input.validity.typeMismatch) {
                            errorDiv.textContent = 'Please enter a valid value';
                        } else if (input.validity.patternMismatch) {
                            errorDiv.textContent = 'Please match the requested format';
                        } else if (input.validity.tooShort) {
                            errorDiv.textContent = `Minimum length is ${input.minLength} characters`;
                        } else if (input.validity.tooLong) {
                            errorDiv.textContent = `Maximum length is ${input.maxLength} characters`;
                        } else if (input.validity.rangeUnderflow) {
                            errorDiv.textContent = `Minimum value is ${input.min}`;
                        } else if (input.validity.rangeOverflow) {
                            errorDiv.textContent = `Maximum value is ${input.max}`;
                        }
                    } else {
                        input.classList.remove('is-invalid');
                        const errorDiv = input.nextElementSibling;
                        if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                            errorDiv.textContent = '';
                        }
                    }
                });
                
                this.classList.add('was-validated');
            }
        });
    });

    // Remove validation classes when user starts typing
    const formInputs = document.querySelectorAll('form input, form select, form textarea');
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const errorDiv = this.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                errorDiv.textContent = '';
            }
        });
    });

    // Confirm before deleting
    const deleteButtons = document.querySelectorAll('.confirm-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Auto-calculate total price
    const priceInputs = document.querySelectorAll('input[name="price"], input[name="quantity"]');
    priceInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // Search with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    }

    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }

    // Handle image preview
    const imageInputs = document.querySelectorAll('input[type="file"][accept^="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(`${input.id}-preview`);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Auto-generate SKU
    const productNameInput = document.getElementById('productName');
    const skuInput = document.getElementById('sku');
    if (productNameInput && skuInput) {
        productNameInput.addEventListener('blur', function() {
            if (!skuInput.value) {
                const sku = this.value
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .substring(0, 10)
                    + '-' + Math.random().toString(36).substr(2, 5).toUpperCase();
                skuInput.value = sku;
            }
        });
    }

    // Handle tab switching with URL hash
    const tabs = document.querySelectorAll('[data-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            window.location.hash = target;
            
            // Show the tab content
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.querySelector(target).classList.add('show', 'active');
            
            // Update active tab
            this.parentNode.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Restore tab from URL hash
    if (window.location.hash) {
        const hash = window.location.hash;
        const tab = document.querySelector(`[href="${hash}"]`);
        if (tab) {
            tab.click();
        }
    }

    // Initialize sortable tables
    const sortableTables = document.querySelectorAll('.sortable-table th[data-sort]');
    sortableTables.forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const table = this.closest('table');
            const column = this.getAttribute('data-sort');
            const direction = this.getAttribute('data-direction') === 'asc' ? 'desc' : 'asc';
            
            // Update all headers
            table.querySelectorAll('th[data-sort]').forEach(header => {
                header.removeAttribute('data-direction');
                header.classList.remove('sort-asc', 'sort-desc');
            });
            
            // Update current header
            this.setAttribute('data-direction', direction);
            this.classList.add(`sort-${direction}`);
            
            // Sort the table
            sortTable(table, column, direction);
        });
    });

    // Export data functionality
    const exportButtons = document.querySelectorAll('.export-btn');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const format = this.getAttribute('data-format');
            const tableId = this.getAttribute('data-table');
            exportTableToCSV(tableId, format);
        });
    });

    // Notification handling
    const notificationCloseButtons = document.querySelectorAll('.notification-close');
    notificationCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.notification').remove();
        });
    });

    // Auto-hide notifications after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.notification').forEach(notification => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        });
    }, 5000);

    // Initialize rich text editors
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '.rich-text-editor',
            height: 300,
            menubar: false,
            plugins: 'link image lists code',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }'
        });
    }
});

// Function to refresh dashboard stats
function refreshDashboardStats() {
    fetch('../../PHP/api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stats cards
                document.querySelectorAll('.stat-card h3').forEach((card, index) => {
                    if (index === 0) card.textContent = data.products;
                    if (index === 1) card.textContent = data.orders;
                    if (index === 2) card.textContent = data.customers;
                    if (index === 3) card.textContent = '$' + data.revenue;
                });
                
                // Show notification
                showNotification('Stats updated', 'success');
            }
        })
        .catch(error => console.error('Error refreshing stats:', error));
}

// Function to sort table
function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`td:nth-child(${getColumnIndex(table, column)})`).textContent.trim();
        const bValue = b.querySelector(`td:nth-child(${getColumnIndex(table, column)})`).textContent.trim();
        
        // Try to compare as numbers
        const aNum = parseFloat(aValue.replace(/[^0-9.-]+/g, ''));
        const bNum = parseFloat(bValue.replace(/[^0-9.-]+/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // Otherwise compare as strings
        return direction === 'asc' 
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });
    
    // Remove existing rows
    rows.forEach(row => row.remove());
    
    // Append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

function getColumnIndex(table, column) {
    const headers = table.querySelectorAll('th');
    for (let i = 0; i < headers.length; i++) {
        if (headers[i].getAttribute('data-sort') === column) {
            return i + 1;
        }
    }
    return 1;
}

// Function to export table to CSV
function exportTableToCSV(tableId, format) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('th, td');
        
        cells.forEach(cell => {
            // Skip action buttons and checkboxes
            if (!cell.classList.contains('no-export')) {
                let text = cell.textContent.trim();
                text = text.replace(/"/g, '""'); // Escape quotes
                rowData.push(`"${text}"`);
            }
        });
        
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (navigator.msSaveBlob) {
        // For IE 10+
        navigator.msSaveBlob(blob, `export-${tableId}-${new Date().toISOString().slice(0,10)}.csv`);
    } else {
        // For modern browsers
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `export-${tableId}-${new Date().toISOString().slice(0,10)}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Function to calculate total price
function calculateTotal() {
    const price = parseFloat(document.querySelector('input[name="price"]')?.value) || 0;
    const quantity = parseFloat(document.querySelector('input[name="quantity"]')?.value) || 0;
    const total = price * quantity;
    
    const totalElement = document.getElementById('totalPrice');
    if (totalElement) {
        totalElement.textContent = total.toFixed(2);
    }
}

// Function to show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Add close event
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.remove();
    });
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Function to initialize charts
function initializeCharts() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales ($)',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Orders Chart
    const ordersCtx = document.getElementById('ordersChart');
    if (ordersCtx) {
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: ['Proteins', 'Pre-Workout', 'Vitamins', 'Supplements'],
                datasets: [{
                    label: 'Orders',
                    data: [65, 59, 80, 81],
                    backgroundColor: [
                        '#4361ee',
                        '#3a0ca3',
                        '#4cc9f0',
                        '#7209b7'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }
}

// Function to confirm logout
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../PHP/logout.php';
    }
}

// Function to copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard', 'success');
    }).catch(err => {
        console.error('Failed to copy: ', err);
        showNotification('Failed to copy', 'error');
    });
}

// Initialize when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminJS);
} else {
    initAdminJS();
}

function initAdminJS() {
    console.log('Admin JS initialized');
}