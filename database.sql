 -- ສ້າງຖານຂໍ້ມູນ
CREATE DATABASE IF NOT EXISTS hoteloudomsup CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE hoteloudomsup;

-- ຕາຕະລາງ customers
CREATE TABLE customers (
    Cus_id INT(10) AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(50) NOT NULL,
    Name VARCHAR(50) NOT NULL,
    Lastname VARCHAR(50) NOT NULL,
    Phone VARCHAR(20) NOT NULL,
    Identity_card_number VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    INDEX (Email),
    INDEX (Phone),
    INDEX (Identity_card_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ຕາຕະລາງ room
CREATE TABLE room (
    Room_id INT(10) AUTO_INCREMENT PRIMARY KEY,
    Room_type VARCHAR(50) NOT NULL,
    Price FLOAT NOT NULL,
    Room_detail TEXT,
    images VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ຕາຕະລາງ bookings
CREATE TABLE bookings (
    booking_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    date DATE DEFAULT CURRENT_DATE,
    booking_start DATE NOT NULL,
    booking_end DATE NOT NULL,
    Total_price FLOAT NOT NULL,
    customer_id INT(10) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (customer_id) REFERENCES customers(Cus_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ຕາຕະລາງ booking_details
CREATE TABLE booking_details (
    No INT(11) AUTO_INCREMENT PRIMARY KEY,
    Booking_id INT(11) NOT NULL,
    Room_id INT(11) NOT NULL,
    Room_price FLOAT NOT NULL,
    FOREIGN KEY (Booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (Room_id) REFERENCES room(Room_id),
    INDEX (Booking_id),
    INDEX (Room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ຕາຕະລາງ payment
CREATE TABLE payment (
    No INT(11) AUTO_INCREMENT PRIMARY KEY,
    Booking_id INT(11) NOT NULL,
    Payment FLOAT NOT NULL,
    Image_qr VARCHAR(20),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    FOREIGN KEY (Booking_id) REFERENCES bookings(booking_id),
    INDEX (Booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ຕາຕະລາງ Admin
CREATE TABLE Admin (
    Id INT(10) AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(50) NOT NULL,
    Lastname VARCHAR(50) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Role ENUM('superadmin', 'admin', 'moderator') DEFAULT 'admin',
    INDEX (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ເພີ່ມຂໍ້ມູນຫ້ອງພັກ
INSERT INTO room (Room_type, Price, Room_detail) VALUES
('ຫ້ອງຕຽງດ່ຽວ', 350000, 'ຫ້ອງຕຽງດ່ຽວທີ່ສະອາດ ມີອຸປະກອນຄົບຖ້ວນ'),
('ຫ້ອງຕຽງຄູ່', 450000, 'ຫ້ອງຕຽງຄູ່ທີ່ເໝາະສຳລັບຄູ່ຮັກ'),
('ຫ້ອງຄອບຄົວ', 550000, 'ຫ້ອງຄອບຄົວທີ່ກວ້າງຂວາງ ເໝາະສຳລັບຄອບຄົວ'),
('ຫ້ອງ VIP', 650000, 'ຫ້ອງ VIP ທີ່ຫຼູຫຼາ ມີບໍລິການພິເສດ');

-- ເພີ່ມ admin ເລີ່ມຕົ້ນ
INSERT INTO Admin (Name, Lastname, Email, Password, Role) VALUES
('ອຸດົມ', 'ຊັບ', 'admin@hoteloudomsup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin'); 