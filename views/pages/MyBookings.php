<?php
    // views/pages/MyBookings.php
    if (! Auth::check()) {
    header("Location: login.php");
    exit;
    }

    // โหลด Services
        require_once APP_ROOT . '/src/Services/BookingService.php';
    require_once APP_ROOT . '/src/Services/PaymentService.php';
    require_once APP_ROOT . '/src/Services/MotorcycleService.php';

    // แก้ไข: เปลี่ยนจาก userId เป็น id ตามโครงสร้าง session ที่ถูกต้อง
    $customerId = Auth::id();
    $bookings   = [];
    $paymentMap = [];

    if ($customerId) {
    // ดึงการจองของลูกค้า
    $bookings = BookingService::getCustomerBookings($customerId);

    // ดึง payment สำหรับแต่ละ booking
    foreach ($bookings as $booking) {
        $reservationId = $booking['reservationId'] ?? null;
        if ($reservationId) {
            $payment = PaymentService::getPaymentByReservation($reservationId);
            if ($payment) {
                $paymentMap[$reservationId] = $payment;
            }
        }
    }
    }

    // เรียงการจองล่าสุดขึ้นก่อน
    usort($bookings, function ($a, $b) {
    return strtotime($b['createdAt'] ?? '') - strtotime($a['createdAt'] ?? '');
    });

    // Config สถานะการจอง (เหมือนตัวอย่าง TrackingPage.php)
    $statusConfig = [
    'pending'     => [
        'label'       => 'รอการยืนยัน',
        'color'       => 'yellow',
        'icon'        => 'clock',
        'description' => 'รอการตรวจสอบจากเจ้าหน้าที่',
    ],
    'confirmed'   => [
        'label'       => 'ยืนยันการจอง',
        'color'       => 'green',
        'icon'        => 'check-circle',
        'description' => 'การจองของคุณได้รับการยืนยันแล้ว',
    ],
    'in_progress' => [
        'label'       => 'กำลังดำเนินการ',
        'color'       => 'blue',
        'icon'        => 'truck',
        'description' => 'รถกำลังถูกเตรียมให้คุณ',
    ],
    'completed'   => [
        'label'       => 'เสร็จสิ้น',
        'color'       => 'green',
        'icon'        => 'circle-check-big',
        'description' => 'การเช่าของคุณเสร็จสมบูรณ์',
    ],
    'cancelled'   => [
        'label'       => 'ยกเลิก',
        'color'       => 'red',
        'icon'        => 'circle-x',
        'description' => 'การจองนี้ถูกยกเลิกแล้ว',
    ],
    'rejected'    => [
        'label'       => 'ปฏิเสธ',
        'color'       => 'red',
        'icon'        => 'ban',
        'description' => 'การจองนี้ถูกปฏิเสธ',
    ],
    ];

    // CSS classes สำหรับสถานะ
    $statusClasses = [
    'pending'     => 'bg-yellow-100 text-yellow-800',
    'confirmed'   => 'bg-green-100 text-green-800',
    'in_progress' => 'bg-blue-100 text-blue-800',
    'completed'   => 'bg-green-100 text-green-800',
    'cancelled'   => 'bg-red-100 text-red-800',
    'rejected'    => 'bg-red-100 text-red-800',
    ];

    $statusIconClasses = [
    'pending'     => 'text-yellow-600',
    'confirmed'   => 'text-green-600',
    'in_progress' => 'text-blue-600',
    'completed'   => 'text-green-600',
    'cancelled'   => 'text-red-600',
    'rejected'    => 'text-red-600',
    ];

    $flash_message = $_SESSION['flash_message'] ?? null;
    if ($flash_message) {
    unset($_SESSION['flash_message']);
    }
?>

