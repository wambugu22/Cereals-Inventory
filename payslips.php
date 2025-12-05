<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

// Get employee ID from URL
$employee_id = isset($_GET['employee_id']) ? clean_input($_GET['employee_id']) : 0;

if ($employee_id == 0) {
    die("Invalid employee ID");
}

// Get employee details
$emp_sql = "SELECT * FROM employees WHERE id = $employee_id";
$emp_result = $conn->query($emp_sql);

if (!$emp_result || $emp_result->num_rows == 0) {
    die("Employee not found");
}

$employee = $emp_result->fetch_assoc();

// Get all salary records for this employee
$salaries_sql = "SELECT * FROM salary_deductions WHERE employee_id = $employee_id ORDER BY month DESC";
$salaries = $conn->query($salaries_sql);

// Function to format currency
if (!function_exists('format_currency')) {
    function format_currency($amount) {
        return 'KSh ' . number_format($amount, 2);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payslips - <?php echo htmlspecialchars($employee['name']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
    .payslip-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    
    .payslip-header {
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .payslip-body {
        padding: 30px;
    }
    
    .company-logo {
        font-size: 48px;
        margin-bottom: 10px;
    }
    
    .section-title {
        background-color: #ecf0f1;
        padding: 10px 15px;
        font-weight: bold;
        margin-top: 20px;
        margin-bottom: 15px;
        border-left: 4px solid #3498db;
    }
    
    .info-table {
        width: 100%;
        margin-bottom: 20px;
    }
    
    .info-table td {
        padding: 8px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .info-table td:first-child {
        font-weight: 600;
        width: 40%;
        color: #7f8c8d;
    }
    
    .breakdown-table {
        width: 100%;
        margin-top: 20px;
        border-collapse: collapse;
    }
    
    .breakdown-table th {
        background-color: #3498db;
        color: white;
        padding: 12px;
        text-align: left;
    }
    
    .breakdown-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .breakdown-table .amount {
        text-align: right;
        font-weight: 600;
    }
    
    .total-row {
        background-color: #ecf0f1;
        font-weight: bold;
        font-size: 1.1em;
    }
    
    .net-salary-row {
        background-color: #27ae60;
        color: white;
        font-size: 1.2em;
        font-weight: bold;
    }
    
    .amount-box {
        background-color: #ecf0f1;
        padding: 20px;
        border-radius: 10px;
        margin-top: 30px;
        text-align: center;
    }
    
    .signature-section {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
    }
    
    .signature-box {
        width: 45%;
    }
    
    .signature-line {
        border-top: 2px solid #2c3e50;
        margin-top: 50px;
        padding-top: 10px;
    }
    
    @media print {
        .no-print { display: none; }
        .payslip-container { box-shadow: none; }
    }
</style>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>📄 Employee Payslips - <?php echo $employee['name']; ?></h2>
        <a href="manage.php" class="btn btn-primary">← Back to Employees</a>
    </div>
        
        <?php if ($salaries && $salaries->num_rows > 0): ?>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2>Salary History</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #3498db; color: white;">
                        <th style="padding: 12px; text-align: left;">Month</th>
                        <th style="padding: 12px; text-align: right;">Gross Salary</th>
                        <th style="padding: 12px; text-align: right;">Total Deductions</th>
                        <th style="padding: 12px; text-align: right;">Net Salary</th>
                        <th style="padding: 12px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($salary = $salaries->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 12px;"><strong><?php echo date('F Y', strtotime($salary['month'] . '-01')); ?></strong></td>
                        <td style="padding: 12px; text-align: right;"><?php echo format_currency($salary['gross_salary']); ?></td>
                        <td style="padding: 12px; text-align: right; color: #e74c3c;"><?php echo format_currency($salary['total_deductions']); ?></td>
                        <td style="padding: 12px; text-align: right; color: #27ae60;"><strong><?php echo format_currency($salary['net_salary']); ?></strong></td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="payslip_print.php?id=<?php echo $salary['id']; ?>" 
                               target="_blank"
                               style="padding: 8px 16px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
                                👁️ View & Print
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        
        <div style="background: #fff3cd; padding: 30px; border-radius: 8px; text-align: center; border-left: 4px solid #ffc107;">
            <h2 style="margin-top: 0;">⚠️ No Salary Records Found</h2>
            <p style="font-size: 16px;">This employee doesn't have any processed salaries yet.</p>
            <a href="manage.php" style="padding: 12px 24px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px;">
                💰 Process Salary First
            </a>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>