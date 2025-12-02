<?php
include '../includes/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    $delete_sql = "DELETE FROM products WHERE id = $id";
    if ($conn->query($delete_sql)) {
        echo '<div class="alert alert-success">Product deleted successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error deleting product: ' . $conn->error . '</div>';
    }
}

// Get all products
$sql = "SELECT * FROM products ORDER BY product_name ASC";
$products = $conn->query($sql);
?>

<div class="card">
    <h2>📦 Products List</h2>
    <div style="margin-bottom: 1rem;">
        <a href="add.php" class="btn btn-primary">➕ Add New Product</a>
    </div>
    
    <?php if ($products->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Buying Price</th>
                <th>Selling Price</th>
                <th>Current Stock</th>
                <th>Reorder Level</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = $products->fetch_assoc()): ?>
            <tr>
                <td><?php echo $product['product_code']; ?></td>
                <td>
                    <?php echo $product['product_name']; ?>
                    <?php if ($product['current_stock'] <= $product['reorder_level']): ?>
                        <span class="low-stock">⚠️</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $product['category']; ?></td>
                <td><?php echo format_currency($product['buying_price']); ?></td>
                <td><?php echo format_currency($product['selling_price']); ?></td>
                <td><?php echo $product['current_stock'] . ' ' . $product['unit']; ?></td>
                <td><?php echo $product['reorder_level'] . ' ' . $product['unit']; ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-warning" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Edit</a>
                    <a href="list.php?delete=<?php echo $product['id']; ?>" 
                       class="btn btn-danger" 
                       style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"
                       onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No products found. <a href="add.php">Add your first product</a>.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>