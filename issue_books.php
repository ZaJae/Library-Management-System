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
}

$notification = '';
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $book_isbn = $_POST['book_isbn'];
    $student_number = $_POST['student_number'];
    $student_name = $_POST['student_name'];
    $student_email = $_POST['student_email'];
    $student_contact = $_POST['student_contact'];
    $borrow_date = $_POST['borrow_date'];
    $return_date = $_POST['return_date'];

    // Check if the book is available
    $checkBook = $conn->prepare("SELECT available_copies FROM books WHERE ISBN = ?");
    $checkBook->bind_param("s", $book_isbn);
    $checkBook->execute();
    $result = $checkBook->get_result();
    
    if ($result->num_rows === 0) {
        $notification = '<div class="notification error">Book not found!</div>';
    } else {
        $book = $result->fetch_assoc();
        if ($book['available_copies'] <= 0) {
            $notification = '<div class="notification error">Book is not available!</div>';
        } else {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert into issued_books table
                $stmt = $conn->prepare("INSERT INTO issued_books (book_isbn, student_number, student_name, student_email, student_contact, borrow_date, return_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $book_isbn, $student_number, $student_name, $student_email, $student_contact, $borrow_date, $return_date);
                $stmt->execute();

                // Update book available copies
                $updateStmt = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE ISBN = ?");
                $updateStmt->bind_param("s", $book_isbn);
                $updateStmt->execute();

                // Commit transaction
                $conn->commit();
                $notification = '<div class="notification success">Book issued successfully!</div>';
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $notification = '<div class="notification error">Error issuing book: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

$books = [];
// Only show books that are not currently issued (exclude books with status = 'issued')
$sql = "SELECT DISTINCT b.ISBN, b.Title, b.Author 
        FROM books b 
        LEFT JOIN issued_books ib ON b.ISBN = ib.book_isbn AND ib.status = 'issued'
        WHERE b.available_copies > 0 AND ib.id IS NULL
        ORDER BY b.Title";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issue Book</title>
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
    </style>
</head>
<body>
    <h2>ISSUE A BOOK</h2>
    <?php if ($notification) echo $notification; ?>
    <div class="container">
        <form method="POST" id="issueForm" onsubmit="return validateForm()">
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

            <div class="button-group">
                <button type="submit">ISSUE</button>
                <button type="button" onclick="printForm()">PRINT</button>
                <button type="button" onclick="location.href='<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'home.php' ?>'">CANCEL</button>
            </div>
        </form>

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
    window.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('borrow_date').value = today;

        // Auto-fill from logged-in user (if session exists)
        <?php if ($loggedInUser): ?>
        document.getElementById('student_number').value = '<?= htmlspecialchars($loggedInStudentNumber, ENT_QUOTES, 'UTF-8') ?>';
        document.getElementById('student_name').value = '<?= htmlspecialchars($loggedInUser['username'], ENT_QUOTES, 'UTF-8') ?>';
        document.getElementById('student_email').value = '<?= htmlspecialchars($loggedInUser['email'], ENT_QUOTES, 'UTF-8') ?>';
        document.getElementById('student_contact').value = '<?= htmlspecialchars($loggedInUser['contact'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
        <?php endif; ?>

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
function validateForm() {
    const isbn = document.getElementById('book_isbn').value;
    const studentNumber = document.getElementById('student_number').value;
    const studentName = document.getElementById('student_name').value;
    const studentEmail = document.getElementById('student_email').value;
    const studentContact = document.getElementById('student_contact').value;
    const borrowDate = document.getElementById('borrow_date').value;
    const returnDate = document.getElementById('return_date').value;

    if (!isbn || !studentNumber || !studentName || !studentEmail || !studentContact || !borrowDate || !returnDate) {
        alert('Please fill in all fields');
        return false;
    }

    // Validate return date is after borrow date
    const bDate = new Date(borrowDate);
    const rDate = new Date(returnDate);
    if (rDate <= bDate) {
        alert('Return date must be after borrow date');
        return false;
    }

    return true;
}
</script>
</body>
</html>
