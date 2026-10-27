<?php
// public/api/admin/payment.php
// จัดการการชำระเงิน/การคืนรถ จากหน้าจัดการการจอง (POST)
//   action=verify   ยืนยันการชำระเงิน
//   action=reject   ปฏิเสธการชำระเงิน (ส่ง reason ได้)
//   action=complete บันทึกการคืนรถ (return_condition, motorcycle_status)
// สิทธิ์: owner และ employee เท่านั้น

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once APP_ROOT . '/src/Services/Admin/AdminService.php';

use Service\Admin\AdminService;

Auth::requireApiRole(['owner', 'employee']);

try {
    $action        = $_POST['action'] ?? $_GET['action'] ?? '';
    $reservationId = $_POST['reservation_id'] ?? $_GET['reservation_id'] ?? '';

    if (empty($reservationId)) {
        throw new Exception('Missing reservation ID');
    }

    switch ($action) {

        case 'verify':
            if (! AdminService::verifyPayment($reservationId)) {
                throw new Exception('ไม่สามารถยืนยันการชำระเงินได้');
            }
            echo json_encode(['success' => true, 'message' => 'ยืนยันการชำระเงินสำเร็จ']);
            break;

        case 'reject':
            $reason = $_POST['reason'] ?? '';
            if (! AdminService::rejectPayment($reservationId, $reason)) {
                throw new Exception('ไม่สามารถปฏิเสธการชำระเงินได้');
            }
            echo json_encode(['success' => true, 'message' => 'ปฏิเสธการชำระเงินสำเร็จ']);
            break;

        case 'complete':
            $returnCondition  = $_POST['return_condition'] ?? '';
            $motorcycleStatus = $_POST['motorcycle_status'] ?? 'READY';

            if (! AdminService::completeBooking($reservationId, $returnCondition, $motorcycleStatus)) {
                throw new Exception('ไม่สามารถอัปเดตสถานะได้');
            }
            echo json_encode(['success' => true, 'message' => 'บันทึกการคืนรถสำเร็จ']);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
