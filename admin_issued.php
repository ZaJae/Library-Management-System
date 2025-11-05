<?php
$currentPage = 'issued';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issued</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; overflow-x: hidden; }
        body { background-color: #C4A092; font-family: Arial, sans-serif; display: flex; flex-direction: column; min-height: 100vh; align-items: center; }
        header { background-color: #AD795B; color: white; text-align: center; padding: 15px; font-size: 24px; font-weight: bold; width: 100%; }
        .container { display: flex; flex: 1; width: 100%; max-width: 1200px; justify-content: center; margin-top: 20px; }
        .sidebar { width: 220px; padding: 15px; background-color: #AD795B; display: flex; flex-direction: column; margin-bottom: 20px; justify-content: space-between; }
        .menu-links { flex-grow: 1; }
        .sidebar h2 { color: white; font-size: 22px; text-align: center; margin-bottom: 30px; }
        .sidebar a { padding: 15px; text-decoration: none; color: white; display: block; transition: all 0.3s ease; }
        .sidebar a:hover { background-color: rgb(221, 199, 91); transform: scale(1.0); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); }
        .sidebar a.active {
            background-color: white;
            color: #AD795B;
            font-weight: bold;
            border-left: 5px solid #FFE699;
        }
        .logout { display: block; width: 100%; padding: 10px; background-color: #FFE699; border: 2px solid white; color: #333; font-size: 14px; font-weight: bold; text-align: center; text-decoration: none; box-sizing: border-box; margin: 5px 0; cursor: pointer; transition: all 0.3s ease; }
        .logout:hover { background-color: rgb(221, 199, 91); color: white; transform: scale(1.02); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); }
        .main-content { width: 75%; padding: 15px; background-color: white; display: flex; flex-direction: column; flex-grow: 1; min-height: 300px; margin-bottom: 20px; }
        .main-content h1 { margin-bottom: 40px; text-align: center; color: black; }
        footer { background-color: #AD795B; color: white; text-align: center; padding: 10px; width: 100%; }
    </style>
</head>
<body>
    <header>Library Management System</header>
    <div class="container">
        <div class="sidebar">
            <h2>MENU</h2>
            <div class="menu-links">
                <a href="admin_dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <a href="admin_issued.php" class="<?php echo $currentPage === 'issued' ? 'active' : ''; ?>">Issued</a>
                <a href="admin_returned.php" class="<?php echo $currentPage === 'returned' ? 'active' : ''; ?>">Returned</a>
                <a href="admin_not_returned.php" class="<?php echo $currentPage === 'not_returned' ? 'active' : ''; ?>">Not Returned</a>
                <a href="admin_balance.php" class="<?php echo $currentPage === 'balance' ? 'active' : ''; ?>">Balance</a>
            </div>
            <button class="logout" onclick="location.href='logout.php'">Log Out</button>
        </div>

        <div class="main-content">
            <h1>Issued</h1>
        </div>
    </div>
    <footer>&copy; 2025 Chapter One. All rights reserved.</footer>
    <script>
        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = today.getFullYear();
        const formattedDate = ${day}/${month}/${year};
        document.querySelector('#dateToday span').textContent = formattedDate;
    </script>
</body>
</html>