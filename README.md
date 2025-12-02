# Cereals Inventory Management System

A simple MySQL-based inventory management system designed for a cereals business in Kenya.  
This project helps track products, purchases, sales, suppliers, and stock levels with basic reporting features.

---

## 📦 Features

- **Product Management**: Add, edit, delete cereals (e.g., maize, beans, rice, millet).
- **Stock Tracking**: Monitor current quantities, reorder levels, and availability status.
- **Sales Recording**: Record customer sales with product, quantity, unit price, and total amount.
- **Purchases/Restocking**: Track supplier purchases and update stock automatically.
- **Basic Reporting**: Generate summaries of stock levels, sales totals, and reorder alerts.

---

## 🗄 Database Schema

The system uses four main tables:

- **products** → Stores cereal details, prices, stock, reorder levels, availability.
- **suppliers** → Supplier information for purchases.
- **purchases** → Records restocking transactions linked to suppliers and products.
- **sales** → Records customer sales linked to products.

---

## ⚙️ Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/cereals-inventory-system.git
   cd cereals-inventory-system
