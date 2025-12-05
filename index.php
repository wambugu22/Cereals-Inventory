<?php
include 'includes/header.php';

// Get current month dates
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

// REVENUE: Total sales this month
$sales_sql = "SELECT 
                COUNT(*) as sales_count,
                COALESCE(SUM(total_amount), 0) as total_sales,
                COALESCE(SUM(total_amount * 0.16 / 1.16), 0) as output_vat
              FROM sales 
              WHERE sale_date BETWEEN '$current_month_start' AND '$current_month_end'";
$sales_data = $conn->query($sales_sql)->fetch_assoc();

// COGS: Cost of goods sold
$cogs_sql = "SELECT COALESCE(SUM(s.quantity * p.buying_price), 0) as cogs
             FROM sales s
             JOIN products p ON s.product_id = p.id
             WHERE s.sale_date BETWEEN '$current_month_start' AND '$current_month_end'";
$cogs = $conn->query($cogs_sql)->fetch_assoc()['cogs'];

// EXPENSES: Total expenses this month
$expenses_sql = "SELECT 
                    COALESCE(SUM(amount), 0) as total_expenses,
                    COALESCE(SUM(vat_amount), 0) as expenses_vat
                 FROM expenses
                 WHERE expense_date BETWEEN '$current_month_start' AND '$current_month_end'";
$expenses_data = $conn->query($expenses_sql)->fetch_assoc();

// PURCHASES: Total purchases and input VAT
$purchases_sql = "SELECT 
                    COALESCE(SUM(total_amount), 0) as total_purchases,
                    COALESCE(SUM(total_amount * 0.16 / 1.16), 0) as input_vat_purchases
                  FROM purchases
                  WHERE purchase_date BETWEEN '$current_month_start' AND '$current_month_end'";
$purchases_data = $conn->query($purchases_sql)->fetch_assoc();

// SALARIES: Employee salaries this month
$salaries_sql = "SELECT COALESCE(SUM(gross_salary), 0) as total_salaries
                 FROM salary_deductions
                 WHERE month = '" . date('Y-m') . "'";
$total_salaries = $conn->query($salaries_sql)->fetch_assoc()['total_salaries'];

// DAMAGES & LOSSES
$losses_sql = "SELECT COALESCE(SUM(total_value), 0) as total_losses
               FROM damages_losses
               WHERE loss_date BETWEEN '$current_month_start' AND '$current_month_end'";
$total_losses = $conn->query($losses_sql)->fetch_assoc()['total_losses'];

// CALCULATIONS
$gross_sales = $sales_data['total_sales'];
$output_vat = $sales_data['output_vat'];
$net_sales = $gross_sales - $output_vat;

$input_vat = $purchases_data['input_vat_purchases'] + $expenses_data['expenses_vat'];
$vat_payable = $output_vat - $input_vat;

$total_expenses = $expenses_data['total_expenses'] + $total_salaries;
$gross_profit = $net_sales - $cogs;
$net_profit = $gross_profit - $total_expenses - $total_losses;

// Get low stock count
$low_stock_count = get_low_stock_count();

// Get total products
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

// Get total stock value
$stock_value_sql = "SELECT SUM(current_stock * buying_price) as value FROM products";
$stock_value = $conn->query($stock_value_sql)->fetch_assoc()['value'] ?? 0;

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
        <h3><?php echo format_currency($net_sales); ?></h3>
        <p>Net Sales (This Month)</p>
        <small style="color: #7f8c8d;"><?php echo $sales_data['sales_count']; ?> transactions</small>
    </div>
    <div class="stat-card">
        <h3 style="color: <?php echo $net_profit >= 0 ? '#27ae60' : '#e74c3c'; ?>">
            <?php echo format_currency($net_profit); ?>
        </h3>
        <p>Net Profit (This Month)</p>
        <small style="color: #7f8c8d;">After all deductions</small>
    </div>
    <div class="stat-card">
        <h3 style="color: <?php echo $vat_payable >= 0 ? '#e74c3c' : '#27ae60'; ?>">
            <?php echo format_currency($vat_payable); ?>
        </h3>
        <p>VAT Payable (20th)</p>
        <small style="color: #7f8c8d;">Output - Input VAT</small>
    </div>
    <div class="stat-card">
        <h3><?php echo format_currency($total_expenses); ?></h3>
        <p>Total Expenses</p>
        <small style="color: #7f8c8d;">Including salaries</small>
    </div>
