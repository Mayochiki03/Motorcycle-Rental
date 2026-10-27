<?php
// public/api/admin/customer_detail.php
// ส่งโปรไฟล์และประวัติการจองของลูกค้าเป็น JSON ให้หน้าจัดการลูกค้า
// สิทธิ์: owner และ employee เท่านั้น

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once APP_ROOT . '/src/Services/Admin/AdminService.php';

use Service\Admin\AdminService;

Auth::requireApiRole(['owner', 'employee']);

$customerId = $_GET['id'] ?? '';

if ($customerId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing customer id']);
    exit;
}

try {
    $profile = AdminService::getCustomerProfile($customerId);

    if (empty($profile['customerId']) || $profile['customerId'] != $customerId) {
        http_response_code(404);
        echo json_encode(['error' => 'customer not found']);
        exit;
    }

    echo json_encode([
        'profile'      => $profile,
        'reservations' => AdminService::getCustomerReservations($customerId),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('Customer detail API error: ' . $e->getMessage());
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในการดึงข้อมูลลูกค้า']);
}
