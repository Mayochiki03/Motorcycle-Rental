<?php
    // views/pages/PaymentPages.php
    // หน้าชำระเงินมัดจำ (พร้อมเพย์) และแนบสลิปโอนเงิน

    if (! Auth::check()) {
    $_SESSION['redirect_url']  = $_SERVER['REQUEST_URI'];
    $_SESSION['flash_message'] = [
        'type'    => 'error',
        'message' => 'กรุณาเข้าสู่ระบบก่อนทำการชำระเงิน',
    ];
    header("Location: login.php");
    exit;
    }

    require_once APP_ROOT . '/src/Services/BookingService.php';
    require_once APP_ROOT . '/src/Services/PaymentService.php';
    require_once APP_ROOT . '/src/Services/MotorcycleService.php';

    $reservationId = $_GET['reservation'] ?? '';
    $booking       = null;
    $error         = '';
    $success       = '';

    // ดึงข้อมูลการจอง
    if ($reservationId) {
    $booking = BookingService::getBookingById($reservationId);

    // ต้องเป็นการจองของผู้ใช้ที่ล็อกอินอยู่เท่านั้น (ใช้ข้อความเดียวกับกรณีไม่พบ เพื่อไม่เปิดเผยว่ามีรหัสนี้อยู่)
    if (! $booking || (string) $booking['customerId'] !== Auth::id()) {
        $booking = null;
        $error   = 'ไม่พบข้อมูลการจอง';
    } else {
        // ดึงข้อมูลรถ
        if ($booking['motorcycleId']) {
            $motorcycleData = MotorcycleService::getMotorcycleById($booking['motorcycleId']);
            if ($motorcycleData) {
                $booking['motorcycleBrand']    = $motorcycleData['brand'] ?? '';
                $booking['motorcycleModel']    = $motorcycleData['model'] ?? '';
                $booking['motorcycleEngineCc'] = $motorcycleData['engineCc'] ?? '';
                $booking['motorcycleImageUrl'] = $motorcycleData['imageUrl'] ?? '';
            }
        }
    }
    }

    // ตรวจสอบ payment ที่มีอยู่
    if ($booking && ! $error) {
    $existingPayment = PaymentService::getPaymentByReservation($reservationId);
    if ($existingPayment) {
        $error = 'การจองนี้ได้ชำระมัดจำแล้ว';
    }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && ! $error) {

    $paymentMethod = 'PROMPTPAY';

    try {
        // 1. สร้าง payment (DB)
        $payment = PaymentService::createPayment([
            'reservationId' => $reservationId,
            'customerId'    => Auth::id(),
            'paymentMethod' => $paymentMethod,
        ]);

        if (! $payment || ! isset($payment['paymentId'])) {
            throw new Exception('สร้างรายการชำระเงินไม่สำเร็จ');
        }

        // 2. อัปโหลดสลิป (แยก try/catch)
        if (
            isset($_FILES['payment_slip']) &&
            $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK
        ) {
            try {
                PaymentService::uploadSlipFromPayment(
                    $reservationId,
                    $_FILES['payment_slip']
                );
            } catch (Exception $e) {
                // สำคัญ: log ชัด แต่ไม่ทำให้ payment พัง
                error_log('UPLOAD SLIP ERROR: ' . $e->getMessage());
                $error = 'อัปโหลดสลิปไม่สำเร็จ: ' . $e->getMessage();
                // ถ้าอยาก “บังคับว่าต้องอัปโหลด” ให้ throw ต่อ
                // throw $e;
            }
        }

        // 3. สำเร็จ → redirect
        $_SESSION['flash_message'] = [
            'type'    => 'success',
            'message' => 'ชำระมัดจำสำเร็จ!',
        ];

        header("Location: index.php?page=my-bookings");
        exit;

    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        error_log('PAYMENT ERROR: ' . $e->getMessage());
    }
    }

?>

