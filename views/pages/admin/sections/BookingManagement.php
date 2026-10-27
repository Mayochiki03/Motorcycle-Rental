<?php

    require_once APP_ROOT . '/src/Services/Admin/AdminService.php';
    use Service\Admin\AdminService;

    AdminService::autoCancelExpiredUnpaidBookings();

    $bookings = AdminService::getAllReservationsDetailed();

    function badge($text, $class)
    {
    return "<span class='px-3 py-1.5 rounded-full text-xs font-medium {$class}'>{$text}</span>";
    }
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">จัดการการจอง</h1>
            <p class="text-gray-600 mt-1">ตรวจสอบการจอง ประวัติลูกค้า และตัดสินใจอนุมัติ</p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Filter Dropdown -->
            <div class="relative">
                <select id="filterStatus" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="pending">รอดำเนินการ</option>
                    <option value="confirmed">ยืนยันแล้ว</option>
                    <option value="completed">เสร็จสิ้น</option>
                    <option value="cancelled">ยกเลิก</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>

            <!-- Search Input -->
            <div class="relative">
                <input type="text"
                       id="searchBooking"
                       placeholder="ค้นหาการจอง..."
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-700 font-medium">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-blue-800 mt-1"><?php echo count($bookings); ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-700 font-medium">รอดำเนินการ</p>
                    <p class="text-2xl font-bold text-yellow-800 mt-1"><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'pending')); ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-700 font-medium">ชำระเงินแล้ว</p>
                    <p class="text-2xl font-bold text-green-800 mt-1"><?php echo count(array_filter($bookings, fn($b) => $b['payment_status'] === 'verified'
                                                                      )); ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-700 font-medium">ยกเลิกแล้ว</p>
                    <p class="text-2xl font-bold text-red-800 mt-1"><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'cancelled')); ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-lg text-gray-800">รายการจองทั้งหมด</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        <?php echo count($bookings); ?> การจอง
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            รหัสการจอง
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            ลูกค้า
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            รถเช่า
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            วันที่เช่า
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            ยอดรวม
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            ชำระเงิน
                        </th>
                        <!-- เพิ่มคอลัมน์จัดการชำระเงิน ↓ -->
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider text-right">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bookings as $b): ?>
                        <tr class="hover:bg-gray-50 transition-colors"
                            data-status="<?php echo e($b['status']); ?>"
                            data-search="<?php echo strtolower(e($b['reservationId'] . ' ' . $b['customerName'] . ' ' . $b['brand'] . ' ' . $b['model'])); ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 font-mono">
                                    <?php echo e($b['reservationId']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($b['created_at'] ?? '')); ?>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-purple-100 to-purple-50 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo e($b['customerName']); ?>
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <?php echo e($b['customerEmail']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-50 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo e($b['brand'] . ' ' . $b['model']); ?>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">
                                            <?php echo e($b['license_plate'] ?? 'ไม่มีทะเบียน'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($b['startDate'])); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    ถึง <?php echo date('d/m/Y', strtotime($b['endDate'])); ?>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    <?php
                                        $start    = new DateTime($b['startDate']);
                                        $end      = new DateTime($b['endDate']);
                                        $interval = $start->diff($end);
                                        echo($interval->days + 1) . ' วัน';
                                    ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">
                                    ฿<?php echo number_format($b['final_price'], 2); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    ราคา/วัน: ฿<?php echo number_format($b['price_per_day'] ?? 0, 2); ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                    $statusColors = [
                                        'pending'   => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusTexts = [
                                        'pending'   => 'รอดำเนินการ',
                                        'confirmed' => 'ยืนยันแล้ว',
                                        'completed' => 'เสร็จสิ้น',
                                        'cancelled' => 'ยกเลิก',
                                    ];
                                ?>
                                <div class="space-y-2">
                                    <?php echo badge($statusTexts[$b['status']] ?? $b['status'], $statusColors[$b['status']] ?? 'bg-gray-100 text-gray-800'); ?>
                                    <?php if ($b['status'] === 'pending' && strtotime($b['startDate']) > time()): ?>
                                        <div class="text-xs text-gray-500">
                                            เริ่ม: <?php echo date('d/m/Y', strtotime($b['startDate'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="space-y-2">
                                    <?php if ($b['payment_status'] === 'verified'): ?>

                                        <?php echo badge('ชำระแล้ว', 'bg-green-100 text-green-800'); ?>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($b['payment_date'] ?? '')); ?>
                                        </div>
                                    <?php else: ?>
                                        <?php echo badge('ยังไม่ชำระ', 'bg-yellow-100 text-yellow-800'); ?>
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <div class="text-xs text-red-500">
                                                รอดำเนินการชำระเงิน
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button onclick="openBookingModal('<?php echo e($b['reservationId']); ?>')"
                                            class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow text-sm">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        ดูรายละเอียด
                                    </button>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-500 mb-2">ยังไม่มีข้อมูลการจอง</p>
                                    <p class="text-gray-400">รอลูกค้าทำการจองรถ</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (! empty($bookings)): ?>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-sm text-gray-600 gap-4">
                    <div>
                        แสดง <span class="font-semibold"><?php echo count($bookings); ?></span> รายการ
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="inline-flex items-center text-sm">
                            <span class="w-3 h-3 rounded-full bg-yellow-100 mr-1"></span>
                            รอดำเนินการ
                            <span class="mx-2">•</span>
                            <span class="w-3 h-3 rounded-full bg-blue-100 mr-1"></span>
                            ยืนยันแล้ว
                            <span class="mx-2">•</span>
                            <span class="w-3 h-3 rounded-full bg-green-100 mr-1"></span>
                            เสร็จสิ้น
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ================= MODAL จอง ================= -->
<div id="booking-modal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-start overflow-auto p-4">

    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden mt-16 animate-fadeIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">รายละเอียดการจอง</h3>
                        <p id="modal-reservation-id" class="text-blue-100 text-sm font-mono"></p>
                    </div>
                </div>
                <button onclick="closeBookingModal()"
                        class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Content -->
        <div class="p-6 max-h-[70vh] overflow-y-auto">
            <div id="booking-detail" class="space-y-6">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
            <button onclick="closeBookingModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                ปิด
            </button>
            <button id="modal-action-button"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors hidden">
                ดำเนินการ
            </button>
        </div>
    </div>
</div>

<!-- ================= MODAL สลิป ================= -->
<div id="slip-modal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center p-4">

    <div class="bg-white rounded-xl shadow-2xl overflow-hidden max-w-2xl w-full animate-fadeIn">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">สลิปการชำระเงิน</h3>
                        <p id="slip-reservation-id" class="text-blue-100 text-sm font-mono"></p>
                    </div>
                </div>
                <button onclick="closeSlipModal()"
                        class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div id="slip-detail" class="space-y-4">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
            <button onclick="closeSlipModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                ปิด
            </button>
        </div>
    </div>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

/* Custom scrollbar */
#booking-modal .overflow-y-auto,
#slip-modal .overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f9fafb;
}

#booking-modal .overflow-y-auto::-webkit-scrollbar,
#slip-modal .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

#booking-modal .overflow-y-auto::-webkit-scrollbar-track,
#slip-modal .overflow-y-auto::-webkit-scrollbar-track {
    background: #f9fafb;
}

#booking-modal .overflow-y-auto::-webkit-scrollbar-thumb,
#slip-modal .overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: #d1d5db;
    border-radius: 3px;
}
</style>

<script>

// escape ข้อความก่อนใส่ลงใน innerHTML (กัน XSS จากชื่อ/ที่อยู่/หมายเหตุที่ผู้ใช้กรอกมา)
function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
}

