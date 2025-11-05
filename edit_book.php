<?php
include 'db_connect.php';

if (!isset($_GET['isbn'])) {
    die("No ISBN provided.");
}

$isbn = $_GET['isbn'];

$stmt = $conn->prepare("SELECT * FROM books WHERE ISBN = ?");
$stmt->bind_param("s", $isbn);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No book found with ISBN: $isbn");
}

$book = $result->fetch_assoc();

// Handle book update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $title = $_POST['Title'];
    $author = $_POST['Author'];
    $publisher = $_POST['Publisher'];
    $genre = $_POST['Genre'];
    $location = $_POST['Location'];
    $available_copies = $_POST['available_copies'];

    $updateStmt = $conn->prepare("UPDATE books SET Title = ?, Author = ?, Publisher = ?, Genre = ?, Location = ?, available_copies = ? WHERE ISBN = ?");
    $updateStmt->bind_param("sssssis", $title, $author, $publisher, $genre, $location, $available_copies, $isbn);

    if ($updateStmt->execute()) {
        echo "<script>alert('Book updated successfully!'); window.location.href='books_inventory.php';</script>";
        exit;
    } else {
        echo "Error updating book: " . $conn->error;
    }
}

// Handle book deletion
if (isset($_POST['delete'])) {
    if (empty($_POST['deletion_reason'])) {
        echo "<script>alert('Please provide a reason for deletion.');</script>";
    } else {
        // First, insert the book into deleted_books table
        $insertDeletedStmt = $conn->prepare("INSERT INTO deleted_books (Title, Author, ISBN, Publisher, Genre, Location, copies, available_copies, deletion_reason) SELECT Title, Author, ISBN, Publisher, Genre, Location, copies, available_copies, ? FROM books WHERE ISBN = ?");
        $insertDeletedStmt->bind_param("ss", $_POST['deletion_reason'], $isbn);
        
        if ($insertDeletedStmt->execute()) {
            // Then delete from books table
            $deleteStmt = $conn->prepare("DELETE FROM books WHERE ISBN = ?");
            $deleteStmt->bind_param("s", $isbn);

            if ($deleteStmt->execute()) {
                echo "<script>alert('Book deleted successfully!'); window.location.href='books_inventory.php';</script>";
                exit;
            } else {
                echo "Error deleting book: " . $conn->error;
            }
        } else {
            echo "Error archiving book: " . $conn->error;
        }
    }
}

