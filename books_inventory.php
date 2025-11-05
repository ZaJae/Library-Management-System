<?php
// Database Connection
include 'db_connect.php';

session_start();

// Dummy credentials
$admin_username = 'admin';
$admin_password = '1234';

// Handle admin login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_login'])) {
    if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
        $_SESSION['is_admin'] = true;
    } else {
        echo "<script>alert('Invalid admin credentials');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['Title'])) {
    $title = $_POST['Title'];
    $author = $_POST['Author'];
    $isbn = $_POST['ISBN'];
    $publisher = $_POST['Publisher'];
    $genre = $_POST['Genre'];
    $location = $_POST['Location'];
    $copies = $_POST['copies'];

    $stmt = $conn->prepare("INSERT INTO books (Title, Author, ISBN, Publisher, Genre, Location, copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssii", $title, $author, $isbn, $publisher, $genre, $location, $copies, $copies);

    if ($stmt->execute()) {
        echo "<script>alert('Book added successfully'); window.location.href = 'books_inventory.php';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

// Fetch books from the database
$sql = "SELECT * FROM books";
$result = $conn->query($sql);

// Fetch unique genres from the database
$genreQuery = "SELECT DISTINCT genre FROM books";
$genreResult = $conn->query($genreQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter One</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
         * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        body {
            background-color: #C4A092;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
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
            width: 20%;
            padding: 15px;
            background-color: #AD795B;
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }
        .button {
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
        .button:hover {
            background-color: rgb(221, 199, 91);
            color: white;
            transform: scale(1.02);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .active-button {
            background-color: rgb(221, 199, 91);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: scale(1.02);
        }
        .inactive-button {
            background-color: #FFE699;
            color: #333;
        }
        .add_book_button {
            background-color: #4d78ff; 
            color: white; 
            border: 2px solid white; 
            font-size: 14px;
            font-weight: bold;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .add_book_button:hover {
            background-color: blue; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: scale(1.02);
        }
        .edit_button {
            background-color: #4d78ff; 
            color: white; 
            border: 2px solid white; 
            font-size: 14px;
            font-weight: bold;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .edit_button:hover {
            background-color: blue; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: scale(1.02);
        }
        .sidebar .spacer {
            flex-grow: 1;
        }
        .spacer {
            height: 15px
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
        }
        .main-content > .content-section {
            display: none;
        }
        .main-content > .content-section.active {
            display: block;
        }
        .search-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
            width: 100%;
            align-items: center;
        }
        .search-bar input {
            flex: 2;
            padding: 10px;
            font-size: 14px;
        }
        .search-bar label {
            white-space: nowrap;
            font-weight: bold;
        }
        .search-bar select {
            flex: 1;
            padding: 10px;
            font-size: 14px;
        }
        .table-container {
            flex-grow: 1;
            overflow-y: auto;
            max-height: 250px;
            position: relative;
        }
        #entrySection .table-container {
            flex-grow: 1;
            overflow-y: auto;
            max-height: 200px;
            position: relative;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        thead {
            position: sticky;
            top: 0;
            background-color: blue;
            color: white;
            z-index: 1;
        }
        thead th {
            padding: 8px;
            border: 1px solid black;
            background-color: blue;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;  
        }
        tbody td {
            padding: 8px;
            border: 1px solid black;
            text-align: center;  
            white-space: normal;  
            overflow: visible; 
            text-overflow: ellipsis;  
        }
        tbody tr:hover td {
            background-color: #f1f1f1;
        }
        footer {
            background-color: #AD795B;
            color: white;
            text-align: center;
            padding: 10px;
            width: 100%;
        }
    </style>
    <script>
        function toggleContent(section) {
            const searchSection = document.getElementById("searchSection");
            const entrySection = document.getElementById("entrySection");
            const recordSection = document.getElementById("recordSection");
            const searchButton = document.getElementById("searchButton");
            const entryButton = document.getElementById("entryButton");
            const recordButton = document.getElementById("recordButton");

            // Reset all sections and buttons
            searchSection.classList.remove("active");
            entrySection.classList.remove("active");
            recordSection.classList.remove("active");
            searchButton.classList.remove("active-button");
            entryButton.classList.remove("active-button");
            recordButton.classList.remove("active-button");
            
            // Show the requested section
            if (section === "search") {
                searchSection.classList.add("active");
                searchButton.classList.add("active-button");
            } else if (section === "entry") {
                entrySection.classList.add("active");
                entryButton.classList.add("active-button");
            } else if (section === "record") {
                recordSection.classList.add("active");
                recordButton.classList.add("active-button");
            }
        }

        // Search for books dynamically
        function filterBooks() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const availabilityFilter = document.getElementById('availabilityFilter').value;

            const rows = document.querySelectorAll('#bookTable tr');
            rows.forEach(row => {
                const title = row.cells[0].innerText.toLowerCase();
                const author = row.cells[1].innerText.toLowerCase();
                const isbn = row.cells[2].innerText.toLowerCase();
                const genre = row.cells[4].innerText.toLowerCase();
                const availableCopies = parseInt(row.cells[6].innerText);

                const matchesSearch = title.includes(searchInput) || author.includes(searchInput) || isbn.includes(searchInput);
                const matchesGenre = categoryFilter ? genre.includes(categoryFilter.toLowerCase()) : true;
                const matchesAvailability = availabilityFilter === '' ? true :
                    (availabilityFilter === 'available' && availableCopies > 0) ||
                    (availabilityFilter === 'unavailable' && availableCopies === 0);

                row.style.display = (matchesSearch && matchesGenre && matchesAvailability) ? '' : 'none';
            });
        }

        function filterBooksEntry() {
            const searchInput = document.getElementById('searchInputEntry').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilterEntry').value.toLowerCase();
            const availabilityFilter = document.getElementById('availabilityFilterEntry').value.toLowerCase();

            const rows = document.querySelectorAll('#bookTableEntry tr');
            rows.forEach(row => {
                const title = row.cells[0].innerText.toLowerCase();
                const author = row.cells[1].innerText.toLowerCase();
                const isbn = row.cells[2].innerText.toLowerCase();
                const genre = row.cells[4].innerText.toLowerCase();
                const availableCopies = parseInt(row.cells[6].innerText);

                const matchesSearch = title.includes(searchInput) || author.includes(searchInput) || isbn.includes(searchInput);
                const matchesGenre = categoryFilter ? genre.includes(categoryFilter) : true;
                const matchesAvailability = availabilityFilter === '' ? true :
                    (availabilityFilter === 'available' && availableCopies > 0) ||
                    (availabilityFilter === 'unavailable' && availableCopies === 0);

                row.style.display = (matchesSearch && matchesGenre && matchesAvailability) ? '' : 'none';
            });
        }

        function filterBooksRecord() {
            const searchInput = document.getElementById('searchInputRecord').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;

            // Filter active books
            const activeRows = document.querySelectorAll('#activeBooksTable tr');
            activeRows.forEach(row => {
                const title = row.cells[0].innerText.toLowerCase();
                const author = row.cells[1].innerText.toLowerCase();
                const isbn = row.cells[2].innerText.toLowerCase();
                
                const matchesSearch = title.includes(searchInput) || author.includes(searchInput) || isbn.includes(searchInput);
                const matchesStatus = statusFilter === '' || statusFilter === 'active';
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });

            // Filter deleted books
            const deletedRows = document.querySelectorAll('#deletedBooksTable tr');
            deletedRows.forEach(row => {
                const title = row.cells[0].innerText.toLowerCase();
                const author = row.cells[1].innerText.toLowerCase();
                const isbn = row.cells[2].innerText.toLowerCase();
                
                const matchesSearch = title.includes(searchInput) || author.includes(searchInput) || isbn.includes(searchInput);
                const matchesStatus = statusFilter === '' || statusFilter === 'deleted';
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }

        window.onload = function() {
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                toggleContent("entry");
                document.getElementById('recordButton').style.display = 'block';
            <?php else: ?>
                toggleContent("search");
                document.getElementById('recordButton').style.display = 'none';
            <?php endif; ?>
            filterBooks();
            filterBooksEntry();
        };

        function handleAdminAccess() {
            <?php if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true): ?>
                document.getElementById('adminLoginModal').style.display = 'flex';
            <?php else: ?>
                toggleContent('entry');
                document.getElementById('recordButton').style.display = 'block';
            <?php endif; ?>
        }

        function closeAdminModal() {
            document.getElementById('adminLoginModal').style.display = 'none';
        }
    </script>
</head>
<body>
    <header>Library Management System</header>
    <div class="spacer"></div>
    <div class="container">
        <div class="sidebar">
            <button id="searchButton" class="button" onclick="toggleContent('search')">Search for Books</button>
            <button id="entryButton" class="button" onclick="handleAdminAccess()">Book Entry</button>
            <button id="recordButton" class="button" onclick="toggleContent('record')" style="display: none;">Book Record</button>
            <div class="spacer"></div>
            <button class="button" onclick="location.href='home.php'">Back to Home</button>
        </div>
        <div class="main-content">
            <div id="searchSection" class="content-section">
                <h2>Search for books</h2>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search by title, author, or ISBN" onkeyup="filterBooks()">
                    <label for="categoryFilter">Genre:</label>
                    <select id="categoryFilter" onchange="filterBooks()">
                        <option value="">All</option>
                        <?php while ($genreRow = $genreResult->fetch_assoc()) {
                            echo "<option value='" . $genreRow['genre'] . "'>" . $genreRow['genre'] . "</option>";
                        } ?>
                    </select>
                    <label for="availabilityFilter">Status:</label>
                    <select id="availabilityFilter" onchange="filterBooks()">
                        <option value="">All Books</option>
                        <option value="available">Has Available Copies</option>
                        <option value="unavailable">No Available Copies</option>
                    </select>
                </div>
                <h2>List of books</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Publisher</th>
                                <th>Genre</th>
                                <th>Location</th>
                                <th>Available Copies</th>
                            </tr>
                        </thead>
                        <tbody id="bookTable">
                            <?php
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['Title']}</td>
                                        <td>{$row['Author']}</td>
                                        <td>{$row['ISBN']}</td>
                                        <td>{$row['Publisher']}</td>
                                        <td>{$row['Genre']}</td>
                                        <td>{$row['Location']}</td>
                                        <td>{$row['available_copies']}</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="entrySection" class="content-section">
                <h2>Search for books</h2>
                <?php
                // Reset genreResult for second dropdown
                $genreResult = $conn->query("SELECT DISTINCT genre FROM books");
                ?>
                <div class="search-bar">
                    <input type="text" id="searchInputEntry" placeholder="Search by title, author, or ISBN" onkeyup="filterBooksEntry()">
                    <label for="categoryFilterEntry">Genre:</label>
                    <select id="categoryFilterEntry" onchange="filterBooksEntry()">
                        <option value="">All</option>
                        <?php while ($genreRow = $genreResult->fetch_assoc()) {
                            echo "<option value='" . $genreRow['genre'] . "'>" . $genreRow['genre'] . "</option>";
                        } ?>
                    </select>
                    <label for="availabilityFilterEntry">Status:</label>
                    <select id="availabilityFilterEntry" onchange="filterBooksEntry()">
                        <option value="">All Books</option>
                        <option value="available">Has Available Copies</option>
                        <option value="unavailable">No Available Copies</option>
                    </select>
                </div>
                <h2>Add Book</h2>
                <form method="POST" onsubmit="return confirm('Add this book to inventory?');" style="margin-bottom: 20px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <input type="text" name="Title" placeholder="Title" required style="flex: 1; padding: 10px;">
                        <input type="text" name="Author" placeholder="Author" required style="flex: 1; padding: 10px;">
                        <input type="text" name="ISBN" placeholder="ISBN" required style="flex: 1; padding: 10px;">
                        <input type="text" name="Publisher" placeholder="Publisher" required style="flex: 1; padding: 10px;">
                        <input type="text" name="Genre" placeholder="Genre" required style="flex: 1; padding: 10px;">
                        <input type="text" name="Location" placeholder="Shelf Location" required style="flex: 1; padding: 10px;">
                        <input type="number" name="copies" placeholder="Number of Copies" required min="1" style="flex: 1; padding: 10px;">
                        <button type="submit" class="add_book_button" style="flex: 0 0 auto;">Add Book</button>
                    </div>
                </form>
                <h2>List of books</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Publisher</th>
                                <th>Genre</th>
                                <th>Location</th>
                                <th>Copies</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody id="bookTableEntry">
                            <?php
                            $result = $conn->query("SELECT * FROM books");
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['Title']}</td>
                                        <td>{$row['Author']}</td>
                                        <td>{$row['ISBN']}</td>
                                        <td>{$row['Publisher']}</td>
                                        <td>{$row['Genre']}</td>
                                        <td>{$row['Location']}</td>
                                        <td>{$row['available_copies']}</td>
                                        <td><a href='edit_book.php?isbn={$row['ISBN']}'><button class='edit_button'>Update</button></a></td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="recordSection" class="content-section">
                <h2>Book Records</h2>
                <div class="search-bar">
                    <input type="text" id="searchInputRecord" placeholder="Search by title, author, or ISBN" onkeyup="filterBooksRecord()">
                    <label for="statusFilter">Status:</label>
                    <select id="statusFilter" onchange="filterBooksRecord()">
                        <option value="">All Books</option>
                        <option value="active">Active Books</option>
                        <option value="deleted">Deleted Books</option>
                    </select>
                </div>

                <h3>Active Books</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Publisher</th>
                                <th>Genre</th>
                                <th>Location</th>
                                <th>Copies</th>
                            </tr>
                        </thead>
                        <tbody id="activeBooksTable">
                            <?php
                            $activeBooksQuery = "SELECT * FROM books";
                            $activeBooksResult = $conn->query($activeBooksQuery);
                            while ($row = $activeBooksResult->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['Title']}</td>
                                        <td>{$row['Author']}</td>
                                        <td>{$row['ISBN']}</td>
                                        <td>{$row['Publisher']}</td>
                                        <td>{$row['Genre']}</td>
                                        <td>{$row['Location']}</td>
                                        <td>{$row['copies']}</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <h3>Deleted Books</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Publisher</th>
                                <th>Genre</th>
                                <th>Location</th>
                                <th>Copies</th>
                                <th>Deletion Reason</th>
                                <th>Deletion Date</th>
                            </tr>
                        </thead>
                        <tbody id="deletedBooksTable">
                            <?php
                            $deletedBooksQuery = "SELECT * FROM deleted_books";
                            $deletedBooksResult = $conn->query($deletedBooksQuery);
                            while ($row = $deletedBooksResult->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['Title']}</td>
                                        <td>{$row['Author']}</td>
                                        <td>{$row['ISBN']}</td>
                                        <td>{$row['Publisher']}</td>
                                        <td>{$row['Genre']}</td>
                                        <td>{$row['Location']}</td>
                                        <td>{$row['copies']}</td>
                                        <td>{$row['deletion_reason']}</td>
                                        <td>{$row['deleted_at']}</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="spacer"></div>
    <footer>&copy; 2025 Chapter One. All rights reserved.</footer>
    
    <div id="adminLoginModal" style="display:none; 
                                     position: fixed; 
                                     top: 0; 
                                     left: 0; 
                                     width: 100%; 
                                     height: 100%; 
                                     background-color: rgba(0,0,0,0.5); 
                                     justify-content: center; 
                                     align-items: center; 
                                     z-index: 9999;">
    <form method="POST" style="background-color: #C4A092; 
                               padding: 30px; 
                               border-radius: 10px; 
                               width: 300px; 
                               text-align: center;">
        <h3 style="margin-top: -3px;">Admin Login</h3>
        <input type="text" name="username" placeholder="Username" required style="width: 100%; 
                                                                              padding: 10px; 
                                                                              margin-bottom: 10px;"><br>
        <input type="password" name="password" placeholder="Password" required style="width: 100%; 
                                                                                  padding: 10px; 
                                                                                  margin-bottom: 10px;"><br>
        <input type="submit" name="admin_login" value="Login" 
               style="padding: 10px 20px; 
                      background-color: #4d78ff; 
                      color: white; 
                      border: 2px solid white; 
                      font-size: 14px; 
                      font-weight: bold; 
                      cursor: pointer; 
                      transition: all 0.3s ease;">
        <button type="button" onclick="closeAdminModal()" 
                style="padding: 10px 20px; 
                       background-color: #ff4d78; 
                       color: white; 
                       border: 2px solid white; 
                       font-size: 14px; 
                       font-weight: bold; 
                       cursor: pointer; 
                       transition: all 0.3s ease; 
                       margin-left: 10px;">
            Cancel
        </button>
    </form>
</div>


</body>
</html>
