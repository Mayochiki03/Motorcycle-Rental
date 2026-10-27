<?php
    // views/pages/TrackingPage.php
    if (! Auth::check()) {
    $_SESSION['redirect_url']  = $_SERVER['REQUEST_URI'];
    $_SESSION['flash_message'] = [
        'type'    => 'error',
        'message' => 'กรุณาเข้าสู่ระบบเพื่อดูสถานะการจอง',
    ];
    header("Location: login.php");
    exit;
    }

            require_once APP_ROOT . '/src/Services/BookingService.php';
    require_once APP_ROOT . '/src/Services/PaymentService.php';
    require_once APP_ROOT . '/src/Services/MotorcycleService.php';
    require_once APP_ROOT . '/src/Services/BookingTrackingService.php';

    // ตรวจสอบการเข้าถึง
    $customerId    = Auth::id();
    $reservationId = $_GET['reservation'] ?? '';

    if (empty($reservationId)) {
    $_SESSION['flash_message'] = [
        'type'    => 'error',
        'message' => 'ไม่พบรหัสการจอง',
    ];
    header("Location: index.php?page=my-bookings");
    exit;
    }

    // ดึงข้อมูลการจองผ่าน BookingTrackingService (ข้อมูลครบถ้วน)
    $bookingData = BookingTrackingService::getCustomerBookingWithStatus($reservationId, $customerId);

    if (! $bookingData) {
    $_SESSION['flash_message'] = [
        'type'    => 'error',
        'message' => 'ไม่พบข้อมูลการจอง',
    ];
    header("Location: index.php?page=my-bookings");
    exit;
    }

    // ตรวจสอบว่าการจองเป็นของลูกค้านี้จริง
    if ($bookingData['customerId'] !== $customerId) {
    $_SESSION['flash_message'] = [
        'type'    => 'error',
        'message' => 'คุณไม่มีสิทธิ์เข้าถึงการจองนี้',
    ];
    header("Location: index.php?page=my-bookings");
    exit;
    }

    // ใช้ข้อมูลจาก BookingTrackingService
    $booking = $bookingData;

    // ดึงข้อมูลที่ต้องการ
    $payment    = $booking['payment'] ?? null;
    $motorcycle = [
    'brand'         => $booking['motorcycleBrand'] ?? '',
    'model'         => $booking['motorcycleModel'] ?? '',
    'engineCc'      => $booking['motorcycleEngineCc'] ?? '',
    'license_plate' => $booking['licensePlate'] ?? '',
    'color'         => $booking['motorcycleColor'] ?? '',
    'imageUrl'      => $booking['motorcycleImageUrl'] ?? '',
    'price_per_day' => $booking['pricePerDay'] ?? 0,

    ];
    $pickupLocation = $booking['pickupLocation'] ?? '';
    $returnLocation = $booking['returnLocation'] ?? '';

    // ตั้งค่าสถานะ
    $bookingStatus = $booking['status'] ?? 'pending';
    $paymentStatus = $payment['paymentStatus'] ?? 'pending';

    // ตั้งค่า theme ตามสถานะ
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

    $currentStatus = $statusConfig[$bookingStatus] ?? $statusConfig['pending'];

    // ใช้ค่าจาก BookingTrackingService โดยตรง
    $canCancel     = $booking['canCancel'] ?? false;
    $canEdit       = $booking['canEdit'] ?? false;
    $canPayDeposit = $booking['canPayDeposit'] ?? false;
    $cancelMessage = $booking['cancelDeadline'] ?? '';

    // ฟังก์ชันจัดการแบบฟอร์ม
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'cancel_booking':
                $reason = $_POST['cancel_reason'] ?? '';

                if (empty($reason)) {
                    $error = 'กรุณาระบุเหตุผลในการยกเลิก';
                } else {
                    $result = BookingTrackingService::cancelBookingByCustomer($reservationId, $customerId, $reason);
                    if ($result['success']) {
                        $_SESSION['flash_message'] = [
                            'type'    => 'success',
                            'message' => $result['message'],
                        ];
                        // รีเฟรชหน้าเพื่อแสดงข้อมูลล่าสุด
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    } else {
                        $error = $result['message'];
                    }
                }
                break;

            case 'update_booking':
                $updateData = [
                    'pickup_location' => $_POST['pickup_location'] ?? '',
                    'return_location' => $_POST['return_location'] ?? '',
                    'pickup_details'  => $_POST['pickup_details'] ?? '',
                    'return_details'  => $_POST['return_details'] ?? '',
                ];

                $result = BookingTrackingService::updateBookingDetailsByCustomer($reservationId, $customerId, $updateData);
                if ($result['success']) {
                    $_SESSION['flash_message'] = [
                        'type'    => 'success',
                        'message' => $result['message'],
                    ];
                    // รีเฟรชหน้าเพื่อแสดงข้อมูลล่าสุด
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
    }

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

    $statusColor     = $statusClasses[$bookingStatus] ?? 'bg-gray-100 text-gray-800';
    $statusIconColor = $statusIconClasses[$bookingStatus] ?? 'text-gray-600';
