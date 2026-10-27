<?php
// src/bootstrap.php
// จุดเริ่มต้นของทุกคำขอ: โหลดค่าตั้งค่า (.env), ตั้งค่า error/timezone, โหลดคลาสพื้นฐาน, เริ่ม session
// ทุกไฟล์ใน public/ ต้อง require ไฟล์นี้เป็นอันดับแรก

if (defined('APP_ROOT')) {
    return; // โหลดไปแล้ว
}

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/src/Support/Env.php';
Env::load(APP_ROOT . '/.env');

// --- การแสดง error ---
// APP_DEBUG=1 แสดง error บนหน้าเว็บ (เฉพาะตอนพัฒนา) / ปิดไว้ตอนใช้งานจริง และบันทึกลง storage/logs เสมอ
$debug = Env::bool('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');

date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Bangkok'));

// --- ค่าคงที่ของระบบ ---
define('IMGBB_API_KEY', (string) Env::get('IMGBB_API_KEY', ''));
define('DEPOSIT_AMOUNT', (float) Env::get('DEPOSIT_AMOUNT', '500')); // ค่ามัดจำต่อการจอง (บาท)

// --- ข้อมูลร้านที่แสดงบนหน้าเว็บ (แก้ได้ใน .env ไม่ต้องแก้โค้ด) ---
define('SHOP_ADDRESS', (string) Env::get('SHOP_ADDRESS', '31/4 ซอยศรีสุดา ตำบลบ่อยาง อำเภอเมืองสงขลา จังหวัดสงขลา'));
define('SHOP_PHONE', (string) Env::get('SHOP_PHONE', '074-123-456'));
define('SHOP_HOURS', (string) Env::get('SHOP_HOURS', 'ทุกวัน 08:00 - 20:00 น.'));
define('SUPPORT_PHONE', (string) Env::get('SUPPORT_PHONE', '093-648-0332'));
define('SUPPORT_EMAIL', (string) Env::get('SUPPORT_EMAIL', 'support@temptation.com'));
define('PAYEE_NAME', (string) Env::get('PAYEE_NAME', 'บริษัท เทมป์เทชัน จำกัด'));
define('PAYEE_PHONE', (string) Env::get('PAYEE_PHONE', '093-648-0332')); // เบอร์พร้อมเพย์ที่แสดงตอนชำระเงิน

require_once APP_ROOT . '/src/Database.php';
require_once APP_ROOT . '/src/Support/helpers.php';
require_once APP_ROOT . '/src/Support/uuid.php';
require_once APP_ROOT . '/src/Support/Session.php';
require_once APP_ROOT . '/src/Support/Csrf.php';
require_once APP_ROOT . '/src/Support/Auth.php';
require_once APP_ROOT . '/src/Support/LoginThrottle.php';
require_once APP_ROOT . '/src/Support/ImageUploader.php';

if (PHP_SAPI !== 'cli') {
    Session::start();

    // header ความปลอดภัยพื้นฐาน
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
