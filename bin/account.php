<?php
// bin/account.php
// สคริปต์จัดการบัญชีผ่านบรรทัดคำสั่ง (รันบนเซิร์ฟเวอร์เท่านั้น ไม่เปิดผ่านเว็บ)
//
//   php bin/account.php create-owner <อีเมล> <ชื่อ> <นามสกุล> <เบอร์โทร> [รหัสผ่าน]
//   php bin/account.php reset-password <อีเมล> [รหัสผ่านใหม่]
//
// ถ้าไม่ระบุรหัสผ่าน ระบบจะสุ่มให้ 1 ครั้งและแสดงบนหน้าจอ (ควรเปลี่ยนทันทีหลังเข้าสู่ระบบ)
// ใช้ได้กับบัญชีทุกประเภท (เจ้าของร้าน / พนักงาน / ลูกค้า) สำหรับ reset-password

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

/** สุ่มรหัสผ่าน 12 ตัว (ตัดตัวอักษรที่สับสนง่าย) */
function randomPassword(int $length = 12): string
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out      = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $out;
}

function usage(): void
{
    fwrite(STDERR, "วิธีใช้:\n");
    fwrite(STDERR, "  php bin/account.php create-owner <อีเมล> <ชื่อ> <นามสกุล> <เบอร์โทร> [รหัสผ่าน]\n");
    fwrite(STDERR, "  php bin/account.php reset-password <อีเมล> [รหัสผ่านใหม่]\n");
    exit(1);
}

$command = $argv[1] ?? '';
$db      = Database::connect();

if ($command === 'create-owner') {
    if (count($argv) < 6) {
        usage();
    }
    [, , $email, $first, $last, $phone] = $argv;
    $password = $argv[6] ?? randomPassword();

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "อีเมลไม่ถูกต้อง\n");
        exit(1);
    }

    // อีเมลต้องไม่ซ้ำกับบัญชีใดๆ
    foreach (['owners', 'employees', 'customers'] as $table) {
        $stmt = $db->prepare("SELECT 1 FROM {$table} WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            fwrite(STDERR, "อีเมลนี้มีบัญชีอยู่แล้ว (ตาราง {$table}) ใช้คำสั่ง reset-password แทน\n");
            exit(1);
        }
    }

    $stmt = $db->prepare(
        'INSERT INTO owners (owner_id, email, password_hash, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([gen_id('owner', 12), $email, password_hash($password, PASSWORD_DEFAULT), $first, $last, $phone]);

    echo "สร้างเจ้าของร้านแล้ว: {$email}\n";
    echo "รหัสผ่าน: {$password}\n";
    exit(0);
}

if ($command === 'reset-password') {
    if (count($argv) < 3) {
        usage();
    }
    $email    = $argv[2];
    $password = $argv[3] ?? randomPassword();

    foreach (['owners', 'employees', 'customers'] as $table) {
        $stmt = $db->prepare("UPDATE {$table} SET password_hash = ? WHERE email = ?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $email]);

        if ($stmt->rowCount() > 0) {
            echo "ตั้งรหัสผ่านใหม่แล้ว ({$table}): {$email}\n";
            echo "รหัสผ่าน: {$password}\n";
            exit(0);
        }
    }

    fwrite(STDERR, "ไม่พบบัญชีอีเมลนี้\n");
    exit(1);
}

usage();
