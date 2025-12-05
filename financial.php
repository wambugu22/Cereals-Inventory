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

// Get date range
$start_date = isset($_GET['start_date']) ? clean_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? clean_input($_GET['end_date']) : date('Y-m-d');

// REVENUE ANALYSIS
$sales_sql = "SELECT 
                SUM(total_amount) as gross_sales,
                SUM(total_amount * 0.16 / 1.16) as output_vat,
                COUNT(*) as num_transactions
              FROM sales 
              WHERE sale_date BETWEEN '$start_date' AND '$end_date'";
$sales_data = $conn->query($sales_sql)->fetch_assoc();

$gross_sales = $sales_data['gross_sales'] ?? 0;
$output_vat = $sales_data['output_vat'] ?? 0;
$net_sales = $gross_sales - $output_vat;

// COST OF GOODS SOLD (COGS)
$cogs_sql = "SELECT 
                SUM(quantity * buying_price) as cogs
             FROM sales s
             JOIN products p ON s.product_id = p.id
             WHERE s.sale_date BETWEEN '$start_date' AND '$end_date'";
$cogs = $conn->query($cogs_sql)->fetch_assoc()['cogs'] ?? 0;

// PURCHASES ANALYSIS
$purchases_sql = "SELECT 
                    SUM(total_amount) as total_purchases,
                    SUM(total_amount * 0.16 / 1.16) as input_vat
                  FROM purchases 
                  WHERE purchase_date BETWEEN '$start_date' AND '$end_date'";
$purchases_data = $conn->query($purchases_sql)->fetch_assoc();

$total_purchases = $purchases_data['total_purchases'] ?? 0;
$input_vat = $purchases_data['input_vat'] ?? 0;

// EXPENSES ANALYSIS
$expenses_sql = "SELECT 
                    SUM(amount) as total_expenses,
                    SUM(vat_amount) as expenses_vat
                 FROM expenses 
                 WHERE expense_date BETWEEN '$start_date' AND '$end_date'";
$expenses_data = $conn->query($expenses_sql)->fetch_assoc();

$total_expenses = $expenses_data['total_expenses'] ?? 0;
$expenses_vat = $expenses_data['expenses_vat'] ?? 0;

// LOSSES
$losses_sql = "SELECT SUM(total_value) as total_losses
               FROM damages_losses 
               WHERE loss_date BETWEEN '$start_date' AND '$end_date'";
$total_losses = $conn->query($losses_sql)->fetch_assoc()['total_losses'] ?? 0;

// CALCULATIONS
$gross_profit = $net_sales - $cogs;
$operating_expenses = $total_expenses + $total_losses;
$operating_profit = $gross_profit - $operating_expenses;

// VAT CALCULATIONS
$total_input_vat = $input_vat + $expenses_vat;
$vat_payable = $output_vat - $total_input_vat;

// PROFIT METRICS
$gross_profit_margin = $net_sales > 0 ? ($gross_profit / $net_sales) * 100 : 0;
$operating_profit_margin = $net_sales > 0 ? ($operating_profit / $net_sales) * 100 : 0;
?>

<div class="card">
    <h2>📊 Comprehensive Financial Report - DevTech Partners</h2>
    <p><strong>Period:</strong> <?php echo date('d/m/Y', strtotime($start_date)); ?> to <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
    
    <form method="GET" action="" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <button type="submit" class="btn btn-primary">Update Report</button>
        </div>
    </form>
    
    <button onclick="window.print()" class="btn btn-success">🖨️ Print Report</button>
</div>

<!-- PROFIT & LOSS STATEMENT -->
<div class="card">
    <h3>💼 Profit & Loss Statement</h3>
    <table>
        <tbody>
            <tr style="background: #e8f5e9;">
                <td><strong>REVENUE</strong></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="padding-left: 2rem;">Gross Sales (incl. VAT)</td>
                <td><?php echo format_currency($gross_sales); ?></td>
                <td></td>
            </tr>
            <tr>
                <td style="padding-left: 2rem;">Less: Output VAT (16%)</td>
                <td>(<?php echo format_currency($output_vat); ?>)</td>
                <td></td>
            </tr>
            <tr style="background: #f1f8e9; font-weight: bold;">
                <td style="padding-left: 2rem;">Net Sales</td>
                <td></td>
                <td><?php echo format_currency($net_sales); ?></td>
            </tr>
            
            <tr style="background: #fff3e0;">
                <td><strong>COST OF GOODS SOLD</strong></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="padding-left: 2rem;">Cost of Goods Sold</td>
                <td></td>
                <td>(<?php echo format_currency($cogs); ?>)</td>
            </tr>
            
            <tr style="background: #e3f2fd; font-weight: bold; font-size: 1.05rem;">
                <td><strong>GROSS PROFIT</strong></td>
                <td><?php echo number_format($gross_profit_margin, 1); ?>%</td>
                <td><strong><?php echo format_currency($gross_profit); ?></strong></td>
            </tr>
            
            <tr style="background: #fce4ec;">
                <td><strong>OPERATING EXPENSES</strong></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="padding-left: 2rem;">Total Expenses</td>
                <td><?php echo format_currency($total_expenses); ?></td>
                <td></td>
            </tr>
            <tr>
                <td style="padding-left: 2rem;">Damages & Losses</td>
                <td><?php echo format_currency($total_losses); ?></td>
                <td></td>
            </tr>
            <tr style="background: #f3e5f5;">
                <td style="padding-left: 2rem;"><strong>Total Operating Expenses</strong></td>
                <td></td>
                <td><strong>(<?php echo format_currency($operating_expenses); ?>)</strong></td>
            </tr>
            
            <tr style="background: <?php echo $operating_profit >= 0 ? '#c8e6c9' : '#ffcdd2'; ?>; font-weight: bold; font-size: 1.1rem;">
                <td><strong>NET OPERATING PROFIT</strong></td>
                <td><?php echo number_format($operating_profit_margin, 1); ?>%</td>
                <td><strong><?php echo format_currency($operating_profit); ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- VAT ANALYSIS -->
