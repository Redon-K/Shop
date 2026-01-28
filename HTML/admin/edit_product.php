<?php
// admin/edit_product.php
session_start();


// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

try {
    $conn = getDBConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = '';
$success = '';

// Get product ID
$product_id = intval($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}

// Get product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $product_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Get categories
$categories_result = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
if (!$categories_result) {
    die("Categories query failed: " . $conn->error);
}
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = sanitize_input($_POST['name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $short_description = sanitize_input($_POST['short_description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $sku = sanitize_input($_POST['sku'] ?? '');
    $weight = floatval($_POST['weight'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name) || empty($price) || $category_id <= 0) {
        $error = 'Please fill in all required fields (Name, Price, Category)';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } elseif ($stock_quantity < 0) {
        $error = 'Stock quantity cannot be negative';
    } else {
        // ✅ FIX: Check for duplicate SKU (only if SKU changed)
        if (!empty($sku) && $sku !== $product['sku']) {
            $check_sku = $conn->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
            if ($check_sku) {
                $check_sku->bind_param("si", $sku, $product_id);
                $check_sku->execute();
                $sku_result = $check_sku->get_result();
                
                if ($sku_result->num_rows > 0) {
                    $error = 'SKU already exists. Please use a different SKU.';
                }
                $check_sku->close();
            } else {
                $error = 'Failed to prepare SKU check query: ' . $conn->error;
            }
        }
        
        if (empty($error)) {
            // Handle image upload
            $image_url = $product['image_url'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../Images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $file_path = $upload_dir . $file_name;
                
                // Check file type
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_types)) {
                    $error = 'Only JPG, PNG, GIF, and WebP images are allowed';
                } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB
                    $error = 'Image size must be less than 5MB';
                } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                    // Delete old image if it exists and is not a placeholder
                    $old_image = $product['image_url'];
                    if ($old_image && !str_contains($old_image, 'placeholder') && file_exists('../../' . $old_image)) {
                        unlink('../../' . $old_image);
                    }
                    $image_url = 'Images/products/' . $file_name;
                } else {
                    $error = 'Failed to upload image';
                }
            }
            
            if (empty($error)) {
                // Update product - Debug the values
                error_log("Updating product: ID=$product_id, Name=$name, Price=$price, Category=$category_id, SKU=$sku");
                
                $stmt = $conn->prepare("
                    UPDATE products SET 
                        category_id = ?, 
                        name = ?, 
                        description = ?, 
                        short_description = ?, 
                        price = ?, 
                        image_url = ?, 
                        stock_quantity = ?, 
                        sku = ?, 
                        weight = ?, 
                        is_featured = ?, 
                        is_active = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                if (!$stmt) {
                    $error = 'Prepare failed: ' . $conn->error;
                } else {
                    // Debug bind_param
                    $bind_result = $stmt->bind_param(
                        "isssdsisdiii",
                        $category_id, $name, $description, $short_description,
                        $price, $image_url, $stock_quantity, $sku, $weight, 
                        $is_featured, $is_active, $product_id
                    );
                    
                    if (!$bind_result) {
                        $error = 'Bind failed: ' . $stmt->error;
                    } elseif (!$stmt->execute()) {
                        $error = 'Execute failed: ' . $stmt->error;
                    } else {
                        // Log the action
                        log_admin_action($_SESSION['user_id'], 'UPDATE', 'products', $product_id, [
                            'product' => $name,
                            'price' => $price,
                            'category_id' => $category_id
                        ]);
                        
                        $success = 'Product updated successfully!';
                        // Refresh product data
                        $refresh_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                        $refresh_stmt->bind_param("i", $product_id);
                        $refresh_stmt->execute();
                        $refresh_result = $refresh_stmt->get_result();
                        $product = $refresh_result->fetch_assoc();
                        $refresh_stmt->close();
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Panel</title>
    <link rel="stylesheet" href="../../CSS/Home.css">
    <!-- Include admin CSS files -->
    <link rel="stylesheet" href="../../CSS/admin/admin_common.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_forms.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_tables.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_charts.css">
</head>
<body>
    <!-- Include navbar component -->
    <?php include 'admin_navbar.php'; ?>

    <div class="admin-container">
        <div class="admin-header">
            <h1>✏️ Edit Product</h1>
            <a href="products.php" class="admin-back-btn">← Back to Products</a>
        </div>
        
        <?php if ($success): ?>
            <div class="admin-message admin-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="admin-message admin-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="admin-form" id="productForm">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            
            <div class="admin-form-group">
                <label for="name" class="required">Product Name</label>
                <input type="text" id="name" name="name" class="admin-form-control" 
                       value="<?php echo htmlspecialchars($product['name']); ?>" 
                       required>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="category_id" class="required">Category</label>
                    <select id="category_id" name="category_id" class="admin-form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="admin-form-group">
                    <label for="sku">SKU (Stock Keeping Unit)</label>
                    <input type="text" id="sku" name="sku" class="admin-form-control" 
                           value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                    <span class="admin-form-hint">Leave empty for auto-generated SKU</span>
                </div>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="price" class="required">Price ($)</label>
                    <input type="number" id="price" name="price" class="admin-form-control" 
                           value="<?php echo number_format($product['price'], 2); ?>" 
                           step="0.01" min="0.01" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" class="admin-form-control" 
                           value="<?php echo $product['stock_quantity']; ?>" 
                           min="0">
                    <span class="admin-form-hint">
                        Current: <?php echo $product['stock_quantity']; ?> units
                        <span class="admin-stock-badge <?php echo $product['stock_quantity'] == 0 ? 'admin-stock-danger' : 'admin-stock-success'; ?>">
                            <?php echo $product['stock_quantity'] == 0 ? 'Out of Stock' : ($product['stock_quantity'] < 10 ? 'Low Stock' : 'In Stock'); ?>
                        </span>
                    </span>
                </div>
                
                <div class="admin-form-group">
                    <label for="weight">Weight (kg)</label>
                    <input type="number" id="weight" name="weight" class="admin-form-control" 
                           value="<?php echo htmlspecialchars($product['weight'] ?? ''); ?>" 
                           step="0.01" min="0">
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="short_description">Short Description</label>
                <textarea id="short_description" name="short_description" class="admin-form-control" 
                          rows="2"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                <span class="admin-form-hint">Brief description shown in product listings</span>
            </div>
            
            <div class="admin-form-group">
                <label for="description">Full Description</label>
                <textarea id="description" name="description" class="admin-form-control" 
                          rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                <span class="admin-form-hint">Detailed product description</span>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="image">Product Image</label>
                    <?php if (!empty($product['image_url'])): ?>
                        <div class="admin-current-image">
                            <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="Current image" 
                                 onerror="this.src='../../Images/placeholder.jpg'">
                            <span class="admin-form-hint">Current image</span>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" class="admin-form-control" accept="image/*">
                    <span class="admin-form-hint">Leave empty to keep current image. Max 5MB. JPG, PNG, GIF, WebP allowed</span>
                    <img id="imagePreview" class="admin-image-preview" alt="New image preview">
                </div>
                
                <div class="admin-form-group">
                    <div class="admin-checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1"
                               <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                        <label for="is_featured">Featured Product</label>
                    </div>
                    <span class="admin-form-hint">Featured products appear on homepage</span>
                    
                    <div class="admin-checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                        <label for="is_active">Active Product</label>
                    </div>
                    <span class="admin-form-hint">Inactive products won't be displayed</span>
                </div>
            </div>
            
            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">💾 Save Changes</button>
                <a href="products.php" class="admin-btn admin-btn-secondary">Cancel</a>
                <a href="products.php?delete=<?php echo $product_id; ?>" 
                   class="admin-btn admin-btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this product? This will mark it as inactive.')">Delete</a>
            </div>
        </form>
        
        <div style="margin-top: 30px; padding: 20px; background: var(--admin-card); border-radius: 8px;">
            <h3 style="color: rgba(248,250,252,0.9); margin-bottom: 15px;">📊 Product Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Created:</strong><br>
                    <?php echo date('F j, Y, g:i a', strtotime($product['created_at'])); ?>
                </div>
                <div>
                    <strong>Last Updated:</strong><br>
                    <?php echo date('F j, Y, g:i a', strtotime($product['updated_at'])); ?>
                </div>
                <div>
                    <strong>Slug:</strong><br>
                    <?php echo htmlspecialchars($product['slug']); ?>
                </div>
                <div>
                    <strong>Stock Value:</strong><br>
                    $<?php echo number_format($product['stock_quantity'] * $product['price'], 2); ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Image preview
    document.getElementById('image').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            preview.src = '';
        }
    });
    
    // Form validation
    document.getElementById('productForm').addEventListener('submit', function(e) {
        const price = parseFloat(document.getElementById('price').value);
        const stock = parseInt(document.getElementById('stock_quantity').value);
        
        if (price <= 0) {
            e.preventDefault();
            alert('Price must be greater than 0');
            return false;
        }
        
        if (stock < 0) {
            e.preventDefault();
            alert('Stock quantity cannot be negative');
            return false;
        }
        
        // Check file size client-side
        const imageInput = document.getElementById('image');
        if (imageInput.files.length > 0) {
            const fileSize = imageInput.files[0].size / 1024 / 1024; // in MB
            if (fileSize > 5) {
                e.preventDefault();
                alert('Image size must be less than 5MB');
                return false;
            }
        }
        
        return true;
    });
    </script>
</body>
</html>

<?php 
// Close connections
if (isset($categories_result)) $categories_result->free();
$conn->close();
?>