?>

<!-- ส่วนหัวของหน้านี้ (สไตล์/คอนฟิก Tailwind เฉพาะหน้า) -->
    <script src="assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        success: {
                            50: '#f0fdf4',
                            500: '#22c55e',
                            600: '#16a34a',
                        },
                        warning: {
                            50: '#fffbeb',
                            500: '#f59e0b',
                            600: '#d97706',
                        },
                        danger: {
                            50: '#fef2f2',
                            500: '#ef4444',
                            600: '#dc2626',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Animation สำหรับ modal */
        .modal-transition {
            transition: opacity 0.2s ease-in-out;
        }
    </style>

<div class="min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="index.php?page=home" class="text-primary-600 hover:text-primary-700">
                        <i data-lucide="home" class="h-4 w-4"></i>
                    </a>
                </li>
                <li class="text-gray-400">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </li>
                <li>
                    <a href="index.php?page=my-bookings" class="text-primary-600 hover:text-primary-700">
                        การจองของฉัน
                    </a>
                </li>
                <li class="text-gray-400">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </li>
                <li class="text-gray-600">
                    ติดตามสถานะ
                </li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="bg-gray-100 p-3 rounded-full">
                    <i data-lucide="<?php echo $currentStatus['icon'] ?>" class="h-12 w-12 <?php echo $statusIconColor ?>"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">ติดตามสถานะการจอง</h1>
            <p class="text-lg text-gray-600">
                รหัสการจอง: <span class="font-mono font-bold"><?php echo e($reservationId) ?></span>
            </p>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="mb-6 <?php echo $_SESSION['flash_message']['type'] === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?> border px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i data-lucide="<?php echo $_SESSION['flash_message']['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="h-5 w-5 mr-2"></i>
                    <span><?php echo e($_SESSION['flash_message']['message']) ?></span>
                </div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (! empty($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i data-lucide="alert-circle" class="h-5 w-5 mr-2"></i>
                    <span><?php echo e($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Status Timeline & Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status Timeline -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i data-lucide="timeline" class="h-6 w-6 text-primary-600 mr-2"></i>
                        สถานะการจอง
                    </h2>

                    <div class="space-y-4">
                        <!-- Current Status -->
                        <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center space-x-4">
                                <div class="bg-gray-100 p-2 rounded-full">
                                    <i data-lucide="<?php echo $currentStatus['icon'] ?>" class="h-6 w-6 <?php echo $statusIconColor ?>"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">สถานะปัจจุบัน</h3>
                                    <p class="text-gray-600"><?php echo e($currentStatus['description']) ?></p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor ?>">
                                <?php echo e($currentStatus['label']) ?>
                            </span>
                        </div>

                        <!-- Status Steps -->
                        <div class="relative">
                            <!-- Timeline Line -->
                            <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                            <!-- Steps -->
                            <?php
                                $steps = [
                                    [
                                        'status'      => 'pending',
                                        'label'       => 'รอการยืนยัน',
                                        'description' => 'ส่งคำขอการจองและรอการตรวจสอบ',
                                        'icon'        => 'clock',
                                        'date'        => date('d/m/Y H:i', strtotime($booking['createdAt'] ?? 'now')),
                                    ],
                                    [
                                        'status'      => 'confirmed',
                                        'label'       => 'ยืนยันการจอง',
                                        'description' => 'เจ้าหน้าที่ยืนยันการจองแล้ว',
                                        'icon'        => 'check-circle',
                                        'date'        => $booking['status'] === 'confirmed' || in_array($booking['status'], ['in_progress', 'completed']) ? date('d/m/Y H:i', strtotime($booking['updatedAt'] ?? 'now')) : null,
                                    ],
                                    [
                                        'status'      => 'completed',
                                        'label'       => 'เสร็จสิ้น',
                                        'description' => 'การเช่าเสร็จสมบูรณ์',
                                        'icon'        => 'circle-check-big',
                                        'date'        => $booking['status'] === 'completed' ? date('d/m/Y H:i', strtotime($booking['updatedAt'] ?? 'now')) : null,
                                    ],
                                ];

                                foreach ($steps as $index => $step):
                                    $isCompleted = array_search($bookingStatus, ['pending', 'confirmed', 'in_progress', 'completed']) >= $index;
                                    $isCurrent   = $step['status'] === $bookingStatus;
                            ?>
                            <div class="relative flex items-start mb-8">
                                <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center">
                                    <div class="relative z-10 w-8 h-8 flex items-center justify-center rounded-full
                                        <?php echo $isCompleted ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' ?>">
                                        <i data-lucide="<?php echo $step['icon'] ?>" class="h-4 w-4"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-semibold text-gray-900 <?php echo $isCurrent ? 'text-primary-600' : '' ?>">
                                            <?php echo e($step['label']) ?>
                                        </h4>
                                        <?php if ($step['date']): ?>
                                            <span class="text-sm text-gray-500"><?php echo e($step['date']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-gray-600 text-sm mt-1"><?php echo e($step['description']) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <i data-lucide="clipboard-list" class="h-6 w-6 text-primary-600 mr-2"></i>
                            รายละเอียดการจอง
                        </h2>

                        <?php if ($canEdit): ?>
                        <button onclick="openEditModal()"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                            <i data-lucide="edit" class="h-4 w-4 mr-2"></i>
                            แก้ไขรายละเอียด
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-6">
                        <!-- Motorcycle Info -->
                        <div class="border-b pb-6">
                            <h3 class="font-semibold text-gray-900 mb-4">ข้อมูลรถ</h3>
                            <div class="flex items-start gap-4">
                                <img src="<?php echo e($motorcycle['imageUrl'] ?? 'assets/img/default-bike.svg') ?>"
                                     alt="Motorcycle"
                                     class="w-24 h-24 object-cover rounded-lg"
                                     onerror="this.src='assets/img/default-bike.svg'">
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-900 text-lg">
                                        <?php echo e(($motorcycle['brand'] ?? '') . ' ' . ($motorcycle['model'] ?? '')) ?>
                                    </h4>
                                    <div class="grid grid-cols-2 gap-4 mt-3">
                                        <div>
                                            <p class="text-sm text-gray-600">ขนาดเครื่องยนต์</p>
                                            <p class="font-semibold"><?php echo e($motorcycle['engineCc'] ?? 'N/A') ?> cc</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">ป้ายทะเบียน</p>
                                            <p class="font-semibold"><?php echo e($motorcycle['license_plate'] ?? 'N/A') ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">สี</p>
                                            <p class="font-semibold"><?php echo e($motorcycle['color'] ?? 'N/A') ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">ราคาต่อวัน</p>
                                            <p class="font-semibold">฿<?php echo number_format($motorcycle['price_per_day'] ?? 0) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="border-b pb-6">
                            <h3 class="font-semibold text-gray-900 mb-4">วันและเวลา</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="bg-primary-100 p-2 rounded-full">
                                            <i data-lucide="calendar" class="h-5 w-5 text-primary-600"></i>
                                        </div>
                                        <h4 class="font-semibold text-gray-900">รับรถ</h4>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($booking['startDate'] ?? $booking['startDatetime'])) ?>
                                    </p>
                                    <p class="text-gray-600 mt-1">
                                        เวลา <?php echo date('H:i', strtotime($booking['startDate'] ?? $booking['startDatetime'])) ?> น.
                                    </p>
                                    <p class="text-sm text-gray-500 mt-2">
                                        <i data-lucide="map-pin" class="h-4 w-4 inline mr-1"></i>
                                        <?php echo e($booking['pickupLocation'] ?? 'ไม่ระบุ') ?>
                                    </p>
                                    <?php if (! empty($booking['pickupDetails'])): ?>
                                    <div class="mt-2 p-3 bg-blue-50 rounded-lg">
                                        <p class="text-sm text-blue-700">
                                            <i data-lucide="info" class="h-4 w-4 inline mr-1"></i>
                                            <?php echo nl2br(e($booking['pickupDetails'])) ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="bg-primary-100 p-2 rounded-full">
                                            <i data-lucide="calendar" class="h-5 w-5 text-primary-600"></i>
                                        </div>
                                        <h4 class="font-semibold text-gray-900">คืนรถ</h4>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($booking['endDate'] ?? $booking['endDatetime'])) ?>
                                    </p>
                                    <p class="text-gray-600 mt-1">
                                        เวลา <?php echo date('H:i', strtotime($booking['endDate'] ?? $booking['endDatetime'])) ?> น.
                                    </p>
                                    <p class="text-sm text-gray-500 mt-2">
                                        <i data-lucide="map-pin" class="h-4 w-4 inline mr-1"></i>
                                        <?php echo e($booking['returnLocation'] ?? 'ไม่ระบุ') ?>
                                    </p>
                                    <?php if (! empty($booking['returnDetails'])): ?>
                                    <div class="mt-2 p-3 bg-blue-50 rounded-lg">
                                        <p class="text-sm text-blue-700">
                                            <i data-lucide="info" class="h-4 w-4 inline mr-1"></i>
                                            <?php echo nl2br(e($booking['returnDetails'])) ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-4">สรุปราคา</h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">จำนวนวันเช่า:</span>
                                        <span class="font-semibold"><?php echo e($booking['totalDays'] ?? 0) ?> วัน</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">ราคารวม:</span>
                                        <span class="font-semibold">฿<?php echo number_format($booking['totalPrice'] ?? 0) ?></span>
                                    </div>
                                    <?php if (($booking['discountAmount'] ?? 0) > 0): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">ส่วนลด:</span>
                                        <span class="font-semibold text-green-600">-฿<?php echo number_format($booking['discountAmount'] ?? 0) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="border-t pt-3 flex justify-between text-lg font-bold">
                                        <span class="text-gray-900">ราคาสุทธิ:</span>
                                        <span class="text-primary-600">฿<?php echo number_format($booking['finalPrice'] ?? 0) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Actions & Payment Status -->
            <div class="space-y-6">
                <!-- Payment Status -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="credit-card" class="h-5 w-5 text-primary-600 mr-2"></i>
                        สถานะการชำระเงิน
                    </h3>

                    <?php if ($payment): ?>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">สถานะ:</span>
                                <span class="px-3 py-1 rounded-full text-sm font-medium
                                    <?php echo $payment['paymentStatus'] === 'verified' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?php echo $payment['paymentStatus'] === 'verified' ? 'ชำระแล้ว' : 'รอตรวจสอบ' ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">วิธีการชำระ:</span>
                                <span class="font-semibold"><?php echo e($payment['paymentMethod'] ?? 'N/A') ?></span>
                            </div>
                            <?php if ($payment['paymentDate']): ?>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">วันที่ชำระ:</span>
                                <span class="font-semibold"><?php echo date('d/m/Y H:i', strtotime($payment['paymentDate'])) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center justify-between text-lg font-bold pt-3 border-t">
                                <span class="text-gray-900">จำนวนเงิน:</span>
                                <span class="text-primary-600">฿<?php echo number_format($payment['amount'] ?? 0) ?></span>
                            </div>

                            <?php if (! empty($payment['slipImageUrl'])): ?>
                            <div class="mt-4">
                                <p class="text-sm text-gray-600 mb-2">สลิปการชำระเงิน:</p>
                                <a href="<?php echo e($payment['slipImageUrl']) ?>"
                                   target="_blank"
                                   class="inline-flex items-center text-primary-600 hover:text-primary-700">
                                    <i data-lucide="external-link" class="h-4 w-4 mr-1"></i>
                                    ดูสลิป
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($canPayDeposit): ?>
                        <div class="text-center py-6">
                            <div class="mb-4">
                                <i data-lucide="credit-card" class="h-12 w-12 text-yellow-500 mx-auto"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">รอชำระมัดจำ</h4>
                            <p class="text-gray-600 text-sm mb-4">
                                ชำระมัดจำ <?php echo number_format((float) ($booking['depositAmount'] ?? DEPOSIT_AMOUNT)); ?> บาท เพื่อยืนยันการจอง
                            </p>
                            <a href="index.php?page=payment&reservation=<?php echo e($reservationId) ?>"
                               class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="credit-card" class="h-5 w-5"></i> ชำระมัดจำทันที
                            </a>
                            <p class="text-xs text-gray-500 mt-3">
                                *ต้องชำระก่อนวันรับรถอย่างน้อย 24 ชั่วโมง
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <p class="text-gray-600">ไม่มีข้อมูลการชำระเงิน</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="zap" class="h-5 w-5 text-primary-600 mr-2"></i>
                        การดำเนินการด่วน
                    </h3>

                    <div class="space-y-3">
                        <?php if ($canPayDeposit): ?>
                        <a href="index.php?page=payment&reservation=<?php echo e($reservationId) ?>"
                           class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="credit-card" class="h-5 w-5"></i> ชำระมัดจำ
                        </a>
                        <?php endif; ?>

                        <?php if ($canEdit): ?>
                        <button onclick="openEditModal()"
                                class="w-full bg-primary-600 hover:bg-primary-700 text-white py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="edit" class="h-5 w-5"></i> แก้ไขรายละเอียด
                        </button>
                        <?php endif; ?>

                        <?php if ($canCancel): ?>
                        <button onclick="openCancelModal()"
                                class="w-full bg-danger-600 hover:bg-danger-700 text-white py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="circle-x" class="h-5 w-5"></i> ยกเลิกการจอง
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-2">
                            <?php echo e($cancelMessage) ?>
                        </p>
                        <?php endif; ?>

                        <a href="index.php?page=my-bookings"
                           class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="arrow-left" class="h-5 w-5"></i> กลับไปการจองของฉัน
                        </a>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="phone" class="h-5 w-5 text-primary-600 mr-2"></i>
                        ติดต่อสนับสนุน
                    </h4>
                    <div class="space-y-3">
                        <a href="tel:<?php echo e(preg_replace('/\D/', '', SUPPORT_PHONE)); ?>" class="flex items-center gap-3 text-gray-700 hover:text-primary-600 transition">
                            <div class="bg-primary-50 p-2 rounded-lg">
                                <i data-lucide="phone-call" class="h-4 w-4 text-primary-600"></i>
                            </div>
                            <div>
                                <p class="font-medium"><?php echo e(SUPPORT_PHONE); ?></p>
                                <p class="text-sm text-gray-500">สายด่วนสนับสนุน</p>
                            </div>
                        </a>
                        <a href="mailto:<?php echo e(SUPPORT_EMAIL); ?>" class="flex items-center gap-3 text-gray-700 hover:text-primary-600 transition">
                            <div class="bg-primary-50 p-2 rounded-lg">
                                <i data-lucide="mail" class="h-4 w-4 text-primary-600"></i>
                            </div>
                            <div>
                                <p class="font-medium"><?php echo e(SUPPORT_EMAIL); ?></p>
                                <p class="text-sm text-gray-500">อีเมลสนับสนุน</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Booking Modal -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <form method="POST" action="">
<?php echo Csrf::field(); ?>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-danger-100 p-2 rounded-full mr-3">
                        <i data-lucide="alert-triangle" class="h-6 w-6 text-danger-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">ยกเลิกการจอง</h3>
                </div>

                <p class="text-gray-600 mb-6">
                    คุณกำลังจะยกเลิกการจองนี้ กรุณาระบุเหตุผลในการยกเลิก
                </p>

                <input type="hidden" name="action" value="cancel_booking">

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2">เหตุผลในการยกเลิก</label>
                    <div class="space-y-2 mb-3">
                        <label class="flex items-center">
                            <input type="radio" name="cancel_reason" value="เปลี่ยนแผนการเดินทาง" class="mr-2">
                            <span>เปลี่ยนแผนการเดินทาง</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="cancel_reason" value="พบตัวเลือกที่ดีกว่า" class="mr-2">
                            <span>พบตัวเลือกที่ดีกว่า</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="cancel_reason" value="มีเหตุจำเป็นส่วนตัว" class="mr-2">
                            <span>มีเหตุจำเป็นส่วนตัว</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="cancel_reason" value="อื่นๆ" class="mr-2" id="other_reason">
                            <span>อื่นๆ</span>
                        </label>
                    </div>

                    <textarea id="custom_reason"
                              name="custom_reason"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent hidden"
                              placeholder="โปรดระบุเหตุผล..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCancelModal()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-danger-600 hover:bg-danger-700 text-white rounded-lg transition-colors">
                        ยืนยันการยกเลิก
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Booking Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <form method="POST" action="">
<?php echo Csrf::field(); ?>
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-primary-100 p-2 rounded-full mr-3">
                            <i data-lucide="edit" class="h-6 w-6 text-primary-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">แก้ไขรายละเอียดการจอง</h3>
                    </div>
                    <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-6 w-6"></i>
                    </button>
                </div>

                <input type="hidden" name="action" value="update_booking">

                <div class="space-y-6">
                    <!-- Pickup Location -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            สถานที่รับรถ <span class="text-danger-600">*</span>
                        </label>
                        <select name="pickup_location" id="pickup_location"
                                required
                                onchange="togglePickupDetails()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="">เลือกสถานที่รับรถ</option>
                            <option value="ร้านเทมป์เทชัน" <?php if ($pickupLocation === 'ร้านเทมป์เทชัน') {
                                                                                               echo 'selected';
                                                                                       }
                                                                                       ?>>
                                ร้านเทมป์เทชัน
                            </option>
                            <option value="สนามบินหาดใหญ่" <?php if ($pickupLocation === 'สนามบินหาดใหญ่') {
                                                                                               echo 'selected';
                                                                                       }
                                                                                       ?>>
                                สนามบินหาดใหญ่
                            </option>
                            <option value="สถานีรถไฟหาดใหญ่" <?php if ($pickupLocation === 'สถานีรถไฟหาดใหญ่') {
                                                                                                     echo 'selected';
                                                                                             }
                                                                                             ?>>
                                สถานีรถไฟหาดใหญ่
                            </option>
                            <option value="สถานีขนส่งหาดใหญ่" <?php if ($pickupLocation === 'สถานีขนส่งหาดใหญ่') {
                                                                                                        echo 'selected';
                                                                                                }
                                                                                                ?>>
                                สถานีขนส่งหาดใหญ่
                            </option>
                            <option value="โรงแรมในเมืองหาดใหญ่" <?php if ($pickupLocation === 'โรงแรมในเมืองหาดใหญ่') {
                                                                                                                 echo 'selected';
                                                                                                         }
                                                                                                         ?>>
                                โรงแรมในเมืองหาดใหญ่
                            </option>
                            <option value="อื่นๆ" <?php if ($pickupLocation === 'อื่นๆ') {
                                                                    echo 'selected';
                                                            }
                                                            ?>>
                                อื่นๆ (ระบุในช่องรายละเอียด)
                            </option>
                        </select>
                    </div>

                    <!-- Pickup Details -->
                    <div id="pickup_details_container">
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            รายละเอียดเพิ่มเติม (รับรถ)
                        </label>
                        <textarea name="pickup_details"
                                  id="pickup_details"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                  placeholder="เช่น ต้องการติดตั้งกระเป๋า, รถสีขาว, ฯลฯ"><?php echo e($booking['pickupDetails'] ?? '') ?></textarea>
                    </div>

                    <!-- Return Location -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            สถานที่คืนรถ <span class="text-danger-600">*</span>
                        </label>
                        <select name="return_location" id="return_location"
                                required
                                onchange="toggleReturnDetails()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="">เลือกสถานที่คืนรถ</option>
                            <option value="ร้านเทมป์เทชัน" <?php if ($returnLocation === 'ร้านเทมป์เทชัน') {
                                                                                               echo 'selected';
                                                                                       }
                                                                                       ?>>
                                ร้านเทมป์เทชัน
                            </option>
                            <option value="สนามบินหาดใหญ่" <?php if ($returnLocation === 'สนามบินหาดใหญ่') {
                                                                                               echo 'selected';
                                                                                       }
                                                                                       ?>>
                                สนามบินหาดใหญ่
                            </option>
                            <option value="สถานีรถไฟหาดใหญ่" <?php if ($returnLocation === 'สถานีรถไฟหาดใหญ่') {
                                                                                                     echo 'selected';
                                                                                             }
                                                                                             ?>>
                                สถานีรถไฟหาดใหญ่
                            </option>
                            <option value="สถานีขนส่งหาดใหญ่" <?php if ($returnLocation === 'สถานีขนส่งหาดใหญ่') {
                                                                                                        echo 'selected';
                                                                                                }
                                                                                                ?>>
                                สถานีขนส่งหาดใหญ่
                            </option>
                            <option value="โรงแรมในเมืองหาดใหญ่" <?php if ($returnLocation === 'โรงแรมในเมืองหาดใหญ่') {
                                                                                                                 echo 'selected';
                                                                                                         }
                                                                                                         ?>>
                                โรงแรมในเมืองหาดใหญ่
                            </option>
                            <option value="อื่นๆ" <?php if ($returnLocation === 'อื่นๆ') {
                                                                    echo 'selected';
                                                            }
                                                            ?>>
                                อื่นๆ (ระบุในช่องรายละเอียด)
                            </option>
                        </select>
                    </div>

                    <!-- Return Details -->
                    <div id="return_details_container">
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            รายละเอียดเพิ่มเติม (คืนรถ)
                        </label>
                        <textarea name="return_details"
                                  id="return_details"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                  placeholder="เช่น ต้องการคืนรถที่สนามบิน, จะส่งคีย์ในตู้เซฟ, ฯลฯ"><?php echo e($booking['returnDetails'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Handle reason selection in cancel modal
    const otherReasonRadio = document.getElementById('other_reason');
    const customReasonTextarea = document.getElementById('custom_reason');
    const reasonRadios = document.querySelectorAll('input[name="cancel_reason"]');

    reasonRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'อื่นๆ') {
                customReasonTextarea.classList.remove('hidden');
                customReasonTextarea.required = true;
            } else {
                customReasonTextarea.classList.add('hidden');
                customReasonTextarea.required = false;
            }
        });
    });

    // Setup form submission to handle custom reason
    const cancelForm = document.querySelector('#cancelModal form');
    if (cancelForm) {
        cancelForm.addEventListener('submit', function(e) {
            const selectedReason = document.querySelector('input[name="cancel_reason"]:checked');
            if (selectedReason && selectedReason.value === 'อื่นๆ') {
                const customReason = document.querySelector('textarea[name="custom_reason"]').value;
                if (customReason.trim()) {
                    // Create hidden input for the actual reason
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'cancel_reason';
                    hiddenInput.value = customReason;
                    this.appendChild(hiddenInput);
                }
            }
        });
    }

    // ตรวจสอบสถานะเริ่มต้นของ Pickup Details
    togglePickupDetails();

    // ตรวจสอบสถานะเริ่มต้นของ Return Details
    toggleReturnDetails();
});

