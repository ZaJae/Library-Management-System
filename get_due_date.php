<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$database = "library_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

if (isset($_GET['isbn']) && isset($_GET['student_number'])) {
    $isbn = $_GET['isbn'];
    $student_number = $_GET['student_number'];
    
    $stmt = $conn->prepare("SELECT return_date FROM issued_books WHERE book_isbn = ? AND student_number = ? AND status = 'issued' LIMIT 1");
    $stmt->bind_param("ss", $isbn, $student_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['due_date' => $row['return_date']]);
    } else {
        echo json_encode(['error' => 'No issued record found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Missing parameters']);
}

$conn->close();
?>

