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

// Handle supplier add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $supplier_name = clean_input($_POST['supplier_name']);
    $contact_person = clean_input($_POST['contact_person']);
    $phone = clean_input($_POST['phone']);
    $email = clean_input($_POST['email']);
    $address = clean_input($_POST['address']);
    $payment_terms = clean_input($_POST['payment_terms']);
    $credit_limit = clean_input($_POST['credit_limit']);
    
    if ($_POST['action'] == 'add') {
        $sql = "INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, payment_terms, credit_limit) 
                VALUES ('$supplier_name', '$contact_person', '$phone', '$email', '$address', '$payment_terms', $credit_limit)";
        if ($conn->query($sql)) {
            $success = "Supplier added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    if ($conn->query("DELETE FROM suppliers WHERE id = $id")) {
        $success = "Supplier deleted successfully!";
    } else {
        $error = "Error deleting supplier!";
    }
}

// Get all suppliers with outstanding balances
$suppliers_sql = "SELECT s.*, 
                  COALESCE(SUM(CASE WHEN p.payment_status != 'Paid' THEN p.total_amount - p.amount_paid ELSE 0 END), 0) as outstanding
                  FROM suppliers s
                  LEFT JOIN purchases p ON s.id = p.supplier_id
                  GROUP BY s.id
                  ORDER BY s.supplier_name ASC";
$suppliers = $conn->query($suppliers_sql);
?>

<div class="card">
    <h2>👥 Supplier Management</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <button onclick="document.getElementById('addSupplierForm').style.display='block'" class="btn btn-primary">
        ➕ Add New Supplier
    </button>
    
    <!-- Add Supplier Form (Hidden by default) -->
    <div id="addSupplierForm" style="display:none; margin-top: 1.5rem; border: 2px solid #3498db; padding: 1.5rem; border-radius: 8px;">
        <h3>Add New Supplier</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Supplier Name *</label>
                <input type="text" name="supplier_name" required>
            </div>
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" name="contact_person">
            </div>
            <div class="form-group">
                <label>Phone *</label>
                <input type="text" name="phone" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email">
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Payment Terms *</label>
                <select name="payment_terms" required>
                    <option value="Cash">Cash on Delivery</option>
                    <option value="7 Days">7 Days Credit</option>
                    <option value="14 Days">14 Days Credit</option>
                    <option value="30 Days">30 Days Credit</option>
                    <option value="60 Days">60 Days Credit</option>
                </select>
            </div>
            <div class="form-group">
                <label>Credit Limit (KSh)</label>
                <input type="number" name="credit_limit" step="0.01" value="0" min="0">
            </div>
            <button type="submit" class="btn btn-success">Save Supplier</button>
            <button type="button" onclick="document.getElementById('addSupplierForm').style.display='none'" class="btn btn-danger">Cancel</button>
        </form>
    </div>
    
    <h3 style="margin-top: 2rem;">Supplier List</h3>
    
    <?php if ($suppliers->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Supplier Name</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Payment Terms</th>
                <th>Credit Limit</th>
                <th>Outstanding Balance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($supplier = $suppliers->fetch_assoc()): ?>
            <tr style="<?php echo $supplier['outstanding'] > $supplier['credit_limit'] && $supplier['credit_limit'] > 0 ? 'background: #fff3cd;' : ''; ?>">
                <td><strong><?php echo $supplier['supplier_name']; ?></strong></td>
                <td><?php echo $supplier['contact_person']; ?></td>
                <td><?php echo $supplier['phone']; ?></td>
                <td><?php echo $supplier['email']; ?></td>
                <td><?php echo $supplier['payment_terms']; ?></td>
                <td><?php echo format_currency($supplier['credit_limit']); ?></td>
                <td style="<?php echo $supplier['outstanding'] > 0 ? 'color: #e74c3c; font-weight: bold;' : 'color: #27ae60;'; ?>">
                    <?php echo format_currency($supplier['outstanding']); ?>
                    <?php if ($supplier['outstanding'] > $supplier['credit_limit'] && $supplier['credit_limit'] > 0): ?>
                        <span class="low-stock">⚠️ Over Limit</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="payments.php?supplier_id=<?php echo $supplier['id']; ?>" class="btn btn-success" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Pay</a>
                    <a href="manage.php?delete=<?php echo $supplier['id']; ?>" 
                       class="btn btn-danger" 
                       style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"
                       onclick="return confirm('Delete this supplier?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No suppliers found.</p>
    <?php endif; ?>
</div>

<div style="text-align: center; margin-top: 1.5rem;">
    <a href="../index.php" class="btn btn-primary">Back to Dashboard</a>
</div>

<?php include '../includes/footer.php'; ?> 