// ฟังก์ชันซ่อน/แสดง Pickup Details
function togglePickupDetails() {
    const pickupLocation = document.getElementById('pickup_location');
    const pickupDetailsContainer = document.getElementById('pickup_details_container');

    if (pickupLocation && pickupDetailsContainer) {
        if (pickupLocation.value === 'ร้านเทมป์เทชัน') {
            pickupDetailsContainer.style.display = 'none';
        } else {
            pickupDetailsContainer.style.display = 'block';
        }
    }
}

// ฟังก์ชันซ่อน/แสดง Return Details
function toggleReturnDetails() {
    const returnLocation = document.getElementById('return_location');
    const returnDetailsContainer = document.getElementById('return_details_container');

    if (returnLocation && returnDetailsContainer) {
        if (returnLocation.value === 'ร้านเทมป์เทชัน') {
            returnDetailsContainer.style.display = 'none';
        } else {
            returnDetailsContainer.style.display = 'block';
        }
    }
}

function openCancelModal() {
    document.getElementById('cancelModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function openEditModal() {
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // เรียกใช้ toggle อีกครั้งเมื่อเปิด Modal เพื่อให้สถานะถูกต้อง
    setTimeout(() => {
        togglePickupDetails();
        toggleReturnDetails();
    }, 50);
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const cancelModal = document.getElementById('cancelModal');
    const editModal = document.getElementById('editModal');

    if (event.target === cancelModal) {
        closeCancelModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
}
</script>
