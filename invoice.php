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

$sale_id = isset($_GET['id']) ? clean_input($_GET['id']) : 0;

$sale_sql = "SELECT s.*, p.product_name, p.product_code, p.unit 
             FROM sales s 
             JOIN products p ON s.product_id = p.id 
             WHERE s.id = $sale_id";
$sale_result = $conn->query($sale_sql);

if ($sale_result->num_rows == 0) {
    die("Sale not found!");
}

$sale = $sale_result->fetch_assoc();
$vat_amount = $sale['total_amount'] * 0.16 / 1.16;
$amount_before_vat = $sale['total_amount'] - $vat_amount;
$outstanding = $sale['total_amount'] - $sale['amount_paid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $sale_id; ?> - DevTech Partners</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; padding: 20px; max-width: 210mm; margin: 0 auto; }
        .invoice { border: 3px solid #2c3e50; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 28px; color: #2c3e50; margin-bottom: 5px; }
        .header p { font-size: 12px; color: #7f8c8d; margin: 2px 0; }
        .doc-type { text-align: center; background: #e74c3c; color: white; padding: 15px; margin: 20px 0; font-size: 24px; font-weight: bold; letter-spacing: 2px; }
        .invoice-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .info-box { border: 2px solid #ddd; padding: 15px; border-radius: 5px; }
        .info-box h3 { color: #2c3e50; margin-bottom: 10px; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        .info-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th { background: #34495e; color: white; padding: 12px; text-align: left; }
        table td { padding: 12px; border-bottom: 1px solid #ddd; }
        .totals { float: right; width: 50%; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 16px; }
        .total-row.grand { background: #e74c3c; color: white; padding: 15px; margin-top: 10px; font-size: 20px; font-weight: bold; }
        .payment-due { background: #fff3cd; border: 3px solid #f39c12; padding: 20px; margin: 20px 0; text-align: center; }
        .payment-due h3 { color: #856404; font-size: 24px; margin-bottom: 10px; }
        .terms { background: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #3498db; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px dashed #ddd; font-size: 12px; color: #7f8c8d; }
        .no-print { text-align: center; margin-top: 20px; }
        @media print { .no-print { display: none; } .invoice { border: none; } }
        button { padding: 12px 25px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn-print { background: #27ae60; color: white; }
        .btn-back { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="header">
            <h1>🌾 DevTech Partners</h1>
            <p>Premium Cereals & Grains Supplier</p>
            <p>Juja, Kiambu County, Kenya | Tel: +254 XXX XXX XXX | PIN: XXXXXXXXXX</p>
        </div>
        
        <div class="doc-type">⚠️ INVOICE - PAYMENT DUE</div>
        
        <div class="invoice-info">
            <div class="info-box">
                <h3>INVOICE DETAILS</h3>
                <div class="info-row"><strong>Invoice No:</strong> <span>INV-<?php echo str_pad($sale_id, 6, '0', STR_PAD_LEFT); ?></span></div>
                <div class="info-row"><strong>Invoice Date:</strong> <span><?php echo date('d/m/Y', strtotime($sale['sale_date'])); ?></span></div>
                <div class="info-row"><strong>Due Date:</strong> <span><?php echo date('d/m/Y', strtotime($sale['sale_date'] . ' +30 days')); ?></span></div>
                <div class="info-row"><strong>Status:</strong> <span style="color: #e74c3c; font-weight: bold;">UNPAID</span></div>
            </div>
            
            <div class="info-box">
                <h3>BILL TO</h3>
                <div class="info-row"><strong>Customer:</strong> <span><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></span></div>
                <div class="info-row"><strong>Date:</strong> <span><?php echo date('d/m/Y', strtotime($sale['sale_date'])); ?></span></div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center;">Quantity</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php echo $sale['product_name']; ?></strong><br><small>Code: <?php echo $sale['product_code']; ?></small></td>
                    <td style="text-align: center;"><?php echo $sale['quantity'] . ' ' . $sale['unit']; ?></td>
                    <td style="text-align: right;">KSh <?php echo number_format($sale['unit_price'], 2); ?></td>
                    <td style="text-align: right;"><strong>KSh <?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class="totals">
            <div class="total-row"><span>Subtotal:</span> <span>KSh <?php echo number_format($amount_before_vat, 2); ?></span></div>
            <div class="total-row"><span>VAT (16%):</span> <span>KSh <?php echo number_format($vat_amount, 2); ?></span></div>
            <div class="total-row grand">
                <span>AMOUNT DUE:</span> <span>KSh <?php echo number_format($outstanding, 2); ?></span>
            </div>
        </div>
        
        <div style="clear: both;"></div>
        
        <div class="payment-due">
            <h3>⚠️ PAYMENT REQUIRED</h3>
            <p style="font-size: 18px; margin: 10px 0;">Amount Outstanding: <strong style="color: #e74c3c;">KSh <?php echo number_format($outstanding, 2); ?></strong></p>
            <p>Payment Due: <strong><?php echo date('d F Y', strtotime($sale['sale_date'] . ' +30 days')); ?></strong></p>
        </div>
        
        <div class="terms">
            <h4 style="margin-bottom: 10px; color: #2c3e50;">PAYMENT TERMS & CONDITIONS:</h4>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>Payment is due within 30 days of invoice date</li>
                <li>Accepted payment methods: Cash, M-Pesa, Bank Transfer</li>
                <li>M-Pesa Paybill: XXXXX, Account: Your Name</li>
                <li>Bank Details: [Your Bank Name], Account: XXXXXXXXXX</li>
                <li>Please quote Invoice Number when making payment</li>
                <li>Late payments may incur additional charges</li>
            </ul>
        </div>
        
        <?php if ($sale['notes']): ?>
        <div style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #e74c3c;">
            <strong>Notes:</strong> <?php echo nl2br($sale['notes']); ?>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>For inquiries, contact us at: info@devtechpartners.co.ke | Tel: +254 XXX XXX XXX</p>
            <p style="margin-top: 10px; font-size: 10px;">This is a computer-generated invoice and is valid without signature.</p>
        </div>
    </div>
    
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Print Invoice</button>
        <button class="btn-back" onclick="window.location.href='history.php'">← Back to Sales</button>
        <button class="btn-back" onclick="window.location.href='../index.php'">🏠 Dashboard</button>
    </div>
</body>
</html>