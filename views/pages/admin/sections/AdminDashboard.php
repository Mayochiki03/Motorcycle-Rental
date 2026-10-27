<?php
    // views/pages/admin/sections/AdminDashboard.php
    require_once APP_ROOT . '/src/Services/Admin/AdminService.php';
    use Service\Admin\AdminService;

    /* ===================== LOAD ALL DATA ===================== */
    try {
    // Dashboard Stats - ใช้ getDashboardStats() ใหม่
    $statsData = AdminService::getDashboardStats();

    // Report Stats
    $allCustomers    = AdminService::getAllCustomers();
    $allReservations = AdminService::getAllReservationsDetailed();

    // แยก period สำหรับรถยอดนิยม
    $motorcyclesPeriod = $_GET['motorcycles_period'] ?? 'all_time';
    $topMotorcycles    = AdminService::getTopMotorcyclesEnhanced(5, $motorcyclesPeriod);
    $allMotorcycles    = AdminService::getAllMotorcyclesWithImages();

    // แยก period สำหรับรายได้
    $revenuePeriod = $_GET['revenue_period'] ?? 'monthly';
    $revenueReport = AdminService::getEnhancedRevenueReport($revenuePeriod);

    // กิจกรรมล่าสุด
    $recentActivities = AdminService::getRecentActivities(10);
    $recentBookings   = AdminService::getRecentReservations(5);

    } catch (Throwable $e) {
    error_log('[AdminDashboard] ' . $e->getMessage());
    $statsData        = [];
    $allCustomers     = [];
    $allReservations  = [];
    $topMotorcycles   = [];
    $allMotorcycles   = [];
    $revenueReport    = [];
    $recentActivities = [];
    $recentBookings   = [];
    }

    /* ===================== CALCULATIONS ===================== */
    $activeCustomers = count(array_filter($allCustomers, fn($c) => ($c['isActive'] ?? 1) == 1));

    $stats = [
    [
        'label' => 'การจองทั้งหมด',
        'value' => $statsData['totalBookings'] ?? 0,
        'icon'  => 'calendar',
        'color' => 'bg-blue-500',
    ],
    [
        'label' => 'รอการยืนยัน',
        'value' => $statsData['pendingBookings'] ?? 0,
        'icon'  => 'clock',
        'color' => 'bg-yellow-500',
    ],
    [
        'label' => 'กำลังเช่า',
        'value' => $statsData['activeBookings'] ?? 0,
        'icon'  => 'trending-up',
        'color' => 'bg-green-500',
    ],
    [
        'label' => 'รายได้รวม',
        'value' => '฿' . number_format($statsData['totalRevenue'] ?? 0),
        'icon'  => 'credit-card',
        'color' => 'bg-purple-500',
    ],
    [
        'label' => 'ลูกค้าใช้งาน',
        'value' => $activeCustomers,
        'icon'  => 'users',
        'color' => 'bg-indigo-500',
    ],
    [
        'label' => 'รถทั้งหมด',
        'value' => count($allMotorcycles),
        'icon'  => 'bike',
        'color' => 'bg-teal-500',
    ],
    ];
?>

