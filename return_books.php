<?php
session_start();

// Connect to your database
$servername = "localhost";  // Change if needed
$username = "root";         // Default MySQL username
$password = "";             // Default is empty for local servers
$database = "library_db";     // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get logged-in user info if session exists
$loggedInUser = null;
$loggedInStudentNumber = '';
$loggedInBorrowDate = '';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userQuery = $conn->prepare("SELECT student_number, username, email, contact FROM users WHERE id = ?");
    $userQuery->bind_param("i", $userId);
    $userQuery->execute();
    $result = $userQuery->get_result();
    if ($result->num_rows > 0) {
        $loggedInUser = $result->fetch_assoc();
        $loggedInStudentNumber = $loggedInUser['student_number'];
    }
    $userQuery->close();
    
    // If ISBN is provided via URL, get the borrow_date from issued_books
    if (isset($_GET['isbn']) && isset($_GET['student_number'])) {
        $isbn = $_GET['isbn'];
        $studentNum = $_GET['student_number'];
        $borrowQuery = $conn->prepare("SELECT borrow_date FROM issued_books WHERE book_isbn = ? AND student_number = ? AND status = 'issued'");
        $borrowQuery->bind_param("ss", $isbn, $studentNum);
        $borrowQuery->execute();
        $borrowResult = $borrowQuery->get_result();
        if ($borrowResult->num_rows > 0) {
            $borrowRow = $borrowResult->fetch_assoc();
            $loggedInBorrowDate = $borrowRow['borrow_date'];
        }
        $borrowQuery->close();
    }
}

$books = [];
$sql = "SELECT ISBN, Title, Author FROM books";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

$notification = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $book_isbn = $_POST['book_isbn'];
    $student_number = $_POST['student_number'];
    $borrow_date = $_POST['borrow_date'];
    $return_date = $_POST['return_date'];
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;

    // Find the issued book record
    $stmt = $conn->prepare("SELECT id, borrow_date, return_date FROM issued_books WHERE book_isbn = ? AND student_number = ? AND status = 'issued'");
    $stmt->bind_param("ss", $book_isbn, $student_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $notification = '<div class="notification error">No issued record found for this book and student.</div>';
    } else {
        $issue = $result->fetch_assoc();
        $due_date = $issue['return_date'];
        $actual_return_date = $return_date;
        $grace_period = 1; // 1 day grace
        $penalty_per_day = 5.00;
        $max_fine = 100.00;
        $overdue_days = (strtotime($actual_return_date) - strtotime($due_date)) / (60*60*24);
        $penalty = 0;
        if ($overdue_days > $grace_period) {
            $penalty = min(($overdue_days - $grace_period) * $penalty_per_day, $max_fine);
        }
        // Begin transaction
        $conn->begin_transaction();
        try {
            // Update issued_books status
            $updateIssued = $conn->prepare("UPDATE issued_books SET status = 'returned' WHERE id = ?");
            $updateIssued->bind_param("i", $issue['id']);
            $updateIssued->execute();
            // Update book availability
            $updateBook = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE ISBN = ?");
            $updateBook->bind_param("s", $book_isbn);
            $updateBook->execute();
            // Insert into book_returns
            $user_id = null;
            $userStmt = $conn->prepare("SELECT id FROM users WHERE student_number = ?");
            $userStmt->bind_param("s", $student_number);
            $userStmt->execute();
            $userStmt->bind_result($user_id);
            $userStmt->fetch();
            $userStmt->close();
            $book_id = null;
            $bookStmt = $conn->prepare("SELECT BookID FROM books WHERE ISBN = ?");
            $bookStmt->bind_param("s", $book_isbn);
            $bookStmt->execute();
            $bookStmt->bind_result($book_id);
            $bookStmt->fetch();
            $bookStmt->close();
            // Check if payment_method column exists, if not, add it
            $checkColumn = $conn->query("SHOW COLUMNS FROM book_returns LIKE 'payment_method'");
            if ($checkColumn->num_rows == 0) {
                $conn->query("ALTER TABLE book_returns ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER late_fee");
            }
            
            $insertReturn = $conn->prepare("INSERT INTO book_returns (book_id, user_id, return_date, due_date, late_fee, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
            $insertReturn->bind_param("iissds", $book_id, $user_id, $actual_return_date, $due_date, $penalty, $payment_method);
            $insertReturn->execute();
            $conn->commit();
            if ($penalty > 0) {
                $notification = '<div class="notification error">Book returned with penalty: ₱' . number_format($penalty, 2) . '</div>';
            } else {
                $notification = '<div class="notification success">Book returned successfully! No penalty.</div>';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $notification = '<div class="notification error">Error returning book: ' . $e->getMessage() . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Book</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        html, body {
            height: 100%; 
            margin: 0; 
            padding: 0; 
            background-color: #C4A092;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        h2 {
            text-align: center;
            color: black;
            margin-top: 20px;
            font-size: 50px;
        }

        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
        }

        form {
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
            max-width: 600px; 
            width: 100%;
            margin-top: -40px;
        }

        .form-group {
            display: flex;
            justify-content: flex-start; 
            align-items: center;
            width: 100%;
            margin-left: 75px;
        }

        label {
            font-size: 22px;
            color: black;
            width: 40%; 
            white-space: nowrap;
        }

        input {
            width: 100%;  
            padding: 10px;
            font-size: 18px;
            border: 1px solid #aaa;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 10px;
            margin-left: 150px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100px;
        }

        button[type="submit"] {
            background-color: #AD795B;
            color: white;
        }

        button[type="button"]:nth-child(2) {
            background-color: #FFE699;
            color: black;
        }

        button[type="button"]:nth-child(3) {
            background-color: #ccc;
            color: black;
        }

        .book-list {
    background-color: #FFF8F0;
    border-left: 2px solid #AD795B;
    padding: 20px;
    width: 500px;
    max-height: 71vh; /* Fixed height, allows scrolling */
    overflow-y: auto;
    margin-top: -40px;
    margin-right: 75px;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* Start content from the top */
}

        .book-list h3 {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 20px;
            color: #333;
        }

        .book-item {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #FFEBD6;
            border-radius: 5px;
            font-size: 16px;
            line-height: 1.4;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .book-item:hover {
            background-color: #FFD6A5;
        }

        .book-item strong {
            display: inline-block;
            width: 70px;
        }
        .violation {
            text-align: center; 
    position: sticky;
    bottom: 0;
    width: 96%;
    background-color: 	#d6eaff;
    border-radius: 5px;
    padding: 10px;
    font-size: 14px;
    line-height: 1.4;
    color: #a94442;
    font-weight: bold;
    z-index: 1;
    box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1); /* optional for emphasis */
}


#penaltyInfo {
    text-align: left;
    margin-top: 15px;
    font-size: 14px;
    color: #333;
    background-color: #f9f9f9;
    padding: 10px;
    border-radius: 10px; /* Rounded corners */
    border-left: 4px solid #AD795B; /* A thicker left border for emphasis */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
    transition: all 0.3s ease; /* Smooth transition for hover effect */
}

#penaltyInfo h4 {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    color: #007bff; /* Blue color for the heading */
    margin-bottom: 10px; /* Space between heading and list */
}

#penaltyInfo ul {
    list-style-type: none;
    padding-left: 0;
    margin: 0;
}

