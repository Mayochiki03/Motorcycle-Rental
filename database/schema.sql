-- ฐานข้อมูลระบบเช่ารถจักรยานยนต์ (MySQL 8 / MariaDB 10.6 ขึ้นไป)
-- โครงสร้างตาราง: เรียกใช้ไฟล์นี้ก่อน แล้วค่อยใช้ seed.sql (ข้อมูลตัวอย่าง ไม่บังคับ)

CREATE DATABASE IF NOT EXISTS motorcycle_rental_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE motorcycle_rental_new;

-- 1. ตารางเจ้าของร้าน
CREATE TABLE owners (
    owner_id VARCHAR(50) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    phone VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. ตารางพนักงาน
CREATE TABLE employees (
    employee_id VARCHAR(50) PRIMARY KEY,
    owner_id VARCHAR(50) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    phone VARCHAR(255) NOT NULL,
    position VARCHAR(255) DEFAULT 'staff',
    is_active TINYINT(1) DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES owners(owner_id) ON DELETE CASCADE
);

-- 3. ตารางลูกค้า
CREATE TABLE customers (
    customer_id VARCHAR(50) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    phone VARCHAR(255) NOT NULL,
    line_id VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. ตารางรถจักรยานยนต์
CREATE TABLE motorcycles (
    motorcycle_id VARCHAR(50) PRIMARY KEY,
    brand VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    year INT,
    license_plate VARCHAR(255) UNIQUE NOT NULL,
    color VARCHAR(255),
    engine_cc INT,
    price_per_day DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_available TINYINT(1) DEFAULT TRUE,
    description TEXT,
    maintenance_status VARCHAR(20) DEFAULT 'ready',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. ตารางการจอง
CREATE TABLE reservations (
    reservation_id VARCHAR(50) PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    employee_id VARCHAR(50),
    motorcycle_id VARCHAR(50) NOT NULL,
    -- วัน + เวลา รับรถ / คืนรถ
    start_datetime DATETIME NOT NULL,
    end_datetime   DATETIME NOT NULL,
    total_days INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    deposit_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    final_price DECIMAL(10,2) NOT NULL,
    -- จุดรับรถ / คืนรถ (ให้พิมพ์อิสระ)
    pickup_location VARCHAR(255) NOT NULL,
    return_location VARCHAR(255) NOT NULL,
    -- รายละเอียดเพิ่มเติม การรับ-คืนรถ
    pickup_details TEXT,
    return_details TEXT,
    return_checked_at DATETIME NULL,
    return_checked_by VARCHAR(50) NULL,
    return_condition TEXT NULL,
    return_motorcycle_status VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (motorcycle_id) REFERENCES motorcycles(motorcycle_id) ON DELETE CASCADE
);


-- 6. ตารางการชำระเงิน
CREATE TABLE payments (
    payment_id VARCHAR(255) PRIMARY KEY,
    reservation_id VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_date TIMESTAMP NULL,
    transaction_id VARCHAR(255),
    slip_image_url VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE
);

-- 7. ตารางส่วนลด
CREATE TABLE discounts (
    discount_id VARCHAR(50) PRIMARY KEY,
    discount_code VARCHAR(255) UNIQUE NOT NULL,
    discount_type VARCHAR(20) NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_rental_days INT DEFAULT 1,
    max_discount_amount DECIMAL(10,2),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT TRUE,
    created_by VARCHAR(50) NOT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES owners(owner_id)
);

-- ตารางสัมพันธ์ระหว่างส่วนลดและการจอง
CREATE TABLE reservation_discounts (
    reservation_id VARCHAR(50),
    discount_id VARCHAR(50),
    applied_amount DECIMAL(10,2) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reservation_id, discount_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (discount_id) REFERENCES discounts(discount_id) ON DELETE CASCADE
);
