<?php
include '../includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = clean_input($_POST['product_name']);
    $product_code = clean_input($_POST['product_code']);
    $category = clean_input($_POST['category']);
    $unit = clean_input($_POST['unit']);
    $buying_price = clean_input($_POST['buying_price']);
    $selling_price = clean_input($_POST['selling_price']);
    $current_stock = clean_input($_POST['current_stock']);
    $reorder_level = clean_input($_POST['reorder_level']);
    
    $sql = "INSERT INTO products (product_name, product_code, category, unit, buying_price, selling_price, current_stock, reorder_level) 
            VALUES ('$product_name', '$product_code', '$category', '$unit', $buying_price, $selling_price, $current_stock, $reorder_level)";
    
    if ($conn->query($sql)) {
        $success = "Product added successfully!";
        // Clear form
        $_POST = array();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<div class="card">
    <h2>➕ Add New Product</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name" required>
        </div>
        
        <div class="form-group">
            <label>Product Code *</label>
            <input type="text" name="product_code" required placeholder="e.g., DTP001">
        </div>
        
        <div class="form-group">
            <label>Category *</label>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="Grains">Grains</option>
                <option value="Legumes">Legumes</option>
                <option value="Seeds">Seeds</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Unit *</label>
            <select name="unit" required>
                <option value="">Select Unit</option>
                <option value="Kg">Kilogram (Kg)</option>
                <option value="Bag">Bag</option>
                <option value="Ton">Ton</option>
                <option value="Litre">Litre</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Buying Price (KSh) *</label>
            <input type="number" name="buying_price" step="0.01" required min="0">
        </div>
        
        <div class="form-group">
            <label>Selling Price (KSh) *</label>
            <input type="number" name="selling_price" step="0.01" required min="0">
        </div>
        
        <div class="form-group">
            <label>Current Stock *</label>
            <input type="number" name="current_stock" required min="0" value="0">
        </div>
        
        <div class="form-group">
            <label>Reorder Level *</label>
            <input type="number" name="reorder_level" required min="0" value="10">
        </div>
        
        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-success">Save Product</button>
            <a href="list.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>