-- ============================================================
--  SMART EVENT PORTAL  -  database  (MySQL / MariaDB / XAMPP)
--  phpMyAdmin > SQL tab > paste all of this > Go
--  Creates a NEW database, so your old "smarteventdb" is untouched.
-- ============================================================
CREATE DATABASE IF NOT EXISTS smart_event_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_event_portal;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(120) NOT NULL,
  username    VARCHAR(60)  NOT NULL UNIQUE,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','user') NOT NULL DEFAULT 'user',
  status      ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE events (
  event_id    INT AUTO_INCREMENT PRIMARY KEY,
  event_name  VARCHAR(150) NOT NULL,
  description TEXT,
  event_date  DATE NOT NULL,
  event_time  TIME NOT NULL,
  location    VARCHAR(200) NOT NULL,
  capacity    INT NOT NULL DEFAULT 100,
  price       DECIMAL(10,2) NOT NULL DEFAULT 0,
  vip_price   DECIMAL(10,2) NOT NULL DEFAULT 0,
  status      ENUM('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  created_by  INT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE registrations (
  registration_id     INT AUTO_INCREMENT PRIMARY KEY,
  event_id            INT NOT NULL,
  user_id             INT NULL,
  attendee_name       VARCHAR(120) NOT NULL,
  email               VARCHAR(150) NOT NULL,
  phone               VARCHAR(40),
  ticket_type         ENUM('General','VIP') NOT NULL DEFAULT 'General',
  ticket_code         VARCHAR(30) NOT NULL UNIQUE,
  amount              DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_status      ENUM('Unpaid','Pending','Paid','Free','Refunded') NOT NULL DEFAULT 'Unpaid',
  registration_status ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  attendance_status   ENUM('Not Attended','Attended') NOT NULL DEFAULT 'Not Attended',
  checked_in_at       DATETIME NULL,
  registration_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE SET NULL,
  INDEX (event_id, registration_status)
) ENGINE=InnoDB;

CREATE TABLE payments (
  payment_id      INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NOT NULL,
  amount          DECIMAL(10,2) NOT NULL,
  method          ENUM('GCash','Maya','Bank Transfer','Cash at venue','Cash') NOT NULL,
  reference_no    VARCHAR(80),
  status          ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  verified_by     INT NULL,
  verified_at     DATETIME NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (registration_id) REFERENCES registrations(registration_id) ON DELETE CASCADE,
  FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------- sample data ----------------
-- Passwords:  admin / admin123    mary / user123    juan / user123
INSERT INTO users (full_name, username, email, password, role) VALUES
('System Administrator','admin','admin@smartevents.local','$2b$12$ltEeax0hRicXCVxwmhZD.eUcFrMc7R7Q5qj4wvHHYsWmiRtMTmtna','admin'),
('Mary Ann Bayawa','mary','mary@example.com','$2b$12$RmNHE/MhcsoZBTSm6GA/9.4cFkyQNlQUbjJeFlx0Eeu/qvzNSVakS','user'),
('Juan Dela Cruz','juan','juan@example.com','$2b$12$RmNHE/MhcsoZBTSm6GA/9.4cFkyQNlQUbjJeFlx0Eeu/qvzNSVakS','user');

INSERT INTO events (event_name, description, event_date, event_time, location, capacity, price, vip_price, status, created_by) VALUES
('Community Health Fair','Free check-ups, vaccinations and wellness talks for the whole barangay.',DATE_ADD(CURDATE(), INTERVAL 10 DAY),'09:00:00','Davao City Hall Grounds',200,0,0,'Upcoming',1),
('Tech Meetup Davao','Talks and networking for developers, designers and founders.',DATE_ADD(CURDATE(), INTERVAL 21 DAY),'14:00:00','SMX Convention Center, Davao',120,150,500,'Upcoming',1),
('Regional Business Summit','Keynotes and workshops for local entrepreneurs.',DATE_ADD(CURDATE(), INTERVAL 35 DAY),'08:30:00','Marco Polo Hotel Davao',80,500,1500,'Upcoming',1),
('Barangay Sports Fest','Basketball, volleyball and fun runs. Limited slots.',DATE_ADD(CURDATE(), INTERVAL 3 DAY),'07:00:00','Barangay Covered Court',4,100,0,'Upcoming',1),
('Year-End Concert','A night of live music from local bands.',DATE_SUB(CURDATE(), INTERVAL 20 DAY),'19:00:00','People''s Park Amphitheater',300,300,800,'Completed',1);

INSERT INTO registrations (event_id,user_id,attendee_name,email,phone,ticket_type,ticket_code,amount,payment_status,registration_status,attendance_status,checked_in_at) VALUES
(1,2,'Mary Ann Bayawa','mary@example.com','0917-111-2222','General','EVT-HEALTH0001',0,'Free','Approved','Not Attended',NULL),
(2,2,'Mary Ann Bayawa','mary@example.com','0917-111-2222','General','EVT-TECH000002',150,'Pending','Pending','Not Attended',NULL),
(4,3,'Juan Dela Cruz','juan@example.com','0918-333-4444','General','EVT-SPORT00003',100,'Paid','Approved','Not Attended',NULL),
(4,NULL,'Walk-in Player 1','p1@example.com','','General','EVT-SPORT00004',100,'Paid','Approved','Not Attended',NULL),
(4,NULL,'Walk-in Player 2','p2@example.com','','General','EVT-SPORT00005',100,'Paid','Approved','Not Attended',NULL),
(4,NULL,'Walk-in Player 3','p3@example.com','','General','EVT-SPORT00006',100,'Paid','Approved','Not Attended',NULL),
(5,2,'Mary Ann Bayawa','mary@example.com','0917-111-2222','General','EVT-CONCERT007',300,'Paid','Approved','Attended',DATE_SUB(NOW(), INTERVAL 20 DAY)),
(5,3,'Juan Dela Cruz','juan@example.com','0918-333-4444','VIP','EVT-CONCERT008',800,'Paid','Approved','Attended',DATE_SUB(NOW(), INTERVAL 20 DAY)),
(5,NULL,'Walk-in Guest','guest@example.com','','General','EVT-CONCERT009',300,'Paid','Approved','Not Attended',NULL),
(3,2,'Mary Ann Bayawa','mary@example.com','0917-111-2222','General','EVT-SUMMIT00010',500,'Unpaid','Rejected','Not Attended',NULL);

INSERT INTO payments (registration_id,amount,method,reference_no,status,verified_by,verified_at) VALUES
(2,150,'GCash','GC-77120345','Pending',NULL,NULL),
(3,100,'GCash','GC-55012233','Verified',1,NOW()),
(4,100,'Cash','Paid at desk','Verified',1,NOW()),
(5,100,'Cash','Paid at desk','Verified',1,NOW()),
(6,100,'Cash','Paid at desk','Verified',1,NOW()),
(7,300,'Maya','MY-2201987','Verified',1,NOW()),
(8,800,'GCash','GC-91820011','Verified',1,NOW()),
(9,300,'Cash','Paid at desk','Verified',1,NOW());
