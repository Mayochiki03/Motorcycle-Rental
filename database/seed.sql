-- ข้อมูลตัวอย่างสำหรับทดลองระบบ (ไม่บังคับ) ใช้หลังจากสร้างตารางด้วย schema.sql แล้ว
--
-- หมายเหตุ: บัญชีผู้ใช้ในไฟล์นี้ "ยังล็อกอินไม่ได้" โดยตั้งใจ (รหัสผ่านเป็นค่าที่ใช้ไม่ได้)
-- ต้องตั้งรหัสผ่านเองด้วยสคริปต์ก่อนใช้งาน เช่น
--     php bin/account.php reset-password sompong@tempstation.com
-- หรือสร้างเจ้าของร้านใหม่:  php bin/account.php create-owner

USE motorcycle_rental_new;

-- =========================
-- 1. owners
-- =========================
INSERT INTO owners (owner_id, email, password_hash, first_name, last_name, phone) VALUES
('owner001', 'sompong@tempstation.com', '!unset-run-bin-account-php', 'สมพงษ์', 'ใจดี', '0812345678'),
('owner002', 'siriporn@tempstation.com', '!unset-run-bin-account-php', 'ศิริพร', 'สุขสวัสดิ์', '0898765432');

-- =========================
-- 2. employees
-- =========================
INSERT INTO employees (employee_id, owner_id, email, password_hash, first_name, last_name, phone, position, is_active) VALUES
('emp001', 'owner001', 'somsak@tempstation.com', '!unset-run-bin-account-php', 'สมศักดิ์', 'ทำงานดี', '0823456789', 'manager', 1),
('emp002', 'owner001', 'wanida@tempstation.com', '!unset-run-bin-account-php', 'วนิดา', 'บริการดี', '0834567890', 'staff', 1),
('emp003', 'owner002', 'preecha@tempstation.com', '!unset-run-bin-account-php', 'ปรีชา', 'รวดเร็ว', '0845678901', 'manager', 1);

-- =========================
-- 3. customers (NEW STRUCTURE)
-- =========================
INSERT INTO customers (customer_id, email, password_hash, first_name, last_name, phone, line_id, is_active) VALUES
('cust001', 'john.doe@email.com', '!unset-run-bin-account-php', 'John', 'Doe', '0856789012', 'line_id_001', 1),
('cust002', 'jane.smith@email.com', '!unset-run-bin-account-php', 'Jane', 'Smith', '0867890123', 'line_id_002', 1),
('cust003', 'surasak@email.com', '!unset-run-bin-account-php', 'สุรศักดิ์', 'เดินทาง', '0878901234', 'line_id_003', 1),
('cust004', 'testuser@email.com', '!unset-run-bin-account-php', 'ทดสอบ', 'ระบบ', '0812345678', 'line_id_004', 1);