</div>

<!-- VAT Alert -->
<?php 
$current_day = date('d');
if ($current_day >= 15 && $current_day <= 20 && $vat_payable > 0): 
?>
<div class="alert alert-warning">
    ⚠️ <strong>VAT Filing Reminder:</strong> You have <strong><?php echo format_currency($vat_payable); ?></strong> VAT payable to KRA. Filing deadline is 20th of this month!
</div>
<?php endif; ?>

<!-- Low Stock Alert -->
<?php if ($low_stock_count > 0): ?>
<div class="alert alert-warning">
    ⚠️ <strong>Warning:</strong> You have <?php echo $low_stock_count; ?> product(s) with low stock levels. Please reorder soon!
</div>
<?php endif; ?>

<!-- Quick Financial Summary -->
<div class="card">
    <h2>💰 Monthly Financial Summary</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <div>
            <h4 style="color: #27ae60;">📈 INCOME</h4>
            <table style="width: 100%;">
                <tr><td>Gross Sales:</td><td style="text-align: right;"><?php echo format_currency($gross_sales); ?></td></tr>
                <tr><td style="padding-left: 1rem;">Less: Output VAT:</td><td style="text-align: right;">(<?php echo format_currency($output_vat); ?>)</td></tr>
                <tr style="font-weight: bold; background: #e8f5e9;"><td>Net Sales:</td><td style="text-align: right;"><?php echo format_currency($net_sales); ?></td></tr>
            </table>
        </div>
        <div>
            <h4 style="color: #e74c3c;">📉 EXPENSES</h4>
            <table style="width: 100%;">
                <tr><td>Cost of Goods:</td><td style="text-align: right;"><?php echo format_currency($cogs); ?></td></tr>
                <tr><td>Operating Expenses:</td><td style="text-align: right;"><?php echo format_currency($expenses_data['total_expenses']); ?></td></tr>
                <tr><td>Salaries:</td><td style="text-align: right;"><?php echo format_currency($total_salaries); ?></td></tr>
                <tr><td>Damages/Losses:</td><td style="text-align: right;"><?php echo format_currency($total_losses); ?></td></tr>
                <tr style="font-weight: bold; background: #ffebee;"><td>Total Expenses:</td><td style="text-align: right;"><?php echo format_currency($cogs + $total_expenses + $total_losses); ?></td></tr>
            </table>
        </div>
    </div>
    
    <div style="margin-top: 1.5rem; padding: 1rem; background: <?php echo $net_profit >= 0 ? '#d4edda' : '#f8d7da'; ?>; border-radius: 4px; text-align: center;">
        <h3 style="color: <?php echo $net_profit >= 0 ? '#155724' : '#721c24'; ?>; margin-bottom: 0.5rem;">
            <?php echo $net_profit >= 0 ? '✅ PROFIT' : '❌ LOSS'; ?>: <?php echo format_currency($net_profit); ?>
        </h3>
        <p style="margin: 0; font-size: 0.9rem;">
            For <?php echo date('F Y'); ?>
        </p>
    </div>
</div>

<div style="text-align: center; margin: 1.5rem 0;">
    <a href="reports/financial.php" class="btn btn-primary">📊 View Full Financial Report</a>
    <a href="expenses/manage.php" class="btn btn-warning">💰 Manage Expenses</a>
    <a href="employees/manage.php" class="btn btn-success">👥 Manage Employees</a>
</div>

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