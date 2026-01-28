<?php
// admin/add_product.php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

$conn = getDBConnection();
$error = '';
$success = '';

// Get categories
$categories_result = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
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
    
    // Validation
    if (empty($name) || empty($price) || $category_id <= 0) {
        $error = 'Please fill in all required fields (Name, Price, Category)';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } elseif ($stock_quantity < 0) {
        $error = 'Stock quantity cannot be negative';
    } else {
        // Handle image upload
        $image_url = 'Images/placeholder.jpg'; // Default placeholder
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
                $image_url = 'Images/products/' . $file_name;
            } else {
                $error = 'Failed to upload image';
            }
        }
        
        if (empty($error)) {
            // Generate slug from name
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            
            // Ensure unique slug
            $counter = 1;
            $original_slug = $slug;
            while (true) {
                $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ?");
                $stmt->bind_param("s", $slug);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();
                
                if ($result->num_rows === 0) {
                    break;
                }
                $slug = $original_slug . '-' . $counter++;
            }
            
            // ✅ FIX: Check for duplicate SKU
            if (!empty($sku)) {
                $check_sku = $conn->prepare("SELECT id FROM products WHERE sku = ?");
                $check_sku->bind_param("s", $sku);
                $check_sku->execute();
                $sku_result = $check_sku->get_result();
                
                if ($sku_result->num_rows > 0) {
                    // SKU already exists, append timestamp
                    $sku = $sku . '-' . time();
                }
                $check_sku->close();
            } else {
                // Generate unique SKU if empty
                $base_sku = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($name, 0, 8)));
                $sku = $base_sku ? $base_sku . '-' . time() : 'PROD-' . time();
            }
            
            // Insert product
            $stmt = $conn->prepare("
                INSERT INTO products (
                    category_id, name, slug, description, short_description, 
                    price, image_url, stock_quantity, sku, weight, is_featured,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->bind_param(
                "issssdsisdi",
                $category_id, $name, $slug, $description, $short_description,
                $price, $image_url, $stock_quantity, $sku, $weight, $is_featured
            );
            
            if ($stmt->execute()) {
                $product_id = $conn->insert_id;
                
                // Log the action
                log_admin_action($_SESSION['user_id'], 'INSERT', 'products', $product_id, [
                    'product' => $name,
                    'price' => $price,
                    'category_id' => $category_id
                ]);
                
                $success = 'Product added successfully!';
                $_POST = []; // Clear form
            } else {
                $error = 'Failed to add product: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Admin Panel</title>
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
            <h1>➕ Add New Product</h1>
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
            <div class="admin-form-group">
                <label for="name" class="required">Product Name</label>
                <input type="text" id="name" name="name" class="admin-form-control" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                       required>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="category_id" class="required">Category</label>
                    <select id="category_id" name="category_id" class="admin-form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="admin-form-group">
                    <label for="sku">SKU (Stock Keeping Unit)</label>
                    <input type="text" id="sku" name="sku" class="admin-form-control" 
                           value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                    <span class="admin-form-hint">Leave empty for auto-generated SKU</span>
                </div>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="price" class="required">Price ($)</label>
                    <input type="number" id="price" name="price" class="admin-form-control" 
                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                           step="0.01" min="0.01" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" class="admin-form-control" 
                           value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>" 
                           min="0">
                </div>
                
                <div class="admin-form-group">
                    <label for="weight">Weight (kg)</label>
                    <input type="number" id="weight" name="weight" class="admin-form-control" 
                           value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>" 
                           step="0.01" min="0">
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="short_description">Short Description</label>
                <textarea id="short_description" name="short_description" class="admin-form-control" 
                          rows="2"><?php echo htmlspecialchars($_POST['short_description'] ?? ''); ?></textarea>
                <span class="admin-form-hint">Brief description shown in product listings</span>
            </div>
            
            <div class="admin-form-group">
                <label for="description">Full Description</label>
                <textarea id="description" name="description" class="admin-form-control" 
                          rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <span class="admin-form-hint">Detailed product description</span>
            </div>
            
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" class="admin-form-control" accept="image/*">
                    <span class="admin-form-hint">Max 5MB. JPG, PNG, GIF, WebP allowed</span>
                    <img id="imagePreview" class="admin-image-preview" alt="Image preview">
                </div>
                
                <div class="admin-form-group">
                    <div class="admin-checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1"
                               <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                        <label for="is_featured">Featured Product</label>
                    </div>
                    <span class="admin-form-hint">Featured products appear on homepage</span>
                </div>
            </div>
            
            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">➕ Add Product</button>
                <a href="products.php" class="admin-btn admin-btn-secondary">Cancel</a>
                <button type="reset" class="admin-btn admin-btn-secondary">Reset Form</button>
            </div>
        </form>
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
    
    // Auto-generate SKU
    document.getElementById('name').addEventListener('blur', function() {
        const skuInput = document.getElementById('sku');
        if (!skuInput.value.trim()) {
            const name = this.value.trim();
            if (name) {
                // Generate simple SKU from name
                const sku = name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 8);
                skuInput.value = sku || 'PROD-' + Math.floor(Math.random() * 10000);
            }
        }
    });
    </script>
</body>
</html>

<?php 
// Close connections
if (isset($categories_result)) $categories_result->free();
$conn->close();
?>