# ระบบจองเช่ามอเตอร์ไซค์ (Motorcycle Rental)

เว็บจองเช่ามอเตอร์ไซค์ เขียนด้วย PHP ล้วน (ไม่มีเฟรมเวิร์ก) ใช้ MySQL/MariaDB
มี 3 บทบาท: ลูกค้า (customer), พนักงาน (employee), เจ้าของร้าน (owner)

เวอร์ชันนี้จัดโครงสร้างใหม่ ลบโค้ดที่ไม่ได้ใช้ และแก้ช่องโหว่ด้านความปลอดภัย โดยหน้าตาและการทำงานหลักยังเหมือนเดิม
รายละเอียดดูที่ `CHANGELOG.md`

## 1. สิ่งที่ต้องมี

- PHP 8.3 ขึ้นไป (เป้าหมายคือ 8.5) พร้อมส่วนเสริม: `pdo_mysql`, `curl`, `fileinfo`, `mbstring`, `openssl`
- MySQL 8 หรือ MariaDB 10.6 ขึ้นไป
- เว็บเซิร์ฟเวอร์ Apache หรือ nginx (หรือใช้ `php -S` ตอนพัฒนา)
- บัญชี ImgBB (ฟรี) สำหรับเก็บรูปสลิปและรูปรถ: https://api.imgbb.com/

## 2. โครงสร้างโฟลเดอร์

```
public/      โฟลเดอร์เดียวที่เว็บเซิร์ฟเวอร์เปิดให้ภายนอกเข้าถึง (DocumentRoot)
  index.php  หน้าหลัก (ตัวคุมเส้นทาง index.php?page=...)
  login.php register.php logout.php forgot_password.php
  api/admin/ API สำหรับหน้าจัดการ (ต้องล็อกอินเป็นพนักงาน/เจ้าของ)
  assets/    ไฟล์ JS ที่เก็บในเครื่อง (lucide, Chart.js, sweetalert2) และรูป placeholder
  uploads/qrcode/qrcode1.jpg   QR พร้อมเพย์ที่แสดงตอนชำระเงิน (เปลี่ยนเป็นของร้านจริง)
src/         โค้ดหลังบ้าน (ภายนอกเข้าไม่ได้)
  bootstrap.php  โหลด .env, ตั้งค่า error, เริ่ม session, ส่ง security header
  Database.php   ต่อฐานข้อมูล (PDO)
  Services/      ตรรกะธุรกิจ: จอง ชำระเงิน ส่วนลด ผู้ใช้ รถ
  Support/       Auth, Csrf, Session, LoginThrottle, ImageUploader, Env
views/       หน้าเว็บ (PHP template) แยกเป็น components/ และ pages/
database/    schema.sql, seed.sql, example_queries.sql, migrations/
bin/         สคริปต์บรรทัดคำสั่ง: account.php (สร้าง/รีเซ็ตบัญชี), check.php (ตรวจเครื่อง)
storage/     logs/ (error log) และ ratelimit/ (ตัวนับล็อกอินผิด) เขียนได้โดย PHP
.env         ค่าตั้งค่าของเครื่องนี้ (ไม่ขึ้น Git)
```

ลิงก์เดิมทั้งหมด (`index.php?page=...`, `login.php`) ยังใช้ได้เหมือนเดิม

## 3. ติดตั้ง

### 3.1 ฐานข้อมูล

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql     # ข้อมูลตัวอย่าง (ข้ามได้ถ้าจะใช้งานจริง)
```

`schema.sql` สร้างฐานข้อมูล `motorcycle_rental_new` ให้เอง แนะนำให้สร้างผู้ใช้เฉพาะแอป ไม่ใช้ root:

```sql
CREATE USER 'carcare'@'localhost' IDENTIFIED BY 'รหัสผ่านที่เดายาก';
GRANT SELECT, INSERT, UPDATE, DELETE ON motorcycle_rental_new.* TO 'carcare'@'localhost';
```

ถ้ามีฐานข้อมูลเก่าอยู่แล้ว ให้รัน `database/migrations/001_payments_updated_at.sql` (เพิ่มคอลัมน์ `payments.updated_at`)

### 3.2 ไฟล์ .env

```bash
cp .env.example .env
```

แล้วแก้ค่า `DB_*` และ `IMGBB_API_KEY` ให้ตรงกับเครื่อง (ดูคำอธิบายในไฟล์) และแก้ข้อมูลร้านที่แสดงบนหน้าเว็บเป็นของจริง ได้แก่ `SHOP_ADDRESS`, `SHOP_PHONE`, `SHOP_HOURS`, `SUPPORT_PHONE`, `SUPPORT_EMAIL` และข้อมูลผู้รับเงินในหน้าชำระเงิน `PAYEE_NAME`, `PAYEE_PHONE` (ไม่ต้องแก้โค้ด) บน Windows ใช้เครื่องหมาย `/` ในพาธ เช่น `C:/php/extras/ssl/cacert.pem`

### 3.3 สร้างบัญชีเจ้าของร้าน

ข้อมูลตัวอย่างใน `seed.sql` ยังไม่มีรหัสผ่านที่ใช้ได้ ต้องสร้างเอง:

```bash
php bin/account.php create-owner owner@example.com ชื่อ นามสกุล 0812345678
# ถ้าไม่ใส่รหัสผ่านท้ายคำสั่ง ระบบจะสุ่มให้และแสดงผลครั้งเดียว