// อนุญาตเฉพาะลิงก์ http/https (กัน javascript: ใน href/src)
function safeUrl(value) {
    return /^https?:\/\//i.test(String(value ?? '')) ? esc(value) : '';
}

function formatDate(d) {
    const date = new Date(d);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDateTime(d) {
    const date = new Date(d);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function openBookingModal(reservationId) {
    fetch('api/admin/booking_detail.php?id=' + reservationId)
        .then(r => r.json())
        .then(data => {
            const b = data.booking;
            const c = data.customer;
            const p = data.payment || null;


            // Update modal title
            document.getElementById('modal-reservation-id').textContent = b.reservationId;

            // Calculate days
            const startDate = new Date(b.startDate);
            const endDate = new Date(b.endDate);
            const days = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

            // Prepare booking details HTML
            document.getElementById('booking-detail').innerHTML = `
                <!-- Customer Info -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6">
                    <h4 class="font-semibold text-lg text-gray-800 mb-4">ข้อมูลลูกค้า</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-600">ชื่อ-สกุล</div>
                            <div class="font-medium">${esc(c.firstName)} ${esc(c.lastName)}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">อีเมล</div>
                            <div class="font-medium">${esc(c.email)}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">เบอร์โทร</div>
                            <div class="font-medium">${esc(c.phone || 'ไม่มีข้อมูล')}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">ประวัติการจอง</div>
                            <div class="font-medium">${c.totalBookings || 0} ครั้ง</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-sm text-gray-600">ยอดใช้จ่ายรวม</div>
                            <div class="font-bold text-green-600 text-lg">฿${Number(c.totalSpent || 0).toLocaleString()}</div>
                        </div>
                    </div>
                </div>

                <!-- Motorcycle Info -->
                <div class="border border-gray-200 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold text-lg text-gray-800">ข้อมูลรถเช่า</h4>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                            ${esc(b.license_plate)}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-sm text-gray-600">ยี่ห้อ/รุ่น</div>
                            <div class="font-medium">${esc(b.brand)} ${esc(b.model)}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">สี</div>
                            <div class="font-medium">${esc(b.color || 'ไม่มีข้อมูล')}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">ราคา/วัน</div>
                            <div class="font-medium">฿${Number(b.price_per_day || 0).toLocaleString()}</div>
                        </div>
                    </div>
                </div>

                <!-- Booking Period -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-gray-200 rounded-xl p-6">
                        <h4 class="font-semibold text-lg text-gray-800 mb-4">ช่วงเวลาการเช่า</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                <div>
                                    <div class="text-sm text-gray-600">วันที่เริ่มเช่า</div>
                                    <div class="font-medium">${formatDate(b.startDate)}</div>
                                </div>
                                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                                <div>
                                    <div class="text-sm text-gray-600">วันที่สิ้นสุดเช่า</div>
                                    <div class="font-medium">${formatDate(b.endDate)}</div>
                                </div>
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-sm text-gray-600">ระยะเวลาเช่าทั้งหมด</div>
                                <div class="font-bold text-lg text-gray-800">${days} วัน</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="border border-gray-200 rounded-xl p-6">
                        <h4 class="font-semibold text-lg text-gray-800 mb-4">สรุปยอดชำระ</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between py-2">
                                <div class="text-gray-600">ราคา/วัน × ${days} วัน</div>
                                <div class="font-medium">฿${Number(b.price_per_day * days || 0).toLocaleString()}</div>
                            </div>
                            <div class="flex justify-between py-2">
                                <div class="text-gray-600">ค่ามัดจำ</div>
                                <div class="font-medium">฿500</div>
                            </div>
                            <div class="border-t pt-3 mt-3">
                                <div class="flex justify-between">
                                    <div class="font-bold text-lg text-gray-800">ยอดรวมทั้งสิ้น</div>
                                    <div class="font-bold text-2xl text-green-600">
                                        ฿${Number(b.final_price || 0).toLocaleString()}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-green-200 rounded-xl p-6 bg-green-50">
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-lg text-gray-800">สถานที่รับรถ</h4>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <div class="text-sm text-gray-600">สถานที่</div>
                                <div class="font-medium">${esc(b.pickup_location || 'ยังไม่ได้ระบุ')}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">รายละเอียดเพิ่มเติม</div>
                                <div class="font-medium">${esc(b.pickup_details || '-')}</div>
                            </div>
                        </div>
                    </div>


                    <!-- ================= PAYMENT SLIP ================= -->
                    <div class="border border-blue-200 rounded-xl p-6 bg-blue-50 space-y-4 row-span-2 flex flex-col">
                        <h4 class="font-semibold text-lg text-gray-800">
                            หลักฐานการชำระเงิน
                        </h4>

                        ${p ? `
                            ${p.slip_image_url ? `
                                <div class="bg-gray-100 rounded-lg p-4 flex justify-center">
                                    <img src="${safeUrl(p.slip_image_url)}"
                                        alt="สลิปการชำระเงิน"
                                        class="w-full object-contain rounded-lg shadow-sm max-h-[420px]"
                                        onerror="this.src='assets/img/default-slip.svg'; this.onerror=null;">
                                </div>
                            ` : `
                                <div class="text-center p-8 bg-gray-100 rounded-lg text-gray-500">
                                    ไม่มีสลิปการชำระเงิน
                                </div>
                            `}

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <div class="text-gray-600">ยอดที่ชำระ</div>
                                    <div class="font-bold text-green-600">
                                        ฿${Number(p.amount || 0).toLocaleString()}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-gray-600">วันที่ชำระ</div>
                                    <div class="font-medium">
                                        ${p.payment_date ? formatDate(p.payment_date) : '-'}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-gray-600">สถานะ</div>
                                    <div>
                                        <span class="px-2 py-1 text-xs rounded-full
                                            ${p.payment_status === 'verified' ? 'bg-green-100 text-green-800' :
                                            p.payment_status === 'rejected' ? 'bg-red-100 text-red-800' :
                                            'bg-yellow-100 text-yellow-800'}">
                                            ${p.payment_status === 'pending' ? 'รอดำเนินการ' :
                                            p.payment_status === 'verified' ? 'ยืนยันแล้ว' :
                                            p.payment_status === 'rejected' ? 'ปฏิเสธแล้ว' : p.payment_status}
                                        </span>
                                    </div>
                                </div>
                                ${p.notes ? `
                                    <div class="col-span-2">
                                        <div class="text-gray-600">หมายเหตุ</div>
                                        <div class="text-sm text-gray-700 bg-white p-2 rounded">${esc(p.notes)}</div>
                                    </div>
                                ` : ''}
                            </div>

                            <!-- แสดงปุ่มเฉพาะตอน payment_status = pending -->
                            ${p.payment_status === 'pending' ? `
                                <div class="flex gap-3 pt-4 border-t">
                                    <button onclick="verifyPayment('${esc(b.reservationId)}')"
                                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg">
                                        ยืนยันการชำระเงิน
                                    </button>
                                    <button onclick="rejectPayment('${esc(b.reservationId)}')"
                                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg">
                                        ปฏิเสธการชำระเงิน
                                    </button>
                                </div>
                            ` : ''}
                        ` : `
                            <div class="text-center p-8 bg-gray-100 rounded-lg text-gray-500">
                                ยังไม่มีข้อมูลการชำระเงิน
                            </div>
                        `}
                    </div>


                    <!-- ================= สถานที่คืนรถ ================= -->
                    <div class="border border-red-200 rounded-xl p-6 bg-red-50">
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-lg text-gray-800">สถานที่คืนรถ</h4>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <div class="text-sm text-gray-600">สถานที่ (บันทึกโดยแอดมิน)</div>
                                <div class="font-medium">${esc(b.return_location || 'ยังไม่ได้ระบุ')}</div>
                            </div>

                            <!-- แสดงข้อมูลการคืนรถเสมอถ้ามี -->
                            ${b.return_condition ? `
                                <div class="mt-3 pt-3 border-t border-red-200">
                                    <div class="text-sm font-semibold text-gray-700 mb-2">ข้อมูลการคืนรถ:</div>
                                    <div class="bg-white p-3 rounded-lg">
                                        <!-- ในส่วนแสดงข้อมูลการคืนรถ -->
                                <div class="flex items-center mb-2">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        ${b.return_motorcycle_status === 'READY' ? 'bg-green-100 text-green-800' :
                                        b.return_motorcycle_status === 'MAINTENANCE' ? 'bg-yellow-100 text-yellow-800' :
                                        b.return_motorcycle_status === 'CLEANING' ? 'bg-blue-100 text-blue-800' :
                                        b.return_motorcycle_status === 'DAMAGED' ? 'bg-red-100 text-red-800' :
                                        b.return_motorcycle_status === 'LOST' ? 'bg-gray-900 text-white' :
                                        b.return_motorcycle_status === 'UNAVAILABLE' ? 'bg-gray-100 text-gray-800' :
                                        'bg-gray-100 text-gray-800'}">
                                        ${b.return_motorcycle_status === 'READY' ? 'สภาพปกติ' :
                                        b.return_motorcycle_status === 'MAINTENANCE' ? 'มีรอย/เสียหายเล็กน้อย' :
                                        b.return_motorcycle_status === 'CLEANING' ? 'กำลังทำความสะอาด' :
                                        b.return_motorcycle_status === 'DAMAGED' ? 'เสียหายรุนแรง' :
                                        b.return_motorcycle_status === 'LOST' ? 'สูญหาย' :
                                        b.return_motorcycle_status === 'UNAVAILABLE' ? 'ไม่พร้อมใช้งาน' :
                                        b.return_motorcycle_status}
                                    </span>
                                </div>
                                        <div class="text-sm text-gray-700 whitespace-pre-wrap">
                                            ${esc(b.return_condition)}
                                        </div>
                                        ${b.return_checked_by ? `
                                            <div class="text-xs text-gray-500 mt-2">
                                                ผู้ตรวจรับ: ${esc(b.return_checked_by)}
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            ` : `
                                <div>
                                    <div class="text-sm text-gray-600">สภาพรถเมื่อคืน</div>
                                    <div class="font-medium">${esc(b.return_details || '-')}</div>
                                </div>
                            `}
                        </div>
                    </div>

                        ${b.status === 'completed' ? `
                        <!-- เสร็จสิ้น - แสดงสรุปการคืนรถ -->
                        <div class="border border-green-200 rounded-xl p-6 bg-green-50">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex-shrink-0 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-lg text-gray-800">การเช่าเสร็จสิ้นแล้ว</h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    ยกเลิกเมื่อ ${b.updatedAt ? formatDateTime(b.updatedAt) : '-'}
                                </p>
                                </div>
                            </div>
                        </div>
                    ` : b.status === 'cancelled' ? `
                        <!-- ยกเลิก - แสดงข้อมูลการยกเลิก -->
                        <div class="border border-red-200 rounded-xl p-6 bg-red-50">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex-shrink-0 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-lg text-gray-800">การจองถูกยกเลิก</h4>
                                    <p class="text-sm text-gray-600 mt-1">
                                        ยกเลิกเมื่อ ${b.updatedAt ? formatDateTime(b.updatedAt) : '-'}
                                    </p>
                                    ${p?.notes ? `
                                        <div class="mt-3 p-3 bg-white rounded-lg border border-red-100">
                                            <div class="text-xs font-semibold text-red-800 mb-1">เหตุผลที่ปฏิเสธ:</div>
                                            <div class="text-sm text-gray-700">${esc(p.notes)}</div>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    ` : b.status === 'confirmed' || b.status === 'pending' ? `
                        <!-- ⏳ รอดำเนินการ/ยืนยันแล้ว - แสดงฟอร์มคืนรถ -->
                        <div class="border border-purple-200 rounded-xl p-6 bg-purple-50">
                            <h4 class="font-semibold text-lg text-gray-800 mb-4">
                                ปิดงาน / คืนรถ
                            </h4>

                            <!-- Checkbox สภาพรถ (Radio Buttons) -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    สภาพรถหลังคืน <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-3 bg-white p-4 rounded-lg border border-purple-100">
                                    <div class="flex items-center">
                                        <input type="radio"
                                            name="motorcycle_status"
                                            id="status_ready"
                                            value="READY"
                                            checked
                                            class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                        <label for="status_ready" class="ml-3 block text-sm font-medium text-gray-700">
                                            <span class="text-green-600 font-semibold">สภาพปกติ</span>
                                            <span class="text-gray-500">- ไม่มีรอยขีดข่วน, เครื่องยนต์ปกติ</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio"
                                            name="motorcycle_status"
                                            id="status_maintenance"
                                            value="MAINTENANCE"
                                            class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                        <label for="status_maintenance" class="ml-3 block text-sm font-medium text-gray-700">
                                            <span class="text-yellow-600 font-semibold">มีรอย/เสียหายเล็กน้อย</span>
                                            <span class="text-gray-500">- มีรอยขีดข่วน, เซาะร่อง, ถลอก</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio"
                                            name="motorcycle_status"
                                            id="status_damaged"
                                            value="DAMAGED"
                                            class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                        <label for="status_damaged" class="ml-3 block text-sm font-medium text-gray-700">
                                            <span class="text-red-600 font-semibold">เสียหายรุนแรง</span>
                                            <span class="text-gray-500">- ชิ้นส่วนแตกหัก, เครื่องยนต์มีปัญหา</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio"
                                            name="motorcycle_status"
                                            id="status_lost"
                                            value="LOST"
                                            class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                        <label for="status_lost" class="ml-3 block text-sm font-medium text-gray-700">
                                            <span class="text-gray-900 font-semibold">สูญหาย/ไม่คืนรถ</span>
                                            <span class="text-gray-500">- ลูกค้าไม่นำรถมาคืน</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- รายละเอียดเพิ่มเติม (Textarea) -->
                            <div class="mb-4">
                                <label for="return-condition" class="block text-sm font-medium text-gray-700 mb-2">
                                    รายละเอียดเพิ่มเติม / หมายเหตุ
                                </label>
                                <textarea id="return-condition"
                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                        rows="4"
                                        placeholder="ระบุรายละเอียดเพิ่มเติม เช่น จุดที่มีรอย, อะไหล่ที่เสียหาย, ค่าเสียหาย ฯลฯ"></textarea>
                            </div>

                            <!-- ปุ่มยืนยัน -->
                            <button onclick="completeBookingWithCondition('${esc(b.reservationId)}')"
                                    class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-lg font-medium shadow-sm hover:shadow transition-all">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                ยืนยันการคืนรถ
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;

            // Show modal
            document.getElementById('booking-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        })
        .catch(error => {
            console.error('Error loading booking details:', error);
            alert('ไม่สามารถโหลดข้อมูลได้');
        });
}

function closeBookingModal() {
    document.getElementById('booking-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// ==================== ฟังก์ชันจัดการชำระเงิน ====================

// ดูสลิป
function viewSlip(reservationId) {
    fetch('api/admin/payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=view_slip&reservation_id=${reservationId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.payment) {
            showSlipModal(data.payment);
        } else {
            alert('ไม่พบสลิปการชำระเงิน');
        }
    })
    .catch(error => {
        console.error('Error viewing slip:', error);
        alert('ไม่สามารถโหลดสลิปได้');
    });
}

// ยืนยันการชำระเงิน
function verifyPayment(reservationId) {
    if (!confirm('ยืนยันการตรวจสอบสลิปและอนุมัติการชำระเงิน?')) return;

    fetch('api/admin/payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=verify&reservation_id=${reservationId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถยืนยันการชำระเงินได้'));
        }
    })
    .catch(error => {
        console.error('Error verifying payment:', error);
        alert('เกิดข้อผิดพลาดในการยืนยัน');
    });
}

// ปฏิเสธการชำระเงิน
function rejectPayment(reservationId) {
    const reason = prompt('โปรดระบุเหตุผลในการปฏิเสธ:');
    if (reason === null) return;

    fetch('api/admin/payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=reject&reservation_id=${reservationId}&reason=${encodeURIComponent(reason)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถปฏิเสธการชำระเงินได้'));
        }
    })
    .catch(error => {
        console.error('Error rejecting payment:', error);
        alert('เกิดข้อผิดพลาดในการปฏิเสธ');
    });
}

