<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$salary_id = isset($_GET['id']) ? clean_input($_GET['id']) : 0;

if ($salary_id == 0) {
    die("Invalid payslip ID");
}

$sql = "SELECT sd.*, e.name, e.id_number, e.position, e.employment_type, e.phone, e.email
        FROM salary_deductions sd
        JOIN employees e ON sd.employee_id = e.id
        WHERE sd.id = $salary_id";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("Payslip not found!");
}

$payslip = $result->fetch_assoc();

// Function to convert number to words
function numberToWords($number) {
    $number = (int)$number;
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 
             'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 
             'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    if ($number < 20) return $ones[$number];
    if ($number < 100) return $tens[floor($number/10)] . ' ' . $ones[$number%10];
    if ($number < 1000) return $ones[floor($number/100)] . ' Hundred ' . numberToWords($number%100);
    if ($number < 1000000) return numberToWords(floor($number/1000)) . ' Thousand ' . numberToWords($number%1000);
    return 'Amount Too Large';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?php echo htmlspecialchars($payslip['name']); ?> - <?php echo date('F Y', strtotime($payslip['month'])); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', 'Courier', monospace;
            padding: 20px;
            background: #f5f5f5;
            font-size: 12px;
        }
        
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border: 2px dashed #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 16px;
            margin: 5px 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header p {
            font-size: 11px;
            margin: 3px 0;
        }
        
        .section {
            margin: 15px 0;
        }
        
        .section-title {
            font-weight: bold;
            text-align: center;
            margin: 10px 0 5px 0;
            text-decoration: underline;
        }
        
        .info-line {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 11px;
        }
        
        .info-line .label {
            font-weight: bold;
        }
        
        .divider {
            border-bottom: 1px dashed #333;
            margin: 10px 0;
        }
        
        .double-divider {
            border-bottom: 2px solid #333;
            margin: 10px 0;
        }
        
        .amount-line {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 11px;
        }
        
        .amount-line.indent {
            padding-left: 20px;
        }
        
        .amount-line.total {
            font-weight: bold;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .amount-line.grand-total {
            font-weight: bold;
            font-size: 13px;
            margin: 10px 0;
            padding: 5px 0;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }
        
        .net-box {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border: 2px solid #333;
            background: #f0f0f0;
        }
        
        .net-box .label {
            font-size: 11px;
            font-weight: bold;
        }
        
        .net-box .amount {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .net-box .words {
            font-size: 10px;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px dashed #333;
            font-size: 10px;
        }
        
        .footer p {
            margin: 3px 0;
        }
        
        .no-print {
            text-align: center;
            margin: 20px auto;
            max-width: 400px;
        }
        
        .no-print button, .no-print a {
            padding: 8px 20px;
            margin: 5px;
            border: 1px solid #333;
            background: white;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            color: #333;
            font-family: 'Courier New', 'Courier', monospace;
        }
        
        .no-print button:hover, .no-print a:hover {
            background: #333;
            color: white;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            .container {
                border: 2px dashed #000;
                max-width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Action Buttons -->
    <div class="no-print">
        <button onclick="window.print()">PRINT</button>
        <a href="payslips.php?employee_id=<?php echo $payslip['employee_id']; ?>">BACK</a>
        <button onclick="window.close()">CLOSE</button>
    </div>

    <!-- Payslip Receipt -->
    <div class="container">
        <!-- Header -->
        <div class="header">
            <p>================================</p>
            <h1>DEVTECH PARTNERS GROUP</h1>
            <p>Cereals Inventory System</p>
            <p>================================</p>
            <p style="font-weight: bold; margin-top: 10px;">SALARY PAYSLIP</p>
            <p><?php echo strtoupper(date('F Y', strtotime($payslip['month'] . '-01'))); ?></p>
        </div>
        
        <!-- Employee Info -->
        <div class="section">
            <div class="info-line">
                <span class="label">EMPLOYEE:</span>
                <span><?php echo strtoupper(substr($payslip['name'], 0, 20)); ?></span>
            </div>
            <div class="info-line">
                <span class="label">ID NUMBER:</span>
                <span><?php echo $payslip['id_number']; ?></span>
            </div>
            <div class="info-line">
                <span class="label">POSITION:</span>
                <span><?php echo strtoupper(substr($payslip['position'], 0, 20)); ?></span>
            </div>
            <div class="info-line">
                <span class="label">TYPE:</span>
                <span><?php echo strtoupper($payslip['employment_type']); ?></span>
            </div>
            <div class="info-line">
                <span class="label">DATE:</span>
                <span><?php echo date('d/m/Y'); ?></span>
            </div>
        </div>
        
        <div class="double-divider"></div>
        
        <!-- Salary Calculation Flow -->
        <div class="section">
            <div class="section-title">SALARY CALCULATION</div>
            
            <div class="amount-line">
                <span>GROSS SALARY</span>
                <span><?php echo number_format($payslip['gross_salary'], 2); ?></span>
            </div>
            
            <?php if ($payslip['employment_type'] == 'Contract'): ?>
            
            <div class="divider"></div>
            <div style="text-align: center; font-size: 10px; margin: 5px 0;">LESS: DEDUCTIONS</div>
            <div class="divider"></div>
            
            <div class="amount-line indent">
                <span>SHIF (2.75%)</span>
                <span>(<?php echo number_format($payslip['shif'], 2); ?>)</span>
            </div>
            
            <div class="amount-line indent">
                <span>NSSF (6%)</span>
                <span>(<?php echo number_format($payslip['nssf'], 2); ?>)</span>
            </div>
            
            <div class="amount-line indent">
                <span>Housing Levy (1.5%)</span>
                <span>(<?php echo number_format($payslip['housing_levy'], 2); ?>)</span>
            </div>
            
            <div class="amount-line indent">
                <span>PAYE Tax</span>
                <span>(<?php echo number_format($payslip['paye'], 2); ?>)</span>
            </div>
            
            <div class="divider"></div>
            
            <div class="amount-line total">
                <span>TOTAL DEDUCTIONS</span>
                <span>(<?php echo number_format($payslip['total_deductions'], 2); ?>)</span>
            </div>
            
            <?php else: ?>
            
            <div class="divider"></div>
            <div style="text-align: center; font-size: 10px; margin: 10px 0; padding: 5px; border: 1px dashed #333;">
                ** CASUAL EMPLOYEE **<br>
                NO DEDUCTIONS APPLIED
            </div>
            
            <?php endif; ?>
            
            <div class="double-divider"></div>
        </div>
        
        <!-- Net Salary Box -->
        <div class="net-box">
            <div class="label">NET SALARY PAYABLE</div>
            <div class="amount">KSh <?php echo number_format($payslip['net_salary'], 2); ?></div>
            <div class="words">
                (<?php echo strtoupper(numberToWords($payslip['net_salary'])); ?> SHILLINGS ONLY)
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>================================</p>
            <p>COMPUTER GENERATED RECEIPT</p>
            <p>NO SIGNATURE REQUIRED</p>
            <p><?php echo date('d/m/Y H:i'); ?></p>
            <p>================================</p>
            <p style="margin-top: 10px; font-size: 9px;">CONFIDENTIAL DOCUMENT</p>
        </div>
    </div>

    <script>
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>