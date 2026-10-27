<?php
// bin/check.php
// ตรวจว่าเครื่องพร้อมรันระบบหรือยัง: เวอร์ชัน PHP, extension, ค่า php.ini, ไฟล์ .env,
// ไฟล์ cacert.pem, สิทธิ์เขียนโฟลเดอร์ storage และการเชื่อมต่อฐานข้อมูล
//
//   php bin/check.php          ตรวจทั้งหมด
//   php bin/check.php --net    ตรวจการเชื่อมต่อ HTTPS ไป ImgBB ด้วย (ต้องมีอินเทอร์เน็ต)
//
// หมายเหตุ: php.ini ที่ใช้กับ command line อาจเป็นคนละไฟล์กับที่ Apache/IIS ใช้
// ให้ดูค่าจริงของเว็บเซิร์ฟเวอร์ด้วย phpinfo() ชั่วคราว (แล้วลบทิ้ง)

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

$failures = 0;
$warnings = 0;

function report(string $level, string $message): void
{
    global $failures, $warnings;
    if ($level === 'FAIL') {
        $failures++;
    }
    if ($level === 'WARN') {
        $warnings++;
    }
    printf("[%-4s] %s\n", $level, $message);
}

/** แปลงค่าเช่น 8M, 2G เป็นไบต์ */
function toBytes(string $value): int
{
    $value = trim($value);
    $unit  = strtolower(substr($value, -1));
    $num   = (int) $value;
    return match ($unit) {
        'g'     => $num * 1024 * 1024 * 1024,
        'm'     => $num * 1024 * 1024,
        'k'     => $num * 1024,
        default => $num,
    };
}

echo "== PHP ==\n";
echo 'PHP ' . PHP_VERSION . ' | php.ini: ' . (php_ini_loaded_file() ?: '(ไม่พบ)') . "\n";

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    report('FAIL', 'ต้องใช้ PHP 8.1 ขึ้นไป (แนะนำ 8.3 / 8.4 / 8.5)');
} elseif (version_compare(PHP_VERSION, '8.3.0', '<')) {
    report('WARN', 'PHP ' . PHP_VERSION . ' หมดช่วงรับแพตช์ความปลอดภัยแล้ว แนะนำอัปเป็น 8.3 ขึ้นไป');
} else {
    report('OK', 'เวอร์ชัน PHP');
}

echo "\n== Extension ==\n";
foreach (['pdo_mysql', 'curl', 'openssl', 'fileinfo', 'mbstring'] as $ext) {
    extension_loaded($ext)
        ? report('OK', $ext)
        : report('FAIL', "ไม่พบ extension {$ext} (ลบเครื่องหมาย ; หน้า extension={$ext} ใน php.ini)");
}

echo "\n== ค่า php.ini ==\n";
$upload = toBytes((string) ini_get('upload_max_filesize'));
$post   = toBytes((string) ini_get('post_max_size'));
$upload >= 5 * 1024 * 1024
    ? report('OK', 'upload_max_filesize = ' . ini_get('upload_max_filesize'))
    : report('FAIL', 'upload_max_filesize = ' . ini_get('upload_max_filesize') . ' (ต้องอย่างน้อย 5M เพราะระบบรับรูปได้ถึง 5MB)');
$post >= 6 * 1024 * 1024
    ? report('OK', 'post_max_size = ' . ini_get('post_max_size'))
    : report('FAIL', 'post_max_size = ' . ini_get('post_max_size') . ' (ต้องอย่างน้อย 6M)');
ini_get('file_uploads')
    ? report('OK', 'file_uploads = On')
    : report('FAIL', 'file_uploads ถูกปิดอยู่');

echo "\n== ไฟล์ตั้งค่า .env ==\n";
if (! is_file(APP_ROOT . '/.env')) {
    report('FAIL', 'ไม่พบไฟล์ .env (คัดลอกจาก .env.example แล้วแก้ค่า)');
} else {
    report('OK', 'พบไฟล์ .env');
}
IMGBB_API_KEY !== ''
    ? report('OK', 'IMGBB_API_KEY ถูกตั้งค่าแล้ว')
    : report('WARN', 'ยังไม่ได้ตั้ง IMGBB_API_KEY (อัปโหลดสลิป/รูปรถจะไม่ได้)');