<!-- ส่วน HTML และ JavaScript ที่เหลือเหมือนเดิมทุกประการ -->
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">การจองของฉัน</h1>
        <p class="text-lg text-gray-600 mb-6">จัดการการจองรถจักรยานยนต์ทั้งหมดของคุณ</p>

        <?php if (empty($bookings)): ?>
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="flex justify-center mb-4">
                    <i data-lucide="calendar" class="h-16 w-16 text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">ยังไม่มีการจอง</h3>
                <p class="text-gray-600 mb-6">เริ่มต้นการจองรถจักรยานยนต์ครั้งแรกของคุณ</p>
                <a href="index.php?page=motorcycles"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2">
                    <i data-lucide="bike" class="h-5 w-5"></i>
                    จองรถเลย
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($bookings as $booking):
                        $payment = $paymentMap[$booking['reservationId']] ?? null;

                        // ดึงสถานะจาก database
                        $bookingStatus = $booking['status'] ?? 'pending';

                        // ตั้งค่าสถานะตาม config
                        $currentStatus = $statusConfig[$bookingStatus] ?? $statusConfig['pending'];
                        $statusColor   = $statusClasses[$bookingStatus] ?? 'bg-gray-100 text-gray-800';
                        $statusIcon    = $currentStatus['icon'] ?? 'clock';
                        $statusText    = $currentStatus['label'] ?? 'รอการยืนยัน';

                        // ตรวจสอบว่าควรแสดงปุ่มชำระเงินหรือไม่
                        $hasPayment    = ! empty($payment);
                        $showPayButton = ! $hasPayment && ! in_array($bookingStatus, ['cancelled', 'rejected', 'completed']);

                        // ตรวจสอบสถานะการชำระเงินสำหรับการแสดงผลเพิ่มเติม
                        $paymentStatus     = $payment['paymentStatus'] ?? null;
                        $isPaymentVerified = $paymentStatus === 'verified';

                        // วันที่
                        $startDate = ! empty($booking['startDate']) ? new DateTime($booking['startDate']) : null;
                        $endDate   = ! empty($booking['endDate']) ? new DateTime($booking['endDate']) : null;
                        $totalDays = ($startDate && $endDate) ? $endDate->diff($startDate)->days : 0;

                        // ชื่อรถ (ยี่ห้อ + รุ่น)
                        $carName = ! empty($booking['brand']) && ! empty($booking['model'])
                            ? $booking['brand'] . ' ' . $booking['model']
                            : 'รถจักรยานยนต์ #' . ($booking['motorcycleId'] ?? '-');
                ?>
                <div class="bg-white rounded-lg shadow-lg p-6 mb-4 hover:shadow-xl transition-shadow duration-200">
                    <div class="flex flex-col md:flex-row gap-6">

                        <?php if (! empty($booking['imageUrl'])): ?>
                        <div class="w-full md:w-48 h-32 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                            <img src="<?php echo e($booking['imageUrl']); ?>"
                                 alt="<?php echo e($carName); ?>"
                                 class="w-full h-full object-cover">
                        </div>
                        <?php endif; ?>

                        <div class="flex-1">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold text-xl text-blue-900">
                                        <?php echo e($carName); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">รหัสการจอง: #<?php echo e($booking['reservationId'] ?? 'N/A'); ?></p>

                                    <!-- แสดงสถานะการชำระเงินเป็นข้อมูลเพิ่มเติม -->
                                    <?php if ($hasPayment): ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            สถานะชำระเงิน:
                                            <span class="<?php echo $isPaymentVerified ? 'text-green-600' : 'text-yellow-600' ?> font-medium">
                                                <?php echo $isPaymentVerified ? 'ชำระแล้ว' : 'รอตรวจสอบ' ?>
                                            </span>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap shrink-0 <?php echo $statusColor; ?>">
                                    <i data-lucide="<?php echo $statusIcon; ?>" class="h-4 w-4 mr-1"></i>
                                    <?php echo e($statusText); ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm bg-gray-50 p-3 rounded-md mb-4">
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">วันรับรถ</p>
                                    <p class="font-medium text-gray-900"><?php echo $startDate ? $startDate->format('d/m/Y') : '-'; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">วันคืนรถ</p>
                                    <p class="font-medium text-gray-900"><?php echo $endDate ? $endDate->format('d/m/Y') : '-'; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">ระยะเวลา</p>
                                    <p class="font-medium text-gray-900"><?php echo e($totalDays); ?> วัน</p>
                                </div>

                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">สถานที่รับรถ</p>
                                    <p class="font-medium text-gray-900" title="<?php echo e($booking['pickupLocation'] ?? '-'); ?>">
                                        <?php echo e($booking['pickupLocation'] ?? '-'); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">สถานที่คืนรถ</p>
                                    <p class="font-medium text-gray-900" title="<?php echo e($booking['returnLocation'] ?? '-'); ?>">
                                        <?php echo e($booking['returnLocation'] ?? '-'); ?>
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">ราคารวม</p>
                                    <p class="font-bold text-blue-600 text-lg">฿<?php echo number_format($booking['finalPrice'] ?? $booking['totalPrice'] ?? 0); ?></p>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2 border-t border-gray-100">
                                <?php if ($showPayButton): ?>
                                <a href="index.php?page=payment&reservation=<?php echo e($booking['reservationId']); ?>"
                                   class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                                    <i data-lucide="credit-card" class="h-4 w-4"></i> ชำระเงินทันที
                                </a>
                                <?php endif; ?>
                                <a href="index.php?page=tracking&reservation=<?php echo e($booking['reservationId']); ?>"
                                class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                                    <i data-lucide="file-text" class="h-4 w-4"></i> ติดตามสถานะการจอง
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- แสดงคำอธิบายสถานะ (สำหรับบางสถานะที่สำคัญ) -->
                    <?php if (in_array($bookingStatus, ['cancelled', 'rejected', 'pending'])): ?>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 pt-0.5">
                                <i data-lucide="info" class="h-4 w-4 text-gray-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-600">
                                    <?php echo e($currentStatus['description'] ?? ''); ?>

                                    <?php if ($bookingStatus === 'pending' && ! $hasPayment): ?>
                                        <span class="text-yellow-600 font-medium">กรุณาชำระเงินเพื่อดำเนินการต่อ</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>