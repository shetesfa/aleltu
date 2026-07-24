# 🛍️ ALELTU POS & Inventory Management System

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Privacy](https://img.shields.io/badge/Repository-Private-red?style=for-the-badge)

A comprehensive Point of Sale (POS), Inventory Control, and Multi-Branch Management web application built for **Aleltu**.

## ✨ Key Features
- 🏪 **Multi-Branch Control**: Transfer stock between main warehouse and branch outlets (`branch_controller.php`, `admin_send_stock.php`).
- 💵 **Daily Cashier Operations**: Manage daily cash flow, transactions, and customer receipts (`daily_cashier.php`, `receipt.php`).
- 📊 **Manager & Super Admin Dashboard**: Real-time sales analytics, transaction edit logs, and user permissions (`super_admin.php`, `edit_history.php`).
- 💾 **Automated Database Backup**: Built-in automated MySQL database backup system (`backups/system/`).
- 📥 **Excel Export**: Export withdrawal logs and sales reports directly to Excel format (`export_withdrawals_excel.php`).

## 📁 Repository Structure
```
aleltu/
├── backups/           # Database backup storage
├── error_docs/        # Custom HTTP error pages
├── logs/              # System access & error logs
├── database.sql       # MySQL Database dump
├── admin_dashboard.php
├── seller_pos.php
├── super_admin.php
└── README.md
```

## 🛠️ Installation & Setup
1. Clone this repository into your XAMPP `htdocs`:
   ```bash
   git clone https://github.com/shetesfa/aleltu.git
   ```
2. Import `database.sql` into **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Configure database credentials in `config.php`.
4. Access via browser: `http://localhost/aleltu/`

---
Developed with ❤️ by **[shetesfa](https://github.com/shetesfa)**
