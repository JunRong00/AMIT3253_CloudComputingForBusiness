CREATE DATABASE IF NOT EXISTS sports_booking_db;
USE sports_booking_db;

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

-- A facility is a SPORT/CATEGORY (e.g. "Badminton"), not a specific physical court.
-- Adding a brand new sport (e.g. Pickleball, Paddleball) is just one row here plus
-- one or more rows in `courts` below - no code changes needed anywhere else.
CREATE TABLE facilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  location VARCHAR(100) NOT NULL,
  capacity INT NOT NULL DEFAULT 1,
  description TEXT NULL,
  materials TEXT NULL,
  rules TEXT NULL,
  image_url VARCHAR(500) NULL,
  layout_url VARCHAR(500) NULL
);

-- A court is a specific bookable instance of a facility (Court A, Lane 3, Table 1...).
-- Bookings/closures reference a court, never a facility directly - that's what lets
-- two courts of the same sport be booked independently for the same date/time.
CREATE TABLE courts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  facility_id INT NOT NULL,
  name VARCHAR(50) NOT NULL,
  location VARCHAR(100) NULL,
  FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

INSERT INTO facilities (name, location, capacity, description, materials, rules, layout_url) VALUES
('Badminton', 'Sports Complex, Level 1', 4,
 'Regulation-size indoor badminton courts with wooden flooring, ideal for doubles matches.',
 'Sprung wooden flooring with a matte vinyl overlay for grip and shock absorption. Court markings follow BWF regulation dimensions, with aluminium-framed nets and LED overhead lighting rated for indoor racket sports.',
 'Non-marking, rubber-soled sports shoes are mandatory on the court.\nNo food or drinks are allowed inside the court area.\nMaximum of 4 players per booked session (doubles play).\nBring your own racket and shuttlecocks; none are provided on-site.',
 '/uploads/sample-badminton-layout.jpg');

SET @badminton_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES
(@badminton_id, 'Court A'),
(@badminton_id, 'Court B');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Futsal', 'Sports Complex, Ground Floor', 10,
 'A full-size indoor futsal court with proper goals and rebound boards, suitable for 5-a-side matches.',
 'Polypropylene synthetic turf over a shock-pad underlay, engineered for indoor ball control and reduced joint impact. Goals are aluminium-framed with weighted bases; perimeter rebound boards are impact-resistant polycarbonate.',
 'Flat-soled futsal or indoor court shoes only - no studs or turf trainers.\nMaximum of 10 players per booked session (5-a-side).\nNo food or drinks on the playing surface.\nShin guards are strongly recommended but not compulsory.');
SET @futsal_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@futsal_id, 'Court 1');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Squash', 'Sports Complex, Level 2', 2,
 'A glass-back squash court for singles play, freshly resurfaced this year.',
 'Four-wall glass-back court built to WSF standard dimensions, with a maple hardwood floor and tempered safety glass rear wall for spectator visibility.',
 'Protective eyewear is strongly recommended for all players.\nNon-marking court shoes are mandatory.\nMaximum of 2 players per booked session (singles play).\nNo food or drinks inside the court.');
SET @squash_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@squash_id, 'Court 1');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Basketball', 'Sports Complex, Level 1', 10,
 'A full indoor basketball court with adjustable hoops, suitable for 5-a-side games or shooting practice.',
 'Maple hardwood sprung flooring with a polyurethane coating for grip and consistent ball bounce. Height-adjustable breakaway rims with tempered glass backboards, and LED lighting rated for indoor ball sports.',
 'Non-marking indoor court shoes are mandatory.\nNo food or drinks on the court.\nMaximum of 10 players per booked session.\nHanging or swinging on the rim is strictly prohibited.');
SET @basketball_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@basketball_id, 'Court 1');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Volleyball', 'Sports Complex, Level 1', 12,
 'An indoor volleyball court with a regulation-height net, suitable for 6-a-side matches.',
 'Sprung wooden flooring with a textured vinyl finish for traction. Net posts are padded aluminium with a crank-adjustable regulation-height net.',
 'Non-marking sports shoes are mandatory.\nMaximum of 12 players per booked session.\nNo food or drinks inside the court.\nPlease reset the net height if you adjusted it.');
