<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_number'])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library_db";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $student_number = $_POST['student_number'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get user ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE student_number = ?");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Update book_returns to set late_fee to 0 for this user
            $updateStmt = $conn->prepare("UPDATE book_returns SET late_fee = 0 WHERE user_id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Balance cleared successfully']);
        } else {
            throw new Exception("User not found");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error clearing balance: ' . $e->getMessage()]);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?> 