// เสร็จสิ้นการเช่า
function completeBooking(reservationId) {
    if (!confirm('ยืนยันว่าการเช่าเสร็จสิ้นและรถถูกคืนแล้ว?')) return;

    fetch('api/admin/payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=complete&reservation_id=${reservationId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถอัปเดตสถานะได้'));
        }
    })
    .catch(error => {
        console.error('Error completing booking:', error);
        alert('เกิดข้อผิดพลาดในการอัปเดต');
    });
}

// ==================== ฟังก์ชันจัดการคืนรถ (แบบมี Checkbox) ====================
function completeBookingWithCondition(reservationId)
{
    // รับค่าสภาพรถจาก Radio
    const motorcycleStatus = document.querySelector('input[name="motorcycle_status"]:checked');
    if (! motorcycleStatus) {
        alert('กรุณาเลือกสภาพรถหลังคืน');
        return;
    }

    const returnCondition = document.getElementById('return-condition').value.trim();

    // ถ้าเลือกสภาพเสียหาย ให้แน่ใจว่ากรอกรายละเอียดด้วย
    if (motorcycleStatus.value !== 'READY' && returnCondition === '') {
        alert('กรุณาระบุรายละเอียดเพิ่มเติมเกี่ยวกับสภาพรถ');
        return;
    }

    // ถ้าเลือกสภาพเสียหายรุนแรง หรือ สูญหาย ให้เตือนอีกครั้ง
    if (motorcycleStatus.value === 'DAMAGED' || motorcycleStatus.value === 'LOST') {
        if (! confirm('คำเตือน: คุณกำลังบันทึกว่ารถเสียหายรุนแรงหรือสูญหาย\n\nกรุณาตรวจสอบข้อมูลให้ถูกต้องก่อนดำเนินการ')) {
            return;
        }
    }

    if (! confirm('ยืนยันว่าการเช่าเสร็จสิ้นและบันทึกสภาพรถเรียบร้อย?')) {
        return;
    }

    // แสดง Loading
    const btn          = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML      = '<svg class="animate-spin w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> กำลังบันทึก...';
    btn.disabled       = true;

    // ส่งข้อมูลไป API
    fetch('api/admin/payment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=complete&reservation_id=${encodeURIComponent(reservationId)}&return_condition=${encodeURIComponent(returnCondition)}&motorcycle_status=${encodeURIComponent(motorcycleStatus.value)}`
    })
    . then(response => {
        if (! response.ok) {
            return response.json().then(err => {throw new Error(err.error || 'เกิดข้อผิดพลาด');});
        }
        return response.json();
    })
    . then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert((data.error || 'เกิดข้อผิดพลาด'));
            btn.innerHTML = originalText;
            btn.disabled  = false;
        }
    })
    . catch (error => {
        console.error('Error:', error);
        alert('ไม่สามารถบันทึกข้อมูลได้: '+error.message);
        btn.innerHTML = originalText;
        btn.disabled  = false;
    });
}


