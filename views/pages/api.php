<?php
// views/pages/api.php
if (isset($_GET['action']) && $_GET['action'] === 'checkDiscount') {

    header('Content-Type: application/json; charset=utf-8');

    require_once APP_ROOT . '/src/Services/DiscountService.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $discountCode = trim($_POST['discount_code'] ?? '');
        $rentalDays   = (int) ($_POST['rental_days'] ?? 0);
        $totalPrice   = (float) ($_POST['total_price'] ?? 0);

        if ($discountCode === '') {
            echo json_encode([
                'valid'   => false,
                'message' => 'กรุณากรอกโค้ดส่วนลด',
            ]);
            exit;
        }

        $result = DiscountService::validateDiscount(
            $discountCode,
            $rentalDays,
            $totalPrice
        );

        echo json_encode($result);
        exit;
    }

    // สำคัญมาก
    exit;
}