// Fetch unique genres from the database for the dropdown
$genreQuery = "SELECT DISTINCT genre FROM books";
$genreResult = $conn->query($genreQuery);
$genres = [];
while ($row = $genreResult->fetch_assoc()) {
    $genres[] = $row['genre'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chapter One</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #C4A092;
            font-family: Arial, sans-serif;
        }

        header {
            background-color: #AD795B;
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 24px;
            font-weight: bold;
        }

        .container {
            max-width: 1135px;
            margin: 40px auto;
            margin-top: 35px;
            background-color: white;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        h3 {
            margin-top: 0;
            color: white;
            text-align: center;
            background-color: #AD795B;
            padding: 10px
        }

        form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        label {
            width: 100%;
            font-weight: bold;
        } 

        input[type="text"], select {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
        }

        .button-container {
            width: 100%;
            text-align: center;
            margin-top: 20px;
        }

        button {
            background-color: #4d78ff;
            color: white;
            border: 2px solid white;
            font-size: 14px;
            font-weight: bold;
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 23%;
            margin-right: 5px;
        }

        button:hover {
            background-color: blue;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .delete-button {
            background-color: #ff7a4d;
            color: white;
            margin-left: 5px;
        }

        .delete-button:hover {
            background-color: red;
        }

        footer {
            background-color: #AD795B;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: 40px;
        }
        .back-link {
            display: block;
            width: 30%;
            padding: 10px;
            background-color: #FFE699;
            border: 2px solid white;
            color: #333;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
            margin-top: 15px;
            margin-left: 395px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background-color: rgb(221, 199, 91);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .popup {
            display: none;
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .popup.active {
            display: block;
        }

        .popup h3 {
            color: green;
        }

        .popup button {
            background-color: #AD795B;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 8px;
        }

        .popup button:hover {
            background-color: #7f5a43;
        }
    </style>
</head>
<body>
    <header>Library Management System</header>

    <div class="container">
        <h3>ISBN: <?= htmlspecialchars($isbn) ?></h3>
        <form method="POST">
            <label for="Title">Title</label>
            <input type="text" name="Title" value="<?= htmlspecialchars($book['Title']) ?>" required>

            <label for="Author">Author</label>
            <input type="text" name="Author" value="<?= htmlspecialchars($book['Author']) ?>" required>

            <label for="Publisher">Publisher</label>
            <input type="text" name="Publisher" value="<?= htmlspecialchars($book['Publisher']) ?>" required>

            <label for="Genre">Genre</label>
            <select name="Genre" required>
                <?php foreach ($genres as $genre) { ?>
                    <option value="<?= $genre ?>" <?= $book['Genre'] === $genre ? 'selected' : '' ?>><?= $genre ?></option>
                <?php } ?>
            </select>

            <label for="Location">Location</label>
            <input type="text" name="Location" value="<?= htmlspecialchars($book['Location']) ?>" required>

            <label for="available_copies">Available Copies</label>
            <input type="number" name="available_copies" value="<?= htmlspecialchars($book['available_copies']) ?>" required min="0" max="<?= htmlspecialchars($book['copies']) ?>" style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ccc;">

            <div class="button-container">
                <button type="submit" name="update">Update Book</button>
                <button type="button" onclick="showDeleteConfirmation()" class="delete-button">Delete Book</button>
            </div>
        </form>
        <a href="books_inventory.php" class="back-link">Back to Inventory</a>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="popup" style="display: none; width: 500px; max-width: 90%;">
        <h3 style="background-color: #FFE699; color: #333; margin: -20px -20px 20px -20px; padding: 15px; border-radius: 8px 8px 0 0; text-align: center;">Delete Book</h3>
        <p style="font-size: 16px; margin-bottom: 20px; text-align: center;">Please provide a reason for deleting this book:</p>
        <form method="POST" style="margin-top: 20px; display: flex; flex-direction: column; align-items: center;">
            <div id="select_container" style="width: 80%; margin: 0 auto; text-align: center;">
                <label for="reason_type" style="display: block; margin-bottom: 8px; font-weight: bold; text-align: center;">Select Reason:</label>
                <select id="reason_type" onchange="handleReasonChange()" style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ccc; margin-bottom: 10px; text-align: center; text-align-last: center; -webkit-appearance: none; -moz-appearance: none; appearance: none; background: #fff url('data:image/svg+xml;utf8,<svg fill="gray" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 10px center;">
                    <option value="" style="text-align: center;">Select a reason...</option>
                    <option value="damaged" style="text-align: center;">Book is damaged beyond repair</option>
                    <option value="lost" style="text-align: center;">Book has been lost</option>
                    <option value="outdated" style="text-align: center;">Content is outdated</option>
                    <option value="duplicate" style="text-align: center;">Duplicate copy</option>
                    <option value="withdrawn" style="text-align: center;">Withdrawn from circulation</option>
                    <option value="replacement" style="text-align: center;">Being replaced with new edition</option>
                    <option value="custom" style="text-align: center;">Other (Please specify)</option>
                </select>
            </div>
            <div id="custom_reason_div" style="display: none; width: 80%; margin: 0 auto; text-align: center;">
                <label for="deletion_reason" style="display: block; margin-bottom: 8px; font-weight: bold; text-align: center;">Custom Reason:</label>
                <textarea name="deletion_reason" id="deletion_reason" required style="width: 100%; padding: 10px; margin-bottom: 15px; min-height: 100px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Enter custom reason for deletion..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: center; align-items: center; margin-top: 20px; text-align: center; width: 100%;">
                <button type="submit" name="delete" style="width: 150px; background-color: #ff7a4d; color: white; border: 2px solid white; font-size: 14px; font-weight: bold; padding: 12px 20px; cursor: pointer; transition: all 0.3s ease;">Confirm Delete</button>
                <button type="button" onclick="closeDeleteModal()" style="width: 150px; background-color: #4d78ff; color: white; border: 2px solid white; font-size: 14px; font-weight: bold; padding: 12px 20px; cursor: pointer; transition: all 0.3s ease;">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Popup for success message -->
    <div id="popup" class="popup">
        <h3>Operation Successful!</h3>
        <p>Your changes have been saved successfully.</p>
        <button onclick="closePopup()">OK</button>
    </div>

    <footer>&copy; 2025 Chapter One. All rights reserved.</footer>

    <script>
        // Open popup on success
        function closePopup() {
            document.getElementById('popup').classList.remove('active');
        }

        function showDeleteConfirmation() {
            document.getElementById('deleteModal').style.display = 'block';
            // Reset the form when opening
            document.getElementById('reason_type').value = '';
            document.getElementById('custom_reason_div').style.display = 'none';
            document.getElementById('deletion_reason').value = '';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function handleReasonChange() {
            const reasonType = document.getElementById('reason_type').value;
            const customReasonDiv = document.getElementById('custom_reason_div');
            const deletionReason = document.getElementById('deletion_reason');
            const selectElement = document.getElementById('reason_type');
            const selectContainer = document.getElementById('select_container');
            const modalContent = document.querySelector('.popup form');

            if (reasonType === 'custom') {
                customReasonDiv.style.display = 'block';
                deletionReason.required = true;
                
                // Create side-by-side layout
                modalContent.style.flexDirection = 'row';
                modalContent.style.flexWrap = 'wrap';
                modalContent.style.justifyContent = 'center';
                modalContent.style.gap = '20px';
                modalContent.style.alignItems = 'flex-start';
                selectContainer.style.width = '45%';
                customReasonDiv.style.width = '45%';
                selectContainer.style.margin = '0';
                customReasonDiv.style.margin = '0';
                customReasonDiv.style.textAlign = 'center';
            } else if (reasonType) {
                customReasonDiv.style.display = 'none';
                deletionReason.value = document.getElementById('reason_type').options[document.getElementById('reason_type').selectedIndex].text;
                deletionReason.required = false;
                
                // Reset to centered layout
                modalContent.style.flexDirection = 'column';
                modalContent.style.alignItems = 'center';
                modalContent.style.gap = '0';
                selectContainer.style.width = '80%';
                selectContainer.style.margin = '0 auto';
            } else {
                customReasonDiv.style.display = 'none';
                deletionReason.value = '';
                deletionReason.required = true;
                
                // Reset to centered layout
                modalContent.style.flexDirection = 'column';
                modalContent.style.alignItems = 'center';
                modalContent.style.gap = '0';
                selectContainer.style.width = '80%';
                selectContainer.style.margin = '0 auto';
            }
        }

        // Add this new function to ensure centering on page load
        window.onload = function() {
            const selectElement = document.getElementById('reason_type');
            selectElement.style.textAlign = 'center';
            selectElement.style.textAlignLast = 'center';
        }

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST') { ?>
            document.getElementById('popup').classList.add('active');
        <?php } ?>
    </script>
</body>
</html>