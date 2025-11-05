<?php
include 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = strtolower(trim($_POST['username']));
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact = trim($_POST['contact']);

    // Validate inputs
    if (strlen($username) > 30) {
        $error = "Username too long (max 30 characters).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^\d{10,15}$/', $contact)) {
        $error = "Invalid contact number (only digits, 10-15 characters).";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Generate student number
        $year = date('Y');
        $prefix = "SN" . $year;

        $stmt = $conn->prepare("SELECT student_number FROM users WHERE student_number LIKE ? ORDER BY student_number DESC LIMIT 1");
        $likePrefix = $prefix . "%";
        $stmt->bind_param("s", $likePrefix);
        $stmt->execute();
        $stmt->bind_result($lastSN);
        $stmt->fetch();
        $stmt->close();

        if ($lastSN) {
            $lastNumber = intval(substr($lastSN, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = "0001";
        }

        $student_number = $prefix . $newNumber;

        // Check for duplicate username only (student number is auto-generated)
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, contact, student_number) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                error_log("Insert prepare failed: " . $conn->error);
                $error = "Server error. Please try again.";
            } else {
                $stmt->bind_param("sssss", $username, $email, $hashedPassword, $contact, $student_number);

                if ($stmt->execute()) {
                    header("Location: login.php?signup=success");
                    exit();
                } else {
                    $error = "Error creating account. Try again.";
                }

                $stmt->close();
            }
        }

        $checkStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        body {
            background-color: #C4A092;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: transparent; 
            padding: 0;                    
            border-radius: 0;              
            box-shadow: none;              
            width: 100%;
            max-width: 1000px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h1 {
            text-align: center;
            color: black;
            width: 100%;
            margin-top: 150px;
            margin-bottom: 30px;
            font-size: 50px;
        }
        h2 {
            text-align: center;
            color: black;
            margin-bottom: -5px;
            margin-top: -5px;
            font-size: 22px;
        }
        input {
            width: 30%;
            margin: 12px auto;
            padding: 10px;
            font-size: 18px;
            border: 1px solid #aaa;
            box-sizing: border-box;
        }
        button {
            padding: 12px;
            background-color: #4d78ff;
            color: white;
            border: 2px solid #C4A092;
            font-weight: bold;
            font-size: 16px;
            margin-top: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 30%;
        }
        button:hover {
            background-color: blue; 
            border: 2px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: scale(1.02);
        }
        .home {
            padding: 12px;
            background-color: #FFE699;
            color: white;
            border: 2px solid #C4A092;
            font-weight: bold;
            font-size: 16px;
            margin-top: 30px;
            margin-bottom: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 30%;
        }
        .home:hover {
            background-color: rgb(221, 199, 91); 
            border: 2px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: scale(1.02);
        }
        .login-message {
            text-align: center;
            margin-top: 20px;
            font-size: 20px;
        }
        .login-message a {
            color: #4d78ff;
            text-decoration: none;
            font-weight: bold;
        }
        .login-message a:hover {
            color: blue;
        }
        .error {
            color: red;
            background-color: #ff4d78;
            text-align: center;
            font-size: 22px;
            width: 100%;
        }
        .success {
            color: green;
            background-color: #78ff4d;
            text-align: center;
            font-size: 22px;
            width: 100%;
        }
        .fade-out {
            transition: opacity 0.5s ease-out;
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create New Account</h1>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <h2>NAME</h2>
            <input type="text" name="username" required>

            <h2>EMAIL</h2>
            <input type="email" name="email" required>

            <h2>PASSWORD</h2>
            <input type="password" name="password" required>

            <h2>CONFIRM PASSWORD</h2>
            <input type="password" name="confirm_password" required>

            <h2>CONTACT NO.</h2>
            <input type="text" name="contact" required>

            <button type="submit">SIGN UP</button>
        </form>

        <div class="login-message">
            ALREADY REGISTERED? <a href="login.php">LOG IN</a>
        </div>

        <button class="home" onclick="location.href='home.php'">Back to Home</button>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const errorMsg = document.querySelector('.error');
        const successMsg = document.querySelector('.success');

        setTimeout(() => {
            if (errorMsg) {
                errorMsg.classList.add('fade-out');
                setTimeout(() => errorMsg.style.display = 'none', 500);
            }
            if (successMsg) {
                successMsg.classList.add('fade-out');
                setTimeout(() => successMsg.style.display = 'none', 500);
            }
        }, 1000);
    });
    </script>
</body>
</html>
