<!DOCTYPE html>
<html>
<head>
<title>Home</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<style>
body {
  background-color: #C4A092;
  font-family: sans-serif;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 100vh;
  margin: 0;
}

.container {
  background-color: rgba(255, 255, 255, 0.25);
  padding: 20px 25px;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  margin-top: 20px;
  width: 80%;
  max-width: 700px;
  min-height: 280px; 
  display: flex;
  flex-direction: column;
  justify-content: space-between; 
}

.header {
  text-align: center;
}

.logo {
  width: 100px;
  height: 100px;
  margin-top: 50px;
}

.button-row {
  width: 100%;
  display: flex;
  justify-content: space-between;
}

.button {
  flex: 1; 
  padding: 30px;
  background-color: #FFE699;
  border: 2px solid white; 
  cursor: pointer;
  font-size: 16px;
  color: #333;
  text-align: center;
  text-decoration: none;
  box-sizing: border-box;
  margin: 5px;
  font-weight: bold;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.button:hover {
  background-color: rgb(221, 199, 91);
  color: white;
  transform: scale(1.02);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.brown-button {
  background-color: #AD795B;
  color: black;
  border: 2px solid white;
}

.brown-button:hover {
  background-color: #8B5A3C;
  color: white;
  transform: scale(1.02);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}
</style>
</head>

<body>
  <div class="header">
    <img src="logo.png" alt="Logo" class="logo">
    <h4>CHAPTER ONE</h4>
    <p>Organizing Knowledge, One Book at a Time</p>
  </div>
  <div class="container">
    <div class="button-row">
      <button class="button" onclick="openBooksInventory()">BOOKS INVENTORY</button>
      <button class="button" onclick="openLogin()">LOGIN</button>
    </div>

    <div class="button-row">
      <button class="button brown-button" onclick="issueBooks()">ISSUE BOOKS</button>
    </div>

    <div class="button-row">
      <button class="button brown-button" onclick="returnBooks()">RETURN BOOKS</button>
    </div>
  </div>

  <script>
    function openBooksInventory() {
      window.location.href = "books_inventory.php"; 
    }

    function openLogin() {
      window.location.href = "login.php"; 
    }

    function issueBooks() {
      window.location.href = "issue_books.php"; 
    }

    function returnBooks() {
      window.location.href = "return_books.php"; 
    }
  </script>
</body>
</html>