#penaltyInfo li {
    margin-bottom: 12px; /* Add more space between list items */
    font-size: 14px; /* Ensure readability */
    line-height: 1.6; /* Add space between lines for readability */
}

#penaltyInfo li strong {
    color: #AD795B; /* Dark brown color for strong text */
}

a {
    color: #007bff;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
}

a:hover {
    text-decoration: none;;
}

@media print {
    body * {
        visibility: hidden;
    }
    #printSection, #printSection * {
        visibility: visible;
    }
    #printSection {
        position: absolute;
        top: 50px;
        left: 0;
        width: 100%;
        background: white;
        padding: 20px;
        z-index: 9999;
    }
}

.notification {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    min-width: 300px;
    max-width: 90vw;
    padding: 15px;
    border-radius: 5px;
    font-size: 18px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    opacity: 1;
    transition: opacity 0.5s;
}
.notification.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.notification.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

/* Payment Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.show {
    display: flex;
}

.modal-content {
    background-color: #fff;
    margin: 0;
    padding: 30px;
    border: 3px solid #dc3545;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    position: relative;
}

.modal-header {
    background-color: #f8d7da;
    padding: 15px;
    margin: -30px -30px 20px -30px;
    border-radius: 7px 7px 0 0;
    border-bottom: 2px solid #dc3545;
    text-align: center;
}

.modal-header h3 {
    margin: 0;
    color: #721c24;
    font-size: 24px;
}

.penalty-display {
    text-align: center;
    margin: 20px 0;
    padding: 15px;
    background-color: #f8d7da;
    border-radius: 5px;
    font-size: 20px;
    font-weight: bold;
    color: #721c24;
}

/* Payment Required View (First Popup) Styles */
#paymentMethodView .penalty-display {
    margin: 20px 0;
}

