-- Creating books table
CREATE TABLE IF NOT EXISTS books (
  BookID INT(11) NOT NULL AUTO_INCREMENT,
  Title VARCHAR(255) DEFAULT NULL,
  Author VARCHAR(255) DEFAULT NULL,
  ISBN VARCHAR(20) NOT NULL UNIQUE,
  Publisher VARCHAR(255) DEFAULT NULL,
  Genre VARCHAR(100) DEFAULT NULL,
  Location VARCHAR(100) DEFAULT NULL,
  copies INT(11) NOT NULL DEFAULT 1,
  available_copies INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (BookID)
);

-- Creating users table (with role column)
CREATE TABLE IF NOT EXISTS users (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  contact VARCHAR(50) DEFAULT NULL,
  student_number VARCHAR(20) NOT NULL UNIQUE,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Creating issued_books table
CREATE TABLE IF NOT EXISTS issued_books (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  book_isbn VARCHAR(20) NOT NULL,
  student_number VARCHAR(20) NOT NULL,
  student_name VARCHAR(255) NOT NULL,
  student_email VARCHAR(255) NOT NULL,
  student_contact VARCHAR(50) NOT NULL,
  borrow_date DATE NOT NULL,
  return_date DATE NOT NULL,
  status ENUM('issued', 'returned') DEFAULT 'issued',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_number) REFERENCES users(student_number),
  FOREIGN KEY (book_isbn) REFERENCES books(ISBN)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Creating book_returns table
CREATE TABLE IF NOT EXISTS book_returns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  user_id INT NOT NULL,
  return_date DATE,
  due_date DATE,
  late_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (book_id) REFERENCES books(BookID),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Creating deleted_books table
CREATE TABLE IF NOT EXISTS deleted_books (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  Title VARCHAR(255) DEFAULT NULL,
  Author VARCHAR(255) DEFAULT NULL,
  ISBN VARCHAR(20) NOT NULL UNIQUE,
  Publisher VARCHAR(255) DEFAULT NULL,
  Genre VARCHAR(100) DEFAULT NULL,
  Location VARCHAR(100) DEFAULT NULL,
  copies INT(11) NOT NULL DEFAULT 1,
  available_copies INT(11) NOT NULL DEFAULT 1,
  deletion_reason TEXT NOT NULL,
  deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO books (Title, Author, ISBN, Publisher, Genre, Location, copies, available_copies) VALUES
('Noli Me Tangere', 'Jose Rizal', '9789711502085', 'National Historical Commission of the Philippines', 'Historical Fiction', 'Shelf A1', 5, 2),
('El Filibusterismo', 'Jose Rizal', '9789711502092', 'National Historical Commission of the Philippines', 'Historical Fiction', 'Shelf A2', 3, 0),
('The Philippines: A Century Hence', 'Jose Rizal', '9789711502108', 'National Historical Commission of the Philippines', 'Non-fiction', 'Shelf A3', 2, 1),
('Hamlet', 'William Shakespeare', '9780486275572', 'Dover Publications', 'Play', 'Shelf B1', 4, 0),
('Macbeth', 'William Shakespeare', '9780486278030', 'Dover Publications', 'Play', 'Shelf B2', 3, 3),
('Romeo and Juliet', 'William Shakespeare', '9780451528389', 'Signet Classics', 'Play', 'Shelf B3', 2, 0),
('War and Peace', 'Leo Tolstoy', '9781853260629', 'Wordsworth Editions', 'Historical Fiction', 'Shelf C1', 3, 2),
('Anna Karenina', 'Leo Tolstoy', '9781853262715', 'Wordsworth Editions', 'Historical Fiction', 'Shelf C2', 2, 0),
('The Death of Ivan Ilyich', 'Leo Tolstoy', '9780140441801', 'Penguin Classics', 'Philosophy', 'Shelf C3', 1, 0),
('Meditations', 'Marcus Aurelius', '9780140449333', 'Penguin Classics', 'Philosophy', 'Shelf D1', 4, 4),
('The Thoughts of Marcus Aurelius', 'Marcus Aurelius', '9780140449357', 'Penguin Classics', 'Philosophy', 'Shelf D2', 2, 1),
('The Essential Marcus Aurelius', 'Marcus Aurelius', '9780061284371', 'HarperOne', 'Philosophy', 'Shelf D3', 3, 0),
('The Trial', 'Franz Kafka', '9780805209990', 'Schocken Books', 'Novel', 'Shelf E1', 2, 0),
('The Metamorphosis', 'Franz Kafka', '9780805209991', 'Schocken Books', 'Novel', 'Shelf E2', 3, 2),
('1984', 'George Orwell', '9780451524935', 'Signet Classics', 'Dystopian Fiction', 'Shelf F1', 5, 3),
('Animal Farm', 'George Orwell', '9780451526342', 'Signet Classics', 'Dystopian Fiction', 'Shelf F2', 4, 0),
('The Hobbit', 'J.R.R. Tolkien', '9780345339683', 'Ballantine Books', 'Fantasy', 'Shelf G1', 3, 1),
('The Lord of the Rings', 'J.R.R. Tolkien', '9780618640157', 'Mariner Books', 'Fantasy', 'Shelf G2', 4, 0),
('The Silmarillion', 'J.R.R. Tolkien', '9780544338011', 'Houghton Mifflin Harcourt', 'Fantasy', 'Shelf G3', 2, 2),
('Moby-Dick', 'Herman Melville', '9781503280786', 'CreateSpace Independent Publishing Platform', 'Adventure', 'Shelf H1', 3, 0),
('The Picture of Dorian Gray', 'Oscar Wilde', '9780141439570', 'Penguin Classics', 'Gothic Fiction', 'Shelf I1', 2, 0),
('The Importance of Being Earnest', 'Oscar Wilde', '9780486275589', 'Dover Publications', 'Play', 'Shelf I2', 3, 2),
('The Complete Works of William Shakespeare', 'William Shakespeare', '9780451527256', 'Signet Classics', 'Play', 'Shelf B4', 4, 1),
('The Old Man and the Sea', 'Ernest Hemingway', '9780684801223', 'Scribner', 'Fiction', 'Shelf J1', 2, 0),
('For Whom the Bell Tolls', 'Ernest Hemingway', '9780684803357', 'Scribner', 'Fiction', 'Shelf J2', 3, 3),
('The Sun Also Rises', 'Ernest Hemingway', '9780684800714', 'Scribner', 'Fiction', 'Shelf J3', 2, 0),
('The Brothers Karamazov', 'Fyodor Dostoevsky', '9780374528379', 'Farrar, Straus and Giroux', 'Philosophical Fiction', 'Shelf K1', 4, 2),
('Crime and Punishment', 'Fyodor Dostoevsky', '9780143058144', 'Penguin Classics', 'Psychological Fiction', 'Shelf K2', 3, 0),
('The Metamorphosis', 'Franz Kafka', '9780805209992', 'Schocken Books', 'Novel', 'Shelf L1', 2, 0),
('The Lord of the Rings: The Fellowship of the Ring', 'J.R.R. Tolkien', '9780618640158', 'Houghton Mifflin Harcourt', 'Fantasy', 'Shelf M1', 3, 1),
('The History of the Decline and Fall of the Roman Empire', 'Edward Gibbon', '9780452008788', 'Penguin Classics', 'History', 'Shelf N1', 2, 2),
('The Guns of August', 'Barbara Tuchman', '9780345476092', 'Random House', 'History', 'Shelf O1', 3, 0),
('Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', '9780062316110', 'Harper', 'History', 'Shelf P1', 4, 3),
('The Bible', 'Various Authors', '9781619701811', 'Shambhala', 'Religion', 'Shelf Q1', 5, 2),
('The Quran', 'Various Authors', '9780143106461', 'Penguin Classics', 'Religion', 'Shelf Q2', 3, 0),
('The Tao Te Ching', 'Laozi', '9780140441316', 'Penguin Classics', 'Philosophy', 'Shelf Q3', 2, 1),
('Mastering the Art of French Cooking', 'Julia Child', '9780375413407', 'Alfred A. Knopf', 'Cookbook', 'Shelf R1', 3, 0),
('The Joy of Cooking', 'Irma S. Rombauer', '9780743246262', 'Scribner', 'Cookbook', 'Shelf R2', 4, 4),
('Salt, Fat, Acid, Heat', 'Samin Nosrat', '9781476753836', 'Scribner', 'Cookbook', 'Shelf R3', 2, 0),
('The Body Keeps the Score', 'Bessel van der Kolk', '9780143127741', 'Penguin Books', 'Health', 'Shelf S1', 3, 2),
('How to Be Healthy', 'Dr. Peter Attia', '9780063080091', 'Avid Reader Press', 'Health', 'Shelf S2', 2, 1),
('Why We Sleep', 'Matthew Walker', '9781501144318', 'Scribe Publications', 'Health', 'Shelf S3', 4, 0),
('The 7 Habits of Highly Effective People', 'Stephen Covey', '9780743269513', 'Simon & Schuster', 'Self-Help', 'Shelf T1', 5, 3),
('How to Win Friends and Influence People', 'Dale Carnegie', '9780671027032', 'Simon & Schuster', 'Self-Help', 'Shelf T2', 3, 0),
('The Power of Habit', 'Charles Duhigg', '9780812981605', 'Random House', 'Self-Help', 'Shelf T3', 2, 2),
('The Complete Idiot\'s Guide to Philosophy', 'Jay Stevenson', '9781592579270', 'Alpha', 'Guides', 'Shelf U1', 3, 1),
('The Artist\'s Way', 'Julia Cameron', '9780143129257', 'TarcherPerigee', 'Self-Help', 'Shelf U2', 2, 0),
('Lonely Planet\'s Ultimate Travel Quiz Book', 'Lonely Planet', '9781788687841', 'Lonely Planet', 'Guides', 'Shelf U3', 4, 0),
('The Oxford English Dictionary', 'Oxford University Press', '9780199571123', 'Oxford University Press', 'Reference', 'Shelf V1', 2, 2),
('Encyclopedia Britannica', 'Encyclopedia Britannica', '9781593394483', 'Encyclopedia Britannica', 'Reference', 'Shelf V2', 3, 3),
('National Geographic Atlas of the World', 'National Geographic', '9781426208287', 'National Geographic', 'Reference', 'Shelf V3', 2, 0);

INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@example.com', '$2y$10$BqgfZlAMppR0C6kKofr2TelRoO461TKC0oKqSSgQrx/8QS9co1Sim', 'admin');