<?php

require 'db_connect.php'; 

if (isset($_GET['student_number'])) {
    $student_number = $_GET['student_number'];

    $stmt = $conn->prepare("SELECT username, email, contact FROM users WHERE student_number = ?");
    $stmt->bind_param("s", $student_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        echo json_encode($user);
    } else {
        echo json_encode(null);
    }

    $stmt->close();
    $conn->close();
}
?>