#paymentMethodView .modal-form-group {
    margin-bottom: 20px;
    margin-top: 0;
}

#paymentMethodView .modal-button-group {
    margin-top: 25px;
}

/* Payment Details View (Second Popup) Styles */
#paymentDetailsView .penalty-display {
    margin-bottom: 60px;
}

#paymentDetailsView .modal-form-group {
    margin-bottom: 20px;
    margin-top: 0;
}

#paymentDetailsView .modal-button-group {
    margin-top: -15px;
}

/* Payment Processing View (Third Popup) Styles */
#paymentProcessingView .penalty-display {
    margin: 20px 0;
}

#paymentProcessingView .notification {
    margin: 20px 0;
    padding: 15px;
    border-radius: 5px;
    font-size: 18px;
    font-weight: bold;
    text-align: center;
}

#paymentProcessingView .notification.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

#paymentProcessingView .notification.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.modal-form-group {
    /* Base styles - specific rules above override margin */
}

.modal-form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.modal-form-group select,
.modal-form-group input {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 1px solid #aaa;
    border-radius: 5px;
    box-sizing: border-box;
}

.modal-button-group {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 25px;
    align-items: center;
}


/* Specific styling for Cash payment fields */
#cashFields {
    margin-bottom: 20px;
}

#cashFields .modal-form-group {
    margin-bottom: 20px;
}

.modal-button-group button {
    flex: 0 1 auto;
    min-width: 150px;
}

.modal-button {
    padding: 12px 30px;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}

.modal-button.submit {
    background-color: #dc3545 !important;
    color: white !important;
    border: none !important;
}

.modal-button.submit:hover {
    background-color: #dc3545 !important;
    color: white !important;
    transform: scale(1.05);
}

.modal-button.cancel {
    background-color: #f8d7da !important;
    color: #721c24 !important;
    border: none !important;
}

.modal-button.cancel:hover {
    background-color: #f8d7da !important;
    color: #721c24 !important;
    transform: scale(1.05);
}
    </style>
