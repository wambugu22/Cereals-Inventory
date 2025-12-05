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
$sale_id = 0;

$products_sql = "SELECT * FROM products WHERE current_stock > 0 ORDER BY product_name ASC";
$products = $conn->query($products_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = clean_input($_POST['product_id']);
    $quantity = clean_input($_POST['quantity']);
    $customer_name = clean_input($_POST['customer_name']);
    $payment_status = clean_input($_POST['payment_status']);
    
    // Get product details
    $prod = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
    $unit_price = $prod['selling_price'];
    $total_amount = $quantity * $unit_price;
    $amount_paid = ($payment_status == 'Paid') ? $total_amount : 0;
    
    if ($prod['current_stock'] < $quantity) {
        $error = "Not enough stock! Only " . $prod['current_stock'] . " available.";
    } else {
        $conn->begin_transaction();
        try {
            $sale_sql = "INSERT INTO sales (product_id, quantity, unit_price, total_amount, customer_name, sale_date, payment_status, amount_paid) 
                        VALUES ($product_id, $quantity, $unit_price, $total_amount, '$customer_name', CURDATE(), '$payment_status', $amount_paid)";
            $conn->query($sale_sql);
            $sale_id = $conn->insert_id;
            
            $conn->query("UPDATE products SET current_stock = current_stock - $quantity WHERE id = $product_id");
            
            $conn->commit();
            $success = "Sale recorded! " . ($payment_status == 'Paid' ? '✅ PAID' : '⚠️ UNPAID');
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <h2>💰 Quick Sale</h2>
    
    <?php if ($success && $sale_id > 0): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
            <div style="margin-top: 1rem;">
                <?php if (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Paid'): ?>
                    <a href="receipt.php?id=<?php echo $sale_id; ?>" target="_blank" class="btn btn-success">🖨️ Print Receipt</a>
                <?php else: ?>
                    <a href="invoice.php?id=<?php echo $sale_id; ?>" target="_blank" class="btn btn-warning">📋 Print Invoice</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- Product Selection -->
        <div class="form-group">
            <label>Select Product *</label>
            <select name="product_id" id="product_id" required onchange="updateInfo()" style="font-size: 16px; padding: 15px;">
                <option value="">-- Choose Product --</option>
                <?php while ($prod = $products->fetch_assoc()): ?>
                <option value="<?php echo $prod['id']; ?>" 
                        data-price="<?php echo $prod['selling_price']; ?>"
                        data-stock="<?php echo $prod['current_stock']; ?>">
                    <?php echo $prod['product_name']; ?> - KSh <?php echo number_format($prod['selling_price'], 0); ?> (<?php echo $prod['current_stock']; ?> in stock)
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <!-- Quantity -->
        <div class="form-group">
            <label>Quantity *</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" required 
                   onchange="calculateTotal()" 
                   style="font-size: 20px; padding: 15px; text-align: center;">
            <small id="stockInfo" style="color: #e74c3c; font-weight: bold;"></small>
        </div>
        
        <!-- Total Display -->
        <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <h3 style="color: #27ae60; margin-bottom: 10px;">TOTAL AMOUNT</h3>
            <div id="total" style="font-size: 36px; font-weight: bold; color: #27ae60;">KSh 0</div>
        </div>
        
        <!-- Customer Name -->
        <div class="form-group">
            <label>Customer Name (Optional)</label>
            <input type="text" name="customer_name" placeholder="Leave blank for walk-in customer" style="font-size: 16px; padding: 15px;">
        </div>
        
        <!-- Payment Status - BIG BUTTONS -->
        <div class="form-group">
            <label>Payment Status *</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                <label style="cursor: pointer;">
                    <input type="radio" name="payment_status" value="Paid" required style="display: none;" onchange="updatePaymentDisplay(this)">
                    <div class="payment-option" id="paid-option" onclick="selectPayment('paid')">
                        <div style="font-size: 40px;">✅</div>
                        <div style="font-size: 20px; font-weight: bold; margin-top: 10px;">PAID</div>
                        <small>Customer paid in full</small>
                    </div>
                </label>
                
                <label style="cursor: pointer;">
                    <input type="radio" name="payment_status" value="Unpaid" required style="display: none;" onchange="updatePaymentDisplay(this)">
                    <div class="payment-option" id="unpaid-option" onclick="selectPayment('unpaid')">
                        <div style="font-size: 40px;">📋</div>
                        <div style="font-size: 20px; font-weight: bold; margin-top: 10px;">UNPAID</div>
                        <small>Will pay later</small>
                    </div>
                </label>
            </div>
        </div>
        
        <!-- Submit Buttons -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-success" style="font-size: 18px; padding: 20px;">
                💾 Record Sale
            </button>
            <a href="history.php" class="btn btn-primary" style="font-size: 18px; padding: 20px; text-align: center; line-height: 1.5;">
                📋 View History
            </a>
        </div>
    </form>
</div>

<style>
.payment-option {
    background: white;
    border: 3px solid #ddd;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s;
}
.payment-option:hover {
    border-color: #3498db;
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.payment-option.selected {
    border-color: #27ae60;
    background: #e8f5e9;
}
</style>

<script>
function updateInfo() {
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    const stock = option.getAttribute('data-stock');
    
    if (stock) {
        document.getElementById('stockInfo').textContent = '✓ ' + stock + ' available';
        document.getElementById('stockInfo').style.color = '#27ae60';
    }
    calculateTotal();
}

function calculateTotal() {
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    const price = parseFloat(option.getAttribute('data-price')) || 0;
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const total = price * quantity;
    
    document.getElementById('total').textContent = 'KSh ' + total.toLocaleString('en-KE', {minimumFractionDigits: 0});
}

function selectPayment(type) {
    document.getElementById('paid-option').classList.remove('selected');
    document.getElementById('unpaid-option').classList.remove('selected');
    
    if (type === 'paid') {
        document.getElementById('paid-option').classList.add('selected');
        document.querySelector('input[value="Paid"]').checked = true;
    } else {
        document.getElementById('unpaid-option').classList.add('selected');
        document.querySelector('input[value="Unpaid"]').checked = true;
    }
}
</script>

<?php include '../includes/footer.php'; ?>