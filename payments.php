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

$success = '';
$error = '';
$supplier_id = isset($_GET['supplier_id']) ? clean_input($_GET['supplier_id']) : 0;

// Get supplier details
$supplier_sql = "SELECT * FROM suppliers WHERE id = $supplier_id";
$supplier_result = $conn->query($supplier_sql);

if ($supplier_result->num_rows == 0) {
    header('Location: manage.php');
    exit;
}

$supplier = $supplier_result->fetch_assoc();

// Get unpaid/partial purchases for this supplier
$unpaid_sql = "SELECT * FROM purchases 
               WHERE supplier_id = $supplier_id 
               AND payment_status != 'Paid'
               ORDER BY purchase_date ASC";
$unpaid_purchases = $conn->query($unpaid_sql);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $purchase_id = clean_input($_POST['purchase_id']);
    $amount = clean_input($_POST['amount']);
    $payment_date = clean_input($_POST['payment_date']);
    $payment_method = clean_input($_POST['payment_method']);
    $reference_number = clean_input($_POST['reference_number']);
    $card_number = isset($_POST['card_number']) ? clean_input($_POST['card_number']) : '';
    $card_holder = isset($_POST['card_holder']) ? clean_input($_POST['card_holder']) : '';
    $notes = clean_input($_POST['notes']);
    
    // Get purchase details
    $purchase = $conn->query("SELECT * FROM purchases WHERE id = $purchase_id")->fetch_assoc();
    $remaining = $purchase['total_amount'] - $purchase['amount_paid'];
    
    if ($amount > $remaining) {
        $error = "Payment amount exceeds outstanding balance of " . format_currency($remaining);
    } else {
        $conn->begin_transaction();
        
        try {
            // Insert payment record
            $payment_notes = $notes;
            if ($payment_method == 'Card' && $card_number) {
                $masked_card = '****-****-****-' . substr($card_number, -4);
                $payment_notes .= " | Card: $masked_card | Holder: $card_holder";
            }
            
            $insert_sql = "INSERT INTO supplier_payments 
                          (purchase_id, supplier_id, amount, payment_date, payment_method, reference_number, notes) 
                          VALUES ($purchase_id, $supplier_id, $amount, '$payment_date', '$payment_method', '$reference_number', '$payment_notes')";
            $conn->query($insert_sql);
            
            // Update purchase payment status
            $new_amount_paid = $purchase['amount_paid'] + $amount;
            $new_status = ($new_amount_paid >= $purchase['total_amount']) ? 'Paid' : 'Partial';
            
            $update_sql = "UPDATE purchases 
                          SET amount_paid = $new_amount_paid, 
                              payment_status = '$new_status',
                              payment_date = '$payment_date'
                          WHERE id = $purchase_id";
            $conn->query($update_sql);
            
            $conn->commit();
            $success = "Payment of " . format_currency($amount) . " recorded successfully!";
            
            // Refresh unpaid purchases
            $unpaid_purchases = $conn->query($unpaid_sql);
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error processing payment: " . $e->getMessage();
        }
    }
}

// Get payment history for this supplier
$history_sql = "SELECT sp.*, p.purchase_date, p.total_amount 
                FROM supplier_payments sp
                JOIN purchases p ON sp.purchase_id = p.id
                WHERE sp.supplier_id = $supplier_id
                ORDER BY sp.payment_date DESC";
$payment_history = $conn->query($history_sql);

// Calculate total outstanding
$outstanding_sql = "SELECT SUM(total_amount - amount_paid) as outstanding 
                    FROM purchases 
                    WHERE supplier_id = $supplier_id AND payment_status != 'Paid'";
$outstanding = $conn->query($outstanding_sql)->fetch_assoc()['outstanding'] ?? 0;
?>

<div class="card">
    <h2>💳 Pay Supplier: <?php echo $supplier['supplier_name']; ?></h2>
    
    <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            <div>
                <strong>Contact:</strong> <?php echo $supplier['contact_person']; ?><br>
                <strong>Phone:</strong> <?php echo $supplier['phone']; ?>
            </div>
            <div>
                <strong>Payment Terms:</strong> <?php echo $supplier['payment_terms']; ?><br>
                <strong>Credit Limit:</strong> <?php echo format_currency($supplier['credit_limit']); ?>
            </div>
            <div>
                <strong>Outstanding Balance:</strong><br>
                <span style="font-size: 1.5rem; color: <?php echo $outstanding > 0 ? '#e74c3c' : '#27ae60'; ?>; font-weight: bold;">
                    <?php echo format_currency($outstanding); ?>
                </span>
            </div>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($outstanding > 0): ?>
    <h3>Unpaid Invoices</h3>
    <table style="margin-bottom: 2rem;">
        <thead>
            <tr>
                <th>Purchase Date</th>
                <th>Invoice Amount</th>
                <th>Amount Paid</th>
                <th>Outstanding</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($purchase = $unpaid_purchases->fetch_assoc()): 
                $remaining = $purchase['total_amount'] - $purchase['amount_paid'];
            ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($purchase['purchase_date'])); ?></td>
                <td><?php echo format_currency($purchase['total_amount']); ?></td>
                <td><?php echo format_currency($purchase['amount_paid']); ?></td>
                <td><strong style="color: #e74c3c;"><?php echo format_currency($remaining); ?></strong></td>
                <td>
                    <span style="background: #f39c12; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                        <?php echo $purchase['payment_status']; ?>
                    </span>
                </td>
                <td>
                    <button onclick="openPaymentForm(<?php echo $purchase['id']; ?>, <?php echo $remaining; ?>)" 
                            class="btn btn-success" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                        Make Payment
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="alert alert-success">
        ✅ All invoices paid! No outstanding balance.
    </div>
    <?php endif; ?>