</head>
<body>
    <h2>RETURN A BOOK</h2>
    <?php if ($notification) echo $notification; ?>
    <div class="container">
        <form method="POST" id="returnForm" onsubmit="return validateReturnForm()">
            <div class="form-group">
                <label for="book_isbn">Book ISBN:</label>
                <input type="text" name="book_isbn" id="book_isbn" required>
            </div>

            <div class="form-group">
                <label for="student_number">Student No.:</label>
                <input type="text" name="student_number" id="student_number" required>
            </div>

            <div class="form-group">
                <label for="student_name">Student Name:</label>
                <input type="name" name="student_name" id="student_name" required>
            </div>

            <div class="form-group">
                <label for="student_email">Student Email:</label>
                <input type="email" name="student_email" id="student_email" required>
            </div>

            <div class="form-group">
                <label for="student_contact">Contact No.:</label>
                <input type="tel" name="student_contact" id="student_contact" required>
            </div>

            <div class="form-group">
                <label for="borrow_date">Borrow Date:</label>
                <input type="date" name="borrow_date" id="borrow_date" required>
            </div>

            <div class="form-group">
                <label for="return_date">Return Date:</label>
                <input type="date" name="return_date" id="return_date" required>
            </div>

            <!-- Hidden payment fields in form -->
            <input type="hidden" name="payment_method" id="payment_method" value="">
            <input type="hidden" name="payment_amount" id="payment_amount" value="0">

            <div class="button-group">
                <button type="submit" id="returnButton">RETURN</button>
                <button type="button" onclick="printForm()">PRINT</button>
                <button type="button" onclick="location.href='<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'home.php' ?>'">CANCEL</button>
            </div>
        </form>

        <!-- Payment Modal Popup -->
        <div id="paymentModal" class="modal">
            <div class="modal-content">
                <!-- Payment Method Selection View -->
                <div id="paymentMethodView">
                    <div class="modal-header">
                        <h3>Payment Required</h3>
                    </div>
                    <div class="penalty-display">
                        Penalty Amount: ₱<span id="penaltyAmount">0.00</span>
                    </div>
                    
                    <div class="modal-form-group">
                        <label for="modal_payment_method">Payment Method:</label>
                        <select id="modal_payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="PayMaya">PayMaya</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Check">Check</option>
                        </select>
                    </div>
                    
                    <div class="modal-form-group">
                        <label for="modal_payment_amount">Amount Paid:</label>
                        <input type="number" id="modal_payment_amount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="modal-button-group">
                        <button type="button" class="modal-button submit" onclick="showPaymentDetails()">Confirm Payment</button>
                        <button type="button" class="modal-button cancel" onclick="closePaymentModal()">Cancel</button>
                    </div>
                </div>

                <!-- Payment Details View -->
                <div id="paymentDetailsView" style="display: none;">
                    <div class="modal-header">
                        <h3 id="paymentDetailsTitle">Payment Details</h3>
                    </div>
                    <div class="penalty-display">
                        Payment Method: <span id="displayPaymentMethod"></span><br>
                        Amount: ₱<span id="displayPaymentAmount">0.00</span>
                    </div>
                    
                    <form id="paymentDetailsForm">
                        <!-- GCash/PayMaya fields -->
                        <div id="gcashFields" style="display: none;">
                            <div class="modal-form-group">
                                <label for="gcash_account_name">Account Name:</label>
                                <input type="text" name="account_name" id="gcash_account_name" required>
                            </div>
                            <div class="modal-form-group">
                                <label for="gcash_account_number">Account Number:</label>
                                <input type="text" name="account_number" id="gcash_account_number" required>
                            </div>
                        </div>

                        <!-- Bank Transfer fields -->
                        <div id="bankTransferFields" style="display: none;">
                            <div class="modal-form-group">
                                <label for="bank_bank_name">Bank Name:</label>
                                <input type="text" name="bank_name" id="bank_bank_name" required>
                            </div>
                            <div class="modal-form-group">
                                <label for="bank_account_name">Account Name:</label>
                                <input type="text" name="account_name" id="bank_account_name" required>
                            </div>
                            <div class="modal-form-group">
                                <label for="bank_account_number">Account Number:</label>
                                <input type="text" name="account_number" id="bank_account_number" required>
                            </div>
                        </div>

                        <!-- Check fields -->
                        <div id="checkFields" style="display: none;">
                            <div class="modal-form-group">
                                <label for="check_check_number">Check Number:</label>
                                <input type="text" name="check_number" id="check_check_number" required>
                            </div>
                            <div class="modal-form-group">
                                <label for="check_bank_name">Bank Name:</label>
                                <input type="text" name="bank_name" id="check_bank_name" required>
                            </div>
                            <div class="modal-form-group">
                                <label for="check_account_name">Account Name:</label>
                                <input type="text" name="account_name" id="check_account_name" required>
                            </div>
                        </div>

                        <!-- Cash fields -->
                        <div id="cashFields" style="display: none;">
                            <div class="modal-form-group">
                                <label for="cash_received_by">Received By:</label>
                                <input type="text" name="received_by" id="cash_received_by" required>
                            </div>
                        </div>
                        
                        <div class="modal-button-group">
                            <button type="button" class="modal-button submit" onclick="submitFinalPayment()">Complete Payment</button>
                            <button type="button" class="modal-button cancel" onclick="backToPaymentMethod()">Back</button>
                        </div>
                    </form>
                </div>

                <!-- Payment Processing View (Third Popup) -->
                <div id="paymentProcessingView" style="display: none;">
                    <div class="modal-header">
                        <h3>Payment Processing</h3>
                    </div>
                    <div class="penalty-display" id="processingMessage">
                        Processing your payment...
                    </div>
                    <div id="processingResult" style="display: none;">
                        <div class="notification" id="resultNotification"></div>
                        <div class="modal-button-group">
                            <button type="button" class="modal-button submit" onclick="closePaymentModalAndRedirect()">Go to Dashboard</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="book-list">
    <h3>Available Books</h3>
    <input type="text" id="bookSearch" placeholder="Search by ISBN, Title, or Author..." style="width:100%;padding:8px;margin-bottom:10px;font-size:16px;border-radius:5px;border:1px solid #aaa;box-sizing:border-box;">

    <?php foreach ($books as $book): ?>
<div class="book-item" data-isbn="<?= htmlspecialchars($book['ISBN']) ?>">
    <div><strong>ISBN:</strong> <?= htmlspecialchars($book['ISBN']) ?></div>
    <div><strong>Title:</strong> <?= htmlspecialchars($book['Title']) ?></div>
    <div><strong>Author:</strong> <?= htmlspecialchars($book['Author']) ?></div>
</div>
<?php endforeach; ?>