php bin/account.php reset-password owner@example.com   # รีเซ็ตรหัสผ่านเจ้าของร้าน
```

พนักงานเพิ่มได้จากหน้า "จัดการพนักงาน" ของเจ้าของร้าน ส่วนรหัสผ่านลูกค้าที่ลืม พนักงานหรือเจ้าของกดปุ่ม "รีเซ็ตรหัสผ่าน" ในหน้าจัดการลูกค้า ระบบจะแสดงรหัสชั่วคราวให้แจ้งลูกค้า

### 3.4 เปิดเว็บ

DocumentRoot ต้องชี้ไปที่โฟลเดอร์ `public/` เท่านั้น

**ทดลองในเครื่อง**
```bash
php -S 127.0.0.1:8080 -t public
```
เปิด http://127.0.0.1:8080/ (ค่า `upload_max_filesize` ใน php.ini ต้องไม่ต่ำกว่า 5M ถ้าเป็น 2M ที่เป็นค่าเริ่มต้น ให้เพิ่ม `-d upload_max_filesize=8M -d post_max_size=10M`)

**Apache** (ตัวอย่าง VirtualHost)
```apache
<VirtualHost *:80>
    ServerName carcare.local
    DocumentRoot "C:/xampp/carcare/public"
    <Directory "C:/xampp/carcare/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
ถ้าตั้ง DocumentRoot ชี้ที่โฟลเดอร์โปรเจกต์ (ไม่ใช่ `public/`) ไฟล์ `.htaccess` ที่ราก จะส่งต่อทุกคำขอเข้า `public/` และปิดไฟล์ `.env`, `.sql`, `.log` ให้ แต่วิธีที่ถูกต้องคือชี้ที่ `public/` โดยตรง

**nginx**
```nginx
server {
    listen 80;
    server_name carcare.local;
    root /var/www/carcare/public;
    index index.php;

    location / { try_files $uri $uri/ =404; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    location ~ /\. { deny all; }
}
```

**Windows + NSSM** (ถ้าต้องรันเป็นบริการ): ใช้ NSSM ครอบ `php.exe -S 0.0.0.0:8080 -t C:\carcare\public` ได้ แต่ถ้ามีผู้ใช้จริงจำนวนมาก แนะนำ Apache/nginx มากกว่า เพราะ `php -S` เป็นเซิร์ฟเวอร์สำหรับพัฒนา

### 3.5 ตรวจความพร้อม

```bash
php bin/check.php          # ตรวจเวอร์ชัน PHP ส่วนเสริม .env ฐานข้อมูล โฟลเดอร์เขียนได้
php bin/check.php --net    # ทดสอบการเชื่อมต่อ ImgBB ด้วย
```

บนลินุกซ์ ให้ `storage/` เขียนได้โดยผู้ใช้ของเว็บเซิร์ฟเวอร์ เช่น `chown -R www-data storage`

## 4. ตั้งค่า PHP บน Windows (ใบรับรอง cacert.pem)

การอัปโหลดรูปไป ImgBB ใช้ cURL ผ่าน HTTPS ถ้าเครื่องไม่มีไฟล์ใบรับรอง CA จะขึ้น error "SSL certificate problem" วิธีแก้ (เลือกอย่างใดอย่างหนึ่ง)

1. **แนะนำ: ตั้งใน php.ini** (ใช้ได้กับทุกโปรเจกต์)
   - โหลด https://curl.se/ca/cacert.pem วางที่ `C:\php\extras\ssl\cacert.pem` (ที่ไหนก็ได้)
   - แก้ php.ini: `curl.cainfo = "C:\php\extras\ssl\cacert.pem"` และ `openssl.cafile = "C:\php\extras\ssl\cacert.pem"`
   - เปิดส่วนเสริม: `extension=curl`, `extension=fileinfo`, `extension=pdo_mysql`, `extension=mbstring`, `extension=openssl`
   - รีสตาร์ท Apache หรือบริการ PHP
2. **หรือ ตั้งใน .env**: `CA_BUNDLE=C:/php/extras/ssl/cacert.pem` (ใช้เฉพาะโปรเจกต์นี้)

ค่าอื่นที่ควรตรวจใน php.ini: `upload_max_filesize = 8M`, `post_max_size = 10M`, `date.timezone = Asia/Bangkok`

แนะนำให้อัปเดต cacert.pem ปีละครั้ง

## 5. การใช้งานระบบ