<div class="card">
    <h3>🧾 VAT Analysis (Value Added Tax - 16%)</h3>
    <table>
        <tbody>
            <tr style="background: #e8f5e9;">
                <td><strong>Output VAT (Sales)</strong></td>
                <td><strong><?php echo format_currency($output_vat); ?></strong></td>
            </tr>
            <tr style="background: #fff3e0;">
                <td style="padding-left: 1rem;">Input VAT - Purchases</td>
                <td><?php echo format_currency($input_vat); ?></td>
            </tr>
            <tr style="background: #fff3e0;">
                <td style="padding-left: 1rem;">Input VAT - Expenses</td>
                <td><?php echo format_currency($expenses_vat); ?></td>
            </tr>
            <tr style="background: #ffe0b2; font-weight: bold;">
                <td style="padding-left: 1rem;"><strong>Total Input VAT (Claimable)</strong></td>
                <td><strong>(<?php echo format_currency($total_input_vat); ?>)</strong></td>
            </tr>
            <tr style="background: <?php echo $vat_payable >= 0 ? '#ffcdd2' : '#c8e6c9'; ?>; font-weight: bold; font-size: 1.05rem;">
                <td><strong>VAT PAYABLE TO KRA</strong></td>
                <td><strong><?php echo format_currency($vat_payable); ?></strong></td>
            </tr>
        </tbody>
    </table>
    <div class="alert alert-warning" style="margin-top: 1rem;">
        <strong>⚠️ Important:</strong> VAT returns must be filed by the 20th of the following month. 
        <?php if ($vat_payable > 0): ?>
            You need to pay <strong><?php echo format_currency($vat_payable); ?></strong> to KRA.
        <?php else: ?>
            You can claim a refund of <strong><?php echo format_currency(abs($vat_payable)); ?></strong> from KRA.
        <?php endif; ?>
    </div>
</div>

<!-- STATUTORY DEDUCTIONS (For reference - if you have employees) -->
<div class="card">
    <h3>📋 Kenya Statutory Deductions Reference (2025)</h3>
    <p><em>These rates apply if you have employees on payroll:</em></p>
    
    <table>
        <thead>
            <tr>
                <th>Statutory Item</th>
                <th>Rate / Amount</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>NSSF</strong><br><small>National Social Security Fund</small></td>
                <td>6% Employee<br>6% Employer</td>
                <td>
                    • Tier I: KES 8,000 (KES 480 each)<br>
                    • Tier II: Up to KES 72,000 (KES 3,840 each)<br>
                    • Max total: KES 8,640/month<br>
                    • Due: 9th of following month
                </td>
            </tr>
            <tr>
                <td><strong>SHIF</strong><br><small>Social Health Insurance Fund</small></td>
                <td>2.75% of gross salary</td>
                <td>
                    • Minimum: KES 300/month<br>
                    • No maximum cap<br>
                    • Tax deductible<br>
                    • Due: 9th of following month
                </td>
            </tr>
            <tr>
                <td><strong>Housing Levy</strong></td>
                <td>1.5% of gross salary</td>
                <td>
                    • Matched by employer (1.5%)<br>
                    • Tax deductible<br>
                    • Due: 9th of following month
                </td>
            </tr>
            <tr>
                <td><strong>PAYE</strong><br><small>Pay As You Earn</small></td>
                <td>Progressive rates</td>
                <td>
                    • 10% (up to KES 24,000)<br>
                    • 25% (KES 24,001 - 32,333)<br>
                    • 30% (KES 32,334 - 500,000)<br>
                    • 32.5% (KES 500,001 - 800,000)<br>
                    • 35% (above KES 800,000)<br>
                    • Personal Relief: KES 2,400/month
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- KEY METRICS -->
<div class="card">
    <h3>📈 Key Performance Indicators</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo format_currency($gross_sales); ?></h3>
            <p>Gross Sales</p>
        </div>
        <div class="stat-card">
            <h3><?php echo format_currency($gross_profit); ?></h3>
            <p>Gross Profit</p>
        </div>
        <div class="stat-card">
            <h3 style="color: <?php echo $operating_profit >= 0 ? '#27ae60' : '#e74c3c'; ?>">
                <?php echo format_currency($operating_profit); ?>
            </h3>
            <p>Net Profit</p>
        </div>
        <div class="stat-card">
            <h3><?php echo number_format($gross_profit_margin, 1); ?>%</h3>
            <p>Profit Margin</p>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, footer, form {
        display: none;
    }
    body {
        background: white;
    }
    .card {
        box-shadow: none;
        page-break-inside: avoid;
    }
}
</style>

<div style="text-align: center; margin-top: 2rem;">
    <a href="../index.php" class="btn btn-primary">Back to Dashboard</a>
    <a href="stock.php" class="btn btn-success">View Stock Report</a>
</div>

<?php include '../includes/footer.php'; ?>