<!-- Late Return Warning -->
<div class="violation">
    ⚠️ Reminder: Students returning books after the due date will be penalized according to library policy.<a href="javascript:void(0);" id="toggleInfo">&nbsp;Get Details</a>
    
    <div id="penaltyInfo" style="display: none;">
    <h4>Library Late Return Penalty Policy:</h4>
    <ul>
        <li><strong>Grace Period:</strong> 1 day after the due date (no penalty).</li>
        <li><strong>Penalty Rate:</strong> ₱5.00 per day per book.</li>
        <li><strong>Maximum Fine:</strong> ₱100.00 per book.</li>
        <li><strong>Overdue Beyond 30 Days:</strong> The student may be required to replace the book or pay its full value in addition to the fine.</li>
        <li><strong>Unpaid Fines:</strong> Students with unpaid fines may be restricted from borrowing more books or receiving clearance for enrollment/graduation.</li>
    </ul>
</div>
</div>
</div>
<!-- Hidden print section -->
<div id="printSection" style="display: none;">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="logo.png" alt="Chapter One Logo" style="height: 150px;"><br>
        <h1>Chapter One</h1>
        <p><em>Organizing Knowledge, One Book at a Time</em></p>
    </div>

    <div id="printContent" style="font-size: 18px; line-height: 1.6; padding: 20px;">
        <p><strong>Book ISBN:</strong> <span id="print_isbn"></span></p>
        <p><strong>Student No.:</strong> <span id="print_number"></span></p>
        <p><strong>Student Name:</strong> <span id="print_name"></span></p>
        <p><strong>Student Email:</strong> <span id="print_email"></span></p>
        <p><strong>Contact No.:</strong> <span id="print_contact"></span></p>
        <p><strong>Borrow Date:</strong> <span id="print_borrow"></span></p>
        <p><strong>Return Date:</strong> <span id="print_return"></span></p>
    </div>