// แสดง Modal สำหรับสลิป
function showSlipModal(payment) {
    document.getElementById('slip-reservation-id').textContent = payment.reservation_id || 'N/A';

    const slipDetail = document.getElementById('slip-detail');
    slipDetail.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <div class="text-sm text-gray-600">ลูกค้า</div>
                <div class="font-medium">${esc(payment.first_name || '')} ${esc(payment.last_name || '')}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">ยอดชำระ</div>
                <div class="font-bold text-green-600">฿${Number(payment.amount || 0).toLocaleString()}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">วันที่ชำระ</div>
                <div class="font-medium">${payment.payment_date ? formatDate(payment.payment_date) : '-'}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">สถานะ</div>
                <div class="font-medium ${payment.payment_status === 'verified' ? 'text-green-600' : payment.payment_status === 'rejected' ? 'text-red-600' : 'text-yellow-600'}">
                    ${payment.payment_status === 'pending' ? 'รอดำเนินการ' :
                      payment.payment_status === 'verified' ? 'ยืนยันแล้ว' :
                      payment.payment_status === 'rejected' ? 'ปฏิเสธแล้ว' : payment.payment_status}
                </div>
            </div>
        </div>

        <div class="border-t pt-4">
            <h4 class="font-semibold text-lg text-gray-800 mb-4">สลิปโอนเงิน</h4>
            ${payment.slip_image_url ?
                `<div class="bg-gray-100 rounded-lg p-4 flex justify-center">
                    <img src="${safeUrl(payment.slip_image_url)}"
                         alt="สลิปการชำระเงิน"
                         class="max-w-full h-auto rounded-lg shadow-sm max-h-96"
                         onerror="this.src='assets/img/default-slip.svg'; this.onerror=null;">
                </div>` :
                `<div class="text-center p-8 bg-gray-100 rounded-lg">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-500">ไม่มีสลิปการชำระเงิน</p>
                </div>`
            }

            ${payment.notes ?
                `<div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="text-sm font-semibold text-yellow-800 mb-1">หมายเหตุ:</div>
                    <div class="text-sm text-yellow-700">${esc(payment.notes)}</div>
                </div>` : ''
            }
        </div>
    `;

    document.getElementById('slip-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeSlipModal() {
    document.getElementById('slip-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('booking-modal').addEventListener('click', function(e) {
    if (e.target === this) closeBookingModal();
});

document.getElementById('slip-modal').addEventListener('click', function(e) {
    if (e.target === this) closeSlipModal();
});

// Filter and Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterStatus = document.getElementById('filterStatus');
    const searchInput = document.getElementById('searchBooking');
    const rows = document.querySelectorAll('tbody tr');

    function filterTable() {
        const statusValue = filterStatus.value;
        const searchValue = searchInput.value.toLowerCase();

        rows.forEach(row => {
            const statusMatch = !statusValue || row.getAttribute('data-status') === statusValue;
            const searchMatch = !searchValue || row.getAttribute('data-search').includes(searchValue);

            row.style.display = (statusMatch && searchMatch) ? '' : 'none';
        });
    }

    filterStatus?.addEventListener('change', filterTable);
    searchInput?.addEventListener('input', filterTable);
});
</script>