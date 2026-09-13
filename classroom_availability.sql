-- =========================================================
-- ClassTrack — Classroom Availability Management System
-- Database Schema + Sample Data
-- Import this file using phpMyAdmin (XAMPP) or the mysql CLI
-- =========================================================

CREATE DATABASE IF NOT EXISTS classroom_availability_db;
USE classroom_availability_db;

-- ---------------------------------------------------------
-- Table: users
-- Stores registered user accounts (students, teachers, staff)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,   -- used to log in (no email required)
    password VARCHAR(255) NOT NULL,   -- stored as a bcrypt hash, never plain text
    department VARCHAR(100) DEFAULT NULL,   -- e.g. "BSIT Department" (editable on Profile page)
    contact_number VARCHAR(30) DEFAULT NULL, -- editable on Profile page
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: classrooms
-- Stores classroom details and current availability status
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type VARCHAR(50) NOT NULL,       -- e.g. Lecture Hall, Laboratory, Seminar Room
    building VARCHAR(50) NOT NULL,        -- e.g. Main Building, Science Building
    floor VARCHAR(20) NOT NULL,           -- e.g. 1st Floor, 2nd Floor
    capacity INT NOT NULL,
    equipment VARCHAR(255) DEFAULT NULL,  -- e.g. Projector, Whiteboard, Air Conditioning
    status ENUM('available','unavailable') NOT NULL DEFAULT 'available',
    occupied_by INT DEFAULT NULL,         -- users.id of the instructor currently using this room (NULL = nobody / admin-marked unavailable)
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Sample classroom data so the site isn't empty on first run
-- ---------------------------------------------------------
INSERT INTO classrooms (room_number, room_type, building, floor, capacity, equipment, status, description) VALUES
('105', 'Lecture Hall',       'Acad Building',  '1st Floor',50, 'Projector, Whiteboard, Air Conditioning', 'available',   'Standard lecture room for general classes.'),
('107', 'Lecture Hall',       'Acad Building',  '1st Floor',50, 'Projector, Whiteboard, Air Conditioning', 'unavailable', 'Standard lecture room for general classes.'),
('202', 'Lecture Hall',       'Acad Building',  '2nd Floor',50, 'Whiteboard, Smart TV',  'available',   'Standard lecture room for general classes.'),
('306', 'Lecture Hall',         'Acad Building',    '3rd Floor', 50, 'Whiteboard, Smart TV',                    'available',   'Standard lecture room for general classes.'),
('203', 'Lecture Hall',         'Acad Building',    '2nd Floor', 50, 'Whiteboard, Smart TV',                    'unavailable', 'Standard lecture room for general classes.'),
('104', 'Computer Laboratory',  'Comlab Building', 'Ground Floor', 25, '35 Computers, Smart TV, Air Conditioning','available',  'Fully equipped computer lab for programming classes.'),
('103', 'Computer Laboratory',  'Comlab Building', 'Ground Floor', 25, '35 Computers, Smart TV, Air Conditioning','unavailable','Fully equipped computer lab for programming classes.'),
('102', 'Computer Laboratory',  'Comlab Building', 'Ground Floor', 25, 'Computers, Smart TV, Air Conditioning', 'available',  'Fully equipped computer lab for programming classes.'),
('101', 'Computer Laboratory',  'Comlab Building',    'Ground Floor', 25, 'Computers, Air Conditioning',       'available',  'Fully equipped computer lab for programming classes.'),
('106', 'Computer Laboratory',   'Comlab Building',    'Ground Floor', 25, 'Computers, Smart TV, Air Conditioning',       'unavailable','Fully equipped computer lab for programming classes.');

-- ---------------------------------------------------------
-- Table: subjects
-- Subjects/courses taught by an instructor.
-- instructor_id matches a row in `users` (the logged-in instructor).
-- Kept as a plain column (no FOREIGN KEY) so this sample data can be
-- imported before any account exists yet — see note below.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    units INT NOT NULL DEFAULT 3,
    instructor_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: class_schedules
-- One row per weekly class meeting (subject + room + day + time).
-- subject_id matches subjects.id, classroom_id matches classrooms.id.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS class_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    classroom_id INT NOT NULL,
    section VARCHAR(50) DEFAULT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Sample subjects & schedule
-- IMPORTANT: instructor_id = 1 below assumes the FIRST account you
-- register on this site gets id 1 (true on a fresh database — MySQL
-- starts AUTO_INCREMENT at 1). Register your instructor account
-- first, then log in, and this sample schedule will appear under
-- the Schedule section. If you register a second account, it won't
-- have any subjects until you add rows for its user id.
--
-- NOTE: as of this version, register.php automatically inserts 4
-- default subjects for every NEW instructor account the moment they
-- register (see the DEFAULT_SUBJECTS list in register.php). The rows
-- below are only the pre-seeded demo data for instructor_id = 1.
-- ---------------------------------------------------------
INSERT INTO subjects (subject_code, subject_name, units, instructor_id) VALUES
('STAT',    'Probability and Statistics',         3, 1),
('ITE 110', 'Web Systems and Technologies',       3, 1),
('ETH',     'ETHICS',                             3, 1),
('ITE 14',  'Data Structures and Algorithms',     3, 1),
('IT 105',  'Networking 2',                       3, 1),
('CSC 104', 'Object-Oriented Programming',        3, 1),
('WC',      'Web Workpalce Communication',        3, 1);

-- classroom_id references: 1 = Room 101, 3 = Room 201, 4 = Room 202, 6 = Room 301 (Computer Lab)
INSERT INTO class_schedules (subject_id, classroom_id, section, day_of_week, start_time, end_time) VALUES
(1, 1, 'BSIT-1A', 'Monday',    '08:00:00', '09:30:00'),
(1, 1, 'BSIT-2B', 'Wednesday', '08:00:00', '09:30:00'),
(2, 3, 'BSIT-2B', 'Tuesday',   '09:30:00', '11:00:00'),
(2, 3, 'BSIT-3C', 'Thursday',  '09:30:00', '11:00:00'),
(3, 4, 'BSIT-1A', 'Monday',    '13:00:00', '14:30:00'),
(4, 6, 'BSIT-3C', 'Tuesday',   '13:00:00', '15:00:00'),
(5, 6, 'BSIT-3C', 'Friday',    '08:00:00', '10:00:00'),
(6, 6, 'BSIT-1A', 'Thursday',  '13:00:00', '14:30:00');
