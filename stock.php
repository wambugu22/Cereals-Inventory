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

// Get all products with stock valuation
$sql = "SELECT 
            p.*,
            (p.current_stock * p.buying_price) as stock_value_buying,
            (p.current_stock * p.selling_price) as stock_value_selling,
            ((p.selling_price - p.buying_price) * p.current_stock) as potential_profit
        FROM products p 
        ORDER BY p.product_name ASC";
$products = $conn->query($sql);

// Calculate totals
$totals_sql = "SELECT 
                COUNT(*) as total_products,
                SUM(current_stock * buying_price) as total_buying_value,
                SUM(current_stock * selling_price) as total_selling_value,
                SUM((selling_price - buying_price) * current_stock) as total_potential_profit
               FROM products";
$totals = $conn->query($totals_sql)->fetch_assoc();

// Get low stock count
$low_stock_count = get_low_stock_count();
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $totals['total_products']; ?></h3>
        <p>Total Products</p>
    </div>
    <div class="stat-card">
        <h3><?php echo format_currency($totals['total_buying_value']); ?></h3>
        <p>Stock Value (Buying)</p>
    </div>
    <div class="stat-card">
        <h3><?php echo format_currency($totals['total_selling_value']); ?></h3>
        <p>Stock Value (Selling)</p>
    </div>
    <div class="stat-card">
        <h3><?php echo format_currency($totals['total_potential_profit']); ?></h3>
        <p>Potential Profit</p>
    </div>
</div>

<?php if ($low_stock_count > 0): ?>
<div class="alert alert-warning">
    ⚠️ <strong>Warning:</strong> <?php echo $low_stock_count; ?> product(s) have low stock levels!
</div>
<?php endif; ?>

<div class="card">
    <h2>📊 Stock Report</h2>
    
    <div style="margin-bottom: 1rem;">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Report</button>
        <a href="../index.php" class="btn btn-success">Back to Dashboard</a>
    </div>
    
    <?php if ($products->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Current Stock</th>
                <th>Reorder Level</th>
                <th>Buying Price</th>
                <th>Selling Price</th>
                <th>Stock Value (Buying)</th>
                <th>Stock Value (Selling)</th>
                <th>Potential Profit</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $products->data_seek(0);
            while ($product = $products->fetch_assoc()): 
                $is_low_stock = $product['current_stock'] <= $product['reorder_level'];
            ?>
            <tr style="<?php echo $is_low_stock ? 'background: #fff3cd;' : ''; ?>">
                <td><?php echo $product['product_code']; ?></td>
                <td>
                    <?php echo $product['product_name']; ?>
                    <?php if ($is_low_stock): ?>
                        <span class="low-stock">⚠️</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $product['category']; ?></td>
                <td><strong><?php echo $product['current_stock'] . ' ' . $product['unit']; ?></strong></td>
                <td><?php echo $product['reorder_level'] . ' ' . $product['unit']; ?></td>
                <td><?php echo format_currency($product['buying_price']); ?></td>
                <td><?php echo format_currency($product['selling_price']); ?></td>
                <td><?php echo format_currency($product['stock_value_buying']); ?></td>
                <td><?php echo format_currency($product['stock_value_selling']); ?></td>
                <td style="color: #27ae60; font-weight: bold;">
                    <?php echo format_currency($product['potential_profit']); ?>
                </td>
                <td>
                    <?php if ($is_low_stock): ?>
                        <span class="low-stock">Low Stock</span>
                    <?php elseif ($product['current_stock'] == 0): ?>
                        <span style="background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                            Out of Stock
                        </span>
                    <?php else: ?>
                        <span style="color: #27ae60;">✓ In Stock</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="background: #34495e; color: white; font-weight: bold;">
                <td colspan="7" style="text-align: right;">TOTALS:</td>
                <td><?php echo format_currency($totals['total_buying_value']); ?></td>
                <td><?php echo format_currency($totals['total_selling_value']); ?></td>
                <td style="color: #2ecc71;"><?php echo format_currency($totals['total_potential_profit']); ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
    <p>No products found.</p>
    <?php endif; ?>
</div>

<style>
@media print {
    .navbar, .btn, footer {
        display: none;
    }
    
    body {
        background: white;
    }
    
    .card {
        box-shadow: none;
    }
}
</style>

<?php include '../includes/footer.php'; ?>