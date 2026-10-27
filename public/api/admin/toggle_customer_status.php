<?php
// public/api/admin/toggle_customer_status.php
// ระงับ (ban) / ปลดระงับ (unban) บัญชีลูกค้า รับ JSON: {"customerId": "...", "action": "ban|unban"}
// สิทธิ์: owner เท่านั้น

require_once __DIR__ . '/../../../src/bootstrap.php';

Auth::requireApiRole(['owner']);

$data       = json_decode(file_get_contents('php://input'), true);
$customerId = $data['customerId'] ?? '';
$action     = $data['action'] ?? '';

if (empty($customerId) || ! in_array($action, ['ban', 'unban'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $db       = Database::connect();
    $isActive = $action === 'unban' ? 1 : 0;

    $stmt = $db->prepare('UPDATE customers SET is_active = ? WHERE customer_id = ?');
    $stmt->execute([$isActive, $customerId]);

    error_log(sprintf(
        '%s ลูกค้า ID: %s โดย %s',
        $action === 'ban' ? 'ระงับบัญชี' : 'ปลดระงับบัญชี',
        $customerId,
        Auth::user()['email'] ?? '-'
    ));

    echo json_encode([
        'success' => true,
        'message' => $action === 'ban' ? 'แบนลูกค้าเรียบร้อยแล้ว' : 'ยกเลิกการแบนลูกค้าเรียบร้อยแล้ว',
    ]);

} catch (Exception $e) {
    error_log('Toggle customer status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ']);
}
