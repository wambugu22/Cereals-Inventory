<?php
include '../includes/header.php';

$success = '';
$error = '';

// Get all products for dropdown
$products_sql = "SELECT * FROM products WHERE current_stock > 0 ORDER BY product_name ASC";
$products = $conn->query($products_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = clean_input($_POST['product_id']);
    $quantity = clean_input($_POST['quantity']);
    $unit_price = clean_input($_POST['unit_price']);
    $customer_name = clean_input($_POST['customer_name']);
    $sale_date = clean_input($_POST['sale_date']);
    $notes = clean_input($_POST['notes']);
    $total_amount = $quantity * $unit_price;
    
    // Check if sufficient stock
    $stock_check = $conn->query("SELECT current_stock, product_name FROM products WHERE id = $product_id");
    $product = $stock_check->fetch_assoc();
    
    if ($product['current_stock'] < $quantity) {
        $error = "Insufficient stock! Available: " . $product['current_stock'] . " units";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert sale
            $sale_sql = "INSERT INTO sales (product_id, quantity, unit_price, total_amount, customer_name, sale_date, notes) 
                        VALUES ($product_id, $quantity, $unit_price, $total_amount, '$customer_name', '$sale_date', '$notes')";
            $conn->query($sale_sql);
            
            // Update stock
            $update_stock = "UPDATE products SET current_stock = current_stock - $quantity WHERE id = $product_id";
            $conn->query($update_stock);
            
            $conn->commit();
            $success = "Sale recorded successfully! Stock updated.";
            $_POST = array();
            
            // Refresh products
            $products = $conn->query($products_sql);
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error recording sale: " . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <h2>💰 Record Sale</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" id="saleForm">
        <div class="form-group">
            <label>Product *</label>
            <select name="product_id" id="product_id" required onchange="updatePrice()">
                <option value="">Select Product</option>
                <?php 
                $products->data_seek(0);
                while ($prod = $products->fetch_assoc()): 
                ?>
                <option value="<?php echo $prod['id']; ?>" 
                        data-price="<?php echo $prod['selling_price']; ?>"
                        data-stock="<?php echo $prod['current_stock']; ?>"
                        data-unit="<?php echo $prod['unit']; ?>">
                    <?php echo $prod['product_name']; ?> (Stock: <?php echo $prod['current_stock']; ?> <?php echo $prod['unit']; ?>)
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Quantity *</label>
            <input type="number" name="quantity" id="quantity" required min="1" value="1" onchange="calculateTotal()">
            <small id="stockInfo" style="color: #7f8c8d;"></small>
        </div>
        
        <div class="form-group">
            <label>Unit Price (KSh) *</label>
            <input type="number" name="unit_price" id="unit_price" step="0.01" required min="0" onchange="calculateTotal()">
        </div>
        
        <div class="form-group">
            <label>Total Amount (KSh)</label>
            <input type="text" id="total_amount" readonly style="background: #f5f5f5; font-weight: bold; font-size: 1.1rem;">
        </div>
        
        <div class="form-group">
            <label>Customer Name</label>
            <input type="text" name="customer_name" placeholder="Optional - Leave blank for walk-in customer">
        </div>
        
        <div class="form-group">
            <label>Sale Date *</label>
            <input type="date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3" placeholder="Optional notes about this sale"></textarea>
        </div>
        
        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-success">Record Sale</button>
            <a href="../index.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<script>
function updatePrice() {
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    const price = option.getAttribute('data-price');
    const stock = option.getAttribute('data-stock');
    const unit = option.getAttribute('data-unit');
    
    if (price) {
        document.getElementById('unit_price').value = price;
        document.getElementById('stockInfo').textContent = 'Available: ' + stock + ' ' + unit;
        calculateTotal();
    }
}

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = quantity * unitPrice;
    document.getElementById('total_amount').value = 'KSh ' + total.toFixed(2);
}
</script>

<?php include '../includes/footer.php'; ?>