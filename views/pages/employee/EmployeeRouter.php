<?php
    // views/pages/employee/EmployeeRouter.php
    // หน้าทำงานของ "พนักงาน (employee)": เปิดได้เฉพาะจัดการรถ / การจอง / ลูกค้า
    // ใช้ไฟล์หน้าจัดการชุดเดียวกับเจ้าของร้าน (views/pages/admin/sections)

    Auth::requireRole(['employee']);
    $isEmployee = true;

    $section = $_GET['section'] ?? 'motorcycles';

    // รายชื่อเมนูที่อนุญาต (whitelist)
    $pages = [
        'bookings'    => 'BookingManagement.php',
        'motorcycles' => 'MotorcyclesManagement.php',
        'customers'   => 'CustomersManagement.php',
    ];

    if (! array_key_exists($section, $pages)) {
        $section = 'motorcycles';
    }

    $employeeContentFile = APP_ROOT . '/views/pages/admin/sections/' . $pages[$section];
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">พนักงาน - จัดการงาน</h1>
            <p class="text-gray-600 mt-2">ยินดีต้อนรับ 
                <?php echo e(trim((Auth::user()['firstName'] ?? '') . ' ' . (Auth::user()['lastName'] ?? '')) ?: 'พนักงาน'); ?>
            </p>
        </div>

        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-4" aria-label="Tabs">

                <!-- Motorcycles Management -->
                <a
                    href="index.php?page=employee&section=motorcycles"
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'motorcycles') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="bike" class="inline h-4 w-4 mr-2"></i>
                    จัดการรถ
                </a>

                <!-- Bookings Management -->
                <a
                    href="index.php?page=employee&section=bookings"
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'bookings') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="calendar" class="inline h-4 w-4 mr-2"></i>
                    จัดการการจอง
                </a>

                <!-- Customers Management -->
                <a
                    href="index.php?page=employee&section=customers"
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'customers') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="users" class="inline h-4 w-4 mr-2"></i>
                    จัดการลูกค้า
                </a>

            </nav>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <?php
                if (file_exists($employeeContentFile)) {
                    include $employeeContentFile;
                } else {
                    echo "<div class='bg-yellow-50 border border-yellow-200 p-4 rounded-lg'>";
                    echo "<p class='text-yellow-800'>ไม่พบไฟล์หน้านี้</p>";
                    echo "</div>";
                }
            ?>
        </div>
    </div>
</div>