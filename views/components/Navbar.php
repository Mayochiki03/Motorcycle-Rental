<?php
// views/components/Navbar.php
// แถบเมนูด้านบน: แสดงเมนูตาม role ของผู้ใช้ (customer / employee / owner)
$isLoggedIn = Auth::check();
$userEmail  = Auth::user()['email'] ?? '';
$userName   = Auth::user()['firstName'] ?? $userEmail;
$userRole   = Auth::role();

$isOwner    = $userRole === 'owner';
$isEmployee = $userRole === 'employee';
$isCustomer = ! $isOwner && ! $isEmployee;
?>
<style>
    .menu-item {
        display: block;
        padding: 0.5rem 1rem;
        color: #000000;
        font-size: 0.875rem;
    }

    .menu-item:hover {
        background-color: #f3f4f6;
    }
</style>


<nav class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- Logo -->
            <?php if ($isCustomer): ?>
                <!-- CUSTOMER: กดโลโก้กลับหน้าแรกได้ -->
                <a href="index.php?page=home" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                    <i data-lucide="bike" class="h-8 w-8"></i>
                    <div>
                        <span class="text-xl font-bold">เทมป์เทชัน</span>
                        <p class="text-xs text-blue-200">มอเตอร์ไซค์ให้เช่า</p>
                    </div>
                </a>
            <?php else: ?>
                <!-- OWNER, ADMIN, EMPLOYEE: กดโลโก้แล้วไม่ไปไหน (หรือไปหน้าเดิม) -->
                <div class="flex items-center space-x-2 cursor-default">
                    <i data-lucide="bike" class="h-8 w-8"></i>
                    <div>
                        <span class="text-xl font-bold">เทมป์เทชัน</span>
                        <p class="text-xs text-blue-200">มอเตอร์ไซค์ให้เช่า</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-8">
                <?php if ($isCustomer): ?>
                    <!-- CUSTOMER: เห็นหน้าแรกและรถเช่า -->
                    <a href="index.php?page=home" class="hover:text-blue-200 transition-colors font-medium">หน้าแรก</a>
                    <a href="index.php?page=motorcycles"
                        class="hover:text-blue-200 transition-colors font-medium">รถเช่า</a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <!-- เมนูสำหรับผู้ใช้ที่ล็อกอินแล้ว -->
                    <div class="flex items-center space-x-6">

                        <?php if ($isCustomer): ?>
                            <!-- CUSTOMER: เห็นการจองของฉัน -->
                            <a href="index.php?page=my-bookings"
                                class="flex items-center space-x-1 hover:text-blue-200 transition-colors font-medium">
                                <i data-lucide="calendar" class="h-4 w-4"></i>
                                <span>การจองของฉัน</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($isOwner || $isEmployee): ?>
                            <!-- OWNER, ADMIN, EMPLOYEE: เห็นเมนูจัดการ -->
                            <div class="relative" id="management-menu">
                                <button class="flex items-center space-x-1 hover:text-blue-200 transition-colors font-medium"
                                    id="management-button">
                                    <i data-lucide="settings" class="h-4 w-4"></i>
                                    <span>จัดการ</span>
                                    <i data-lucide="chevron-down" class="h-4 w-4" id="management-chevron"></i>
                                </button>

                                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50 border"
                                    id="management-dropdown">

                                    <?php if ($isOwner): ?>
                                        <!-- OWNER (admin) เห็นทุกเมนูจัดการ -->
                                        <a href="index.php?page=admin&section=dashboard" class="menu-item">แดชบอร์ด</a>
                                        <a href="index.php?page=admin&section=motorcycles" class="menu-item">จัดการรถ</a>
                                        <a href="index.php?page=admin&section=bookings" class="menu-item">จัดการการจอง</a>
                                        <a href="index.php?page=admin&section=customers" class="menu-item">จัดการลูกค้า</a>
                                        <a href="index.php?page=admin&section=employees" class="menu-item">จัดการพนักงาน</a>
                                        <a href="index.php?page=admin&section=discounts" class="menu-item">ส่วนลด</a>

                                    <?php elseif ($isEmployee): ?>
                                        <!-- EMPLOYEE (พนักงาน) เห็นสามหน้า -->
                                        <a href="index.php?page=employee&section=motorcycles" class="menu-item">จัดการรถ</a>
                                        <a href="index.php?page=employee&section=bookings" class="menu-item">จัดการการจอง</a>
                                        <a href="index.php?page=employee&section=customers" class="menu-item">จัดการลูกค้า</a>
                                    <?php endif; ?>

                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- เมนูผู้ใช้ (ทุก role เห็น) -->
                        <div class="relative" id="user-menu">
                            <button class="flex items-center space-x-2 hover:text-blue-200 transition-colors font-medium"
                                id="user-button">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                    <i data-lucide="user" class="h-4 w-4"></i>
                                </div>
                                <span
                                    class="max-w-32 truncate"><?php echo e($userName ?: $userEmail); ?></span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition-transform" id="user-chevron"></i>
                            </button>

                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 hidden z-50 border"
                                id="user-dropdown">
                                <div class="px-4 py-3 border-b">
                                    <p class="font-semibold text-gray-900 truncate">
                                        <?php echo e($userName ?: $userEmail); ?></p>
                                    <p class="text-sm text-gray-600 truncate"><?php echo e($userEmail); ?>
                                    </p>
                                    <?php if ($isOwner || $isEmployee): ?>
                                        <div class="mt-1">
                                            <?php if ($isOwner): ?>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    <i data-lucide="crown" class="h-3 w-3 mr-1"></i>
                                                    เจ้าของร้าน
                                                </span>
                                            <?php elseif ($isEmployee): ?>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i data-lucide="briefcase" class="h-3 w-3 mr-1"></i>
                                                    พนักงาน
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($isCustomer): ?>
                                    <!-- CUSTOMER: เห็นโปรไฟล์และการจอง -->
                                    <a href="index.php?page=profile"
                                        class="flex items-center space-x-2 px-4 py-2 text-gray-700 hover:bg-blue-50 transition-colors">
                                        <i data-lucide="user" class="h-4 w-4"></i>
                                        <span>โปรไฟล์ของฉัน</span>
                                    </a>
                                    <a href="index.php?page=my-bookings"
                                        class="flex items-center space-x-2 px-4 py-2 text-gray-700 hover:bg-blue-50 transition-colors">
                                        <i data-lucide="calendar" class="h-4 w-4"></i>
                                        <span>การจองของฉัน</span>
                                    </a>
                                <?php else: ?>
                                    <!-- OWNER, ADMIN, EMPLOYEE: เห็นแค่โปรไฟล์ -->
                                    <a href="index.php?page=profile"
                                        class="flex items-center space-x-2 px-4 py-2 text-gray-700 hover:bg-blue-50 transition-colors">
                                        <i data-lucide="user" class="h-4 w-4"></i>
                                        <span>โปรไฟล์ของฉัน</span>
                                    </a>
                                <?php endif; ?>

                                <hr class="my-2">

                                <a href="logout.php"
                                    class="flex items-center space-x-2 px-4 py-2 text-gray-700 hover:bg-red-50 transition-colors">
                                    <i data-lucide="log-out" class="h-4 w-4"></i>
                                    <span>ออกจากระบบ</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- เมนูสำหรับผู้ใช้ที่ยังไม่ได้ล็อกอิน -->
                    <div class="flex items-center space-x-4">
                        <a href="login.php" class="hover:text-blue-200 transition-colors font-medium">เข้าสู่ระบบ</a>
                        <a href="register.php"
                            class="bg-blue-500 hover:bg-blue-400 px-4 py-2 rounded-lg transition-colors font-medium">
                            สมัครสมาชิก
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobile-menu-button"
                    class="text-white hover:text-blue-200 p-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu-content"
            class="md:hidden mt-2 flex flex-col space-y-1 hidden pb-4 border-t border-blue-700 pt-4">

            <?php if ($isCustomer): ?>
                <!-- CUSTOMER: หน้าแรก, รถเช่า -->
                <a href="index.php?page=home"
                    class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium">หน้าแรก</a>
                <a href="index.php?page=motorcycles"
                    class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium">รถเช่า</a>
            <?php endif; ?>

            <?php if ($isLoggedIn): ?>
                <hr class="border-blue-700 mx-4" />

                <?php if ($isCustomer): ?>
                    <!-- CUSTOMER: การจองของฉัน -->
                    <a href="index.php?page=my-bookings"
                        class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium flex items-center space-x-3">
                        <i data-lucide="calendar" class="h-5 w-5"></i>
                        <span>การจองของฉัน</span>
                    </a>
                <?php endif; ?>

                <?php if ($isOwner || $isEmployee): ?>
                    <div class="px-4 py-3">
                        <p class="text-blue-200 text-sm font-medium mb-2">การจัดการ</p>

                        <?php if ($isOwner): ?>
                            <!-- OWNER -->
                            <a href="index.php?page=admin&section=dashboard"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">แดชบอร์ด</a>
                            <a href="index.php?page=admin&section=motorcycles"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">จัดการรถ</a>
                            <a href="index.php?page=admin&section=bookings"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">จัดการการจอง</a>
                            <a href="index.php?page=admin&section=customers"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">จัดการลูกค้า</a>
                            <a href="index.php?page=admin&section=employees"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">จัดการพนักงาน</a>
                            <a href="index.php?page=admin&section=discounts"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">ส่วนลด</a>

                        <?php elseif ($isEmployee): ?>
                            <!-- EMPLOYEE -->
                            <a href="index.php?page=employee&section=motorcycles"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">จัดการรถ</a>
                            <a href="index.php?page=employee&section=bookings"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">จัดการการจอง</a>
                            <a href="index.php?page=employee&section=customers"
                                class="block py-2 text-white hover:bg-blue-700 px-2 rounded">จัดการลูกค้า</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- ข้อมูลผู้ใช้ (Mobile) -->
                <div class="px-4 py-3 border-t border-blue-700 mt-2 pt-4">
                    <p class="font-medium text-blue-100"><?php echo e($userName ?: $userEmail); ?></p>
                    <p class="text-sm text-blue-300"><?php echo e($userEmail); ?></p>
                    <?php if ($isOwner || $isEmployee): ?>
                        <div class="mt-2">
                            <?php if ($isOwner): ?>
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-600 text-white">
                                    <i data-lucide="crown" class="h-3 w-3 mr-1"></i>
                                    เจ้าของร้าน
                                </span>
                            <?php elseif ($isEmployee): ?>
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-600 text-white">
                                    <i data-lucide="briefcase" class="h-3 w-3 mr-1"></i>
                                    พนักงาน
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- เมนูผู้ใช้ (Mobile) -->
                <a href="index.php?page=profile"
                    class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium flex items-center space-x-3">
                    <i data-lucide="user" class="h-5 w-5"></i>
                    <span>โปรไฟล์ของฉัน</span>
                </a>

                <?php if ($isCustomer): ?>
                    <!-- CUSTOMER: เห็นการจองอีกครั้ง (เผื่อ) -->
                    <a href="index.php?page=my-bookings"
                        class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium flex items-center space-x-3">
                        <i data-lucide="calendar" class="h-5 w-5"></i>
                        <span>การจองของฉัน</span>
                    </a>
                <?php endif; ?>

                <a href="logout.php"
                    class="mobile-menu-link px-4 py-3 hover:bg-red-700 transition-colors rounded-lg font-medium flex items-center space-x-3 mt-2">
                    <i data-lucide="log-out" class="h-5 w-5"></i>
                    <span>ออกจากระบบ</span>
                </a>

            <?php else: ?>
                <!-- ไม่ได้ล็อกอิน -->
                <hr class="border-blue-700 mx-4" />
                <a href="login.php"
                    class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium">เข้าสู่ระบบ</a>
                <a href="register.php"
                    class="mobile-menu-link px-4 py-3 bg-blue-500 hover:bg-blue-400 transition-colors rounded-lg font-medium text-center mx-4">
                    สมัครสมาชิก
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Mobile Menu Toggle
        const menuButton = document.getElementById('mobile-menu-button');
        const menuContent = document.getElementById('mobile-menu-content');
        const menuLinks = document.querySelectorAll('.mobile-menu-link');

        if (menuButton && menuContent) {
            menuButton.addEventListener('click', function () {
                menuContent.classList.toggle('hidden');
            });

            menuLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    menuContent.classList.add('hidden');
                });
            });
        }

        // User Dropdown Toggle
        const userButton = document.getElementById('user-button');
        const userDropdown = document.getElementById('user-dropdown');
        const userChevron = document.getElementById('user-chevron');

        if (userButton && userDropdown) {
            userButton.addEventListener('click', function (e) {
                e.stopPropagation();
                const isHidden = userDropdown.classList.contains('hidden');

                // Close all other dropdowns
                closeAllDropdowns();

                // Toggle this dropdown
                userDropdown.classList.toggle('hidden', !isHidden);
                userChevron.classList.toggle('rotate-180', !isHidden);
            });
        }

        // Management Dropdown Toggle
        const managementButton = document.getElementById('management-button');
        const managementDropdown = document.getElementById('management-dropdown');
        const managementChevron = document.getElementById('management-chevron');

        if (managementButton && managementDropdown) {
            managementButton.addEventListener('click', function (e) {
                e.stopPropagation();
                const isHidden = managementDropdown.classList.contains('hidden');

                // Close all other dropdowns
                closeAllDropdowns();

                // Toggle this dropdown
                managementDropdown.classList.toggle('hidden', !isHidden);
                managementChevron.classList.toggle('rotate-180', !isHidden);
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function () {
            closeAllDropdowns();
        });

        // Prevent dropdown close when clicking inside dropdown
        if (userDropdown) {
            userDropdown.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }

        if (managementDropdown) {
            managementDropdown.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }

        function closeAllDropdowns() {
            if (userDropdown) {
                userDropdown.classList.add('hidden');
                userChevron.classList.remove('rotate-180');
            }
            if (managementDropdown) {
                managementDropdown.classList.add('hidden');
                managementChevron.classList.remove('rotate-180');
            }
        }

        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>