| บทบาท | เข้าที่ | ทำอะไรได้ |
|---|---|---|
| ลูกค้า | `index.php` | ดู/ค้นหารถ จอง ชำระมัดจำ (QR + อัปโหลดสลิป) ติดตามสถานะ แก้โปรไฟล์ |
| พนักงาน | `index.php?page=employee` | จัดการการจอง ตรวจสลิป จัดการรถ ดูข้อมูลลูกค้า รีเซ็ตรหัสผ่านลูกค้า |
| เจ้าของ | `index.php?page=admin` | ทุกอย่างของพนักงาน + สรุปรายได้ จัดการส่วนลด จัดการพนักงาน ระงับบัญชีลูกค้า |

ค่ามัดจำตั้งที่ `DEPOSIT_AMOUNT` ใน `.env` (ค่าเริ่มต้น 500 บาท) ส่วนส่วนลดของร้านคิด 50 บาททุก 3 วันที่เช่า (เหมือนเดิม)

หน้า "เงื่อนไขการเช่า" (`index.php?page=about`) อยู่ที่ `views/pages/AboutPage.php` เนื้อหาตอนนี้อ้างอิงเฉพาะสิ่งที่ระบบทำจริง ถ้าร้านมีเงื่อนไขเพิ่ม (เอกสารที่ต้องใช้ ประกัน ค่าปรับ) ให้เพิ่มในไฟล์นั้น ส่วนหน้า "ติดต่อเรา" ดึงข้อมูลจาก `.env`

รหัสผ่านทุกบัญชีต้องยาวอย่างน้อย 8 ตัวอักษร

QR ชำระเงิน: แทนที่ไฟล์ `public/uploads/qrcode/qrcode1.jpg` ด้วย QR พร้อมเพย์ของร้านจริง

## 6. ความปลอดภัย (สิ่งที่ควรรู้)

- ฟอร์มและ API ทุกตัวที่แก้ข้อมูลตรวจ CSRF token
- รหัสผ่านเก็บแบบ bcrypt; ล็อกอินผิด 5 ครั้งใน 15 นาที (ต่อ IP+อีเมล) จะถูกล็อกชั่วคราว
- ส่วนลดและยอดเงินคำนวณฝั่งเซิร์ฟเวอร์ ค่าจากฟอร์มของผู้ใช้ไม่ถูกเชื่อถือ
- การจองล็อกแถวรถและตรวจช่วงเวลาซ้ำในธุรกรรมเดียว กันสองคนจองรถคันเดียวกันพร้อมกัน
- ถ้าใช้งานจริงบนอินเทอร์เน็ต ให้ใช้ HTTPS เสมอ และตั้ง `APP_DEBUG=0`
- ห้ามนำ `.env` ขึ้น Git (ถูกกันไว้ใน `.gitignore` แล้ว)
- ข้อมูลตัวอย่างใน `seed.sql` เป็นข้อมูลปลอม ส่วนข้อมูลร้านบนหน้าเว็บให้ตั้งใน `.env` (ดูหัวข้อ 3.2)

## 7. ปัญหาที่พบบ่อย

| อาการ | วิธีแก้ |
|---|---|
| อัปโหลดรูปแล้ว "SSL certificate problem" | ดูหัวข้อ 4 |
| อัปโหลดรูปแล้วไม่ผ่านเพราะขนาด | เพิ่ม `upload_max_filesize` / `post_max_size` ใน php.ini |
| เข้าเว็บแล้วหน้าขาว | ดู `storage/logs/php-error.log` หรือตั้ง `APP_DEBUG=1` ชั่วคราว |
| เชื่อมต่อฐานข้อมูลไม่ได้ | ตรวจ `DB_*` ใน `.env` แล้วรัน `php bin/check.php` |
| ฟอร์มขึ้น "CSRF" / 419 | session หมดอายุ รีเฟรชหน้าแล้วลองใหม่ |
| หน้าเว็บไม่มีไอคอน/ปุ่มไม่ทำงาน | หน้าใช้ Tailwind จาก CDN ต้องต่ออินเทอร์เน็ต |

## 8. หมายเหตุสำหรับนักพัฒนา

- เพิ่มหน้าใหม่: สร้างไฟล์ใน `views/pages/` แล้วเพิ่มชื่อใน `$pageMap` ของ `public/index.php`
- ในไฟล์ view ใช้ `e($ข้อความ)` ครอบทุกค่าที่พิมพ์ออกหน้าเว็บ และใส่ `<?= Csrf::field() ?>` ในทุกฟอร์ม POST
- fetch จาก JavaScript ใส่ CSRF header ให้อัตโนมัติ (ผ่าน `<meta name="csrf-token">`)
- ทดสอบบน PHP 8.3 แล้ว; PHP 8.5 เขียนเลี่ยงฟังก์ชันที่ถูกยกเลิก (เช่น `curl_close`) แต่ยังควรทดสอบบนเครื่องที่ติดตั้ง 8.5 ก่อนใช้จริง