</div>

<!-- Payment Form Modal -->
<div id="paymentModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; max-width: 600px; margin: 50px auto; padding: 2rem; border-radius: 8px; position: relative;">
        <button onclick="closePaymentForm()" style="position: absolute; top: 10px; right: 10px; border: none; background: #e74c3c; color: white; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
            ✕ Close
        </button>
        
        <h3>💳 Process Payment</h3>
        
        <form method="POST" action="" id="paymentForm">
            <input type="hidden" name="purchase_id" id="modal_purchase_id">
            
            <div class="form-group">
                <label>Amount to Pay (KSh) *</label>
                <input type="number" name="amount" id="modal_amount" step="0.01" required min="0.01">
                <small id="modal_remaining" style="color: #e74c3c;"></small>
            </div>
            
            <div class="form-group">
                <label>Payment Date *</label>
                <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Payment Method *</label>
                <select name="payment_method" id="payment_method" required onchange="toggleCardFields()">
                    <option value="">Select Method</option>
                    <option value="Cash">Cash</option>
                    <option value="M-Pesa">M-Pesa</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Card">Credit/Debit Card</option>
                </select>
            </div>
            
            <!-- Card Payment Fields -->
            <div id="cardFields" style="display:none; border: 2px solid #3498db; padding: 1rem; border-radius: 4px; margin: 1rem 0; background: #f8f9fa;">
                <h4 style="margin-bottom: 1rem; color: #3498db;">💳 Card Details</h4>
                
                <div class="form-group">
                    <label>Card Number *</label>
                    <input type="text" name="card_number" id="card_number" maxlength="19" placeholder="1234 5678 9012 3456" onkeyup="formatCardNumber(this)">
                    <small>We only store last 4 digits for security</small>
                </div>
                
                <div class="form-group">
                    <label>Card Holder Name *</label>
                    <input type="text" name="card_holder" id="card_holder" placeholder="Name on card">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Expiry Date *</label>
                        <input type="text" id="card_expiry" maxlength="5" placeholder="MM/YY" onkeyup="formatExpiry(this)">
                    </div>
                    <div class="form-group">
                        <label>CVV *</label>
                        <input type="password" id="card_cvv" maxlength="3" placeholder="123">
                    </div>
                </div>
                
                <div class="alert alert-warning" style="margin-top: 1rem;">
                    🔒 <strong>Security Note:</strong> Card details are NOT stored in the database. Only the last 4 digits are saved for reference.
                </div>
            </div>
            
            <div class="form-group">
                <label>Reference Number</label>
                <input type="text" name="reference_number" placeholder="Transaction/Receipt number">
            </div>
            
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="3" placeholder="Additional payment notes"></textarea>
            </div>
            
            <button type="submit" class="btn btn-success">Process Payment</button>
            <button type="button" onclick="closePaymentForm()" class="btn btn-danger">Cancel</button>
        </form>
    </div>
</div>

<!-- Payment History -->
<div class="card">
    <h3>📜 Payment History</h3>
    
    <?php if ($payment_history->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Payment Date</th>
                <th>Amount Paid</th>
                <th>Payment Method</th>
                <th>Reference</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($payment = $payment_history->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                <td><strong><?php echo format_currency($payment['amount']); ?></strong></td>
                <td><?php echo $payment['payment_method']; ?></td>
                <td><?php echo $payment['reference_number']; ?></td>
                <td><?php echo $payment['notes']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No payment history yet.</p>
    <?php endif; ?>
</div>

<script>
let currentPurchaseId = 0;
let currentRemaining = 0;

function openPaymentForm(purchaseId, remaining) {
    currentPurchaseId = purchaseId;
    currentRemaining = remaining;
    
    document.getElementById('modal_purchase_id').value = purchaseId;
    document.getElementById('modal_amount').value = remaining.toFixed(2);
    document.getElementById('modal_amount').max = remaining;
    document.getElementById('modal_remaining').textContent = 'Outstanding: KSh ' + remaining.toFixed(2);
    
    document.getElementById('paymentModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePaymentForm() {
    document.getElementById('paymentModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('paymentForm').reset();
    document.getElementById('cardFields').style.display = 'none';
}

function toggleCardFields() {
    const method = document.getElementById('payment_method').value;
    const cardFields = document.getElementById('cardFields');
    
    if (method === 'Card') {
        cardFields.style.display = 'block';
        document.getElementById('card_number').required = true;
        document.getElementById('card_holder').required = true;
        document.getElementById('card_expiry').required = true;
        document.getElementById('card_cvv').required = true;
    } else {
        cardFields.style.display = 'none';
        document.getElementById('card_number').required = false;
        document.getElementById('card_holder').required = false;
        document.getElementById('card_expiry').required = false;
        document.getElementById('card_cvv').required = false;
    }
}

function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, '');
    let formattedValue = value.match(/.{1,4}/g);
    input.value = formattedValue ? formattedValue.join(' ') : value;
}

function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 2) {
        input.value = value.slice(0, 2) + '/' + value.slice(2, 4);
    } else {
        input.value = value;
    }
}

// Close modal when clicking outside
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentForm();
    }
});
</script>

<div style="text-align: center; margin-top: 2rem;">
    <a href="manage.php" class="btn btn-primary">← Back to Suppliers</a>
    <a href="../index.php" class="btn btn-success">🏠 Dashboard</a>
</div>

<?php include '../includes/footer.php'; ?>