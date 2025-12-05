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

// Get filter parameters
$start_date = isset($_GET['start_date']) ? clean_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? clean_input($_GET['end_date']) : date('Y-m-d');

// Get sales with filters
$sql = "SELECT s.*, p.product_name, p.product_code 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        WHERE s.sale_date BETWEEN '$start_date' AND '$end_date'
        ORDER BY s.sale_date DESC, s.created_at DESC";
$sales = $conn->query($sql);

// Calculate totals
$totals_sql = "SELECT 
                SUM(total_amount) as total_sales,
                SUM(quantity) as total_quantity,
                COUNT(*) as total_transactions
               FROM sales 
               WHERE sale_date BETWEEN '$start_date' AND '$end_date'";
$totals = $conn->query($totals_sql)->fetch_assoc();
?>

<div class="card">
    <h2>📊 Sales History</h2>
    
    <form method="GET" action="" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
    
    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
            <h3><?php echo $totals['total_transactions'] ?? 0; ?></h3>
            <p>Total Transactions</p>
        </div>
        <div class="stat-card">
            <h3><?php echo format_currency($totals['total_sales'] ?? 0); ?></h3>
            <p>Total Sales</p>
        </div>
    </div>
    
    <?php if ($sales->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Product Code</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Customer</th>
                <th>Notes</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($sale = $sales->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($sale['sale_date'])); ?></td>
                <td><?php echo $sale['product_name']; ?></td>
                <td><?php echo $sale['product_code']; ?></td>
                <td><?php echo $sale['quantity']; ?></td>
                <td><?php echo format_currency($sale['unit_price']); ?></td>
                <td><strong><?php echo format_currency($sale['total_amount']); ?></strong></td>
                <td><?php echo $sale['customer_name'] ?: 'Walk-in'; ?></td>
                <td><?php echo $sale['notes']; ?></td>
                <td>
                    <a href="receipt.php?id=<?php echo $sale['id']; ?>" target="_blank" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                        🖨️ Receipt
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No sales found for the selected period.</p>
    <?php endif; ?>
</div>

<div style="text-align: center; margin-top: 1.5rem;">
    <a href="record.php" class="btn btn-success">Record New Sale</a>
    <a href="../index.php" class="btn btn-primary">Back to Dashboard</a>
</div>

<?php include '../includes/footer.php'; ?>