-- 001: เพิ่มคอลัมน์ updated_at ในตาราง payments
-- ใช้กับฐานข้อมูลเดิมที่สร้างจาก Structure.sql รุ่นเก่า (ที่ตาราง payments ยังไม่มีคอลัมน์นี้)
-- โค้ดมีคำสั่ง UPDATE payments ... updated_at = NOW() จึงต้องมีคอลัมน์นี้
-- ฐานข้อมูลใหม่ที่สร้างจาก schema.sql ไม่ต้องรันไฟล์นี้

ALTER TABLE payments
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
