<?php
    // views/pages/MotorcyclesPages.php
        require_once APP_ROOT . '/src/Services/MotorcycleService.php';
    require_once APP_ROOT . '/src/Services/BookingService.php';

    // รับค่าตัวกรองจาก URL
    $searchTerm    = $_GET['search'] ?? '';
    $selectedBrand = $_GET['brand'] ?? '';
    $selectedType  = $_GET['type'] ?? '';
    $priceRangeMin = (int) ($_GET['min_price'] ?? 0);
    $priceRangeMax = (int) ($_GET['max_price'] ?? 1000);
    $startDate     = $_GET['start_date'] ?? '';
    $endDate       = $_GET['end_date'] ?? '';

    // ตรวจสอบวันที่รับ-คืน (เฉพาะเมื่อกดค้นหาแล้ว)
    $dateError = ''; // ประกาศตัวแปรก่อนใช้
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date']) && $startDate && $endDate) {
        // ตรวจรูปแบบวันที่ (ค่าจาก URL ที่ผิดรูปแบบต้องไม่ทำให้หน้าพัง)
        $start = DateTime::createFromFormat('Y-m-d', $startDate);
        $end   = DateTime::createFromFormat('Y-m-d', $endDate);

        if (! $start || ! $end) {
            $dateError = 'รูปแบบวันที่ไม่ถูกต้อง';
        } elseif ($end < $start) {
            $dateError = 'วันที่คืนรถต้องไม่น้อยกว่าวันที่รับรถ';
        }

        if ($dateError) {
            // เคลียร์ค่าวันที่เพื่อให้เลือกใหม่
            $startDate = '';
            $endDate = '';
        }
    }

    // ดึงข้อมูลจาก Service
    try {
        $filters = [
            'brand'    => $searchTerm ? '' : $selectedBrand,
            'model'    => $searchTerm,
            'minPrice' => $priceRangeMin,
            'maxPrice' => $priceRangeMax,
            'type'     => $selectedType,
        ];

        $motorcycles_data = MotorcycleService::searchMotorcycles($filters, $startDate, $endDate);

        if (! is_array($motorcycles_data)) {
            $motorcycles_data = [];
        }

    } catch (Exception $e) {
        $motorcycles_data = [];
        error_log('MotorcyclesPages load error: ' . $e->getMessage());
        $error_message    = "ไม่สามารถโหลดข้อมูลรถได้ กรุณาลองใหม่อีกครั้ง";
    }

    // ตรวจสอบรถที่ถูกจองในช่วงวันที่เลือก
    $bookedMotorcycleIds = [];
    if ($startDate && $endDate && !$dateError) {
        try {
            $bookings = BookingService::getBookingsByDateRange($startDate, $endDate);
            foreach ($bookings as $booking) {
                $status = strtolower($booking['status']);
                if (in_array($status, ['pending', 'confirmed', 'active'])) {
                    $bookedMotorcycleIds[] = $booking['motorcycleId'];
                }
            }
        } catch (Exception $e) {
            // ไม่แสดง error หากดึงข้อมูลการจองไม่สำเร็จ
        }
    }

    // กรองข้อมูลเพิ่มเติมหน้าเว็บ
    $filteredMotorcycles = array_filter($motorcycles_data, function ($bike) use ($searchTerm, $selectedBrand, $selectedType, $priceRangeMin, $priceRangeMax, $bookedMotorcycleIds, $startDate, $endDate, $dateError) {
        // กรองตามคำค้นหา
        if ($searchTerm) {
            $term       = strtolower($searchTerm);
            $brandModel = strtolower(($bike['brand'] ?? '') . ' ' . ($bike['model'] ?? ''));
            if (! str_contains($brandModel, $term)) {
                return false;
            }
        }

        // กรองตามยี่ห้อ
        if ($selectedBrand && ($bike['brand'] ?? '') !== $selectedBrand) {
            return false;
        }

        // กรองตามขนาดเครื่อง
        if ($selectedType) {
            $cc        = $bike['engineCc'] ?? 0;
            $typeMatch = false;
            if ($selectedType === 'small' && $cc <= 150) {
                $typeMatch = true;
            }
            if ($selectedType === 'medium' && $cc > 150 && $cc <= 300) {
                $typeMatch = true;
            }
            if ($selectedType === 'large' && $cc > 300) {
                $typeMatch = true;
            }
            if (! $typeMatch) {
                return false;
            }
        }

        // กรองตามราคา
        $price = $bike['pricePerDay'] ?? 0;
        if ($price < $priceRangeMin || $price > $priceRangeMax) {
            return false;
        }

        // ตรวจสอบว่ารถว่างในช่วงวันที่เลือกหรือไม่
        if ($startDate && $endDate && !$dateError) {
            $motorcycleId = $bike['motorcycleId'] ?? '';
            if (in_array($motorcycleId, $bookedMotorcycleIds)) {
                return false; // ไม่แสดงรถที่ถูกจองแล้ว
            }
        }

        // ตรวจสอบสถานะบำรุงรักษา
        $maintenanceStatus = strtolower($bike['maintenanceStatus'] ?? 'ready');
        if ($maintenanceStatus !== 'ready') {
            return false;
        }

        return true;
    });

    // คำนวณจำนวนวัน (เฉพาะเมื่อไม่มี error)
    $totalDays = 1;
    if ($startDate && $endDate && !$dateError) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $totalDays = $interval->days + 1;
    }

    // เตรียม Dropdowns
    $brands = ! empty($motorcycles_data) ? array_unique(array_column($motorcycles_data, 'brand')) : [];
    $types  = [
        'small'  => 'เล็ก (≤ 150cc)',
        'medium' => 'กลาง (151-300cc)',
        'large'  => 'ใหญ่ (> 300cc)',
    ];

    // โปรโมชั่นตัวอย่าง
    function calculateDiscount($days, $pricePerDay)
    {
        $normalPrice = $days * $pricePerDay;
        $discount    = ($days >= 3) ? floor($days / 3) * 50 : 0;
        return [
            'normalPrice' => $normalPrice,
            'finalPrice'  => $normalPrice - $discount,
            'discount'    => $discount,
        ];
    }

    $promoDays        = 3;
    $promoPricePerDay = 650;
    $promoData        = calculateDiscount($promoDays, $promoPricePerDay);

    // Base64 placeholder image
    $placeholderImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