<!-- ส่วนหัวของหน้านี้ (สไตล์/คอนฟิก Tailwind เฉพาะหน้า) -->
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
                        }
                    }
                }
            }
        }
    </script>

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
                    ชำระมัดจำ
                </li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-3">ชำระมัดจำ</h1>
            <p class="text-lg text-gray-600">ชำระมัดจำ <?php echo number_format((float) ($booking['depositAmount'] ?? DEPOSIT_AMOUNT)); ?> บาท เพื่อยืนยันการจองรถของคุณ</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i data-lucide="alert-circle" class="h-5 w-5 mr-2"></i>
                    <span><?php echo e($error); ?></span>
                </div>
                <?php if (strpos($error, 'ไม่พบข้อมูลการจอง') !== false): ?>
                    <a href="index.php?page=my-bookings" class="text-red-800 underline ml-7 mt-1 inline-block">ดูการจองทั้งหมด</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i data-lucide="check-circle" class="h-5 w-5 mr-2"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (! $error && $booking): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Payment Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Payment Method Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <i data-lucide="smartphone" class="h-6 w-6 text-primary-600 mr-2"></i>
                            ชำระผ่านพร้อมเพย์
                        </h2>

                        <form method="POST" action="" enctype="multipart/form-data" class="space-y-8">
<?php echo Csrf::field(); ?>
                            <!-- PromptPay Details -->
                            <div class="bg-gradient-to-r from-primary-50 to-blue-50 border border-primary-200 rounded-xl p-6 space-y-5">
                                <div class="flex items-start gap-4">
                                    <div class="bg-primary-100 p-3 rounded-full">
                                        <i data-lucide="qr-code" class="h-6 w-6 text-primary-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 text-lg">สแกนเพื่อชำระเงิน</h3>
                                        <p class="text-gray-600 mt-1">สแกน QR Code ด้านล่างผ่านแอปธนาคารหรือแอปพร้อมเพย์</p>
                                    </div>
                                </div>

                                <div class="text-sm text-gray-700 space-y-3 bg-white p-4 rounded-lg border">
                                    <div class="flex justify-between">
                                        <span class="font-medium text-gray-600">ผู้รับเงิน:</span>
                                        <span class="font-semibold"><?php echo e(PAYEE_NAME); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-medium text-gray-600">เบอร์โทรศัพท์:</span>
                                        <span class="font-semibold"><?php echo e(PAYEE_PHONE); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-medium text-gray-600">จำนวนเงิน:</span>
                                        <span class="font-bold text-primary-600 text-lg"><?php echo number_format((float) ($booking['depositAmount'] ?? DEPOSIT_AMOUNT)); ?> บาท</span>
                                    </div>
                                </div>

                                <div class="flex justify-center pt-2">
                                    <div class="bg-white border-2 border-primary-300 rounded-xl p-5 shadow-md">
                                        <div class="qr-container w-80 h-80 bg-white rounded-2xl shadow-xl overflow-hidden border-4 border-white">
                                            <img src="uploads/qrcode/qrcode1.jpg"
                                                alt="QR Code พร้อมเพย์"
                                                class="w-full h-full object-contain p-2">
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <input type="hidden" name="payment_method" value="PROMPTPAY">

                            <!-- Upload Slip Section -->
                            <div class="bg-white border border-gray-200 rounded-xl p-6">
                                <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                                    <i data-lucide="upload" class="h-5 w-5 text-primary-600 mr-2"></i>
                                    อัพโหลดหลักฐานการชำระเงิน
                                </h3>

                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-primary-400 transition duration-300"
                                     id="upload-area">
                                    <input type="file" name="payment_slip" id="payment_slip"
                                           accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" onchange="handleFileSelect(event)">

                                    <label for="payment_slip" class="cursor-pointer block">
                                        <div class="mx-auto w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center mb-4">
                                            <i data-lucide="upload-cloud" class="h-8 w-8 text-primary-600"></i>
                                        </div>
                                        <p class="text-gray-700 font-medium text-lg mb-2">อัพโหลดสลิปการโอน</p>
                                        <p class="text-gray-500 mb-4">
                                            ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์
                                        </p>
                                        <div class="inline-flex items-center gap-2 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg">
                                            <i data-lucide="folder-open" class="h-4 w-4"></i>
                                            <span>เลือกไฟล์</span>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-4">
                                            รองรับไฟล์รูปภาพ (JPG, PNG, GIF, WebP) ขนาดไม่เกิน 5MB
                                        </p>
                                    </label>

                                    <div id="file-preview" class="mt-6 hidden">
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-green-100 p-2 rounded-full">
                                                    <i data-lucide="check-circle" class="h-5 w-5 text-green-600"></i>
                                                </div>
                                                <div>
                                                    <p id="file-name" class="font-medium text-green-800"></p>
                                                    <p id="file-size" class="text-sm text-green-600"></p>
                                                </div>
                                            </div>
                                            <button type="button" onclick="removeFile()" class="text-gray-400 hover:text-gray-600">
                                                <i data-lucide="x" class="h-5 w-5"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="bg-white border border-gray-200 rounded-xl p-6">
                                <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                                    <i data-lucide="dollar-sign" class="h-5 w-5 text-primary-600 mr-2"></i>
                                    จำนวนเงิน
                                </h3>

                                <div class="relative">
                                    <div class="flex items-center bg-gray-50 border border-gray-300 rounded-lg px-4 py-3">
                                        <span class="text-gray-700 mr-3">฿</span>
                                        <input type="number" value="<?php echo (float) ($booking['depositAmount'] ?? DEPOSIT_AMOUNT); ?>" step="0.01" min="0" readonly
                                               class="w-full bg-transparent text-2xl font-bold text-gray-900 focus:outline-none">
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2 ml-1">
                                        มัดจำจองรถ <?php echo number_format((float) ($booking['depositAmount'] ?? DEPOSIT_AMOUNT)); ?> บาท (ส่วนที่เหลือจ่ายตอนรับรถ)
                                    </p>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit"
                                    class="w-full bg-gradient-to-r from-success-600 to-green-600 hover:from-success-700 hover:to-green-700 text-white py-4 px-6 rounded-xl font-semibold text-lg transition-all transform hover:scale-[1.02] shadow-lg flex items-center justify-center gap-3">
                                <i data-lucide="check-circle" class="h-6 w-6"></i>
                                ยืนยันการชำระมัดจำ
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Help Info -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                    <h4 class="font-semibold text-blue-900 mb-3 flex items-center">
                        <i data-lucide="info" class="h-5 w-5 text-blue-600 mr-2"></i>
                        คำแนะนำการชำระเงิน
                    </h4>
                    <ul class="space-y-2 text-blue-700">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle" class="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0"></i>
                            <span>ชำระมัดจำภายใน 24 ชั่วโมงเพื่อยืนยันการจอง</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle" class="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0"></i>
                            <span>เก็บสลิปการโอนไว้เป็นหลักฐานจนกว่าการจองจะเสร็จสมบูรณ์</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle" class="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0"></i>
                            <span>สามารถยกเลิกการจองได้ก่อน 24 ชั่วโมง (คืนเงินมัดจำเต็มจำนวน)</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Booking Summary -->
            <div class="space-y-6">
                <!-- Booking Details Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-600 to-blue-600 p-6 text-white">
                        <h3 class="font-bold text-xl mb-2 flex items-center">
                            <i data-lucide="calendar" class="h-5 w-5 mr-2"></i>
                            สรุปการจอง
                        </h3>
                        <p class="text-primary-100">รหัสการจอง: <span class="font-mono font-bold"><?php echo e($booking['reservationId']); ?></span></p>
                    </div>

                    <div class="p-6">
                        <!-- Motorcycle Info -->
                        <div class="flex items-start gap-4 mb-6 pb-6 border-b">
                            <?php if ($booking['motorcycleImageUrl']): ?>
                                <img src="<?php echo e($booking['motorcycleImageUrl']); ?>"
                                     alt="<?php echo e(($booking['motorcycleBrand'] ?? '') . ' ' . ($booking['motorcycleModel'] ?? '')); ?>"
                                     class="w-20 h-20 object-cover rounded-lg">
                            <?php else: ?>
                                <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <i data-lucide="bike" class="h-8 w-8 text-gray-400"></i>
                                </div>
                            <?php endif; ?>

                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900">
                                    <?php
                                        $brand = $booking['motorcycleBrand'] ?? '';
                                        $model = $booking['motorcycleModel'] ?? $booking['motorcycleId'] ?? 'N/A';
                                        echo e($brand . ' ' . $model);
                                    ?>
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    <?php echo e($booking['motorcycleEngineCc'] ?? ''); ?> cc
                                </p>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600 mb-1 flex items-center">
                                        <i data-lucide="calendar" class="h-3 w-3 mr-1"></i>
                                        วันที่รับรถ
                                    </p>
                                    <p class="font-semibold text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($booking['startDate'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1 flex items-center">
                                        <i data-lucide="calendar" class="h-3 w-3 mr-1"></i>
                                        วันที่คืนรถ
                                    </p>
                                    <p class="font-semibold text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($booking['endDate'])); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600 mb-1 flex items-center">
                                        <i data-lucide="clock" class="h-3 w-3 mr-1"></i>
                                        เวลารับรถ
                                    </p>
                                    <p class="font-semibold text-gray-900">
                                        <?php echo date('H:i', strtotime($booking['startDate'])); ?> น.
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1 flex items-center">
                                        <i data-lucide="clock" class="h-3 w-3 mr-1"></i>
                                        เวลาคืนรถ
                                    </p>
                                    <p class="font-semibold text-gray-900">
                                        <?php echo date('H:i', strtotime($booking['endDate'])); ?> น.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="mt-6 pt-6 border-t">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">จำนวนวัน:</span>
                                    <span class="font-semibold"><?php echo e($booking['totalDays'] ?? 0); ?> วัน</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">ราคารวม:</span>
                                    <span class="font-semibold">฿<?php echo number_format($booking['totalPrice'] ?? 0); ?></span>
                                </div>
                                <?php if (($booking['discountAmount'] ?? 0) > 0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">ส่วนลด:</span>
                                    <span class="font-semibold text-green-600">-฿<?php echo number_format($booking['discountAmount'] ?? 0); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="flex justify-between text-lg font-bold pt-3 border-t">
                                    <span class="text-gray-900">ราคาสุทธิ:</span>
                                    <span class="text-primary-600">฿<?php echo number_format($booking['finalPrice'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Deposit Amount -->
                        <div class="mt-6 p-4 bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-orange-800 font-semibold">มัดจำที่ต้องชำระ</p>
                                    <p class="text-sm text-orange-600 mt-1">*ส่วนที่เหลือจ่ายที่ร้าน</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-orange-600">฿<?php echo number_format((float) ($booking['depositAmount'] ?? DEPOSIT_AMOUNT)); ?></p>
                                </div>
                            </div>
                        </div>
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
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Drag and drop functionality
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('payment_slip');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadArea.classList.add('border-primary-500', 'bg-primary-50');
    }

    function unhighlight() {
        uploadArea.classList.remove('border-primary-500', 'bg-primary-50');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFileSelect({ target: fileInput });
    }
});

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file size (5MB max)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        alert('ไฟล์ต้องมีขนาดไม่เกิน 5MB');
        event.target.value = '';
        return;
    }

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('ไฟล์ต้องเป็นรูปภาพ (JPG, PNG, GIF, WebP) เท่านั้น');
        event.target.value = '';
        return;
    }

    // Show preview
    const preview = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');

    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    preview.classList.remove('hidden');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function removeFile() {
    const fileInput = document.getElementById('payment_slip');
    const preview = document.getElementById('file-preview');

    fileInput.value = '';
    preview.classList.add('hidden');
}
</script>