Env::get('DB_PASS', '') === ''
    ? report('WARN', 'DB_PASS ว่างเปล่า (ใช้ได้เฉพาะเครื่องทดสอบ)')
    : report('OK', 'DB_PASS ถูกตั้งค่าแล้ว');
Env::bool('APP_DEBUG')
    ? report('WARN', 'APP_DEBUG=1 จะแสดง error บนหน้าเว็บ ใช้งานจริงให้ตั้งเป็น 0')
    : report('OK', 'APP_DEBUG ปิดอยู่');

echo "\n== ใบรับรอง CA (cacert.pem) สำหรับ HTTPS ==\n";
$ca = Env::get('CA_BUNDLE', '') ?: ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
if ($ca && is_file($ca)) {
    report('OK', 'ใช้ CA bundle: ' . $ca);
} elseif ($ca) {
    report('FAIL', 'ตั้งค่า CA ไว้ที่ ' . $ca . ' แต่ไม่พบไฟล์');
} elseif (PHP_OS_FAMILY === 'Windows') {
    report('FAIL', 'Windows ต้องตั้ง curl.cainfo ใน php.ini (หรือ CA_BUNDLE ใน .env) ชี้ไปที่ cacert.pem ดูขั้นตอนใน README');
} else {
    report('OK', 'ไม่ได้ตั้งค่า CA เอง (Linux ใช้ใบรับรองของระบบ)');
}

echo "\n== โฟลเดอร์ที่ต้องเขียนได้ ==\n";
foreach (['storage/logs', 'storage/ratelimit'] as $dir) {
    is_writable(APP_ROOT . '/' . $dir)
        ? report('OK', $dir)
        : report('FAIL', $dir . ' เขียนไม่ได้ (ตั้งสิทธิ์ให้ผู้ใช้ที่รันเว็บเซิร์ฟเวอร์เขียนได้)');
}

echo "\n== ฐานข้อมูล ==\n";
try {
    $db = Database::connect();
    report('OK', 'เชื่อมต่อฐานข้อมูลสำเร็จ (' . Env::get('DB_NAME', 'motorcycle_rental_new') . ')');

    $tables = ['owners', 'employees', 'customers', 'motorcycles', 'reservations', 'payments', 'discounts', 'reservation_discounts'];
    $have   = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        in_array($t, $have, true) ? report('OK', "ตาราง {$t}") : report('FAIL', "ไม่พบตาราง {$t} (นำเข้า database/schema.sql)");
    }

    if (in_array('payments', $have, true)) {
        $cols = $db->query('SHOW COLUMNS FROM payments')->fetchAll(PDO::FETCH_COLUMN);
        in_array('updated_at', $cols, true)
            ? report('OK', 'payments.updated_at')
            : report('FAIL', 'ตาราง payments ไม่มีคอลัมน์ updated_at (รัน database/migrations/001_payments_updated_at.sql)');
    }

    if (in_array('owners', $have, true)) {
        $owners = (int) $db->query('SELECT COUNT(*) FROM owners')->fetchColumn();
        $owners > 0
            ? report('OK', "มีบัญชีเจ้าของร้าน {$owners} บัญชี")
            : report('WARN', 'ยังไม่มีเจ้าของร้าน สร้างด้วย: php bin/account.php create-owner ...');
    }
} catch (Throwable $e) {
    report('FAIL', 'เชื่อมต่อฐานข้อมูลไม่ได้: ' . $e->getMessage());
}

if (in_array('--net', $argv, true)) {
    echo "\n== ทดสอบ HTTPS ไป ImgBB ==\n";
    $ch = curl_init('https://api.imgbb.com/');
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_NOBODY => true];
    $caBundle = Env::get('CA_BUNDLE', '');
    if ($caBundle !== '' && is_file($caBundle)) {
        $opt[CURLOPT_CAINFO] = $caBundle;
    }
    curl_setopt_array($ch, $opt);
    curl_exec($ch) === false
        ? report('FAIL', 'เชื่อมต่อไม่ได้: ' . curl_error($ch))
        : report('OK', 'เชื่อมต่อ api.imgbb.com ได้ (HTTP ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ')');
}

echo "\nสรุป: ไม่ผ่าน {$failures} รายการ, เตือน {$warnings} รายการ\n";
exit($failures > 0 ? 1 : 0);
