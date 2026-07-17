CREATE DATABASE IF NOT EXISTS library_booking_db;
USE library_booking_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  id_number VARCHAR(20) NULL,
  faculty VARCHAR(150) NULL,
  date_of_birth DATE NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed admin account: admin@example.com / admin123
-- Change this password immediately in any real deployment.
INSERT INTO users (name, email, password_hash, is_admin) VALUES
('Admin', 'admin@example.com', '$2y$10$HI3gLmyD4OGmfNLAGUIL8.eBhhKu5nzL7wTDws.6mUNO9V44kyM5q', 1);

CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_name VARCHAR(100) NOT NULL,
  location VARCHAR(100) NOT NULL,
  capacity INT NOT NULL DEFAULT 1,
  image_url VARCHAR(500) NULL
);

INSERT INTO rooms (room_name, location, capacity, image_url) VALUES
('Discussion Room 1', 'Library, Level 2', 6, '/uploads/sample-room1.jpg'),
('Discussion Room 2', 'Library, Level 2', 6, '/uploads/sample-room2.jpg'),
('Discussion Room 3', 'Library, Level 3', 4, '/uploads/sample-room3.jpg'),
('Silent Study Pod', 'Library, Level 3', 2, '/uploads/sample-study-pod.jpg');

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  room_id INT NOT NULL,
  booking_date DATE NOT NULL,
  time_slot VARCHAR(20) NOT NULL,
  purpose VARCHAR(150) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (room_id) REFERENCES rooms(id),
  UNIQUE KEY unique_slot (room_id, booking_date, time_slot)
);

CREATE TABLE equipment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipment_name VARCHAR(100) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  total_units INT NOT NULL DEFAULT 1,
  image_url VARCHAR(500) NULL
);

INSERT INTO equipment (equipment_name, category, description, total_units, image_url) VALUES
('Dell Latitude Laptop', 'Computing', 'Windows laptop with charger, loaned for on-campus use.', 10, '/uploads/sample-equipment-laptop.jpg'),
('Portable Projector', 'AV Equipment', 'Mini projector for group presentations and study room use.', 4, '/uploads/sample-equipment-projector.jpg'),
('Scientific Calculator', 'Academic Tools', 'Casio scientific/graphing calculator for exams and tutorials.', 15, '/uploads/sample-equipment-calculator.jpg'),
('HDMI Adapter Kit', 'Computing', 'HDMI/USB-C adapter kit for connecting laptops to room displays.', 8, '/uploads/sample-equipment-hdmi.jpg');

CREATE TABLE equipment_loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  equipment_id INT NOT NULL,
  loan_date DATE NOT NULL,
  time_slot VARCHAR(20) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  purpose VARCHAR(150) NOT NULL,
  returned_at TIMESTAMP NULL,
  fine_paid_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (equipment_id) REFERENCES equipment(id)
);

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  author VARCHAR(150) NOT NULL,
  isbn VARCHAR(20) NULL,
  category VARCHAR(100) NOT NULL,
  total_copies INT NOT NULL DEFAULT 1,
  image_url VARCHAR(500) NULL
);

INSERT INTO books (title, author, isbn, category, total_copies, image_url) VALUES
('Clean Code', 'Robert C. Martin', '9780132350884', 'Computer Science', 3, '/uploads/sample-book-cleancode.jpg'),
('Introduction to Algorithms', 'Thomas H. Cormen', '9780262046305', 'Computer Science', 2, '/uploads/sample-book-algorithms.png'),
('The Pragmatic Programmer', 'Andrew Hunt', '9780135957059', 'Computer Science', 3, '/uploads/sample-book-pragmatic.jpg'),
('Principles of Accounting', 'Belverd Needles', '9781111530771', 'Accountancy', 4, '/uploads/sample-book-accounting.jpg');

CREATE TABLE book_loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  checkout_date DATE NOT NULL,
  due_date DATE NOT NULL,
  returned_at TIMESTAMP NULL,
  fine_paid_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (book_id) REFERENCES books(id)
);

CREATE TABLE testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  room_id INT NOT NULL,
  comment TEXT NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
