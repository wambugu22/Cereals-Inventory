<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit;
}
$_SESSION['last_activity'] = time();

include '../includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = clean_input($_POST['product_name']);
    $product_code = clean_input($_POST['product_code']);
    $category = clean_input($_POST['category']);
    $buying_price = clean_input($_POST['buying_price']);
    $selling_price = clean_input($_POST['selling_price']);
    $current_stock = clean_input($_POST['current_stock']);
    
    $sql = "INSERT INTO products (product_name, product_code, category, unit, buying_price, selling_price, current_stock, reorder_level) 
            VALUES ('$product_name', '$product_code', '$category', 'Kg', $buying_price, $selling_price, $current_stock, 50)";
    
    if ($conn->query($sql)) {
        $success = "Product added successfully! ✅";
        $_POST = array();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<div class="card">
    <h2>➕ Add New Product</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
            <a href="list.php" class="btn btn-primary" style="margin-top: 10px;">View All Products</a>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- Product Name -->
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name" required placeholder="e.g., White Maize" 
                   style="font-size: 18px; padding: 15px;">
        </div>
        
        <!-- Product Code -->
        <div class="form-group">
            <label>Product Code *</label>
            <input type="text" name="product_code" required placeholder="e.g., DTP001" 
                   style="font-size: 18px; padding: 15px;">
        </div>
        
        <!-- Category -->
        <div class="form-group">
            <label>Category *</label>
            <select name="category" required style="font-size: 18px; padding: 15px;">
                <option value="">-- Select Category --</option>
                <option value="Grains">🌾 Grains</option>
                <option value="Legumes">🫘 Legumes</option>
                <option value="Seeds">🌱 Seeds</option>
                <option value="Other">📦 Other</option>
            </select>
        </div>
        
        <!-- Prices -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Buying Price (KSh) *</label>
                <input type="number" name="buying_price" step="0.01" required placeholder="0" 
                       style="font-size: 20px; padding: 15px; text-align: center;">
                <small>What you pay suppliers</small>
            </div>
            
            <div class="form-group">
                <label>Selling Price (KSh) *</label>
                <input type="number" name="selling_price" step="0.01" required placeholder="0" 
                       style="font-size: 20px; padding: 15px; text-align: center;">
                <small>What customers pay</small>
            </div>
        </div>
        
        <!-- Initial Stock -->
        <div class="form-group">
            <label>Initial Stock (Kg) *</label>
            <input type="number" name="current_stock" value="0" min="0" required 
                   style="font-size: 20px; padding: 15px; text-align: center;">
            <small>How many do you have now</small>
        </div>
        
        <!-- Submit -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-success" style="font-size: 18px; padding: 20px;">
                💾 Save Product
            </button>
            <a href="list.php" class="btn btn-danger" style="font-size: 18px; padding: 20px; text-align: center; line-height: 1.5;">
                ❌ Cancel
            </a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>