SET @volleyball_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@volleyball_id, 'Court 1');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Swimming', 'Sports Complex, Ground Floor', 30,
 'An 8-lane 25m indoor swimming pool with a dedicated shallow end for beginners.',
 'Reinforced concrete shell with a ceramic tile lining, chlorine-filtered and temperature-controlled to 27-29°C. Lane ropes are anti-wave polypropylene.',
 'Proper swimwear is mandatory; no cotton clothing in the pool.\nShower before entering the pool.\nMaximum of 30 swimmers per booked session.\nNo diving in the shallow end.\nChildren under 12 must be accompanied by an adult.');
SET @swimming_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@swimming_id, 'Main Pool');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Gym', 'Sports Complex, Level 3', 20,
 'A fully-equipped gym with free weights, resistance machines and cardio equipment.',
 'Rubberised shock-absorbent flooring throughout. Equipment includes a full free-weight rack, cable machines, and a cardio row with treadmills, ellipticals and stationary bikes.',
 'Proper athletic attire and closed-toe shoes are required.\nWipe down equipment after use.\nRe-rack weights when finished.\nMaximum of 20 members per booked session.\nBring your own towel.');
SET @gym_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@gym_id, 'Main Floor');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Tennis', 'Sports Complex, Outdoor Courts', 4,
 'An outdoor hard-court tennis court with floodlighting for evening play.',
 'Acrylic-coated hard court surface over an asphalt base, with regulation-height net posts and floodlights rated for evening matches.',
 'Non-marking tennis shoes are mandatory.\nMaximum of 4 players per booked session (doubles play).\nBring your own racket and balls.\nPlay may be suspended during heavy rain.');
SET @tennis_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@tennis_id, 'Court 1');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Table Tennis', 'Sports Complex, Level 2', 4,
 'A dedicated indoor room with table tennis tables for casual or competitive play.',
 'Vinyl sports flooring with ITTF-approved table tennis tables and adjustable nets.',
 'Indoor court shoes are recommended.\nMaximum of 4 players per booked session.\nBring your own paddles and balls.\nPlease push tables back against the wall after use.');
SET @tabletennis_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES
(@tabletennis_id, 'Table 1'),
(@tabletennis_id, 'Table 2');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Yoga & Aerobics', 'Sports Complex, Level 3', 15,
 'A mirrored studio for yoga, aerobics and group fitness classes, with mats and equipment on hand.',
 'Sprung floating floor with a cushioned vinyl finish to reduce joint impact, a full-length mirrored wall, and a built-in sound system.',
 'Socks or bare feet only - no outdoor shoes on the studio floor.\nMaximum of 15 participants per booked session.\nMats and blocks are provided; please wipe down and return after use.\nNo food or drinks inside the studio.');
SET @yoga_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@yoga_id, 'Studio 1');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Climbing', 'Sports Complex, Ground Floor', 6,
 'An indoor bouldering and top-rope climbing wall for beginners through advanced climbers.',
 'A 12m plywood climbing wall with resin-textured holds, a shock-absorbent bouldering mat base, and auto-belay top-rope stations.',
 'Climbing shoes and a harness are mandatory for top-rope routes.\nMaximum of 6 climbers per booked session.\nA spotter is required for all bouldering attempts.\nNo climbing above marked height limits without top-rope gear.');
SET @climbing_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@climbing_id, 'Wall 1');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Football', 'Sports Complex, Outdoor Grounds', 22,
 'A full-size outdoor football field with natural turf, suitable for 11-a-side matches.',
 'Natural turf pitch over a sand-based drainage system, with regulation-size goals and perimeter fencing.',
 'Studded football boots are recommended on grass.\nMaximum of 22 players per booked session (11-a-side).\nNo glass containers on the field.\nField may be closed after heavy rain to protect the turf.');
SET @football_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES (@football_id, 'Field 1');

