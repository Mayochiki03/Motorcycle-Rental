-- ตัวอย่างคำสั่ง SQL ที่ใช้ดูข้อมูล (ไว้ใช้เองตอนตรวจสอบ ระบบไม่ได้เรียกไฟล์นี้)

-- Owner ดูพนักงานทั้งหมด
SELECT e.*, o.first_name as owner_name 
FROM employees e 
JOIN owners o ON e.owner_id = o.owner_id 
WHERE e.owner_id = 'OWN001';

-- Owner สร้างพนักงานใหม่
INSERT INTO employees (employee_id, owner_id, email, password_hash, first_name, last_name, phone, position) 
VALUES ('EMP004', 'OWN001', 'newemployee@tempstation.com', '$2b$10$newhash', 'ใหม่', 'สมาย', '0855555555', 'staff');

-- ดูการจองทั้งหมดพร้อมข้อมูลลูกค้าและรถ
SELECT 
    r.reservation_id,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    m.brand, m.model, m.license_plate,
    r.start_datetime, r.end_datetime,
    r.total_price, r.final_price,
    r.status,
    CASE 
        WHEN r.employee_id IS NULL THEN 'Online Booking'
        ELSE CONCAT(e.first_name, ' ', e.last_name)
    END as handled_by
FROM reservations r
JOIN customers c ON r.customer_id = c.customer_id
JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
LEFT JOIN employees e ON r.employee_id = e.employee_id
ORDER BY r.created_at DESC;