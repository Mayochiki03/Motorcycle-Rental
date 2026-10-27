<?php
    // views/pages/BookingPages.php
    // หน้าจองรถ: เลือกวันรับ-คืน สถานที่ ส่วนลด แล้วสร้างการจอง

    require_once APP_ROOT . '/src/Services/BookingService.php';
    require_once APP_ROOT . '/src/Services/MotorcycleService.php';
    require_once APP_ROOT . '/src/Services/DiscountService.php';

    // เช็ค user login
    if (! Auth::check()) {
        $_SESSION['redirect_url']  = $_SERVER['REQUEST_URI'];
        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง',
        ];
        header("Location: login.php");
        exit;
    }

    $error       = '';
    $success     = '';
    $today       = date('Y-m-d');
    $currentTime = date('H:i');

    $motorcycle_id = $_GET['id'] ?? null;
    $motorcycle    = $motorcycle_id ? MotorcycleService::getMotorcycleById($motorcycle_id) : null;
    $customerId    = Auth::id();

    // รับวันที่จากหน้ารถ (ถ้ามี)
    $startDateFromUrl = $_GET['start_date'] ?? '';
    $endDateFromUrl   = $_GET['end_date'] ?? '';

    if (! $motorcycle_id || ! $motorcycle) {
        $error = 'ไม่พบรหัสรถหรือข้อมูลรถจักรยานยนต์';
    }

    // ฟังก์ชันคำนวณราคาตามวัน
    function calculatePrice($startDate, $endDate, $pricePerDay)
    {
        $start    = new DateTime($startDate);
        $end      = new DateTime($endDate);
        $interval = $start->diff($end);
        $days     = $interval->days + 1; // รวมวันเริ่มต้นด้วย

        $totalPrice    = $days * $pricePerDay;
        $depositAmount = DEPOSIT_AMOUNT; // ค่ามัดจำ ตั้งที่ DEPOSIT_AMOUNT ใน .env

        // ราคาที่ต้องจ่ายตอนรับรถ (หักมัดจำแล้ว)
        $remainingAmount = $totalPrice - $depositAmount;

        return [
            'days'            => $days,
            'totalPrice'      => $totalPrice,
            'depositAmount'   => $depositAmount,
            'remainingAmount' => $remainingAmount > 0 ? $remainingAmount : 0,
            'pricePerDay'     => $pricePerDay,
        ];
    }

    // ตรวจสอบวันที่รับ-คืน (เฉพาะเมื่อกด submit แล้ว)
    $dateError = '';
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $startDateRaw = $_POST['start_date'] ?? '';
        $endDateRaw   = $_POST['end_date'] ?? '';
        
        if ($startDateRaw && $endDateRaw) {
            // ตรวจรูปแบบวันที่ก่อน (กันค่าที่ไม่ใช่วันที่ ซึ่งทำให้ new DateTime() โยน error)
            $startOk = DateTime::createFromFormat('Y-m-d', $startDateRaw);
            $endOk   = DateTime::createFromFormat('Y-m-d', $endDateRaw);

            if (! $startOk || ! $endOk) {
                $dateError = 'รูปแบบวันที่ไม่ถูกต้อง';
            } elseif ($endOk < $startOk) {
                $dateError = 'วันที่คืนรถต้องไม่น้อยกว่าวันที่รับรถ';
            } elseif ($startOk->format('Y-m-d') < $today) {
                $dateError = 'ไม่สามารถจองย้อนหลังได้';
            }
        }
    }

    // POST Booking
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !$dateError) {
        $startDateRaw   = $_POST['start_date'] ?? '';
        $endDateRaw     = $_POST['end_date'] ?? '';
        $pickupLocation = $_POST['pickup_location'] ?? '';
        $returnLocation = $_POST['return_location'] ?? '';
        $pickupTime     = $_POST['pickup_time'] ?? '09:00';
        $returnTime     = $_POST['return_time'] ?? '17:00';

        // เวลาต้องเป็นรูปแบบ HH:MM
        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $pickupTime)) {
            $pickupTime = '09:00';
        }
        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $returnTime)) {
            $returnTime = '17:00';
        }
        $pickupDetails  = trim($_POST['pickup_details'] ?? '');
        $returnDetails  = trim($_POST['return_details'] ?? '');
        $discountCode   = strtoupper(trim($_POST['discount_code'] ?? ''));

        // เพิ่ม: ตรวจสอบ customerId โดยเฉพาะ
        if (!$customerId) {
            $error = 'ไม่พบข้อมูลลูกค้า กรุณาเข้าสู่ระบบใหม่อีกครั้ง';
            error_log("ERROR: customerId is null or empty");
        }
        elseif (empty($startDateRaw) || empty($endDateRaw) || empty($pickupLocation) || empty($returnLocation) || ! $motorcycle || ! $customerId) {
            $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            error_log("ERROR: Missing required fields");
        } else {
            try {
                // ตรวจสอบว่าช่วงวันที่นี้ว่างหรือไม่
                $startObj = new DateTime($startDateRaw);
                $endObj   = new DateTime($endDateRaw);

                // รวมเวลาเข้าไปด้วย
                $startDatetime = $startObj->format('Y-m-d') . ' ' . $pickupTime . ':00';
                $endDatetime   = $endObj->format('Y-m-d') . ' ' . $returnTime . ':00';

                $start     = $startObj->format('Y-m-d');
                $end       = $endObj->format('Y-m-d');
                $diff      = $endObj->diff($startObj);
                $totalDays = $diff->days + 1; // รวมวันเริ่มต้น

                // เช็ควันที่คืนรถต้องอยู่หลังวันที่รับรถ
                if ($totalDays <= 0) {
                    $error = 'วันที่คืนรถต้องอยู่หลังวันที่รับรถ';
                } else {
                    // เช็คว่ารถว่างในช่วงเวลานี้หรือไม่
                    $availableBikes = MotorcycleService::getAvailableMotorcycles($start, $end);
                    $isAvailable    = false;

                    foreach ($availableBikes as $bike) {
                        if ($bike['motorcycleId'] == $motorcycle_id) {
                            $isAvailable = true;
                            break;
                        }
                    }

                    if (! $isAvailable) {
                        $error = 'รถคันนี้ไม่ว่างในช่วงวันที่ที่เลือก กรุณาเลือกวันที่อื่น';
                    } else {
                        // คำนวณราคา
                        $pricePerDay = floatval($motorcycle['pricePerDay']);
                        $priceData   = calculatePrice($start, $end, $pricePerDay);

                        $totalPrice    = $priceData['totalPrice'];
                        $depositAmount = $priceData['depositAmount'];

                        if (empty($error)) {
                            $finalPrice = null;

                            $bookingData = [
                                'customerId'     => $customerId,
                                'motorcycleId'   => $motorcycle['motorcycleId'],
                                'startDate'      => $startDatetime,
                                'endDate'        => $endDatetime,
                                'totalDays'      => $totalDays,
                                'totalPrice'     => $totalPrice,
                                'depositAmount'  => $depositAmount,
                                'finalPrice'     => $finalPrice,
                                'pickupLocation' => $pickupLocation,
                                'returnLocation' => $returnLocation,
                                'pickupDetails'  => $pickupDetails,
                                'returnDetails'  => $returnDetails,
                            ];

                            if (! empty($discountCode)) {
                                $bookingData['discountCode'] = $discountCode;
                            }

                            // Log prepared booking
                            error_log("Booking Data Prepared: " . json_encode($bookingData));

                            $bookingId = BookingService::createBooking($bookingData);

                            if ($bookingId) {
                                $_SESSION['flash_message'] = [
                                    'type'    => 'success',
                                    'message' => 'จองสำเร็จ!',
                                ];
                                header("Location: index.php?page=my-bookings");
                                exit;
                            } else {
                                $error = 'ไม่สามารถทำการจองได้ กรุณาลองใหม่';
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                // ข้อผิดพลาดฐานข้อมูล: บันทึก log แต่ไม่แสดงรายละเอียดให้ผู้ใช้เห็น
                error_log("Booking DB error: " . $e->getMessage());
                $error = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง';
            } catch (Exception $e) {
                error_log("Booking Exception: " . $e->getMessage());
                $error = 'ไม่สามารถทำการจองได้: ' . $e->getMessage();
            }
        }
    }

    // สำหรับแสดงราคาแบบ real-time
    $pricePerDay = $motorcycle ? $motorcycle['pricePerDay'] : 0;
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (! $motorcycle): ?>
            <div class="min-h-[60vh] flex items-center justify-center">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">ไม่พบข้อมูลรถ</h2>
                    <a href="index.php?page=motorcycles" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">กลับไปเลือกรถ</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 text-sm">
                    <li>
                        <a href="index.php?page=home" class="text-blue-600 hover:text-blue-700">
                            <i data-lucide="home" class="h-4 w-4"></i>
                        </a>
                    </li>
                    <li class="text-gray-400">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </li>
                    <li>
                        <a href="index.php?page=motorcycles" class="text-blue-600 hover:text-blue-700">
                            รถจักรยานยนต์
                        </a>
                    </li>
                    <li class="text-gray-400">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </li>
                    <li class="text-gray-600">
                        จองรถ
                    </li>
                </ol>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column: Motorcycle Details -->
                <div class="space-y-6">
                    <!-- Motorcycle Card -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="relative h-64">
                            <img src="<?php echo e($motorcycle['imageUrl']); ?>"
                                 alt="<?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='assets/img/default-bike.svg';"/>
                            <div class="absolute top-4 right-4">
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                    <?php echo($motorcycle['isAvailable']) ? 'พร้อมใช้งาน' : 'ไม่ว่าง'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h1 class="text-2xl font-bold text-gray-900 mb-4">
                                <?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>
                            </h1>

                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i data-lucide="zap" class="h-5 w-5 text-blue-500"></i>
                                    <span><?php echo e($motorcycle['engineCc']); ?> cc</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i data-lucide="calendar" class="h-5 w-5 text-blue-500"></i>
                                    <span><?php echo e($motorcycle['year']); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i data-lucide="tag" class="h-5 w-5 text-blue-500"></i>
                                    <span><?php echo e($motorcycle['licensePlate']); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i data-lucide="palette" class="h-5 w-5 text-blue-500"></i>
                                    <span><?php echo e($motorcycle['color']); ?></span>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h3 class="font-semibold text-gray-900 mb-3">รายละเอียด</h3>
                                <p class="text-gray-700"><?php echo e($motorcycle['description']); ?></p>
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-medium text-gray-900">ราคาต่อวัน</span>
                                    <span id="price-per-day" data-price-per-day="<?php echo $pricePerDay; ?>"
                                          class="text-2xl font-bold text-blue-600">
                                        ฿<?php echo number_format($pricePerDay, 2); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price Calculator (จะอัปเดตด้วย JavaScript) -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">คำนวณราคา</h3>
                        <div id="price-calculator" class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">จำนวนวัน:</span>
                                <span id="calc-days" class="font-medium">0 วัน</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">ราคารวม:</span>
                                <span id="calc-total-price" class="font-medium">฿0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">ส่วนลด:</span>
                                <span id="calc-discount" class="font-medium text-red-600">฿0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">ราคาสุทธิ:</span>
                                <span id="calc-final-price" class="font-bold text-blue-700">฿0.00</span>
                            </div>
                            <div class="flex justify-between border-t pt-4">
                                <span class="text-lg font-semibold text-gray-900">มัดจำจอง:</span>
                                <span id="calc-deposit" class="text-xl font-bold text-orange-600">฿0.00</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                *จ่ายมัดจำเพื่อจองรถ ค่าบริการที่เหลือจ่ายที่ร้านตอนรับรถ
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Booking Form -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">จองรถจักรยานยนต์</h2>

                    <!-- แสดงข้อผิดพลาดวันที่ -->
                    <?php if ($dateError): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                            <?php echo e($dateError); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                            <?php echo e($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($success)): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                            <?php echo e($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=booking&id=<?php echo e($motorcycle['motorcycleId']); ?>" class="space-y-6" id="bookingForm">
<?php echo Csrf::field(); ?>
                        <!-- User Info -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-700 font-medium">
                                <i data-lucide="user" class="h-4 w-4 inline mr-2"></i>
                                <?php echo e($_SESSION['user']['firstName'] ?? 'ผู้ใช้'); ?>
                                <?php echo e($_SESSION['user']['lastName'] ?? ''); ?>
                            </p>
                            <p class="text-gray-600 text-sm mt-1">
                                <i data-lucide="mail" class="h-4 w-4 inline mr-2"></i>
                                <?php echo e($_SESSION['user']['email'] ?? ''); ?>
                            </p>
                        </div>

                        <!-- Date Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar" class="h-4 w-4 inline mr-1"></i>
                                    วันที่รับรถ
                                </label>
                                <input type="date"
                                    name="start_date"
                                    id="start_date"
                                    value="<?php echo e($startDateFromUrl); ?>"
                                    min="<?php echo $today; ?>"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar" class="h-4 w-4 inline mr-1"></i>
                                    วันที่คืนรถ
                                </label>
                                <input type="date"
                                    name="end_date"
                                    id="end_date"
                                    value="<?php echo e($endDateFromUrl); ?>"
                                    min="<?php echo $today; ?>"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"/>
                            </div>
                        </div>

                        <!-- เวลา -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="clock" class="h-4 w-4 inline mr-1"></i>
                                    เวลารับรถ
                                </label>
                                <select name="pickup_time"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="08:00">08:00 น.</option>
                                    <option value="08:30">08:30 น.</option>
                                    <option value="09:00" selected>09:00 น.</option>
                                    <option value="09:30">09:30 น.</option>
                                    <option value="10:00">10:00 น.</option>
                                    <option value="10:30">10:30 น.</option>
                                    <option value="11:00">11:00 น.</option>
                                    <option value="11:30">11:30 น.</option>
                                    <option value="12:00">12:00 น.</option>
                                    <option value="12:30">12:30 น.</option>
                                    <option value="13:00">13:00 น.</option>
                                    <option value="13:30">13:30 น.</option>
                                    <option value="14:00">14:00 น.</option>
                                    <option value="14:30">14:30 น.</option>
                                    <option value="15:00">15:00 น.</option>
                                    <option value="15:30">15:30 น.</option>
                                    <option value="16:00">16:00 น.</option>
                                    <option value="16:30">16:30 น.</option>
                                    <option value="17:00">17:00 น.</option>
                                    <option value="17:30">17:30 น.</option>
                                    <option value="18:00">18:00 น.</option>
                                    <option value="18:30">18:30 น.</option>
                                    <option value="19:00">19:00 น.</option>
                                    <option value="19:30">19:30 น.</option>
                                    <option value="20:00">20:00 น.</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="clock" class="h-4 w-4 inline mr-1"></i>
                                    เวลาคืนรถ
                                </label>
                                <select name="return_time"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="08:00">08:00 น.</option>
                                    <option value="08:30">08:30 น.</option>
                                    <option value="09:00">09:00 น.</option>
                                    <option value="09:30">09:30 น.</option>
                                    <option value="10:00">10:00 น.</option>
                                    <option value="10:30">10:30 น.</option>
                                    <option value="11:00">11:00 น.</option>
                                    <option value="11:30">11:30 น.</option>
                                    <option value="12:00">12:00 น.</option>
                                    <option value="12:30">12:30 น.</option>
                                    <option value="13:00">13:00 น.</option>
                                    <option value="13:30">13:30 น.</option>
                                    <option value="14:00">14:00 น.</option>
                                    <option value="14:30">14:30 น.</option>
                                    <option value="15:00">15:00 น.</option>
                                    <option value="15:30">15:30 น.</option>
                                    <option value="16:00">16:00 น.</option>
                                    <option value="16:30">16:30 น.</option>
                                    <option value="17:00" selected>17:00 น.</option>
                                    <option value="17:30">17:30 น.</option>
                                    <option value="18:00">18:00 น.</option>
                                    <option value="18:30">18:30 น.</option>
                                    <option value="19:00">19:00 น.</option>
                                    <option value="19:30">19:30 น.</option>
                                    <option value="20:00">20:00 น.</option>
                                </select>
                            </div>
                        </div>

                        <!-- Pickup Location -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="map-pin" class="h-4 w-4 inline mr-1"></i>
                                สถานที่รับรถ
                            </label>
                            <select name="pickup_location"
                                    id="pickup_location"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">เลือกสถานที่รับรถ</option>
                                <option value="ร้านเทมป์เทชัน">ร้านเทมป์เทชัน</option>
                                <option value="สนามบินหาดใหญ่">สนามบินหาดใหญ่</option>
                                <option value="สถานีรถไฟหาดใหญ่">สถานีรถไฟหาดใหญ่</option>
                                <option value="สถานีขนส่งหาดใหญ่">สถานีขนส่งหาดใหญ่</option>
                                <option value="โรงแรมในเมืองหาดใหญ่">โรงแรมในเมืองหาดใหญ่</option>
                                <option value="อื่นๆ">อื่นๆ (ระบุในช่องรายละเอียด)</option>
                            </select>
                        </div>

                        <!-- Pickup Details -->
                        <div id="pickup-details-container" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="info" class="h-4 w-4 inline mr-1"></i>
                                รายละเอียดเพิ่มเติมสำหรับรับรถ
                                <span class="text-xs text-gray-500">(เช่น ชื่อโรงแรม, หมายเลขห้อง, จุดนัดพบ, ที่อยู่)</span>
                            </label>
                            <textarea name="pickup_details"
                                    rows="2"
                                    placeholder="กรอกรายละเอียดเพิ่มเติม เช่น โรงแรม ABC ห้อง 123, นัดพบที่ล็อบบี้, ที่อยู่จัดส่ง"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <!-- Return Location -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="map-pin" class="h-4 w-4 inline mr-1"></i>
                                สถานที่คืนรถ
                            </label>
                            <select name="return_location"
                                    id="return_location"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">เลือกสถานที่คืนรถ</option>
                                <option value="ร้านเทมป์เทชัน">ร้านเทมป์เทชัน</option>
                                <option value="สนามบินหาดใหญ่">สนามบินหาดใหญ่</option>
                                <option value="สถานีรถไฟหาดใหญ่">สถานีรถไฟหาดใหญ่</option>
                                <option value="สถานีขนส่งหาดใหญ่">สถานีขนส่งหาดใหญ่</option>
                                <option value="โรงแรมในเมืองหาดใหญ่">โรงแรมในเมืองหาดใหญ่</option>
                                <option value="อื่นๆ">อื่นๆ (ระบุในช่องรายละเอียด)</option>
                            </select>
                        </div>

                        <!-- Return Details -->
                        <div id="return-details-container" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="info" class="h-4 w-4 inline mr-1"></i>
                                รายละเอียดเพิ่มเติมสำหรับคืนรถ
                                <span class="text-xs text-gray-500">(เช่น ชื่อโรงแรม, หมายเลขห้อง, จุดนัดพบ, ที่อยู่)</span>
                            </label>
                            <textarea name="return_details"
                                    rows="2"
                                    placeholder="กรอกรายละเอียดเพิ่มเติมสำหรับการคืนรถ"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <!-- Discount Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="tag" class="h-4 w-4 inline mr-1"></i>
                                โค้ดส่วนลด (ถ้ามี)
                            </label>
                            <div class="flex gap-2">
                                <input type="text"
                                       name="discount_code"
                                       id="discount_code"
                                       placeholder="กรอกโค้ดส่วนลด"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"/>
                                <button type="button"
                                        id="apply-discount"
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium">
                                    ใช้โค้ด
                                </button>
                            </div>
                            <div id="discount-message" class="text-sm mt-2"></div>
                        </div>
                        <input type="hidden" name="discount_amount" id="discount_amount" value="0">

                        <!-- Terms and Conditions -->
                        <div class="text-xs text-gray-600 bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold mb-2 text-gray-900">เงื่อนไขการจอง</h4>
                            <ul class="list-disc list-inside space-y-1">
                                <li>จ่ายมัดจำ <?php echo number_format(DEPOSIT_AMOUNT); ?> บาทเพื่อยืนยันการจอง</li>
                                <li>ค่าบริการที่เหลือจ่ายที่ร้านตอนรับรถ</li>
                                <li>สามารถยกเลิกการจองได้ก่อน 24 ชั่วโมง (คืนเงินมัดจำเต็มจำนวน)</li>
                                <li>ต้องมีใบขับขี่ที่ถูกต้องและบัตรประจำตัวประชาชน</li>
                                <li>ค่าเสียหายจะหักจากเงินประกันตามจริง</li>
                                <li>การคืนรถล่าช้ามีค่าปรับ 200 บาท/ชั่วโมง</li>
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                id="submit-btn"
                                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-3 px-4 rounded-lg font-semibold transition-all transform hover:scale-[1.02] shadow-lg">
                            <i data-lucide="check-circle" class="h-5 w-5 inline mr-2"></i>
                            ยืนยันการจองและชำระมัดจำ
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    let promoDiscount = 0;

    document.addEventListener('DOMContentLoaded', function() {
        const pricePerDay = <?php echo (float) $pricePerDay; ?>;
        const DEPOSIT_AMOUNT = <?php echo (float) DEPOSIT_AMOUNT; ?>; // ค่ามัดจำจาก .env
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const discountCodeInput = document.getElementById('discount_code');
        const applyDiscountBtn = document.getElementById('apply-discount');
        const discountMessage = document.getElementById('discount-message');
        const pickupLocationSelect = document.getElementById('pickup_location');
        const returnLocationSelect = document.getElementById('return_location');
        const pickupDetailsContainer = document.getElementById('pickup-details-container');
        const returnDetailsContainer = document.getElementById('return-details-container');
        const bookingForm = document.getElementById('bookingForm');

        /* =====================================================
           renderPrice
           ===================================================== */
        function renderPrice(days, totalPrice, discountAmount, finalPrice, depositAmount)
        {
            document.getElementById('calc-days').textContent =
                days + ' วัน';

            document.getElementById('calc-total-price').textContent =
                '฿' + Math.round(totalPrice).toLocaleString('th-TH');

            document.getElementById('calc-discount').textContent =
                '฿' + Math.round(discountAmount).toLocaleString('th-TH');

            document.getElementById('calc-final-price').textContent =
                '฿' + Math.round(finalPrice).toLocaleString('th-TH');

            document.getElementById('calc-deposit').textContent =
                '฿' + depositAmount.toLocaleString('th-TH');
        }

        // ฟังก์ชันคำนวณราคา
        function calculatePrice(){
            const days = calculateDays();

            if (days <= 0) {
                renderPrice(0, 0, 0, 0, DEPOSIT_AMOUNT);
                return;
            }

            const totalPrice    = days * pricePerDay;
            const shopDiscount  = Math.floor(days / 3) * 50; // ส่วนลดร้าน
            const totalDiscount = shopDiscount + promoDiscount;
            const finalPrice    = Math.max(totalPrice - totalDiscount, 0);
            const deposit       = Math.min(DEPOSIT_AMOUNT, finalPrice); // มัดจำไม่เกินราคาสุทธิ

            renderPrice(days, totalPrice, totalDiscount, finalPrice, deposit);
        }

        // ฟังก์ชันตรวจสอบโค้ดส่วนลด
        async function checkDiscountCode(code) {
            if (!code.trim()) {
                promoDiscount = 0;
                discountMessage.textContent = '';
                discountMessage.className   = 'text-sm mt-2';
                calculatePrice();
                return;
            }

            const days       = calculateDays();
            const totalPrice = days * pricePerDay;

            try {
                const response = await fetch('index.php?page=api&action=checkDiscount', {
                    method: 'POST',
                    headers: {
                        'Content-Type':'application/x-www-form-urlencoded',
                    },
                    body: `discount_code=${encodeURIComponent(code)}&rental_days=${days}&total_price=${totalPrice}`,
                });

                const data = await response.json();

                if (data.valid) {
                    promoDiscount = Math.round(data.discountAmount);
                    document.getElementById('discount_amount').value = promoDiscount;

                    discountMessage.textContent = data.message;
                    discountMessage.className   = 'text-sm mt-2 text-green-600';
                } else {
                    promoDiscount = 0;
                    document.getElementById('discount_amount').value = 0;

                    discountMessage.textContent = data.message;
                    discountMessage.className   = 'text-sm mt-2 text-red-600';
                }

                calculatePrice();

            } catch (error) {
                promoDiscount = 0;
                discountMessage.textContent = 'เกิดข้อผิดพลาดในการตรวจสอบโค้ด';
                discountMessage.className   = 'text-sm mt-2 text-red-600';
                calculatePrice();
            }
        }

        // คำนวณจำนวนวัน
        function calculateDays()
        {
            if (!startDateInput.value || !endDateInput.value) {
                return 0;
            }

            const startDate = new Date(startDateInput.value);
            const endDate   = new Date(endDateInput.value);
            const timeDiff  = endDate.getTime() - startDate.getTime();
            return Math.floor(timeDiff / (1000 * 3600 * 24)) + 1;
        }

        // ฟังก์ชันตรวจสอบวันที่
        function validateDates() {
            if (startDateInput.value && endDateInput.value) {
                if (endDateInput.value < startDateInput.value) {
                    alert('วันที่คืนรถต้องไม่น้อยกว่าวันที่รับรถ');
                    endDateInput.value = startDateInput.value;
                    return false;
                }
            }
            return true;
        }

        // toggleDetailsFields
        function toggleDetailsFields()
        {
            const pickupValue = pickupLocationSelect.value;
            pickupDetailsContainer.classList.toggle(
                'hidden',
                pickupValue === 'ร้านเทมป์เทชัน' || pickupValue === ''
            );

            const returnValue = returnLocationSelect.value;
            returnDetailsContainer.classList.toggle(
                'hidden',
                returnValue === 'ร้านเทมป์เทชัน' || returnValue === ''
            );
        }

        // Event Listeners สำหรับวันที่
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                if (this.value) {
                    endDateInput.min = this.value;
                    if (endDateInput.value && endDateInput.value < this.value) {
                        endDateInput.value = this.value;
                    }
                }
                calculatePrice();
            });

            endDateInput.addEventListener('change', function() {
                if (this.value && startDateInput.value && this.value < startDateInput.value) {
                    this.value = startDateInput.value;
                }
                calculatePrice();
            });
        }

        // ตรวจสอบก่อนส่งฟอร์ม
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                }
            });
        }

        applyDiscountBtn.addEventListener('click', function () {
            checkDiscountCode(discountCodeInput.value);
        });

        discountCodeInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                checkDiscountCode(discountCodeInput.value);
            }
        });

        pickupLocationSelect.addEventListener('change', toggleDetailsFields);
        returnLocationSelect.addEventListener('change', toggleDetailsFields);

        // init
        setTimeout(calculatePrice, 100);
        toggleDetailsFields();
    });
</script>