</div>
 
    <script>
    // Define functions in global scope for onclick handlers
    function openPaymentModal() {
        document.getElementById('paymentModal').classList.add('show');
    }
    
    function closePaymentModal() {
        document.getElementById('paymentModal').classList.remove('show');
        document.getElementById('modal_payment_method').value = '';
        document.getElementById('modal_payment_amount').value = '';
        // Reset views
        document.getElementById('paymentMethodView').style.display = 'block';
        document.getElementById('paymentDetailsView').style.display = 'none';
        document.getElementById('paymentProcessingView').style.display = 'none';
        document.getElementById('paymentDetailsForm').reset();
    }
    
    function closePaymentModalAndRedirect() {
        closePaymentModal();
        window.location.href = '<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'home.php' ?>';
    }
    
    function showPaymentDetails() {
        const paymentMethod = document.getElementById('modal_payment_method').value;
        const paymentAmount = parseFloat(document.getElementById('modal_payment_amount').value) || 0;
        const penaltyAmount = parseFloat(document.getElementById('penaltyAmount').textContent) || 0;
        
        if (!paymentMethod) {
            alert('Please select a payment method');
            return;
        }
        
        if (!paymentAmount || paymentAmount < penaltyAmount) {
            alert('Payment amount must be at least ₱' + penaltyAmount.toFixed(2));
            return;
        }
        
        // Hide payment method view, show payment details view
        document.getElementById('paymentMethodView').style.display = 'none';
        document.getElementById('paymentDetailsView').style.display = 'block';
        
        // Update display
        document.getElementById('displayPaymentMethod').textContent = paymentMethod;
        document.getElementById('displayPaymentAmount').textContent = paymentAmount.toFixed(2);
        document.getElementById('paymentDetailsTitle').textContent = 'Payment Details - ' + paymentMethod;
        
        // Show appropriate fields based on payment method
        document.getElementById('gcashFields').style.display = 'none';
        document.getElementById('bankTransferFields').style.display = 'none';
        document.getElementById('checkFields').style.display = 'none';
        document.getElementById('cashFields').style.display = 'none';
        
        if (paymentMethod === 'GCash' || paymentMethod === 'PayMaya') {
            document.getElementById('gcashFields').style.display = 'block';
        } else if (paymentMethod === 'Bank Transfer') {
            document.getElementById('bankTransferFields').style.display = 'block';
        } else if (paymentMethod === 'Check') {
            document.getElementById('checkFields').style.display = 'block';
        } else if (paymentMethod === 'Cash') {
            document.getElementById('cashFields').style.display = 'block';
        }
    }
    
    function backToPaymentMethod() {
        document.getElementById('paymentDetailsView').style.display = 'none';
        document.getElementById('paymentMethodView').style.display = 'block';
        // Clear payment details form
        document.getElementById('paymentDetailsForm').reset();
    }
    
    function submitFinalPayment() {
        const paymentMethod = document.getElementById('modal_payment_method').value;
        const paymentAmount = parseFloat(document.getElementById('modal_payment_amount').value) || 0;
        
        // Validate payment details based on method
        let isValid = true;
        
        if (paymentMethod === 'GCash' || paymentMethod === 'PayMaya') {
            if (!document.getElementById('gcash_account_name').value.trim()) {
                alert('Please enter account name');
                isValid = false;
            } else if (!document.getElementById('gcash_account_number').value.trim()) {
                alert('Please enter account number');
                isValid = false;
            }
        } else if (paymentMethod === 'Bank Transfer') {
            if (!document.getElementById('bank_bank_name').value.trim()) {
                alert('Please enter bank name');
                isValid = false;
            } else if (!document.getElementById('bank_account_name').value.trim()) {
                alert('Please enter account name');
                isValid = false;
            } else if (!document.getElementById('bank_account_number').value.trim()) {
                alert('Please enter account number');
                isValid = false;
            }
        } else if (paymentMethod === 'Check') {
            if (!document.getElementById('check_check_number').value.trim()) {
                alert('Please enter check number');
                isValid = false;
            } else if (!document.getElementById('check_bank_name').value.trim()) {
                alert('Please enter bank name');
                isValid = false;
            } else if (!document.getElementById('check_account_name').value.trim()) {
                alert('Please enter account name');
                isValid = false;
            }
        } else if (paymentMethod === 'Cash') {
            if (!document.getElementById('cash_received_by').value.trim()) {
                alert('Please enter the name of the person who received the payment');
                isValid = false;
            }
        }
        
        if (!isValid) {
            return;
        }
        
        // Collect payment details
        const paymentDetails = {};
        if (paymentMethod === 'GCash' || paymentMethod === 'PayMaya') {
            paymentDetails.account_name = document.getElementById('gcash_account_name').value;
            paymentDetails.account_number = document.getElementById('gcash_account_number').value;
            // Reference number will be auto-generated
        } else if (paymentMethod === 'Bank Transfer') {
            paymentDetails.bank_name = document.getElementById('bank_bank_name').value;
            paymentDetails.account_name = document.getElementById('bank_account_name').value;
            paymentDetails.account_number = document.getElementById('bank_account_number').value;
            // Reference number will be auto-generated
        } else if (paymentMethod === 'Check') {
            paymentDetails.check_number = document.getElementById('check_check_number').value;
            paymentDetails.bank_name = document.getElementById('check_bank_name').value;
            paymentDetails.account_name = document.getElementById('check_account_name').value;
        } else if (paymentMethod === 'Cash') {
            paymentDetails.received_by = document.getElementById('cash_received_by').value;
            // Receipt number will be auto-generated
        }
        
        // Get all return form data
        const returnData = {
            book_isbn: document.getElementById('book_isbn').value,
            student_number: document.getElementById('student_number').value,
            student_name: document.getElementById('student_name').value,
            student_email: document.getElementById('student_email').value,
            student_contact: document.getElementById('student_contact').value,
            borrow_date: document.getElementById('borrow_date').value,
            return_date: document.getElementById('return_date').value,
            payment_method: paymentMethod,
            payment_amount: paymentAmount,
            penalty_amount: parseFloat(document.getElementById('penaltyAmount').textContent) || 0,
            payment_details: paymentDetails
        };
        
        // Store data in sessionStorage
        sessionStorage.setItem('returnData', JSON.stringify(returnData));
        
        // Show payment processing view (third popup)
        document.getElementById('paymentDetailsView').style.display = 'none';
        document.getElementById('paymentProcessingView').style.display = 'block';
        document.getElementById('processingMessage').style.display = 'block';
        document.getElementById('processingResult').style.display = 'none';
        
        // Prepare form data for submission
        const formData = new FormData();
        formData.append('payment_method', paymentMethod);
        formData.append('payment_amount', paymentAmount);
        formData.append('return_data', JSON.stringify(returnData));
        
        // Add payment details as form data
        for (const [key, value] of Object.entries(paymentDetails)) {
            formData.append(key, value);
        }
        
        // Submit via AJAX
        fetch('process_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Hide processing message
            document.getElementById('processingMessage').style.display = 'none';
            
            // Show result
            const resultDiv = document.getElementById('processingResult');
            const notificationDiv = document.getElementById('resultNotification');
            
            notificationDiv.className = 'notification ' + (data.success ? 'success' : 'error');
            notificationDiv.textContent = data.message;
            
            resultDiv.style.display = 'block';
            
            // Clear sessionStorage
            sessionStorage.removeItem('returnData');
        })
        .catch(error => {
            // Hide processing message
            document.getElementById('processingMessage').style.display = 'none';
            
            // Show error
            const resultDiv = document.getElementById('processingResult');
            const notificationDiv = document.getElementById('resultNotification');
            
            notificationDiv.className = 'notification error';
            notificationDiv.textContent = 'Error processing payment: ' + error.message;
            
            resultDiv.style.display = 'block';
        });
    }
    
    window.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('return_date').value = today;

        // Auto-fill from logged-in user (if session exists)
        <?php if ($loggedInUser): ?>
        document.getElementById('student_number').value = '<?= htmlspecialchars($loggedInStudentNumber, ENT_QUOTES, 'UTF-8') ?>';
        document.getElementById('student_name').value = '<?= htmlspecialchars($loggedInUser['username'], ENT_QUOTES, 'UTF-8') ?>';
        document.getElementById('student_email').value = '<?= htmlspecialchars($loggedInUser['email'], ENT_QUOTES, 'UTF-8') ?>';
        document.getElementById('student_contact').value = '<?= htmlspecialchars($loggedInUser['contact'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
        <?php if ($loggedInBorrowDate): ?>
        document.getElementById('borrow_date').value = '<?= htmlspecialchars($loggedInBorrowDate, ENT_QUOTES, 'UTF-8') ?>';
        <?php endif; ?>
        <?php endif; ?>

        // Auto-fill ISBN and student number from URL parameters (overrides session if provided)
        const urlParams = new URLSearchParams(window.location.search);
        const isbn = urlParams.get('isbn');
        const studentNumber = urlParams.get('student_number');
        
        if (isbn) {
            document.getElementById('book_isbn').value = isbn;
        }
        
        if (studentNumber) {
            document.getElementById('student_number').value = studentNumber;
            // Trigger blur event to auto-fill other student info if not already filled from session
            <?php if (!$loggedInUser): ?>
            document.getElementById('student_number').dispatchEvent(new Event('blur'));
            <?php endif; ?>
        }

        // Function to calculate penalty when RETURN button is clicked
        let currentPenalty = 0;
        async function calculatePenaltyForBook() {
            const isbn = document.getElementById('book_isbn').value;
            const studentNumber = document.getElementById('student_number').value;
            const returnDate = document.getElementById('return_date').value;
            
            if (!isbn || !studentNumber || !returnDate) {
                return false;
            }
            
            // Fetch due date from server
            try {
                const response = await fetch(`get_due_date.php?isbn=${encodeURIComponent(isbn)}&student_number=${encodeURIComponent(studentNumber)}`);
                if (!response.ok) {
                    throw new Error('Failed to fetch due date');
                }
                const data = await response.json();
                
                if (data.due_date) {
                    const dueDate = new Date(data.due_date + 'T00:00:00');
                    const actualReturnDate = new Date(returnDate + 'T00:00:00');
                    const gracePeriod = 1;
                    const penaltyPerDay = 5.00;
                    const maxFine = 100.00;
                    
                    const overdueDays = Math.floor((actualReturnDate - dueDate) / (1000 * 60 * 60 * 24));
                    let penalty = 0;
                    
                    if (overdueDays > gracePeriod) {
                        penalty = Math.min((overdueDays - gracePeriod) * penaltyPerDay, maxFine);
                    }
                    
                    currentPenalty = penalty;
                    
                    if (penalty > 0) {
                        document.getElementById('penaltyAmount').textContent = penalty.toFixed(2);
                        document.getElementById('modal_payment_amount').value = penalty.toFixed(2);
                        document.getElementById('modal_payment_amount').setAttribute('min', penalty.toFixed(2));
                        return true; // Penalty exists
                    } else {
                        return false; // No penalty
                    }
                } else {
                    return false;
                }
            } catch (error) {
                console.error('Error calculating penalty:', error);
                return false;
            }
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if (event.target == modal) {
                closePaymentModal();
            }
        }

        // Handle RETURN button click - intercept form submission
        document.getElementById('returnButton').addEventListener('click', async function(e) {
            e.preventDefault();
            
            // First validate basic fields
            const isbn = document.getElementById('book_isbn').value;
            const studentNumber = document.getElementById('student_number').value;
            const studentName = document.getElementById('student_name').value;
            const studentEmail = document.getElementById('student_email').value;
            const studentContact = document.getElementById('student_contact').value;
            const borrowDate = document.getElementById('borrow_date').value;
            const returnDate = document.getElementById('return_date').value;
            const paymentSection = document.getElementById('paymentSection');
            const paymentMethod = document.getElementById('payment_method').value;
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;

            if (!isbn || !studentNumber || !studentName || !studentEmail || !studentContact || !borrowDate || !returnDate) {
                alert('Please fill in all fields');
                return;
            }

            // Validate return date is after borrow date
            const bDate = new Date(borrowDate);
            const rDate = new Date(returnDate);
            if (rDate < bDate) {
                alert('Return date must be after borrow date');
                return;
            }
            
            // Calculate penalty when RETURN button is clicked
            const hasPenalty = await calculatePenaltyForBook();
            
            // If penalty exists, show payment modal
            if (hasPenalty) {
                openPaymentModal();
            } else {
                // No penalty, submit the form directly
                document.getElementById('returnForm').submit();
            }
        });

        document.getElementById('student_number').addEventListener('blur', function() {
            const studentNumber = this.value;

            if (studentNumber.trim() !== "") {
                fetch(`get_user_info.php?student_number=${encodeURIComponent(studentNumber)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data) {
                            document.getElementById('student_name').value = data.username;
                            document.getElementById('student_email').value = data.email;
                            document.getElementById('student_contact').value = data.contact;
                        } else {
                            document.getElementById('student_name').value = '';
                            document.getElementById('student_email').value = '';
                            document.getElementById('student_contact').value = '';
                            alert("Student not found.");
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching student info:', error);
                    });
            }
        });

        // Auto-hide notification after 1 second
        const notif = document.querySelector('.notification');
        if (notif) {
            setTimeout(() => {
                notif.style.opacity = 0;
                setTimeout(() => notif.remove(), 500);
            }, 1000);
        }
    });
    </script>
    <script>
document.getElementById('bookSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const items = document.querySelectorAll('.book-item');

    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(query) ? 'block' : 'none';
    });
});
</script>
<script>
document.querySelectorAll('.book-item').forEach(item => {
    item.addEventListener('click', () => {
        const isbn = item.getAttribute('data-isbn');
        document.getElementById('book_isbn').value = isbn;
    });
});
</script>
<script>
document.getElementById('toggleInfo').addEventListener('click', function() {
    const penaltyInfo = document.getElementById('penaltyInfo');
    if (penaltyInfo.style.display === 'none') {
        penaltyInfo.style.display = 'block';
    } else {
        penaltyInfo.style.display = 'none';
    }
});
</script>
<script>
function printForm() {
    // Copy data from form fields
    document.getElementById("print_isbn").textContent = document.getElementById("book_isbn").value;
    document.getElementById("print_number").textContent = document.getElementById("student_number").value;
    document.getElementById("print_name").textContent = document.getElementById("student_name").value;
    document.getElementById("print_email").textContent = document.getElementById("student_email").value;
    document.getElementById("print_contact").textContent = document.getElementById("student_contact").value;
    document.getElementById("print_borrow").textContent = document.getElementById("borrow_date").value;
    document.getElementById("print_return").textContent = document.getElementById("return_date").value;

    // Show print section
    const printSection = document.getElementById("printSection");
    printSection.style.display = "block";

    // Trigger print
    window.print();

    // Hide after printing
    printSection.style.display = "none";
}
</script>
<script>
function validateReturnForm() {
    const isbn = document.getElementById('book_isbn').value;
    const studentNumber = document.getElementById('student_number').value;
    const studentName = document.getElementById('student_name').value;
    const studentEmail = document.getElementById('student_email').value;
    const studentContact = document.getElementById('student_contact').value;
    const borrowDate = document.getElementById('borrow_date').value;
    const returnDate = document.getElementById('return_date').value;
    const paymentSection = document.getElementById('paymentSection');
    const paymentMethod = document.getElementById('payment_method').value;
    const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
    const penaltyAmount = parseFloat(document.getElementById('penaltyAmount').textContent) || 0;

    if (!isbn || !studentNumber || !studentName || !studentEmail || !studentContact || !borrowDate || !returnDate) {
        alert('Please fill in all fields');
        return false;
    }

    // Validate return date is after borrow date
    const bDate = new Date(borrowDate);
    const rDate = new Date(returnDate);
    if (rDate < bDate) {
        alert('Return date must be after borrow date');
        return false;
    }
    
    // Validate payment if penalty exists
    if (paymentSection.style.display === 'block' && penaltyAmount > 0) {
        if (!paymentMethod) {
            alert('Please select a payment method');
            return false;
        }
        if (!paymentAmount || paymentAmount < penaltyAmount) {
            alert('Payment amount must be at least ₱' + penaltyAmount.toFixed(2));
            return false;
        }
    }
    
    return true;
}
</script>
</body>
</html>
