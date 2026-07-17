CREATE DATABASE IF NOT EXISTS vendor_services_db;
USE vendor_services_db;

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

CREATE TABLE vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_name VARCHAR(100) NOT NULL,
  category VARCHAR(100) NOT NULL,
  location VARCHAR(100) NOT NULL,
  description TEXT NULL,
  image_url VARCHAR(500) NULL,
  price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
  unit_label VARCHAR(30) NOT NULL DEFAULT 'item',
  capacity INT NOT NULL DEFAULT 1
);

INSERT INTO vendors (vendor_name, category, location, description, image_url, price_per_unit, unit_label, capacity) VALUES
('Campus Print Hub', 'Printing & Stationery', 'Student Centre, Ground Floor', 'Printing, binding and photocopy services for assignments and reports.', '/uploads/sample-print-hub.jpg', 0.20, 'page', 5),
('Quick Wash Laundry', 'Laundry Services', 'Hostel Block B', 'Drop-off laundry and dry cleaning with same-day service slots.', '/uploads/sample-laundry.jpg', 5.00, 'kg', 3),
('Campus Tailor', 'Tailoring & Alterations', 'Student Centre, Level 1', 'Uniform alterations, name tag sewing and minor repairs.', '/uploads/sample-tailor.jpg', 15.00, 'item', 2),
('Tech Repair Kiosk', 'Gadget Repair', 'IT Building, Lobby', 'Laptop and phone screen repair, battery replacement and diagnostics.', '/uploads/sample-tech-repair.jpg', 50.00, 'item', 2);

-- Price shown up front is an estimate the vendor confirms in person (pay-on-
-- collection, like the real-world services these represent) - there's no
-- online checkout, unlike event-ticketing's simulated payment.
--
-- Unlike a single-facility booking (one exclusive reservation per slot),
-- a vendor can realistically serve several students in the same time slot,
-- so slots are capacity-checked (how many bookings exist for that vendor/
-- date/time vs. vendors.capacity) rather than exclusively locked with a
-- UNIQUE constraint - same pattern as equipment/book loans and shuttle bus
-- seats elsewhere in this project.
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  vendor_id INT NOT NULL,
  booking_date DATE NOT NULL,
  time_slot VARCHAR(20) NOT NULL,
  purpose VARCHAR(150) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  estimated_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (vendor_id) REFERENCES vendors(id),
  KEY idx_vendor_slot (vendor_id, booking_date, time_slot)
);

CREATE TABLE testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  vendor_id INT NOT NULL,
  comment TEXT NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