?>


    <!-- Hero Section -->
    <div class="relative bg-gradient-to-br from-blue-900 to-blue-700 text-white overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-20"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4 tracking-tight">
                    ค้นหารถจักรยานยนต์ที่เหมาะกับคุณ
                </h1>
                <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                    จาก 110cc ถึง 700cc พร้อมบริการส่ง-รับรถถึงที่
                </p>
                <div class="inline-flex items-center gap-2 bg-blue-800/50 backdrop-blur-sm rounded-full px-6 py-3">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium">พร้อมใช้งาน <?php echo count($filteredMotorcycles); ?> คัน</span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-8">
        <!-- Search Card -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-8 border border-gray-100">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">ค้นหาและจองรถ</h2>
                    <p class="text-gray-600 text-sm">กรอกวันที่รับ-คืนรถและตัวกรองอื่นๆ</p>
                </div>
            </div>

            <!-- แสดงข้อความ error เฉพาะเมื่อกดค้นหาแล้วมีปัญหา -->
            <?php if ($dateError): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-700"><?php echo e($dateError); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="GET" action="index.php" class="space-y-6">
                <input type="hidden" name="page" value="motorcycles">

                <!-- Date Picker Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-blue-50 p-4 rounded-xl">
                    <div>
                        <label class="block text-sm font-semibold text-blue-800 mb-2">วันที่รับรถ</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                value="<?php echo e($startDate); ?>"
                                class="w-full pl-10 pr-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                min="<?php echo date('Y-m-d'); ?>"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-blue-800 mb-2">วันที่คืนรถ</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                value="<?php echo e($endDate); ?>"
                                class="w-full pl-10 pr-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                min="<?php echo date('Y-m-d'); ?>"
                                required
                            />
                        </div>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            ค้นหารถที่ว่าง
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="border-t pt-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search Input -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">รุ่นรถ</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input
                                    type="text"
                                    name="search"
                                    placeholder="เช่น Honda CBR, Yamaha..."
                                    value="<?php echo e($searchTerm); ?>"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <!-- Brand Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ยี่ห้อ</label>
                            <select
                                name="brand"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">ทุกยี่ห้อ</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option
                                        value="<?php echo e($brand); ?>"
                                        <?php if ($selectedBrand == $brand) echo 'selected'; ?>
                                    >
                                        <?php echo e($brand); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ขนาดเครื่อง</label>
                            <select
                                name="type"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">ทุกขนาด</option>
                                <?php foreach ($types as $key => $label): ?>
                                    <option
                                        value="<?php echo $key; ?>"
                                        <?php if ($selectedType == $key) echo 'selected'; ?>
                                    >
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range (ลบ min max ออก) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ราคาต่อวัน (บาท)
                            </label>
                            <div class="flex gap-2">
                                <input
                                    type="number"
                                    name="min_price"
                                    min="0"
                                    placeholder="ต่ำสุด"
                                    value="<?php echo e($priceRangeMin); ?>"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <span class="flex items-center text-gray-500">-</span>
                                <input
                                    type="number"
                                    name="max_price"
                                    min="0"
                                    placeholder="สูงสุด"
                                    value="<?php echo e($priceRangeMax); ?>"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center mt-6">
                        <div class="text-sm text-gray-600">
                            <?php if ($startDate && $endDate && !$dateError): ?>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>เลือกวันที่: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?> (<?php echo e($totalDays); ?> วัน)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-3">
                            <a
                                href="index.php?page=motorcycles"
                                class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                รีเซ็ตตัวกรอง
                            </a>
                            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                                ค้นหา
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">รถที่พร้อมให้เช่า</h2>
                    <p class="text-gray-600 mt-1">
                        <?php if ($startDate && $endDate && !$dateError): ?>
                            <span class="text-green-600 font-medium">
                                <?php echo count($filteredMotorcycles); ?> คัน ว่างในช่วงวันที่เลือก (<?php echo e($totalDays); ?> วัน)
                            </span>
                        <?php else: ?>
                            <span>กรุณาเลือกวันที่รับ-คืนรถเพื่อดูรถที่ว่าง</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo e($error_message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($filteredMotorcycles) && !$startDate && !$endDate): ?>
            <!-- Date Selection Prompt -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-sm mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">กรุณาเลือกวันที่รับ-คืนรถ</h3>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                    เลือกวันที่เพื่อดูรถจักรยานยนต์ที่ว่างในช่วงเวลาที่คุณต้องการ
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="#search-form" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        เลือกวันที่ตอนนี้
                    </a>
                    <a href="index.php?page=about" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        ดูเงื่อนไขการเช่า
                    </a>
                </div>
            </div>
        <?php elseif (empty($filteredMotorcycles) && $startDate && $endDate && !$dateError): ?>
            <!-- No Results -->
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">ไม่พบรถที่ว่างในช่วงวันที่เลือก</h3>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                    ลองเปลี่ยนวันที่หรือตัวกรองอื่นๆ หรือติดต่อเราเพื่อสอบถามรถที่ใกล้เคียง
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="index.php?page=motorcycles" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        รีเซ็ตตัวกรอง
                    </a>
                    <a href="index.php?page=contact" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        ติดต่อสอบถาม
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Motorcycles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($filteredMotorcycles as $motorcycle): ?>
                    <?php
                        $isAvailable   = true;
                        $motorcycleId  = $motorcycle['motorcycleId'] ?? '';
                        $pricePerDay   = $motorcycle['pricePerDay'] ?? 0;
                        $engineCc      = $motorcycle['engineCc'] ?? 0;
                        $imageUrl      = $motorcycle['imageUrl'] ?? '';
                        $hasValidImage = ! empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL);

                        // คำนวณราคารวม
                        $currentTotalDays = $totalDays;
                        $totalPrice = $currentTotalDays * $pricePerDay;
                    ?>

                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <!-- Image & Badge -->
                        <div class="relative h-56 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img
                                src="<?php echo $hasValidImage ? e($imageUrl) : $placeholderImage; ?>"
                                alt="<?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                                class="w-full h-full object-cover"
                                onerror="this.onerror=null; this.src='<?php echo $placeholderImage; ?>';"
                                loading="lazy"
                            />

                            <!-- Availability Badge -->
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-1.5 bg-green-100 text-green-800 rounded-full text-xs font-semibold flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    ว่าง
                                </span>
                            </div>

                            <!-- Engine CC Badge -->
                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-semibold">
                                    <?php echo number_format($engineCc); ?> cc
                                </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <!-- Title -->
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-gray-900">
                                    <?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">
                                    ปี <?php echo e($motorcycle['year'] ?? 'N/A'); ?>
                                    <?php if (! empty($motorcycle['color'])): ?>
                                        • สี <?php echo e($motorcycle['color']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- เพิ่มราคาต่อวัน -->
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-gray-600">ราคาต่อวัน</span>
                                <span class="text-lg font-semibold text-blue-600">฿<?php echo number_format($pricePerDay, 0); ?></span>
                            </div>

                            <!-- Description -->
                            <?php if (! empty($motorcycle['description'])): ?>
                                <p class="text-gray-600 text-sm mb-5 line-clamp-2">
                                    <?php echo e($motorcycle['description']); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Total Price & Action -->
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="text-sm text-gray-500">รวมทั้งสิ้น (<?php echo e($totalDays); ?> วัน)</div>
                                        <div class="text-2xl font-bold text-green-600">
                                            ฿<?php echo number_format($totalPrice, 0); ?>
                                        </div>
                                    </div>

                                    <a
                                        href="index.php?page=booking&id=<?php echo e($motorcycleId); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>"
                                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 shadow-lg hover:shadow-xl"
                                    >
                                        จองเลย
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function bookMotorcycle(motorcycleId) {
            const startDate = document.getElementById('start_date')?.value;
            const endDate = document.getElementById('end_date')?.value;

            let url = `index.php?page=booking&id=${motorcycleId}`;
            if (startDate && endDate) {
                url += `&start_date=${startDate}&end_date=${endDate}`;
            }
            window.location.href = url;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            // Set min date
            if (startDateInput) startDateInput.min = today;
            if (endDateInput) endDateInput.min = today;

            // ตรวจสอบวันที่แบบเรียลไทม์ (แค่ปรับค่า ไม่เตือน)
            if (startDateInput && endDateInput) {
                startDateInput.addEventListener('change', function() {
                    if (this.value) {
                        endDateInput.min = this.value;
                        if (endDateInput.value && endDateInput.value < this.value) {
                            endDateInput.value = this.value;
                        }
                    }
                });

                endDateInput.addEventListener('change', function() {
                    if (this.value && startDateInput.value && this.value < startDateInput.value) {
                        this.value = startDateInput.value;
                    }
                });
            }
        });
    </script>
