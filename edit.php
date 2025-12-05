<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit;
}

// Auto-logout after 10 minutes (600 seconds)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 600) {
    header('Location: ../logout.php');
    exit;
}
$_SESSION['last_activity'] = time();
include '../includes/header.php';

$success = '';
$error = '';
$id = isset($_GET['id']) ? clean_input($_GET['id']) : 0;

// Get product details
$sql = "SELECT * FROM products WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: list.php');
    exit;
}

$product = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = clean_input($_POST['product_name']);
    $product_code = clean_input($_POST['product_code']);
    $category = clean_input($_POST['category']);
    $unit = clean_input($_POST['unit']);
    $buying_price = clean_input($_POST['buying_price']);
    $selling_price = clean_input($_POST['selling_price']);
    $current_stock = clean_input($_POST['current_stock']);
    $reorder_level = clean_input($_POST['reorder_level']);
    
    $update_sql = "UPDATE products SET 
                   product_name = '$product_name',
                   product_code = '$product_code',
                   category = '$category',
                   unit = '$unit',
                   buying_price = $buying_price,
                   selling_price = $selling_price,
                   current_stock = $current_stock,
                   reorder_level = $reorder_level
                   WHERE id = $id";
    
    if ($conn->query($update_sql)) {
        $success = "Product updated successfully!";
        // Refresh product data
        $result = $conn->query($sql);
        $product = $result->fetch_assoc();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<div class="card">
    <h2>✏️ Edit Product</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name" value="<?php echo $product['product_name']; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Product Code *</label>
            <input type="text" name="product_code" value="<?php echo $product['product_code']; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Category *</label>
            <select name="category" required>
                <option value="Grains" <?php echo $product['category'] == 'Grains' ? 'selected' : ''; ?>>Grains</option>
                <option value="Legumes" <?php echo $product['category'] == 'Legumes' ? 'selected' : ''; ?>>Legumes</option>
                <option value="Seeds" <?php echo $product['category'] == 'Seeds' ? 'selected' : ''; ?>>Seeds</option>
                <option value="Other" <?php echo $product['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Unit *</label>
            <select name="unit" required>
                <option value="Kg" <?php echo $product['unit'] == 'Kg' ? 'selected' : ''; ?>>Kilogram (Kg)</option>
                <option value="Bag" <?php echo $product['unit'] == 'Bag' ? 'selected' : ''; ?>>Bag</option>
                <option value="Ton" <?php echo $product['unit'] == 'Ton' ? 'selected' : ''; ?>>Ton</option>
                <option value="Litre" <?php echo $product['unit'] == 'Litre' ? 'selected' : ''; ?>>Litre</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Buying Price (KSh) *</label>
            <input type="number" name="buying_price" step="0.01" value="<?php echo $product['buying_price']; ?>" required min="0">
        </div>
        
        <div class="form-group">
            <label>Selling Price (KSh) *</label>
            <input type="number" name="selling_price" step="0.01" value="<?php echo $product['selling_price']; ?>" required min="0">
        </div>
        
        <div class="form-group">
            <label>Current Stock *</label>
            <input type="number" name="current_stock" value="<?php echo $product['current_stock']; ?>" required min="0">
        </div>
        
        <div class="form-group">
            <label>Reorder Level *</label>
            <input type="number" name="reorder_level" value="<?php echo $product['reorder_level']; ?>" required min="0">
        </div>
        
        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-success">Update Product</button>
            <a href="list.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>