<?php
    // views/pages/admin/AdminRouter.php
    // หน้าจัดการระบบสำหรับ "เจ้าของร้าน (owner)" เท่านั้น: เลือกเมนูย่อยจาก ?section=...
    // (พนักงานใช้ EmployeeRouter ซึ่งเปิดหน้าจัดการรถ/การจอง/ลูกค้าได้เท่านั้น)

    require_once APP_ROOT . '/src/Services/Admin/AdminService.php';

    $section = $_GET['section'] ?? 'dashboard';

    // เฉพาะเจ้าของร้านเท่านั้น (ถ้าไม่ได้ login จะพาไปหน้า login)
    Auth::requireRole(['owner']);
    $isOwner = true;

    // รายชื่อเมนูย่อยที่อนุญาต (whitelist)
    $adminPages = [
        'dashboard'   => 'AdminDashboard.php',
        'employees'   => 'EmployeeDashboard.php',
        'bookings'    => 'BookingManagement.php',
        'motorcycles' => 'MotorcyclesManagement.php',
        'customers'   => 'CustomersManagement.php',
        'discounts'   => 'DiscountManagement.php',
    ];

    $adminContentFile = APP_ROOT . '/views/pages/admin/sections/' . ($adminPages[$section] ?? $adminPages['dashboard']);
?>

<!-- Admin Layout -->
<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Admin Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">จัดการระบบ</h1>
            <p class="text-gray-600 mt-2">
                ยินดีต้อนรับ
                <?php echo e(trim((Auth::user()['firstName'] ?? '') . ' ' . (Auth::user()['lastName'] ?? ''))); ?>
            </p>
        </div>

        <!-- Admin Navigation Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-4" aria-label="Tabs">

                <!-- Dashboard -->
                <?php if ($isOwner): ?>
                <a href="index.php?page=admin&section=dashboard"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'dashboard') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="chart-column" class="inline h-4 w-4 mr-2"></i>
                    แดชบอร์ด
                </a>
                <?php endif; ?>

                <!-- Motorcycles -->
                <?php if ($isOwner): ?>
                <a href="index.php?page=admin&section=motorcycles"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'motorcycles') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="bike" class="inline h-4 w-4 mr-2"></i>
                    จัดการรถ
                </a>
                <?php endif; ?>

                <!-- Bookings -->
                <?php if ($isOwner): ?>
                <a href="index.php?page=admin&section=bookings"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'bookings') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="calendar" class="inline h-4 w-4 mr-2"></i>
                    จัดการการจอง
                </a>
                <?php endif; ?>

                <!-- Employees (owner เท่านั้น) -->
                <?php if ($isOwner): ?>
                <a href="index.php?page=admin&section=employees"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'employees') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="users" class="inline h-4 w-4 mr-2"></i>
                    จัดการพนักงาน
                </a>
                <?php endif; ?>

                <!-- Customers -->
                <?php if ($isOwner): ?>
                <a href="index.php?page=admin&section=customers"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'customers') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="users" class="inline h-4 w-4 mr-2"></i>
                    จัดการลูกค้า
                </a>
                <?php endif; ?>

                <!-- Discounts (owner เท่านั้น) -->
                <?php if ($isOwner): ?>
                <a href="index.php?page=admin&section=discounts"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'discounts') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="tag" class="inline h-4 w-4 mr-2"></i>
                    ส่วนลด
                </a>
                <?php endif; ?>

            </nav>
        </div>

        <!-- Admin Content -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <?php
                if (file_exists($adminContentFile)) {
                    include $adminContentFile;
                } else {
                    echo "<div class='bg-yellow-50 border border-yellow-200 p-4 rounded-lg'>";
                    echo "<p class='text-yellow-800'>ไม่พบไฟล์หน้านี้</p>";
                    echo "</div>";
                }
            ?>
        </div>
    </div>
</div>