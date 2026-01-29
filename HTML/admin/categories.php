<?php
// admin/categories.php
session_start();
require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$category_id = intval($_GET['id'] ?? 0);

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
        case 'edit':
            $name = sanitize_input($_POST['name'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            
            if (empty($name)) {
                $_SESSION['error'] = 'Category name is required';
                header("Location: categories.php");
                exit();
            }
            
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($action === 'edit' && $category_id > 0) {
                // Update category
                $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("sssii", $name, $slug, $description, $is_active, $category_id);
                $message = 'Category updated successfully';
            } else {
                // Add category
                $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $name, $slug, $description, $is_active);
                $message = 'Category added successfully';
            }
            
            if ($stmt->execute()) {
                log_admin_action($_SESSION['user_id'], strtoupper($action), 'categories', $category_id ?: $conn->insert_id, [
                    'name' => $name,
                    'is_active' => $is_active
                ]);
                $_SESSION['success'] = $message;
            } else {
                $_SESSION['error'] = 'Failed to save category';
            }
            $stmt->close();
            
            header("Location: categories.php");
            exit();
            
        case 'delete':
            if ($category_id > 0) {
                // Check if category has products
                $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND is_active = 1");
                $check_stmt->bind_param("i", $category_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $data = $result->fetch_assoc();
                $check_stmt->close();
                
                if ($data['count'] > 0) {
                    $_SESSION['error'] = 'Cannot delete category that has active products';
                } else {
                    // Soft delete
                    $stmt = $conn->prepare("UPDATE categories SET is_active = 0 WHERE id = ?");
                    $stmt->bind_param("i", $category_id);
                    
                    if ($stmt->execute()) {
                        log_admin_action($_SESSION['user_id'], 'DELETE', 'categories', $category_id, [
                            'action' => 'soft_delete'
                        ]);
                        $_SESSION['success'] = 'Category deleted successfully';
                    } else {
                        $_SESSION['error'] = 'Failed to delete category';
                    }
                    $stmt->close();
                }
            }
            header("Location: categories.php");
            exit();
    }
}

// Get category for edit
$category = null;
if ($action === 'edit' && $category_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
}

// Get all categories
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $search_term = "%$search%";
    array_push($params, $search_term, $search_term);
    $types .= 'ss';
}

if ($status === 'active') {
    $where[] = "is_active = 1";
} elseif ($status === 'inactive') {
    $where[] = "is_active = 0";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) as product_count
          FROM categories c 
          $where_clause 
          ORDER BY c.name ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($query);
    $categories = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="admin-message admin-success">
                ✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="admin-message admin-error">
                ❌ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-header">
            <h1>
                <?php if ($action === 'add'): ?>➕ Add New Category
                <?php elseif ($action === 'edit'): ?>✏️ Edit Category
                <?php else: ?>🏷️ Manage Categories<?php endif; ?>
            </h1>
            <div>
                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <a href="categories.php" class="admin-back-btn">← Back to Categories</a>
                <?php else: ?>
                    <a href="?action=add" class="admin-btn admin-btn-primary">➕ Add Category</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="categories.php" class="active">Categories</a>
            <a href="orders.php">Orders</a>
            <a href="customers.php">Customers</a>
            <a href="inventory.php">Inventory</a>
            <a href="reports.php">Reports</a>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Category Form -->
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                
                <div class="admin-form-group">
                    <label for="name" class="required">Category Name</label>
                    <input type="text" id="name" name="name" class="admin-form-control" 
                           value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" 
                           required>
                </div>
                
                <div class="admin-form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="admin-form-control" 
                              rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="admin-form-group">
                    <div class="admin-checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               <?php echo isset($category['is_active']) ? ($category['is_active'] ? 'checked' : '') : 'checked'; ?>>
                        <label for="is_active">Active (visible on site)</label>
                    </div>
                </div>
                
                <div class="admin-form-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <?php echo $action === 'add' ? '➕ Add Category' : '💾 Save Changes'; ?>
                    </button>
                    <a href="categories.php" class="admin-btn admin-btn-secondary">Cancel</a>
                </div>
            </form>
            
        <?php else: ?>
            <!-- Categories List -->
            <div class="admin-filters">
                <form method="GET" class="admin-filter-form">
                    <input type="text" name="search" placeholder="Search categories..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <button type="submit">🔍 Search</button>
                    <?php if ($search || $status): ?>
                    <a href="categories.php" style="color: #6366f1; text-decoration: none; padding: 0 20px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($cat['name']); ?></strong><br>
                                    <small style="color: rgba(248,250,252,0.6);">Slug: <?php echo htmlspecialchars($cat['slug']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(substr($cat['description'] ?? '', 0, 100)) . (strlen($cat['description'] ?? '') > 100 ? '...' : ''); ?></td>
                                <td>
                                    <span style="background: rgba(99, 102, 241, 0.2); color: #6366f1; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600;">
                                        <?php echo $cat['product_count']; ?> products
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-status-badge <?php echo $cat['is_active'] ? 'admin-status-active' : 'admin-status-inactive'; ?>">
                                        <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-action-buttons">
                                        <a href="?action=edit&id=<?php echo $cat['id']; ?>" 
                                           class="admin-btn-action admin-btn-edit">Edit</a>
                                        <a href="products.php?category=<?php echo $cat['id']; ?>" 
                                           class="admin-btn-action admin-btn-view">View Products</a>
                                        <a href="?action=delete&id=<?php echo $cat['id']; ?>" 
                                           class="admin-btn-action admin-btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this category? This will mark it as inactive.')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="admin-empty-state">
                                <div style="font-size: 48px; margin-bottom: 20px;">🏷️</div>
                                <h3>No categories found</h3>
                                <?php if ($search || $status): ?>
                                    <p>Try adjusting your search filters</p>
                                    <a href="categories.php" style="display: inline-block; margin-top: 15px; color: #6366f1;">Clear filters</a>
                                <?php else: ?>
                                    <p>Get started by adding your first category</p>
                                    <a href="?action=add" class="admin-btn admin-btn-primary" style="margin-top: 20px;">➕ Add Category</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (!empty($categories)): ?>
            <div style="margin-top: 20px; color: rgba(248,250,252,0.6); text-align: center;">
                Showing <?php echo count($categories); ?> categor<?php echo count($categories) === 1 ? 'y' : 'ies'; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    // Add confirmation for delete actions
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.admin-btn-delete');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this category?\n\nThis will mark the category as inactive. Products in this category will remain but the category won\'t be visible.')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>

<?php 
// Close connection
$conn->close();
?>