<?php
include 'db_connect.php';
session_start();
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = strtolower(trim($_POST['username']));
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $error = "Server error. Please try again later.";
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($userId, $hashedPassword, $role);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                session_regenerate_id(true);
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $userId;
                $_SESSION['role'] = $role;

                if ($role === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }

        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In</title>
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
            margin-top: -5px;
            margin-bottom: 50px;
            font-size: 50px;
        }
        h2 {
            text-align: center;
            color: black;
            margin-bottom: -5px;
            margin-top: -5px;
            font-size: 22px;
        }
        input[type="text"],
        input[type="password"] {
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
            margin-top: 35px;
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
        .signup-message {
            text-align: center;
            margin-top: 20px;
            font-size: 20px;
        }
        .signup-message a {
            color: #4d78ff;
            text-decoration: none;
            font-weight: bold;
        }
        .signup-message a:hover {
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
        <h1>Welcome to Chapter One</h1>

        <!-- Display error message if there's an issue -->
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Display success message if signup was successful -->
        <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
            <p class="success">Signup successful! Please log in.</p>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="">
            <h2>NAME</h2>
            <input type="text" name="username" required>
            <h2>PASSWORD</h2>
            <input type="password" name="password" required>
            <button type="submit">LOG IN</button>
        </form>

        <!-- Sign up link -->
        <div class="signup-message">
            DON'T HAVE ACCOUNT?<a href="signup.php"> SIGN UP HERE</a>
        </div>

        <!-- Back to home button -->
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