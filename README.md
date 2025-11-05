# 📚 Library Management System

A comprehensive web-based Library Management System built with PHP, MySQL, and JavaScript. This system provides complete book management functionality for both administrators and users, including book issuing, returning, real-time penalty calculation, and integrated payment processing.

## ✨ Features

### 👤 User Features
- **User Authentication**: Secure login/signup system with session management
- **Personal Dashboard**: Track issued books, returned books, overdue books, and balance
- **Book Issuing**: Request and issue books with automatic form filling for logged-in users
- **Book Returning**: Return books with automatic penalty calculation
- **Real-time Penalty Tracking**: Automatic calculation of overdue penalties with grace period
- **Payment Processing**: Integrated payment modal with multiple payment methods:
  - Cash (with receipt number generation)
  - GCash/PayMaya (with account details and reference number)
  - Bank Transfer (with bank and account details)
  - Check (with check details)
- **Auto-generated Receipts**: Automatic generation of receipt and reference numbers
- **Book Search**: Search and filter books by title, author, ISBN, genre, and availability

### 🔐 Admin Features
- **Admin Dashboard**: Comprehensive overview of all library operations
- **Book Inventory Management**: Add, edit, and manage books in the library
- **Issued Books Tracking**: Monitor all currently issued books
- **Returned Books History**: View complete history of returned books
- **Overdue Books Management**: Track and manage overdue books
- **Balance Management**: View and clear user balances
- **User Management**: Manage user accounts and permissions

### 💳 Payment System
- **Multi-step Payment Modal**: 
  1. Payment method selection
  2. Payment details form (dynamic fields based on payment method)
  3. Payment processing with real-time feedback
- **Auto-generated Identifiers**: 
  - Receipt numbers for cash payments
  - Reference numbers for digital payments
- **Payment History**: Complete payment tracking with transaction details

### ⚙️ Technical Features
- **Real-time Penalty Calculation**: Automatic calculation based on:
  - Grace period (1 day)
  - Daily penalty rate (₱5.00/day)
  - Maximum fine cap (₱100.00)
- **Database Transactions**: Secure transaction handling for book returns
- **Responsive Design**: Modern UI with color-coded sections and tables
- **AJAX Integration**: Seamless payment processing without page reloads
- **Session Management**: Secure user sessions with role-based access control

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: XAMPP (Apache + MySQL)

## 📋 Database Schema

The system uses the following main tables:
- `users` - User accounts with role-based access
- `books` - Book inventory with availability tracking
- `issued_books` - Book issuance records
- `book_returns` - Return records with payment details
- `deleted_books` - Archive for deleted books

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/library-management-system.git
   cd library-management-system
   ```

2. **Set up XAMPP**
   - Install XAMPP on your system
   - Start Apache and MySQL services

3. **Import Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `library_db`
   - Import `library_db.sql` file

4. **Configure Database Connection**
   - Update `db_connect.php` with your database credentials if needed:
     ```php
     $servername = "localhost";
     $username = "root";
     $password = "";
     $database = "library_db";
     ```

5. **Place Files**
   - Copy all files to `C:\xampp\htdocs\chapterone\` (or your XAMPP htdocs directory)

6. **Access the Application**
   - Open browser and navigate to: `http://localhost/chapterone/home.php`

## 📝 Default Admin Credentials

- **Username**: `admin`
- **Password**: `1234`

(Change these in `books_inventory.php` for production use)

## 🎯 Usage

### For Users:
1. Sign up for a new account or log in
2. Browse available books from the home page
3. Issue books from the dashboard
4. Return books through the return page (with automatic penalty calculation)
5. View your personal dashboard for tracking

### For Administrators:
1. Log in with admin credentials
2. Access the admin dashboard
3. Manage book inventory
4. Monitor issued and returned books
5. Track overdue books and manage balances

## 📸 Features Overview

- ✅ User authentication and authorization
- ✅ Book inventory management
- ✅ Book issuing and returning
- ✅ Real-time penalty calculation
- ✅ Multi-payment method support
- ✅ Automatic receipt/reference number generation
- ✅ Admin and user dashboards
- ✅ Overdue book tracking
- ✅ Balance management
- ✅ Search and filter functionality

## 🔒 Security Features

- Session-based authentication
- Password hashing
- SQL injection prevention (prepared statements)
- Role-based access control
- Input validation and sanitization

## 📄 License

This project is open source and available under the MIT License.

## 👥 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📧 Contact

For questions or support, please open an issue in the repository.

---

**Note**: This is a development project. Update database credentials and admin passwords before deploying to production.

