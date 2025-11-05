<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$database = "library_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get payment details
    $paymentMethod = $_POST['payment_method'];
    $paymentAmount = floatval($_POST['payment_amount']);
    $returnDataJson = $_POST['return_data'];
    $returnData = json_decode($returnDataJson, true);
    
    // Get payment-specific details
    $paymentDetails = [];
    
    // Generate auto reference/receipt numbers
    $timestamp = date('YmdHis');
    $random = rand(1000, 9999);
    
    if ($paymentMethod === 'GCash' || $paymentMethod === 'PayMaya') {
        $paymentDetails['account_name'] = $_POST['account_name'] ?? '';
        $paymentDetails['account_number'] = $_POST['account_number'] ?? '';
        $paymentDetails['reference_number'] = 'REF-' . $timestamp . '-' . $random;
    } elseif ($paymentMethod === 'Bank Transfer') {
        $paymentDetails['bank_name'] = $_POST['bank_name'] ?? '';
        $paymentDetails['account_name'] = $_POST['account_name'] ?? '';
        $paymentDetails['account_number'] = $_POST['account_number'] ?? '';
        $paymentDetails['reference_number'] = 'REF-' . $timestamp . '-' . $random;
    } elseif ($paymentMethod === 'Check') {
        $paymentDetails['check_number'] = $_POST['check_number'] ?? '';
        $paymentDetails['bank_name'] = $_POST['bank_name'] ?? '';
        $paymentDetails['account_name'] = $_POST['account_name'] ?? '';
    } elseif ($paymentMethod === 'Cash') {
        $paymentDetails['received_by'] = $_POST['received_by'] ?? '';
        $paymentDetails['receipt_number'] = 'RCP-' . $timestamp . '-' . $random;
    }
    
    // Extract return data
    $book_isbn = $returnData['book_isbn'];
    $student_number = $returnData['student_number'];
    $borrow_date = $returnData['borrow_date'];
    $return_date = $returnData['return_date'];
    $penalty_amount = floatval($returnData['penalty_amount']);
    
    // Find the issued book record
    $stmt = $conn->prepare("SELECT id, borrow_date, return_date FROM issued_books WHERE book_isbn = ? AND student_number = ? AND status = 'issued'");
    $stmt->bind_param("ss", $book_isbn, $student_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response = ['success' => false, 'message' => 'No issued record found for this book and student.'];
    } else {
        $issue = $result->fetch_assoc();
        $due_date = $issue['return_date'];
        
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
            
            // Get user_id and book_id
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
            
            // Check if payment_details column exists, if not, add it
            $checkDetailsColumn = $conn->query("SHOW COLUMNS FROM book_returns LIKE 'payment_details'");
            if ($checkDetailsColumn->num_rows == 0) {
                $conn->query("ALTER TABLE book_returns ADD COLUMN payment_details TEXT DEFAULT NULL AFTER payment_method");
            }
            
            // Insert into book_returns with payment details
            $paymentDetailsJson = json_encode($paymentDetails);
            $insertReturn = $conn->prepare("INSERT INTO book_returns (book_id, user_id, return_date, due_date, late_fee, payment_method, payment_details) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insertReturn->bind_param("iissdss", $book_id, $user_id, $return_date, $due_date, $penalty_amount, $paymentMethod, $paymentDetailsJson);
            $insertReturn->execute();
            
            $conn->commit();
            
            // Build success message with receipt/reference number
            $receiptInfo = '';
            if ($paymentMethod === 'Cash' && isset($paymentDetails['receipt_number'])) {
                $receiptInfo = ' Receipt Number: ' . $paymentDetails['receipt_number'];
            } elseif (($paymentMethod === 'GCash' || $paymentMethod === 'PayMaya' || $paymentMethod === 'Bank Transfer') && isset($paymentDetails['reference_number'])) {
                $receiptInfo = ' Reference Number: ' . $paymentDetails['reference_number'];
            }
            
            if ($penalty_amount > 0) {
                $response = [
                    'success' => true,
                    'message' => 'Book returned successfully with penalty: ₱' . number_format($penalty_amount, 2) . '. Payment method: ' . htmlspecialchars($paymentMethod) . '.' . $receiptInfo
                ];
            } else {
                $response = [
                    'success' => true,
                    'message' => 'Book returned successfully! No penalty. Payment method: ' . htmlspecialchars($paymentMethod) . '.' . $receiptInfo
                ];
            }
        } catch (Exception $e) {
            $conn->rollback();
            $response = ['success' => false, 'message' => 'Error returning book: ' . $e->getMessage()];
        }
    }
}

$conn->close();
echo json_encode($response);
?>