<style>
    .revenue-chart-container {
        position: relative;
        height: 300px;
    }

    .chart-bars-wrapper {
        display: flex;
        height: 200px;
        align-items: flex-end;
        padding: 0 20px 40px 20px;
        min-width: min-content;
    }

    .chart-bar-group {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 0 4px;
        height: 100%;
    }

    .chart-bar {
        width: 100%;
        transition: all 0.3s ease;
        border-radius: 4px 4px 0 0;
    }

    .chart-label {
        margin-top: 8px;
        text-align: center;
        font-size: 0.7rem;
        color: #6b7280;
        white-space: nowrap;
    }

    .chart-bar:hover {
        opacity: 0.9;
        transform: scale(1.05);
    }

    .revenue-chart-container::-webkit-scrollbar {
        height: 6px;
    }

    .revenue-chart-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .revenue-chart-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .revenue-chart-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<div class="space-y-6">

    <!-- Stats Grid - 6 Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <?php foreach ($stats as $stat): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start">
                    <div class="<?php echo $stat['color']; ?> p-3 rounded-lg mr-3">
                        <i data-lucide="<?php echo $stat['icon']; ?>" class="h-5 w-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 mb-1"><?php echo $stat['label']; ?></p>
                        <p class="text-xl font-bold text-gray-900"><?php echo $stat['value']; ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Top Motorcycles Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-lg text-gray-800">รถยอดนิยม</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        <?php
                            if ($motorcyclesPeriod === 'monthly') {
                                echo 'รถที่ถูกจองมากที่สุด (เดือนนี้)';
                            } elseif ($motorcyclesPeriod === 'yearly') {
                                echo 'รถที่ถูกจองมากที่สุด (ปีนี้)';
                            } else {
                                echo 'รถที่ถูกจองมากที่สุด (ทั้งหมด)';
                            }
                        ?>
                    </p>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">ช่วงเวลา:</span>
                    <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden">
                        <a href="index.php?page=admin&section=dashboard&motorcycles_period=all_time"
                        class="px-3 py-1.5 text-xs font-medium <?php echo($motorcyclesPeriod === 'all_time') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border-r border-gray-300 transition-colors">
                            ทั้งหมด
                        </a>
                        <a href="index.php?page=admin&section=dashboard&motorcycles_period=monthly"
                        class="px-3 py-1.5 text-xs font-medium <?php echo($motorcyclesPeriod === 'monthly') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border-r border-gray-300 transition-colors">
                            รายเดือน
                        </a>
                        <a href="index.php?page=admin&section=dashboard&motorcycles_period=yearly"
                        class="px-3 py-1.5 text-xs font-medium <?php echo($motorcyclesPeriod === 'yearly') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> transition-colors">
                            รายปี
                        </a>
                    </div>
                    <span class="px-3 py-1 bg-gradient-to-r from-purple-500 to-purple-600 text-white text-xs font-medium rounded-full">
                        <?php echo count($topMotorcycles); ?> รายการ
                    </span>
                </div>
            </div>
        </div>

        <div class="p-5">
            <?php if (! empty($topMotorcycles)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <?php foreach ($topMotorcycles as $index => $motorcycle): ?>
                        <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                            <!-- Badge อันดับ -->
                            <div class="absolute top-3 left-3 z-10">
                                <?php if ($index === 0): ?>
                                    <span class="px-2 py-1 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white text-xs font-bold rounded-full">
                                        <i data-lucide="crown" class="h-3 w-3 inline mr-1"></i> อันดับ 1
                                    </span>
                                <?php elseif ($index === 1): ?>
                                    <span class="px-2 py-1 bg-gradient-to-r from-gray-400 to-gray-500 text-white text-xs font-bold rounded-full">
                                        <i data-lucide="star" class="h-3 w-3 inline mr-1"></i> อันดับ 2
                                    </span>
                                <?php elseif ($index === 2): ?>
                                    <span class="px-2 py-1 bg-gradient-to-r from-orange-500 to-orange-600 text-white text-xs font-bold rounded-full">
                                        <i data-lucide="award" class="h-3 w-3 inline mr-1"></i> อันดับ 3
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs font-medium rounded-full">
                                        อันดับ <?php echo $index + 1; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- รูปภาพรถ -->
                            <div class="h-40 bg-gray-100 overflow-hidden">
                                <?php if (! empty($motorcycle['imageUrl'])): ?>
                                    <img src="<?php echo e($motorcycle['imageUrl']); ?>"
                                         alt="<?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                        <i data-lucide="bike" class="h-12 w-12 text-gray-400"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- สถานะรถ -->
                                <div class="absolute bottom-3 right-3">
                                    <?php if ($motorcycle['isAvailable']): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                            <i data-lucide="check-circle" class="h-3 w-3 inline mr-1"></i> ว่าง
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                                            <i data-lucide="circle-x" class="h-3 w-3 inline mr-1"></i> ไม่ว่าง
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- รายละเอียดรถ -->
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 truncate">
                                            <?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span class="inline-block bg-blue-50 text-blue-700 px-2 py-0.5 rounded mr-2">
                                                <?php echo e($motorcycle['licensePlate']); ?>
                                            </span>
                                            <?php if (! empty($motorcycle['year'])): ?>
                                                <span class="text-gray-600">ปี <?php echo e($motorcycle['year']); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- ข้อมูลจำเพาะ -->
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                                    <?php if (! empty($motorcycle['color'])): ?>
                                        <div class="flex items-center">
                                            <i data-lucide="palette" class="h-3 w-3 mr-1 text-gray-400"></i>
                                            <span><?php echo e($motorcycle['color']); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (! empty($motorcycle['engineCc'])): ?>
                                        <div class="flex items-center">
                                            <i data-lucide="settings" class="h-3 w-3 mr-1 text-gray-400"></i>
                                            <span><?php echo number_format($motorcycle['engineCc']); ?> cc</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- สถิติการจอง -->
                                <div class="border-t border-gray-100 pt-3">
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div>
                                            <div class="text-sm font-bold text-purple-600">
                                                <?php echo e($motorcycle['bookingCount'] ?? 0); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">การจอง</div>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-green-600">
                                                ฿<?php echo number_format($motorcycle['totalRevenue'] ?? 0); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">รายได้</div>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-blue-600">
                                                ฿<?php echo number_format($motorcycle['pricePerDay'] ?? 0); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">ต่อวัน</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ปุ่มจัดการ -->
                                <div class="mt-4">
                                    <a href="index.php?page=admin&section=motorcycles&action=edit&id=<?php echo urlencode($motorcycle['motorcycleId']); ?>"
                                       class="block w-full text-center px-3 py-2 bg-gradient-to-r from-blue-50 to-blue-100 text-blue-700 text-sm font-medium rounded-lg hover:from-blue-100 hover:to-blue-200 transition-all">
                                        <i data-lucide="edit" class="h-4 w-4 inline mr-1"></i>
                                        จัดการรถ
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="py-12 text-center text-gray-400">
                    <i data-lucide="bike" class="h-16 w-16 mx-auto mb-4"></i>
                    <p class="text-lg font-medium mb-2">ยังไม่มีข้อมูลรถยอดนิยม</p>
                    <p class="text-sm">เริ่มรับการจองเพื่อดูสถิติ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Revenue Chart with Period Selector -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="font-semibold text-lg text-gray-800">รายได้</h2>
                <p class="text-sm text-gray-600 mt-1">สรุปรายได้ตามช่วงเวลา</p>
            </div>

            <!-- Revenue Period Selector -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">ช่วงเวลา:</span>
                <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden">
                    <a href="index.php?page=admin&section=dashboard&revenue_period=daily"
                    class="px-4 py-2 text-sm font-medium <?php echo($revenuePeriod === 'daily') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border-r border-gray-300 transition-colors">
                        รายวัน
                    </a>
                    <a href="index.php?page=admin&section=dashboard&revenue_period=monthly"
                    class="px-4 py-2 text-sm font-medium <?php echo($revenuePeriod === 'monthly') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border-r border-gray-300 transition-colors">
                        รายเดือน
                    </a>
                    <a href="index.php?page=admin&section=dashboard&revenue_period=yearly"
                    class="px-4 py-2 text-sm font-medium <?php echo($revenuePeriod === 'yearly') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> transition-colors">
                        รายปี
                    </a>
                </div>
            </div>
        </div>

        <?php if (! empty($revenueReport)): ?>
    <div class="relative">
        <canvas id="revenueChart" height="280"></canvas>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
            <!-- รายได้รวม -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 text-center border border-green-200 shadow-sm">
                <div class="flex justify-center mb-3">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-sm font-medium text-green-700 mb-2">รายได้รวม</div>
                <div class="text-3xl font-bold text-green-600 mb-1">
                    ฿<?php
                           if ($revenuePeriod === 'daily' && count($revenueReport) > 0) {
                               $today        = date('Y-m-d');
                               $dailyRevenue = 0;
                               foreach ($revenueReport as $row) {
                                   if ($row['label'] === $today) {
                                       $dailyRevenue = $row['revenue'];
                                       break;
                                   }
                               }
                               $totalRevenue = $dailyRevenue;
                           } else if ($revenuePeriod === 'monthly' && count($revenueReport) > 0) {
                               $currentMonth   = date('Y-m');
                               $monthlyRevenue = 0;
                               foreach ($revenueReport as $row) {
                                   if ($row['label'] === $currentMonth) {
                                       $monthlyRevenue = $row['revenue'];
                                       break;
                                   }
                               }
                               $totalRevenue = $monthlyRevenue;
                           } else {
                               $totalRevenue = array_sum(array_column($revenueReport, 'revenue'));
                           }
                           echo number_format($totalRevenue);
                       ?>
                </div>
            </div>

            <!-- การจองรวม -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 text-center border border-blue-200 shadow-sm">
                <div class="flex justify-center mb-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-sm font-medium text-blue-700 mb-2">การจองรวม</div>
                <div class="text-3xl font-bold text-blue-600 mb-1">
                    <?php
                        if ($revenuePeriod === 'daily' && count($revenueReport) > 0) {
                            $today        = date('Y-m-d');
                            $bookingCount = 0;
                            foreach ($revenueReport as $row) {
                                if ($row['label'] === $today) {
                                    $bookingCount = $row['bookingCount'] ?? 0;
                                    break;
                                }
                            }
                        } else if ($revenuePeriod === 'monthly' && count($revenueReport) > 0) {
                            $currentMonth = date('Y-m');
                            $bookingCount = 0;
                            foreach ($revenueReport as $row) {
                                if ($row['label'] === $currentMonth) {
                                    $bookingCount = $row['bookingCount'] ?? 0;
                                    break;
                                }
                            }
                        } else if ($revenuePeriod === 'yearly' && count($revenueReport) > 0) {
                            $currentYear  = date('Y');
                            $bookingCount = 0;
                            foreach ($revenueReport as $row) {
                                if ($row['label'] == $currentYear) {
                                    $bookingCount = $row['bookingCount'] ?? 0;
                                    break;
                                }
                            }
                        } else {
                            $bookingCount = array_sum(array_column($revenueReport, 'bookingCount')) ?? 0;
                        }
                        echo $bookingCount;
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="h-64 flex flex-col items-center justify-center text-gray-400">
        <i data-lucide="bar-chart" class="h-16 w-16 mb-4"></i>
        <p class="text-lg font-medium mb-2">ยังไม่มีข้อมูลรายได้ในช่วงเวลานี้</p>
        <p class="text-sm">เริ่มรับการจองเพื่อดูสถิติรายได้</p>
    </div>
<?php endif; ?>
    </div>  <!-- ปิด div ของ Revenue Chart -->

    <!-- Recent Activities & Bookings -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Activities -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-lg text-gray-800">กิจกรรมล่าสุด</h2>
                        <p class="text-sm text-gray-600 mt-1">ประวัติการดำเนินงานทั้งหมด</p>
                    </div>
                </div>
            </div>
            <div class="p-5 max-h-96 overflow-y-auto">
                <?php if (! empty($recentActivities)): ?>
                    <div class="space-y-4">
                        <?php foreach ($recentActivities as $a): ?>
                            <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="flex-shrink-0 mt-1">
                                    <?php if ($a['type'] === 'booking'): ?>
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i data-lucide="calendar" class="h-4 w-4 text-blue-600"></i>
                                        </div>
                                    <?php elseif ($a['type'] === 'payment'): ?>
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <i data-lucide="credit-card" class="h-4 w-4 text-green-600"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo e($a['description']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i data-lucide="clock" class="h-3 w-3 inline mr-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($a['createdAt'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-12 text-center text-gray-400">
                        <i data-lucide="activity" class="h-12 w-12 mx-auto mb-3"></i>
                        <p class="text-sm">ยังไม่มีกิจกรรมในช่วงเวลานี้</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-lg text-gray-800">การจองล่าสุด</h2>
                        <p class="text-sm text-gray-600 mt-1">5 รายการล่าสุด</p>
                    </div>
                    <a href="index.php?page=admin&section=bookings"
                       class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        ดูทั้งหมด →
                    </a>
                </div>
            </div>
            <div class="p-5 max-h-96 overflow-y-auto">
                <?php if (! empty($recentBookings)): ?>
                    <div class="space-y-4">
                        <?php foreach ($recentBookings as $booking): ?>
                            <?php
                                $statusText  = $booking['status'] ?? 'pending';
                                $statusColor = 'bg-gray-100 text-gray-800';
                                $statusIcon  = 'clock';

                                if ($statusText === 'pending') {
                                    $statusText  = 'รอยืนยัน';
                                    $statusColor = 'bg-yellow-100 text-yellow-800';
                                } elseif ($statusText === 'confirmed') {
                                    $statusText  = 'ยืนยันแล้ว';
                                    $statusColor = 'bg-blue-100 text-blue-800';
                                    $statusIcon  = 'check-circle';
                                } elseif ($statusText === 'active') {
                                    $statusText  = 'กำลังเช่า';
                                    $statusColor = 'bg-green-100 text-green-800';
                                    $statusIcon  = 'play-circle';
                                } elseif ($statusText === 'completed') {
                                    $statusText  = 'เสร็จสิ้น';
                                    $statusColor = 'bg-gray-100 text-gray-800';
                                    $statusIcon  = 'check-circle';
                                } elseif ($statusText === 'cancelled') {
                                    $statusText  = 'ยกเลิก';
                                    $statusColor = 'bg-red-100 text-red-800';
                                    $statusIcon  = 'circle-x';
                                }
                            ?>

                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors border border-gray-100">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-sm font-medium text-gray-900">
                                            <?php echo e($booking['brand'] . ' ' . $booking['model']); ?>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                            <i data-lucide="<?php echo $statusIcon; ?>" class="h-3 w-3 mr-1"></i>
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-1">
                                        <i data-lucide="user" class="h-3 w-3 inline mr-1"></i>
                                        <?php echo e($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <i data-lucide="calendar" class="h-3 w-3 inline mr-1"></i>
                                        <?php echo date('d/m/Y', strtotime($booking['start_date'])) . ' - ' . date('d/m/Y', strtotime($booking['end_date'])); ?>
                                    </p>
                                </div>
                                <div class="text-right ml-4">
                                    <p class="text-sm font-bold text-gray-900">
                                        ฿<?php echo number_format($booking['total_price'], 0); ?>
                                    </p>
                                    <a href="index.php?page=admin&section=reservations&action=view&id=<?php echo urlencode($booking['reservation_id']); ?>"
                                       class="text-xs text-blue-600 hover:text-blue-700 mt-1 inline-block">
                                        ดูรายละเอียด
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-12 text-center text-gray-400">
                        <i data-lucide="calendar" class="h-12 w-12 mx-auto mb-3"></i>
                        <p class="text-sm">ยังไม่มีการจอง</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) {
            lucide.createIcons();
        }

        <?php if (! empty($revenueReport)): ?>
            const labels = <?php echo json_encode(array_column($revenueReport, 'label')); ?>;
            const revenues = <?php echo json_encode(array_column($revenueReport, 'revenue')); ?>;
            const bookingCounts = <?php echo json_encode(array_column($revenueReport, 'bookingCount')); ?>;

            const revenueColor = 'rgba(59, 130, 246, 0.8)';
            const bookingColor = 'rgba(16, 185, 129, 0.8)';

            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'รายได้ (บาท)',
                            data: revenues,
                            backgroundColor: revenueColor,
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'จำนวนการจอง',
                            data: bookingCounts,
                            backgroundColor: bookingColor,
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 1,
                            type: 'line',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label.includes('รายได้')) {
                                        return `${label}: ฿${context.parsed.y.toLocaleString()}`;
                                    }
                                    return `${label}: ${context.parsed.y} ครั้ง`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'รายได้ (บาท)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '฿' + value.toLocaleString();
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'จำนวนการจอง'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + ' ครั้ง';
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>