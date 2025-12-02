<?php
require_once __DIR__ . '/../config/database.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevTech Partners Group</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .navbar nav {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .navbar a:hover,
        .navbar a.active {
            background: #34495e;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .card h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        table th,
        table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background: #34495e;
            color: white;
            font-weight: 600;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }
        
        .low-stock {
            background: #e74c3c;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .navbar nav {
                flex-direction: column;
            }
            
            table {
                font-size: 0.85rem;
            }
            
            .btn {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🌾 DevTech Partners Group</h1>
        <nav>
            <a href="/cereals-inventory/index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="/cereals-inventory/products/list.php" class="<?php echo $current_page == 'list.php' ? 'active' : ''; ?>">Products</a>
            <a href="/cereals-inventory/sales/record.php" class="<?php echo $current_page == 'record.php' && strpos($_SERVER['PHP_SELF'], 'sales') ? 'active' : ''; ?>">Record Sale</a>
            <a href="/cereals-inventory/purchases/record.php" class="<?php echo $current_page == 'record.php' && strpos($_SERVER['PHP_SELF'], 'purchases') ? 'active' : ''; ?>">Record Purchase</a>
            <a href="/cereals-inventory/sales/history.php" class="<?php echo $current_page == 'history.php' && strpos($_SERVER['PHP_SELF'], 'sales') ? 'active' : ''; ?>">Sales History</a>
            <a href="/cereals-inventory/purchases/history.php" class="<?php echo $current_page == 'history.php' && strpos($_SERVER['PHP_SELF'], 'purchases') ? 'active' : ''; ?>">Purchase History</a>
            <a href="/cereals-inventory/reports/stock.php" class="<?php echo $current_page == 'stock.php' ? 'active' : ''; ?>">Stock Report</a>
        </nav>
    </div>
    <div class="container">