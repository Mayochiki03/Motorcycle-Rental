<?php
// public/api/admin/booking_detail.php
// ส่งรายละเอียดการจอง (พร้อมข้อมูลลูกค้าและการชำระเงิน) เป็น JSON ให้หน้าจัดการการจอง
// สิทธิ์: owner และ employee เท่านั้น

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once APP_ROOT . '/src/Services/Admin/AdminService.php';

use Service\Admin\AdminService;

Auth::requireApiRole(['owner', 'employee']);

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing reservation id']);
    exit;
}

$booking = AdminService::getBookingDetail($_GET['id']);

if (! $booking) {
    http_response_code(404);
    echo json_encode(['error' => 'booking not found']);
    exit;
}

// สรุปข้อมูลลูกค้า
$customerSummary = AdminService::getCustomerSummary($booking['customer_id']);

// แยกข้อมูลการชำระเงินออกมาให้ฝั่งหน้าเว็บใช้
$payment = null;
if (! empty($booking['payment_status'])) {
    $payment = [
        'payment_status' => $booking['payment_status'],
        'payment_method' => $booking['payment_method'] ?? null,
        'payment_date'   => $booking['payment_date'] ?? null,
        'amount'         => $booking['amount'] ?? 0,
        'slip_image_url' => $booking['slip_image_url'] ?? null,
        'notes'          => $booking['notes'] ?? null,
    ];
}

echo json_encode([
    'booking'  => $booking,
    'customer' => [
        'firstName'     => $booking['firstName'] ?? '',
        'lastName'      => $booking['lastName'] ?? '',
        'email'         => $booking['email'] ?? '',
        'phone'         => $booking['phone'] ?? '',
        'totalBookings' => $customerSummary['totalBookings'] ?? 0,
        'totalSpent'    => $customerSummary['totalSpent'] ?? 0,
    ],
    'payment'  => $payment,
]);
