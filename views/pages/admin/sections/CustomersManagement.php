<?php
    // views/pages/admin/sections/CustomersManagement.php
    require_once APP_ROOT . '/src/Services/Admin/AdminService.php';
    use Service\Admin\AdminService;

    $customers = AdminService::getAllCustomers();
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">จัดการลูกค้า</h1>
            <p class="text-gray-600 mt-1">ดูข้อมูลลูกค้า ประวัติการจอง และจัดการการเข้าถึงระบบ</p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Search Input -->
            <div class="relative">
                <input type="text"
                       id="searchCustomer"
                       placeholder="ค้นหาลูกค้า..."
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
                    <p class="text-sm text-blue-700 font-medium">ลูกค้าทั้งหมด</p>
                    <p class="text-2xl font-bold text-blue-800 mt-1"><?php echo count($customers); ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 3.75l-2.5 2.5m-5-5l5-5m-5 5l5 5"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-700 font-medium">กำลังใช้งาน</p>
                    <p class="text-2xl font-bold text-green-800 mt-1">
                        <?php
                            $activeCustomers = array_filter($customers, fn($c) => ($c['isActive'] ?? 1) == 1);
                            echo count($activeCustomers);
                        ?>
                    </p>
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
                    <p class="text-sm text-red-700 font-medium">ถูกแบน</p>
                    <p class="text-2xl font-bold text-red-800 mt-1">
                        <?php
                            $bannedCustomers = array_filter($customers, fn($c) => ($c['isActive'] ?? 1) == 0);
                            echo count($bannedCustomers);
                        ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-700 font-medium">ใหม่เดือนนี้</p>
                    <p class="text-2xl font-bold text-yellow-800 mt-1">
                        <?php
                            $currentMonth = date('Y-m');
                            $newThisMonth = array_filter($customers, function ($c) use ($currentMonth) {
                                return date('Y-m', strtotime($c['createdAt'])) === $currentMonth;
                            });
                            echo count($newThisMonth);
                        ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($customers)): ?>
        <!-- Empty State -->
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-2xl p-8 text-center">
            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">ไม่พบข้อมูลลูกค้า</h3>
            <p class="text-yellow-700">ยังไม่มีลูกค้าในระบบ</p>
        </div>
    <?php else: ?>

        <!-- Table Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-lg text-gray-800">รายชื่อลูกค้าทั้งหมด</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php echo count($customers); ?> คน
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                ลูกค้า
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                ข้อมูลติดต่อ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                สถิติ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider text-right">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($customers as $c): ?>
                            <tr class="hover:bg-gray-50 transition-colors"
                                data-search="<?php echo strtolower(e($c['customerId'] . ' ' . $c['firstName'] . ' ' . $c['lastName'] . ' ' . $c['email'])); ?>">
                                <!-- Customer Info -->
                                <td class="px-6 py-4">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-50 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">
                                                <?php echo e($c['firstName'] . ' ' . $c['lastName']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500 font-mono mt-1">
                                                ID: <?php echo e($c['customerId']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                สมัครเมื่อ: <?php echo date('d/m/Y', strtotime($c['createdAt'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact Info -->
                                <td class="px-6 py-4">
                                    <div class="space-y-2">
                                        <div class="flex items-center text-sm text-gray-900">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89-5.26a2 2 0 012.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            <?php echo e($c['email']); ?>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-900">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            <?php echo e($c['phone'] ?? 'ไม่มีข้อมูล'); ?>
                                        </div>
                                        <?php if (! empty($c['line_id'])): ?>
                                        <div class="flex items-center text-sm text-gray-900">
                                            <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                            <?php echo e($c['line_id']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Statistics -->
                                <td class="px-6 py-4">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">การจอง:</span>
                                            <span class="font-semibold"><?php echo($c['totalBookings'] ?? 0); ?> ครั้ง</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">ยอดใช้จ่าย:</span>
                                            <span class="font-semibold text-green-600">฿<?php echo number_format(($c['totalSpent'] ?? 0), 2); ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($c['isActive'] ?? 1) == 1): ?>
                                        <span class="px-3 py-1.5 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                            <svg class="w-3 h-3 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            ใช้งาน
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1.5 rounded-full bg-red-100 text-red-700 text-xs font-medium">
                                            <svg class="w-3 h-3 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" clip-rule="evenodd"></path>
                                            </svg>
                                            ถูกแบน
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button onclick="openCustomerModal('<?php echo e($c['customerId']); ?>')"
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
                    </tbody>
                </table>
            </div>

            <?php if (! empty($customers)): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between text-sm text-gray-600 gap-4">
                        <div>
                            แสดง <span class="font-semibold"><?php echo count($customers); ?></span> ลูกค้า
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="inline-flex items-center text-sm">
                                <span class="w-3 h-3 rounded-full bg-green-100 mr-1"></span>
                                ใช้งาน
                                <span class="mx-2">•</span>
                                <span class="w-3 h-3 rounded-full bg-red-100 mr-1"></span>
                                ถูกแบน
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<!-- ================= MODAL ================= -->
<div id="customer-modal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-start overflow-auto p-4">

    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden mt-16 animate-fadeIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">ข้อมูลลูกค้า</h3>
                        <p id="modal-customer-id" class="text-blue-100 text-sm font-mono"></p>
                    </div>
                </div>
                <button onclick="closeCustomerModal()"
                        class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Content -->
        <div class="p-6 max-h-[70vh] overflow-y-auto">
            <div id="customer-detail" class="space-y-6">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
            <div id="ban-button-container">
                <!-- ปุ่มแบน/ยกเลิกการแบน จะถูกใส่ตรงนี้โดย JavaScript -->
            </div>
            <button onclick="closeCustomerModal()"
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
#customer-modal .overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f9fafb;
}

#customer-modal .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

#customer-modal .overflow-y-auto::-webkit-scrollbar-track {
    background: #f9fafb;
}

#customer-modal .overflow-y-auto::-webkit-scrollbar-thumb {
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

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function openCustomerModal(customerId) {
    fetch('api/admin/customer_detail.php?id=' + customerId)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            const p = data.profile;
            const list = data.reservations || [];

            // Update modal title
            document.getElementById('modal-customer-id').textContent = p.customerId;

            // สร้างปุ่มแบน/ยกเลิกการแบน
            const banButtonHtml = p.isActive == 1 ?
                `<button onclick="toggleCustomerStatus('${esc(p.customerId)}', 'ban')"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                    แบนลูกค้า
                </button>` :
                `<button onclick="toggleCustomerStatus('${esc(p.customerId)}', 'unban')"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    ยกเลิกการแบน
                </button>`;

            // ปุ่มรีเซ็ตรหัสผ่าน (สำหรับลูกค้าที่ลืมรหัสผ่าน)
            const resetButtonHtml = `<button onclick="resetCustomerPassword('${esc(p.customerId)}')"
                        class="inline-flex items-center px-4 py-2 ml-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow">
                    รีเซ็ตรหัสผ่าน
                </button>`;

            document.getElementById('ban-button-container').innerHTML = banButtonHtml + resetButtonHtml;

            // Prepare customer details HTML
            document.getElementById('customer-detail').innerHTML = `
                <!-- Profile Section -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-6">
                    <div class="flex flex-col md:flex-row md:items-center gap-6">
                        <!-- Avatar -->
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                            <span class="text-2xl font-bold text-white">
                                ${esc(p.firstName?.charAt(0) || '')}${esc(p.lastName?.charAt(0) || '')}
                            </span>
                        </div>

                        <!-- Basic Info -->
                        <div class="flex-1">
                            <h4 class="text-xl font-bold text-gray-800 mb-2">
                                ${esc(p.firstName)} ${esc(p.lastName)}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="space-y-2">
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89-5.26a2 2 0 012.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        ${esc(p.email)}
                                    </div>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        ${esc(p.phone || 'ไม่มีข้อมูล')}
                                    </div>
                                    ${p.lineId ? `
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        Line ID: ${esc(p.lineId)}
                                    </div>
                                    ` : ''}
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        สมัครเมื่อ: ${formatDate(p.createdAt)}
                                    </div>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        จองล่าสุด: ${formatDate(p.lastBookingAt) || 'ยังไม่เคยจอง'}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            ${p.isActive == 1 ?
                                '<span class="px-4 py-2 bg-green-100 text-green-700 rounded-full font-medium">ใช้งานอยู่</span>' :
                                '<span class="px-4 py-2 bg-red-100 text-red-700 rounded-full font-medium">ถูกแบน</span>'
                            }
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-5">
                        <div class="text-center">
                            <div class="text-sm text-blue-700 font-medium mb-2">จำนวนการจอง</div>
                            <div class="text-3xl font-bold text-blue-800">${p.totalBookings || 0}</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-5">
                        <div class="text-center">
                            <div class="text-sm text-green-700 font-medium mb-2">ยอดใช้จ่ายรวม</div>
                            <div class="text-3xl font-bold text-green-800">฿${Number(p.totalSpent || 0).toLocaleString()}</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-xl p-5">
                        <div class="text-center">
                            <div class="text-sm text-emerald-700 font-medium mb-2">สำเร็จ</div>
                            <div class="text-3xl font-bold text-emerald-800">${p.successful_bookings || 0}</div>
                            <div class="text-xs text-emerald-600 mt-1">รายการ</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200 rounded-xl p-5">
                        <div class="text-center">
                            <div class="text-sm text-rose-700 font-medium mb-2">ไม่สำเร็จ</div>
                            <div class="text-3xl font-bold text-rose-800">${p.failed_bookings || 0}</div>
                            <div class="text-xs text-rose-600 mt-1">รายการ</div>
                        </div>
                    </div>
                </div>

                <!-- Reservations Section -->
                <div>
                    <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        ประวัติการจอง (${list.length})
                    </h4>

                    ${list.length > 0 ?
                        `<div class="space-y-4">
                            ${list.sort((a, b) => new Date(b.startDate) - new Date(a.startDate))
                                .map(r => `
                                    <div class="border border-gray-200 rounded-xl p-5 hover:shadow-sm transition-shadow">
                                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                            <div>
                                                <div class="font-bold text-gray-900">${esc(r.brand)} ${esc(r.model)}</div>
                                                <div class="text-sm text-gray-600 mt-1">
                                                    ${formatDate(r.startDate)} → ${formatDate(r.endDate)}
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <span class="px-3 py-1 rounded-full ${r.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                                                        r.status === 'confirmed' ? 'bg-blue-100 text-blue-800' :
                                                                                        r.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                                                        'bg-red-100 text-red-800'} text-xs font-medium">
                                                    ${r.status === 'completed' ? 'เสร็จสิ้น' :
                                                     r.status === 'confirmed' ? 'ยืนยันแล้ว' :
                                                     r.status === 'pending' ? 'รอดำเนินการ' :
                                                     'ยกเลิก'}
                                                </span>
                                                <div class="font-bold text-lg text-green-600">
                                                    ฿${Number(r.final_price || 0).toLocaleString()}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                            <div class="space-y-2">
                                                <div class="text-gray-600">การชำระเงิน</div>
                                                <div class="font-medium">
                                                    ${r.payment_status === 'paid' ?
                                                        '<span class="text-green-600">ชำระแล้ว</span>' :
                                                        '<span class="text-yellow-600">ยังไม่ชำระ</span>'}
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="text-gray-600">ทะเบียนรถ</div>
                                                <div class="font-medium">${esc(r.license_plate || 'ไม่มีข้อมูล')}</div>
                                            </div>
                                            <div class="md:col-span-2 space-y-2">
                                                <div class="text-gray-600">สถานที่นัดรับรถ</div>
                                                <div class="font-medium">${esc(r.pickup_location || 'ยังไม่ได้ระบุ')}</div>
                                                ${r.pickup_details ?
                                                    `<div class="text-sm text-gray-500 mt-1">${esc(r.pickup_details)}</div>` :
                                                    ''}
                                            </div>
                                            ${r.return_location || r.return_details ? `
                                                <div class="md:col-span-2 space-y-2">
                                                    <div class="text-gray-600">บันทึกหลังคืนรถ</div>
                                                    <div class="bg-red-50 rounded-lg p-3">
                                                        ${r.return_location ? `<div class="font-medium">สถานที่: ${esc(r.return_location)}</div>` : ''}
                                                        ${r.return_details ? `<div class="text-sm text-gray-600 mt-1">รายละเอียด: ${esc(r.return_details)}</div>` : ''}
                                                    </div>
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                `).join('')
                            }
                        </div>` :
                        `<div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-2xl p-8 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <h5 class="text-lg font-semibold text-gray-600 mb-2">ไม่มีประวัติการจอง</h5>
                            <p class="text-gray-500">ลูกค้ายังไม่เคยทำการจองรถ</p>
                        </div>`
                    }
                </div>
            `;

            // Show modal
            document.getElementById('customer-modal').classList.remove('hidden');

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        })
        .catch(error => {
            console.error('Error loading customer details:', error);
            alert('ไม่สามารถโหลดข้อมูลได้');
        });
}

function closeCustomerModal() {
    document.getElementById('customer-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    // เคลียร์ปุ่มแบนเมื่อปิด modal
    document.getElementById('ban-button-container').innerHTML = '';
}

// Close modal when clicking outside
document.getElementById('customer-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCustomerModal();
    }
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchCustomer');
    const rows = document.querySelectorAll('tbody tr');

    function filterTable() {
        const searchValue = searchInput.value.toLowerCase();

        rows.forEach(row => {
            const searchMatch = !searchValue || row.getAttribute('data-search').includes(searchValue);
            row.style.display = searchMatch ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', filterTable);
});

// Function to toggle customer status (ban/unban)
function toggleCustomerStatus(customerId, action) {
    const actionText = action === 'ban' ? 'แบนลูกค้า' : 'ยกเลิกการแบนลูกค้า';
    const confirmText = action === 'ban'
        ? 'คุณแน่ใจว่าต้องการแบนลูกค้ารายนี้? ลูกค้าจะไม่สามารถเข้าสู่ระบบได้อีก'
        : 'คุณแน่ใจว่าต้องการยกเลิกการแบนลูกค้ารายนี้? ลูกค้าจะสามารถเข้าสู่ระบบได้อีกครั้ง';

    if (!confirm(confirmText)) {
        return;
    }

    // Show loading
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>กำลังดำเนินการ...';
    button.disabled = true;

    fetch('api/admin/toggle_customer_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            customerId: customerId,
            action: action
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // ปิด modal และ reload หน้า
            closeCustomerModal();
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถดำเนินการได้'));
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// รีเซ็ตรหัสผ่านลูกค้า: ระบบสร้างรหัสชั่วคราวให้ 1 ครั้ง แจ้งลูกค้าแล้วให้ลูกค้าเปลี่ยนเองที่หน้าโปรไฟล์
function resetCustomerPassword(customerId) {
    if (!confirm('ต้องการรีเซ็ตรหัสผ่านของลูกค้ารายนี้ใช่หรือไม่?\nรหัสผ่านเดิมจะใช้ไม่ได้ทันที')) {
        return;
    }

    fetch('api/admin/reset_customer_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customerId: customerId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // แสดงในช่องข้อความเพื่อให้คัดลอกได้ (รหัสนี้จะไม่ถูกเก็บหรือแสดงอีก)
            window.prompt('รหัสผ่านชั่วคราว (คัดลอกแล้วแจ้งลูกค้า)', data.tempPassword);
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถรีเซ็ตรหัสผ่านได้'));
        }
    })
    .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
}
</script>