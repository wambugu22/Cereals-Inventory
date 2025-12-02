<?php
include 'includes/header.php';

// Get statistics
$total_products_sql = "SELECT COUNT(*) as count FROM products";
$total_products = $conn->query($total_products_sql)->fetch_assoc()['count'];

$low_stock_sql = "SELECT COUNT(*) as count FROM products WHERE current_stock <= reorder_level";
$low_stock = $conn->query($low_stock_sql)->fetch_assoc()['count'];

$total_stock_value_sql = "SELECT SUM(current_stock * buying_price) as value FROM products";
$total_stock_value = $conn->query($total_stock_value_sql)->fetch_assoc()['value'] ?? 0;

$today_sales_sql = "SELECT SUM(total_amount) as total FROM sales WHERE sale_date = CURDATE()";
$today_sales = $conn->query($today_sales_sql)->fetch_assoc()['total'] ?? 0;

// Get low stock products
$low_stock_products_sql = "SELECT * FROM products WHERE current_stock <= reorder_level ORDER BY current_stock ASC LIMIT 10";
$low_stock_products = $conn->query($low_stock_products_sql);

// Get recent sales
$recent_sales_sql = "SELECT s.*, p.product_name FROM sales s 
                     JOIN products p ON s.product_id = p.id 
                     ORDER BY s.sale_date DESC, s.created_at DESC LIMIT 5";
$recent_sales = $conn->query($recent_sales_sql);
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $total_products; ?></h3>
        <p>Total Products</p>
    </div>
    <div class="stat-card">
        <h3 style="color: <?php echo $low_stock > 0 ? '#e74c3c' : '#27ae60'; ?>">
            <?php echo $low_stock; ?>
        </h3>
        <p>Low Stock Alerts</p>
    </div>
    <div class="stat-card">
        <h3><?php echo format_currency($total_stock_value); ?></h3>
        <p>Total Stock Value</p>
    </div>
    <div class="stat-card">
        <h3><?php echo format_currency($today_sales); ?></h3>
        <p>Today's Sales</p>
    </div>
</div>

<?php if ($low_stock > 0): ?>
<div class="alert alert-warning">
    ⚠️ <strong>Warning:</strong> You have <?php echo $low_stock; ?> product(s) with low stock levels. Please reorder soon!
</div>
<?php endif; ?>

<div class="card">
    <h2>📉 Low Stock Products</h2>
    <?php if ($low_stock_products->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Product Code</th>
                <th>Product Name</th>
                <th>Current Stock</th>
                <th>Reorder Level</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = $low_stock_products->fetch_assoc()): ?>
            <tr>
                <td><?php echo $product['product_code']; ?></td>
                <td><?php echo $product['product_name']; ?></td>
                <td><?php echo $product['current_stock'] . ' ' . $product['unit']; ?></td>
                <td><?php echo $product['reorder_level'] . ' ' . $product['unit']; ?></td>
                <td>
                    <span class="low-stock">⚠️ Low Stock</span>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color: #27ae60; margin-top: 1rem;">✅ All products have sufficient stock levels!</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>📊 Recent Sales</h2>
    <?php if ($recent_sales->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Amount</th>
                <th>Customer</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($sale = $recent_sales->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($sale['sale_date'])); ?></td>
                <td><?php echo $sale['product_name']; ?></td>
                <td><?php echo $sale['quantity']; ?></td>
                <td><?php echo format_currency($sale['total_amount']); ?></td>
                <td><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="margin-top: 1rem;">No sales recorded yet.</p>
    <?php endif; ?>
</div>

<div style="text-align: center; margin-top: 2rem;">
    <a href="products/list.php" class="btn btn-primary">Manage Products</a>
    <a href="sales/record.php" class="btn btn-success">Record New Sale</a>
    <a href="purchases/record.php" class="btn btn-warning">Record Purchase</a>
</div>
<?php include 'includes/footer.php'; ?>