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
require_once '../config/database.php';

// Get sale ID from URL
$sale_id = isset($_GET['id']) ? clean_input($_GET['id']) : 0;

// Get sale details
$sale_sql = "SELECT s.*, p.product_name, p.product_code, p.unit 
             FROM sales s 
             JOIN products p ON s.product_id = p.id 
             WHERE s.id = $sale_id";
$sale_result = $conn->query($sale_sql);

if ($sale_result->num_rows == 0) {
    die("Sale not found!");
}

$sale = $sale_result->fetch_assoc();

// Calculate VAT
$vat_amount = $sale['total_amount'] * 0.16 / 1.16;
$amount_before_vat = $sale['total_amount'] - $vat_amount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Receipt #<?php echo $sale_id; ?> - DevTech Partners</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            max-width: 80mm;
            margin: 0 auto;
        }
        
        .receipt {
            border: 2px solid #000;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 11px;
            margin: 2px 0;
        }
        
        .info-section {
            margin: 15px 0;
            font-size: 12px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .items {
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            padding: 10px 0;
            margin: 15px 0;
        }
        
        .item-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            margin: 5px 0;
            font-size: 11px;
        }
        
        .totals {
            margin-top: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 12px;
        }
        
        .total-row.grand {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            border-top: 2px dashed #000;
            padding-top: 10px;
        }
        
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            .receipt {
                border: none;
            }
        }
        
        button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-print {
            background: #27ae60;
            color: white;
        }
        
        .btn-back {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <h1>🌾 DevTech Partners</h1>
            <p>Premium Cereals & Grains Supplier</p>
            <p>Juja, Kiambu County, Kenya</p>
            <p>Tel: +254 718249497</p>
            <p>PIN: WAMBUGUKEVIN11</p>
        </div>
        
        <!-- Receipt Info -->
        <div class="info-section">
            <div class="info-row">
                <strong>RECEIPT NO:</strong>
                <span>#<?php echo str_pad($sale_id, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <strong>DATE:</strong>
                <span><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <strong>CUSTOMER:</strong>
                <span><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></span>
            </div>
        </div>
        
        <!-- Items -->
        <div class="items">
            <div class="item-header">
                <div>ITEM</div>
                <div style="text-align: right;">QTY</div>
                <div style="text-align: right;">PRICE</div>
                <div style="text-align: right;">TOTAL</div>
            </div>
            
            <div class="item-row">
                <div><?php echo $sale['product_name']; ?></div>
                <div style="text-align: right;"><?php echo $sale['quantity']; ?></div>
                <div style="text-align: right;"><?php echo number_format($sale['unit_price'], 2); ?></div>
                <div style="text-align: right;"><?php echo number_format($sale['total_amount'], 2); ?></div>
            </div>
        </div>
        
        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>KSh <?php echo number_format($amount_before_vat, 2); ?></span>
            </div>
            <div class="total-row">
                <span>VAT (16%):</span>
                <span>KSh <?php echo number_format($vat_amount, 2); ?></span>
            </div>
            <div class="total-row grand">
                <span>TOTAL:</span>
                <span>KSh <?php echo number_format($sale['total_amount'], 2); ?></span>
            </div>
        </div>
        
        <!-- Payment Info -->
        <div class="info-section" style="margin-top: 15px;">
            <div class="info-row">
                <strong>PAYMENT METHOD:</strong>
                <span>CASH/M-PESA</span>
            </div>
            <div class="info-row">
                <strong>STATUS:</strong>
                <span>PAID</span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>Quality cereals you can trust</p>
            <p style="margin-top: 10px;">For inquiries: info@devtechpartners.co.ke</p>
            <p style="margin-top: 5px; font-size: 10px;">
                This is a computer-generated receipt.<br>
                Valid without signature.
            </p>
        </div>
    </div>
    
    <!-- Print Buttons -->
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
        <button class="btn-back" onclick="window.location.href='history.php'">← Back to Sales</button>
        <button class="btn-back" onclick="window.location.href='../index.php'">🏠 Dashboard</button>
    </div>
</body>
</html> 