INSERT INTO facilities (name, location, capacity, description, materials, rules) VALUES
('Bowling', 'Sports Complex, Level 1', 8,
 'A 4-lane indoor bowling alley with automatic scoring and pin reset.',
 'Synthetic wood-grain lane surfaces with automatic pinsetters and electronic scoring displays.',
 'Bowling shoes are mandatory and available for rental at the counter.\nMaximum of 8 bowlers per booked session (2 per lane).\nNo food or drinks on the lane approach.\nPlease keep hands dry - no unauthorised substances on the ball.');
SET @bowling_id = LAST_INSERT_ID();
INSERT INTO courts (facility_id, name) VALUES
(@bowling_id, 'Lane 1'),
(@bowling_id, 'Lane 2'),
(@bowling_id, 'Lane 3'),
(@bowling_id, 'Lane 4');

UPDATE facilities SET image_url = '/uploads/sample-badminton-photo.jpg' WHERE name = 'Badminton';
UPDATE facilities SET image_url = '/uploads/sample-futsal.jpg' WHERE name = 'Futsal';
UPDATE facilities SET image_url = '/uploads/sample-squash.jpg' WHERE name = 'Squash';
UPDATE facilities SET image_url = '/uploads/sample-basketball.jpg' WHERE name = 'Basketball';
UPDATE facilities SET image_url = '/uploads/sample-volleyball.jpg' WHERE name = 'Volleyball';
UPDATE facilities SET image_url = '/uploads/sample-swimming.jpg' WHERE name = 'Swimming';
UPDATE facilities SET image_url = '/uploads/sample-gym.jpg' WHERE name = 'Gym';
UPDATE facilities SET image_url = '/uploads/sample-tennis.jpg' WHERE name = 'Tennis';
UPDATE facilities SET image_url = '/uploads/sample-tabletennis.jpg' WHERE name = 'Table Tennis';
UPDATE facilities SET image_url = '/uploads/sample-yoga.jpg' WHERE name = 'Yoga & Aerobics';
UPDATE facilities SET image_url = '/uploads/sample-climbing.jpg' WHERE name = 'Climbing';
UPDATE facilities SET image_url = '/uploads/sample-football.jpg' WHERE name = 'Football';
UPDATE facilities SET image_url = '/uploads/sample-bowling.jpg' WHERE name = 'Bowling';

CREATE TABLE time_slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(20) NOT NULL,
  sort_order INT NOT NULL
);

INSERT INTO time_slots (label, sort_order) VALUES
('08:00 - 09:00', 1),
('09:00 - 10:00', 2),
('10:00 - 11:00', 3),
('11:00 - 12:00', 4),
('14:00 - 15:00', 5),
('15:00 - 16:00', 6),
('16:00 - 17:00', 7),
('17:00 - 18:00', 8),
('18:00 - 19:00', 9),
('19:00 - 20:00', 10),
('20:00 - 21:00', 11);

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  court_id INT NOT NULL,
  time_slot_id INT NOT NULL,
  booking_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_slot (court_id, booking_date, time_slot_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (court_id) REFERENCES courts(id),
  FOREIGN KEY (time_slot_id) REFERENCES time_slots(id)
);

-- time_slot_id = NULL means the court is closed for the whole day
CREATE TABLE closures (
  id INT AUTO_INCREMENT PRIMARY KEY,
  court_id INT NOT NULL,
  closure_date DATE NOT NULL,
  time_slot_id INT NULL,
  reason VARCHAR(150) NOT NULL,
  FOREIGN KEY (court_id) REFERENCES courts(id),
  FOREIGN KEY (time_slot_id) REFERENCES time_slots(id)
);

-- In-app notices shown to a user on their next visit, e.g. when an admin
-- closure cancels one of their existing bookings. No email/SMS involved.
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(500) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  facility_id INT NOT NULL,
  comment TEXT NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

-- Public contact form submissions - no login required to send one
CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PHP sessions are stored here instead of on local disk, so that any EC2
-- instance behind an ALB/ASG can read a session written by a different
-- instance. See auth.php's DbSessionHandler.
CREATE TABLE sessions (
  id VARCHAR(128) PRIMARY KEY,
  data MEDIUMTEXT NOT NULL,
  last_activity INT NOT NULL,
  INDEX idx_last_activity (last_activity)
);
