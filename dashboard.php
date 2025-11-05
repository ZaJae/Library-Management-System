<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role']; // 'admin' or 'user'

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "library_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];

$studentNumber = '';
$userQuery = $conn->prepare("SELECT student_number FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userQuery->bind_result($studentNumber);
$userQuery->fetch();
$userQuery->close();

// Issued: books currently issued (not yet returned)
$issuedCount = $conn->query("SELECT COUNT(*) FROM issued_books WHERE student_number = '$studentNumber' AND status = 'issued'")->fetch_row()[0];
// Returned: books returned
$returnedCount = $conn->query("SELECT COUNT(*) FROM issued_books WHERE student_number = '$studentNumber' AND status = 'returned'")->fetch_row()[0];
// Not Returned: books overdue (not returned and past due date)
$notReturnedCount = $conn->query("SELECT COUNT(*) FROM issued_books WHERE student_number = '$studentNumber' AND status = 'issued' AND return_date < CURDATE()") ->fetch_row()[0];
// Balance: total penalty from returned books
$balanceResult = $conn->query("SELECT SUM(late_fee) FROM book_returns INNER JOIN users ON book_returns.user_id = users.id WHERE users.student_number = '$studentNumber'");
$balanceRow = $balanceResult->fetch_row();
$balanceCount = $balanceRow[0] ? $balanceRow[0] : 0;

// Calculate real-time penalties for overdue books (not yet returned)
$grace_period = 1; // 1 day grace
$penalty_per_day = 5.00;
$max_fine = 100.00;
$today = date('Y-m-d');

// Get overdue books (issued and past return date)
$overdueBooksQuery = $conn->query("SELECT ib.*, b.Title, b.ISBN FROM issued_books ib JOIN books b ON ib.book_isbn = b.ISBN WHERE ib.student_number = '$studentNumber' AND ib.status = 'issued' AND ib.return_date < CURDATE()");
$overduePenalties = [];
$totalOverduePenalty = 0;

while ($overdue = $overdueBooksQuery->fetch_assoc()) {
    $due_date = $overdue['return_date'];
    $overdue_days = (strtotime($today) - strtotime($due_date)) / (60*60*24);
    $penalty = 0;
    
    if ($overdue_days > $grace_period) {
        $penalty = min(($overdue_days - $grace_period) * $penalty_per_day, $max_fine);
    }
    
    if ($penalty > 0) {
        $overduePenalties[] = [
            'ISBN' => $overdue['ISBN'],
            'Title' => $overdue['Title'],
            'return_date' => $due_date,
            'late_fee' => $penalty,
            'overdue_days' => floor($overdue_days)
        ];
        $totalOverduePenalty += $penalty;
    }
}

// Add overdue penalties to balance
$balanceCount += $totalOverduePenalty;

// Fetch lists for each status
$issuedBooks = $conn->query("SELECT ib.*, b.Title FROM issued_books ib JOIN books b ON ib.book_isbn = b.ISBN WHERE ib.student_number = '$studentNumber' AND ib.status = 'issued'");
$returnedBooks = $conn->query("SELECT ib.*, b.Title FROM issued_books ib JOIN books b ON ib.book_isbn = b.ISBN WHERE ib.student_number = '$studentNumber' AND ib.status = 'returned'");
$notReturnedBooks = $conn->query("SELECT ib.*, b.Title FROM issued_books ib JOIN books b ON ib.book_isbn = b.ISBN WHERE ib.student_number = '$studentNumber' AND ib.status = 'issued' AND ib.return_date < CURDATE()");
$balanceBooks = $conn->query("SELECT b.Title, b.ISBN, br.late_fee, br.return_date FROM book_returns br INNER JOIN books b ON br.book_id = b.BookID INNER JOIN users u ON br.user_id = u.id WHERE u.student_number = '$studentNumber' AND br.late_fee > 0");

$conn->close();
$currentPage = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; overflow-x: hidden; }
        body {
            background-color: #C4A092;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            align-items: center;
        }
        header {
            background-color: #AD795B;
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 24px;
            font-weight: bold;
            width: 100%;
        }
        .container {
            display: flex;
            flex: 1;
            width: 100%;
            max-width: 1200px;
            justify-content: center;
            margin-top: 20px;
        }
        .sidebar {
            width: 220px;
            padding: 15px;
            background-color: #AD795B;
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
            justify-content: space-between;
        }
        .menu-links { flex-grow: 1; }
        .sidebar h2 {
            color: white;
            font-size: 22px;
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar a {
            padding: 15px;
            text-decoration: none;
            color: white;
            display: block;
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background-color: rgb(221, 199, 91);
            transform: scale(1.0);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .sidebar a.active {
            background-color: white;
            color: #AD795B;
            font-weight: bold;
            border-left: 5px solid #FFE699;
        }
        .logout {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #FFE699;
            border: 2px solid white;
            color: #333;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
            margin: 5px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .logout:hover {
            background-color: rgb(221, 199, 91);
            color: white;
            transform: scale(1.02);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .main-content {
            width: 75%;
            padding: 15px;
            background-color: white;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            min-height: 300px;
            margin-bottom: 20px;
            position: relative;
        }
        .main-content h1 {
            margin-bottom: 40px;
            text-align: center;
            color: black;
        }
        .main-content h3 {
            margin-top: 150px;
            text-align: center;
            color: black;
        }
        .status-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .status-box {
            width: 172px;
            height: 172px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .status-box-content a {
            text-decoration: none;
        }
        .status-box.today,
        .status-box.issued,
        .status-box.returned,
        .status-box.not-returned,
        .status-box.balance {
            position: relative;
            text-align: center;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            display: flex;
            padding: 10px;
        }
        .status-box.today span,
        .status-box.issued strong,
        .status-box.returned strong,
        .status-box.not-returned strong,
        .status-box.balance strong {
            position: absolute;
            bottom: 8px;
            right: 10px;
            font-size: 25px;
            font-weight: normal;
        }
        .status-box-content { text-align: center; padding: 10px; }
        .issued, .issued a { background-color: #d34dff; color: #69267f; }
        .returned, .returned a { background-color: #4d78ff; color: #263c7f; }
        .not-returned, .not-returned a { background-color: #FFE699; color: #7f734c; }
        .balance, .balance a { background-color: #78ff4d; color: #3c7f26; }
        .today { background-color: #ff4d78; color: #721c24; }
        footer {
            background-color: #AD795B;
            color: white;
            text-align: center;
            padding: 10px;
            width: 100%;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            border: 1px solid #ccc; 
            padding: 8px; 
            text-align: left; 
        }
        .issued-table {
            background-color: #d34dff;
            color: #69267f;
        }
        .returned-table {
            background-color: #4d78ff;
            color: #263c7f;
        }
        .not-returned-table {
            background-color: #FFE699;
            color: #7f734c;
        }
        .balance-table {
            background-color: #78ff4d;
            color: #3c7f26;
        }
        .issued-table th {
            background-color: #d34dff !important;
            color: #69267f !important;
        }
        .returned-table th {
            background-color: #4d78ff !important;
            color: #263c7f !important;
        }
        .not-returned-table th {
            background-color: #FFE699 !important;
            color: #7f734c !important;
        }
        .balance-table th {
            background-color: #78ff4d !important;
            color: #3c7f26 !important;
        }
        .table-container th {
            background: inherit !important;
            color: inherit !important;
        }
        /* Add new table styles */
        .table-container td {
            background-color: white !important;
            color: #333 !important;
        }
        .issued-table td {
            border-color: #d34dff !important;
        }
        .returned-table td {
            border-color: #4d78ff !important;
        }
        .not-returned-table td {
            border-color: #FFE699 !important;
        }
        .balance-table td {
            border-color: #78ff4d !important;
        }
        .return-btn {
            display: inline-block;
            padding: 5px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .issued-table .return-btn {
            background-color: #d34dff;
            color: #69267f;
        }
        .issued-table .return-btn:hover {
            background-color: #b832d9;
            transform: scale(1.05);
        }
        .not-returned-table .return-btn {
            background-color: #FFE699;
            color: #7f734c;
        }
        .not-returned-table .return-btn:hover {
            background-color: rgb(221, 199, 91);
            transform: scale(1.05);
        }
        .action-buttons {
            position: absolute;
            top: 15px;
            right: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .action-btn {
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            display: inline-block;
        }
        .action-btn.issue {
            background-color: #d34dff;
            color: #69267f;
        }
        .action-btn.issue:hover {
            background-color: #b832d9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
        .action-btn.returned {
            background-color: #4d78ff;
            color: #263c7f;
        }
        .action-btn.returned:hover {
            background-color: #3d68e6;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
        .action-btn.not-returned {
            background-color: #FFE699;
            color: #7f734c;
        }
        .action-btn.not-returned:hover {
            background-color: rgb(221, 199, 91);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
        .action-btn.balance {
            background-color: #78ff4d;
            color: #3c7f26;
        }
        .action-btn.balance:hover {
            background-color: #68e63d;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <header>Library Management System</header>
    <div class="container">
        <div class="sidebar">
            <h2>MENU</h2>
            <div class="menu-links">
                <a href="?view=dashboard" class="<?= $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <a href="?view=issued" class="<?= $currentPage === 'issued' ? 'active' : ''; ?>">Issued</a>
                <a href="?view=returned" class="<?= $currentPage === 'returned' ? 'active' : ''; ?>">Returned</a>
                <a href="?view=not_returned" class="<?= $currentPage === 'not_returned' ? 'active' : ''; ?>">Not Returned</a>
                <a href="?view=balance" class="<?= $currentPage === 'balance' ? 'active' : ''; ?>">Balance</a>
            </div>
            <button class="logout" onclick="location.href='logout.php'">Log Out</button>
        </div>
        <div class="main-content">
            <?php if ($currentPage === 'dashboard'): ?>
            <h1>Welcome <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
            <div class="status-container">
                <div class="status-box issued">
                    <div class="status-box-content">
                        <a href="?view=issued">ISSUED</a><br>
                        <strong><?= $issuedCount ?></strong>
                    </div>
                </div>
                <div class="status-box returned">
                    <div class="status-box-content">
                        <a href="?view=returned">RETURNED</a><br>
                        <strong><?= $returnedCount ?></strong>
                    </div>
                </div>
                <div class="status-box not-returned">
                    <div class="status-box-content">
                        <a href="?view=not_returned">NOT RETURNED</a><br>
                        <strong><?= $notReturnedCount ?></strong>
                    </div>
                </div>
                <div class="status-box balance">
                    <div class="status-box-content">
                        <a href="?view=balance">BALANCE</a><br>
                        <strong>₱<?= number_format($balanceCount, 2) ?></strong>
                    </div>
                </div>
                <div class="status-box today" id="dateToday">
                    <div class="status-box-content">
                        DATE TODAY<br><span></span>
                    </div>
                </div>
            </div>
            <h3>Student No: <?= htmlspecialchars($studentNumber) ?></h3>
            <?php elseif ($currentPage === 'issued'): ?>
                <h2>Issued Books</h2>
                <div class="action-buttons">
                    <a href="issue_books.php" class="action-btn issue">Issue Books</a>
                </div>
                <div class="table-container"><table class="issued-table"><tr><th>ISBN</th><th>Title</th><th>Borrow Date</th><th>Return Date</th><th>Action</th></tr>
                <?php while($row = $issuedBooks->fetch_assoc()): ?>
                    <tr><td><?= htmlspecialchars($row['book_isbn']) ?></td><td><?= htmlspecialchars($row['Title']) ?></td><td><?= htmlspecialchars($row['borrow_date']) ?></td><td><?= htmlspecialchars($row['return_date']) ?></td><td><a href="return_books.php?isbn=<?= urlencode($row['book_isbn']) ?>&student_number=<?= urlencode($studentNumber) ?>" class="return-btn">Return Book</a></td></tr>
                <?php endwhile; ?></table></div>
            <?php elseif ($currentPage === 'returned'): ?>
                <h2>Returned Books</h2>
                <div class="action-buttons">
                    <a href="issue_books.php" class="action-btn returned">Issue Books</a>
                </div>
                <div class="table-container"><table class="returned-table"><tr><th>ISBN</th><th>Title</th><th>Borrow Date</th><th>Return Date</th></tr>
                <?php while($row = $returnedBooks->fetch_assoc()): ?>
                    <tr><td><?= htmlspecialchars($row['book_isbn']) ?></td><td><?= htmlspecialchars($row['Title']) ?></td><td><?= htmlspecialchars($row['borrow_date']) ?></td><td><?= htmlspecialchars($row['return_date']) ?></td></tr>
                <?php endwhile; ?></table></div>
            <?php elseif ($currentPage === 'not_returned'): ?>
                <h2>Not Returned (Overdue) Books</h2>
                <div class="action-buttons">
                    <a href="issue_books.php" class="action-btn not-returned">Issue Books</a>
                </div>
                <div class="table-container"><table class="not-returned-table"><tr><th>ISBN</th><th>Title</th><th>Borrow Date</th><th>Return Date</th><th>Action</th></tr>
                <?php while($row = $notReturnedBooks->fetch_assoc()): ?>
                    <tr><td><?= htmlspecialchars($row['book_isbn']) ?></td><td><?= htmlspecialchars($row['Title']) ?></td><td><?= htmlspecialchars($row['borrow_date']) ?></td><td><?= htmlspecialchars($row['return_date']) ?></td><td><a href="return_books.php?isbn=<?= urlencode($row['book_isbn']) ?>&student_number=<?= urlencode($studentNumber) ?>" class="return-btn">Return Book</a></td></tr>
                <?php endwhile; ?></table></div>
            <?php elseif ($currentPage === 'balance'): ?>
                <h2>Balance (Penalties)</h2>
                <div class="action-buttons">
                    <a href="issue_books.php" class="action-btn balance">Issue Books</a>
                </div>
                <div class="table-container"><table class="balance-table"><tr><th>ISBN</th><th>Title</th><th>Due/Return Date</th><th>Penalty</th><th>Status</th></tr>
                <?php 
                $totalPenalty = 0;
                
                // Display returned book penalties
                while($row = $balanceBooks->fetch_assoc()): 
                    $totalPenalty += $row['late_fee'];
                ?>
                    <tr><td><?= htmlspecialchars($row['ISBN']) ?></td><td><?= htmlspecialchars($row['Title']) ?></td><td><?= htmlspecialchars($row['return_date']) ?></td><td>₱<?= number_format($row['late_fee'], 2) ?></td><td>Returned</td></tr>
                <?php endwhile; ?>
                
                <?php 
                // Display overdue book penalties (real-time calculation)
                foreach($overduePenalties as $overdue): 
                    $totalPenalty += $overdue['late_fee'];
                ?>
                    <tr><td><?= htmlspecialchars($overdue['ISBN']) ?></td><td><?= htmlspecialchars($overdue['Title']) ?></td><td><?= htmlspecialchars($overdue['return_date']) ?></td><td>₱<?= number_format($overdue['late_fee'], 2) ?></td><td>Overdue (<?= $overdue['overdue_days'] ?> days)</td></tr>
                <?php endforeach; ?>
                
                <tr class="total-row"><td colspan="3" style="text-align: right; font-weight: bold;">Total:</td><td style="font-weight: bold;">₱<?= number_format($totalPenalty, 2) ?></td><td></td></tr>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
    <footer>&copy; 2025 Chapter One. All rights reserved.</footer>
    <script>
        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = today.getFullYear();
        document.querySelector('#dateToday span').textContent = `${day}/${month}/${year}`;
    </script>
</body>
</html>
