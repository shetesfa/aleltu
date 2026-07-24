# ðŸ›ï¸ ALELTU POS & Inventory Management System (Private)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Privacy](https://img.shields.io/badge/Repository-Private-red?style=for-the-badge)

A comprehensive Point of Sale (POS), Inventory Control, and Multi-Branch Management application built for **Aleltu**.

## âœ¨ Key Features
- ðŸª **Multi-Branch Control**: Transfer stock between main warehouse and branch outlets (ranch_controller.php, dmin_send_stock.php).
- ðŸ’µ **Daily Cashier Operations**: Manage daily cash flow, transactions, and customer receipts (daily_cashier.php, eceipt.php).
- ðŸ“Š **Manager & Super Admin Dashboard**: Real-time sales analytics, transaction edit logs, and user permissions (super_admin.php, edit_history.php).
- ðŸ’¾ **Automated Database Backup**: Built-in automated MySQL database backup system (ackups/system/).
- ðŸ“¥ **Excel Export**: Export withdrawal logs and sales reports directly to Excel format (export_withdrawals_excel.php).

## ðŸ“ Repository Structure
`
aleltu/
â”œâ”€â”€ backups/           # Database backup storage
â”œâ”€â”€ error_docs/        # Custom HTTP error pages
â”œâ”€â”€ logs/              # System access & error logs
â”œâ”€â”€ database.sql       # MySQL Database dump
â”œâ”€â”€ admin_dashboard.php
â”œâ”€â”€ seller_pos.php
â”œâ”€â”€ super_admin.php
â””â”€â”€ README.md
`

## ðŸ› ï¸ Installation & Setup
1. Clone this repository into your XAMPP htdocs:
   `ash
   git clone https://github.com/shetesfa/aleltu.git
   `
2. Import database.sql into **phpMyAdmin** (http://localhost/phpmyadmin).
3. Configure database credentials in config.php.
4. Access via browser: http://localhost/aleltu/

---
Developed with â¤ï¸ by **[shetesfa](https://github.com/shetesfa)**
