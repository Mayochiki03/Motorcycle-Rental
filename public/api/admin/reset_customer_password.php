<?php
// public/api/admin/reset_customer_password.php
// ตั้งรหัสผ่านชั่วคราวใหม่ให้ลูกค้าที่ลืมรหัสผ่าน (POST JSON: {"customerId": "..."})
// ระบบสร้างรหัสสุ่มให้ 1 ครั้ง ผู้ดูแลแจ้งลูกค้า แล้วให้ลูกค้าเปลี่ยนรหัสเองที่หน้าโปรไฟล์
// สิทธิ์: owner และ employee เท่านั้น

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once APP_ROOT . '/src/Services/AuthService.php';

Auth::requireApiRole(['owner', 'employee']);

$data       = json_decode(file_get_contents('php://input'), true);
$customerId = $data['customerId'] ?? '';

if ($customerId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $db   = Database::connect();
    $stmt = $db->prepare('SELECT email FROM customers WHERE customer_id = ? LIMIT 1');
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    if (! $customer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ไม่พบลูกค้า']);
        exit;
    }

    // รหัสสุ่ม 10 ตัว (ตัดตัวที่สับสนง่าย เช่น 0/O, 1/l/I)
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $temp     = '';
    for ($i = 0; $i < 10; $i++) {
        $temp .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    if (! AuthService::updatePassword($customerId, 'customer', 'customers', $temp)) {
        throw new Exception('update failed');
    }

    error_log('รีเซ็ตรหัสผ่านลูกค้า ID: ' . $customerId . ' โดย ' . (Auth::user()['email'] ?? '-'));

    echo json_encode([
        'success'      => true,
        'message'      => 'รีเซ็ตรหัสผ่านเรียบร้อยแล้ว',
        'tempPassword' => $temp,
    ]);

} catch (Exception $e) {
    error_log('Reset customer password error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ']);
}