INSERT INTO motorcycles
(motorcycle_id,brand,model,year,license_plate,color,engine_cc,price_per_day,image_url,is_available,description,maintenance_status)
VALUES
('MOTO001','Honda','Super Cub',2023,'กข1234','เขียว',110,350.00,'https://www.thaihonda.co.th/honda/uploads/cache/926/photos/shares/newsupercub/ThaiHonda_Supercub2023_BikeGallery-Green-926x518.jpg',1,'รถคลาสสิก สไตล์วินเทจ เหมาะกับการใช้งานในเมือง','ready'),
('MOTO002','Honda','Wave 110i',2023,'กข1235','แดง',110,300.00,'https://www.thaihonda.co.th/honda/uploads/product_image_slide/photos/shares/10ThHonda_New-Wave_110i_2023_Wing-Center_Thumbnail_Product_430x310.png',1,'รถครอบจักรยานยนต์เศรษฐกิจ ประหยัดน้ำมัน','ready'),
('MOTO003','Honda','Scoopy i',2022,'กข1236','ขาว',110,320.00,'https://www.thaihonda.co.th/honda/uploads/product_image_slide/photos/shares/Website_Product_Photo-Thumb_nail_Product_430x310.png',1,'รถสกูตเตอร์สไตล์น่ารัก สำหรับผู้หญิง','ready'),
('MOTO004','Honda','Giorno',2024,'กข1237','เหลือง',125,380.00,'https://www.thaihonda.co.th/honda/uploads/cache/926/photos/shares/giono24/galgiorno24/Honda_Giorno_Bike_Gallery926x518_yellow_copy.jpg',1,'รถสกูตเตอร์พรีเมียม สไตล์โมเดิร์น','ready'),
('MOTO005','Honda','Wave 125i',2024,'กข1238','แดง',125,330.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/2024_Wave125i/Color/ThaiHonda_Wave125i_2024_ColorChart-685x426-red-wheel.png',1,'รถครอบจักรยานยนต์ 125cc ประสิทธิภาพสูง','ready'),
('MOTO006','Honda','Click 125i',2022,'กข1239','ฟ้า',125,400.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/AllNewClick125-2022/Colorselection/Click125-WebsiteProductPhoto-ColorSection-Urban-B-685x426.png',1,'รถสกูตเตอร์อัตโนมัติ สมรรถนะดี','ready'),
('MOTO007','Honda','Lead 125',2023,'กข1240','ดำ',125,420.00,'https://www.thaihonda.co.th/honda/uploads/cache/1440/photos/shares/Lead125/LO_LEAD125Black_Product_Photo_Thumbnail_Product.jpg',1,'รถสกูตเตอร์พรีเมียม ขนาดกะทัดรัด','ready'),
('MOTO008','Honda','PCX160',2023,'กข1241','ดำ',160,650.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/new-pcx-160-2023/7/png/02-G-B-__________-_______.png',1,'รถสกูตเตอร์อัตโนมัติ สภาพดีมาก','ready'),
('MOTO009','Honda','Click 160i',2023,'กข1242','ขาว',160,580.00,'https://www.thaihonda.co.th/honda/uploads/cache/1370/photos/shares/NewClick160-2023/Model/Honda_Click160-color_section685x426-whiteABS.png',1,'รถสกูตเตอร์สมรรถนะสูง 160cc','ready'),
('MOTO010','Honda','ADV160',2022,'กข1243','ดำ',160,750.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/NewADV160-2022/Carcolor/02-Black.png',1,'รถสกูตเตอร์ผจญภัย ออฟโรด','ready'),
('MOTO011','Honda','Forza 350',2024,'กข1244','ขาว',350,1200.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/24NewForza350/Color_Chart_W685xH426_PX.jpg',1,'รถสกูตเตอร์ทัวริงขนาดใหญ่ สบายสบาย','ready'),
('MOTO012','Honda','ADV350',2023,'กข1245','แดง',350,1300.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/ADV350RoadSync/Color_Chart_W685xH426_PX_RED.png',1,'รถสกูตเตอร์ผจญภัยขนาดใหญ่','ready'),
('MOTO013','Yamaha','Fazzio',2025,'กค1234','ขาว',125,450.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/fazzio-2025/lineup-360-retro-white/14.png?sfvrsn=6513ab43_2',1,'รถสกูตเตอร์รีโทร สไตล์คลาสสิก','ready'),
('MOTO014','Yamaha','Finn',2024,'กค1235','เขียว',125,380.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2024/finn-2024/lineup-360-green-ubs/2.png?sfvrsn=37bb5c14_2',1,'รถสกูตเตอร์เศรษฐกิจ ใช้งานง่าย','ready'),
('MOTO015','Yamaha','Grand Filano',2024,'กค1236','เทา',125,480.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2024/grand-filano-hybrid-connected-2024/lineup-360-titanium-gray/2.png?sfvrsn=d33825a6_2',1,'รถสกูตเตอร์ไฮบริดพรีเมียม','ready'),
('MOTO016','Yamaha','Aerox',2025,'กค1237','เงิน',155,700.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/all-new-aerox-2025/lineup-360-silver-star/2.png?sfvrsn=ba06ecb7_2',1,'รถสกูตเตอร์สปอร์ต สไตล์สปอร์ต','ready'),
('MOTO017','Yamaha','NMAX',2025,'กค1238','ดำ',155,750.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/all-new-nmax-2025/lineup-360-magma-black/2.png?sfvrsn=f4ef4f1a_2',1,'รถสกูตเตอร์พรีเมี่ยม','ready'),
('MOTO018','Yamaha','XMAX',2025,'กค1239','เทา',250,1500.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/xmax-2025/lineup-360-dark-gray/2.png?sfvrsn=e29fd942_2',1,'รถสกูตเตอร์ทัวริงขนาดใหญ่ สบายสุด','ready'),
('MOTO019','Kawasaki','Ninja 300',2021,'กจ3456','เขียว',300,1200.00,'../img/default-bike.jpg',0,'รถสปอร์ตขนาดใหญ่ กำลังซ่อมบำรุง','maintenance'),
('MOTO020','Kawasaki','Z650',2022,'กจ3457','ดำ',650,1800.00,'../img/default-bike.jpg',1,'รถเนคคิดสตรีทฟิเกอร์ สไตล์ล้ำสมัย','ready'),
('MOTO021','Kawasaki','Versys 650',2023,'กจ3458','ส้ม',650,1600.00,'../img/default-bike.jpg',1,'รถผจญภัยอเนกประสงค์','ready'),
('MOTO022','Honda','CBR150R',2022,'กง9012','แดง',150,800.00,'../img/default-bike.jpg',1,'รถสปอร์ต สภาพดี','ready'),
('MOTO023','Yamaha','MT-15',2023,'กค1240','น้ำเงิน',150,850.00,'../img/default-bike.jpg',1,'รถเนคคิดสปอร์ต ขนาดกะทัดรัด','ready'),
('MOTO024','Kawasaki','Ninja 250',2022,'กจ3459','เขียว',250,1000.00,'../img/default-bike.jpg',1,'รถสปอร์ตสำหรับเริ่มต้น','ready'),
('MOTO025','Yamaha','NMAX',2024,'กค1241','ดำ',155,750.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/xmax-2025/lineup-360-dark-gray/2.png?sfvrsn=e29fd942_2',1,'รถสกูตเตอร์พรีเมียม','ready');


-- =========================
-- 5. reservations (NEW + DETAILS)
-- =========================
INSERT INTO reservations
(reservation_id, customer_id, employee_id, motorcycle_id,
 start_datetime, end_datetime, total_days, total_price,
 status, deposit_amount, discount_amount, final_price,
 pickup_location, return_location, pickup_details, return_details)
VALUES
('res001','cust001','emp001','MOTO008',
 '2024-01-15 10:00:00','2024-01-17 18:00:00',3,1950,
 'completed',1000,0,1950,
 'สาขาหลัก','สาขาหลัก',
 'รับรถพร้อมหมวกกันน็อค 2 ใบ น้ำมันเต็มถัง',
 'คืนรถสภาพสมบูรณ์ ไม่มีรอย'),

('res002','cust002',NULL,'MOTO009',
 '2024-02-01 09:00:00','2024-02-03 18:00:00',3,1740,
 'confirmed',800,100,1640,
 'สาขาหลัก','สาขาหลัก',
 'รับรถช่วงเช้า พนักงานอธิบายการใช้งาน',
 'คืนรถตรงเวลา'),

('res003','cust003',NULL,'MOTO010',
 '2024-03-10 12:00:00','2024-03-12 12:00:00',2,1500,
 'pending',500,0,1500,
 'สาขารัชดา','สาขารัชดา',
 'รับรถช่วงเที่ยง',
 'รอตรวจสภาพ');

-- =========================
-- 6. payments
-- =========================
INSERT INTO payments
(payment_id, reservation_id, amount, payment_method, payment_status,
 payment_date, transaction_id, slip_image_url, notes)
VALUES
('pay001','res001',1950,'cash','verified','2024-01-14 10:30:00','CASH001',NULL,'ชำระเงินสดหน้าร้าน'),
('pay002','res002',1640,'PROMPTPAY','verified','2024-01-31 20:10:00','BANK002',NULL,'โอนผ่านแอป'),
('pay003','res003',1500,'PROMPTPAY','pending',NULL,NULL,NULL,'รอการชำระ');

-- =========================
-- 7. discounts
-- =========================
INSERT INTO discounts
(discount_id, discount_code, discount_type, discount_value,
 min_rental_days, max_discount_amount, start_date, end_date,
 is_active, created_by, usage_limit, used_count)
VALUES
('disc001','WELCOME100','fixed',100,2,100,'2026-01-01','2030-12-31',1,'owner001',100,10),
('disc002','SUMMER10','percentage',10,3,500,'2026-01-01','2030-12-31',1,'owner001',50,20);

-- =========================
-- 8. reservation_discounts
-- =========================
INSERT INTO reservation_discounts
(reservation_id, discount_id, applied_amount)
VALUES
('res002','disc001',100);