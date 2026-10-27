<?php
    // views/pages/ProfilePages.php
    if (! Auth::check()) {
        $_SESSION['redirect_url']  = $_SERVER['REQUEST_URI'];
        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'กรุณาเข้าสู่ระบบเพื่อดูโปรไฟล์',
        ];
        header("Location: login.php");
        exit;
    }

        require_once APP_ROOT . '/src/Services/UserService.php';

    // ======================
    // USER INFO
    // ======================
    $user   = $_SESSION['user'];
    $userId = $user['id'] ?? '';
    $role   = $user['role'] ?? 'customer';

    // ======================
    // HANDLE FORM SUBMISSION
    // ======================
    $success = '';
    $error   = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_profile':
                    // รับข้อมูลจากฟอร์ม
                    $phone = $_POST['phone'] ?? '';
                    
                    // ตรวจสอบเบอร์โทรศัพท์
                    if (!preg_match('/^0[0-9]{9}$/', $phone)) {
                        if (empty($phone)) {
                            $error = 'กรุณากรอกเบอร์โทรศัพท์';
                        } elseif (!preg_match('/^[0-9]+$/', $phone)) {
                            $error = 'เบอร์โทรศัพท์ต้องเป็นตัวเลขเท่านั้น';
                        } elseif (strlen($phone) !== 10) {
                            $error = 'เบอร์โทรศัพท์ต้องมี 10 หลัก';
                        } elseif (!str_starts_with($phone, '0')) {
                            $error = 'เบอร์โทรศัพท์ต้องขึ้นต้นด้วย 0';
                        } else {
                            $error = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
                        }
                        break; // ออกจาก switch เพื่อไม่ให้ดำเนินการต่อ
                    }
                    
                    $updateData = [
                        'first_name' => $_POST['first_name'] ?? '',
                        'last_name'  => $_POST['last_name'] ?? '',
                        'phone'      => $phone, // ใช้ค่าที่ตรวจสอบแล้ว
                    ];

                    // เพิ่มฟิลด์เฉพาะตาม role
                    if ($role === 'customer') {
                        $updateData['line_id'] = $_POST['line_id'] ?? '';
                    } elseif ($role === 'employee') {
                        $updateData['position'] = $_POST['position'] ?? 'staff';
                    }
                    // owner ไม่มีฟิลด์พิเศษ

                    // ตรวจสอบข้อมูลเบื้องต้น
                    if (empty($updateData['first_name']) || empty($updateData['last_name'])) {
                        $error = 'กรุณากรอกชื่อและนามสกุลให้ครบถ้วน';
                    } else {
                        // อัปเดตข้อมูล
                        $result = UserService::updateUserProfile($userId, $role, $updateData);

                        if ($result['success']) {
                            // ดึงข้อมูลล่าสุดจาก DB มาเก็บใน session
                            $latestData = UserService::getUserProfile($userId, $role);

                            if ($latestData) {
                                $_SESSION['user']['firstName'] = $latestData['first_name'] ?? $updateData['first_name'];
                                $_SESSION['user']['lastName']  = $latestData['last_name'] ?? $updateData['last_name'];
                                $_SESSION['user']['phone']     = $latestData['phone'] ?? $updateData['phone'];

                                if ($role === 'customer') {
                                    $_SESSION['user']['lineId'] = $latestData['line_id'] ?? $updateData['line_id'];
                                } elseif ($role === 'employee') {
                                    $_SESSION['user']['position'] = $latestData['position'] ?? $updateData['position'];
                                }
                                // owner ไม่มีฟิลด์พิเศษ
                            }

                            $success = $result['message'];

                            // รีเฟรชหน้าเพื่อแสดงข้อมูลล่าสุด
                            header("Location: " . $_SERVER['REQUEST_URI']);
                            exit;
                        } else {
                            $error = $result['message'];
                        }
                    }
                    break;

                case 'change_password':
                    $currentPassword = $_POST['current_password'] ?? '';
                    $newPassword     = $_POST['new_password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';

                    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                        $error = 'กรุณากรอกรหัสผ่านให้ครบถ้วน';
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = 'รหัสผ่านใหม่ไม่ตรงกัน';
                    } elseif (strlen($newPassword) < 8) {
                        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
                    } else {
                        $result = UserService::changePassword($userId, $role, $currentPassword, $newPassword);

                        if ($result['success']) {
                            $success = $result['message'];
                            header("Location: " . $_SERVER['REQUEST_URI']);
                            exit;
                        } else {
                            $error = $result['message'];
                        }
                    }
                    break;
            }
        }
    }

    // ======================
    // GET LATEST USER DATA
    // ======================
    $userData = UserService::getUserProfile($userId, $role);

    if ($userData) {
        $firstName = $userData['first_name'] ?? $user['firstName'] ?? '';
        $lastName  = $userData['last_name'] ?? $user['lastName'] ?? '';
        $email     = $userData['email'] ?? $user['email'] ?? '';
        $phone     = $userData['phone'] ?? $user['phone'] ?? '';
        $lineId    = $userData['line_id'] ?? $user['lineId'] ?? '';
        $position  = $userData['position'] ?? $user['position'] ?? 'staff';
        $isActive  = $userData['is_active'] ?? 1;
        $createdAt = $userData['created_at'] ?? '';
        $updatedAt = $userData['updated_at'] ?? '';

        // อัปเดต session
        $_SESSION['user']['firstName'] = $firstName;
        $_SESSION['user']['lastName']  = $lastName;
        $_SESSION['user']['phone']     = $phone;

        if ($role === 'customer') {
            $_SESSION['user']['lineId'] = $lineId;
        } elseif ($role === 'employee') {
            $_SESSION['user']['position'] = $position;
        }
    } else {
        $firstName = $user['firstName'] ?? '';
        $lastName  = $user['lastName'] ?? '';
        $email     = $user['email'] ?? '';
        $phone     = $user['phone'] ?? '';
        $lineId    = $user['lineId'] ?? '';
        $position  = $user['position'] ?? 'staff';
        $isActive  = 1;
        $createdAt = '';
        $updatedAt = '';
    }

    $fullName = trim($firstName . ' ' . $lastName);
    if ($fullName === '') {
        $fullName = $email;
    }

    $memberSince = ! empty($createdAt) ? date('d M Y', strtotime($createdAt)) : 'ไม่ระบุ';
    $lastUpdated = ! empty($updatedAt) ? date('d M Y H:i', strtotime($updatedAt)) : 'ไม่ระบุ';

    // ตัวเลือกตำแหน่งสำหรับพนักงาน
    $positions = [
        'staff'   => 'พนักงานทั่วไป',
        'manager' => 'ผู้จัดการ',
    ];
?>

<!-- ส่วนหัวของหน้านี้ (สไตล์/คอนฟิก Tailwind เฉพาะหน้า) -->
    <style>
        .tab-active {
            border-bottom: 3px solid #3b82f6;
            color: #1e40af;
            font-weight: 600;
        }
        .transition-all {
            transition: all 0.2s ease;
        }
    </style>

    <div class="min-h-screen py-10">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">โปรไฟล์ของฉัน</h1>
                <p class="text-gray-600 mt-2">จัดการข้อมูลส่วนตัวและความปลอดภัยของบัญชี</p>
            </div>

            <!-- Flash Messages -->
            <?php if (! empty($success)): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                    <i data-lucide="check-circle" class="h-5 w-5 mr-2"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            <?php endif; ?>

            <?php if (! empty($error)): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                    <i data-lucide="alert-circle" class="h-5 w-5 mr-2"></i>
                    <span><?php echo e($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Profile Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-8 text-white">
                    <div class="flex items-center gap-6">
                        <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur flex items-center justify-center">
                            <i data-lucide="user" class="w-10 h-10 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold"><?php echo e($fullName); ?></h2>
                            <p class="text-blue-100 flex items-center gap-2 mt-1">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                                <?php echo e($email); ?>
                            </p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="px-3 py-1 bg-white/20 rounded-full text-sm backdrop-blur">
                                    <?php
                                        echo match ($role) {
                                            'owner'    => 'เจ้าของร้าน',
                                            'employee' => 'พนักงาน',
                                            default    => 'ลูกค้า',
                                        };
                                    ?>
                                </span>
                                <?php if ($isActive == 1): ?>
                                    <span class="px-3 py-1 bg-green-500/20 rounded-full text-sm backdrop-blur flex items-center gap-1">
                                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                        บัญชีปกติ
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-red-500/20 rounded-full text-sm backdrop-blur">
                                        ระงับการใช้งาน
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 px-6">
                    <div class="flex space-x-8">
                        <button onclick="switchTab('profile')" id="tab-profile-btn" class="tab-active py-4 px-1 text-sm font-medium transition-all">
                            <i data-lucide="user" class="w-4 h-4 inline mr-2"></i>
                            ข้อมูลส่วนตัว
                        </button>
                        <button onclick="switchTab('security')" id="tab-security-btn" class="py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 transition-all">
                            <i data-lucide="shield" class="w-4 h-4 inline mr-2"></i>
                            ความปลอดภัย
                        </button>
                    </div>
                </div>

                <!-- Tab: Profile Information -->
                <div id="tab-profile" class="p-6">
                    <form method="POST" action="" class="space-y-6" id="profile-form">
<?php echo Csrf::field(); ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- อีเมล (ไม่สามารถแก้ไขได้) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="mail" class="w-4 h-4 inline mr-1"></i>
                                    อีเมล
                                </label>
                                <input type="email"
                                       value="<?php echo e($email); ?>"
                                       disabled
                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-500 cursor-not-allowed">
                                <p class="text-xs text-gray-500 mt-1">ไม่สามารถเปลี่ยนอีเมลได้</p>
                            </div>

                            <!-- ชื่อ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="user" class="w-4 h-4 inline mr-1"></i>
                                    ชื่อ <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="first_name"
                                       id="first_name"
                                       value="<?php echo e($firstName); ?>"
                                       required
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            </div>

                            <!-- นามสกุล -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="user" class="w-4 h-4 inline mr-1"></i>
                                    นามสกุล <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="last_name"
                                       id="last_name"
                                       value="<?php echo e($lastName); ?>"
                                       required
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            </div>

                            <!-- เบอร์โทรศัพท์ (เพิ่ม id และ required) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="phone" class="w-4 h-4 inline mr-1"></i>
                                    เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                                </label>
                                <input type="tel"
                                       name="phone"
                                       id="phone"
                                       value="<?php echo e($phone); ?>"
                                       required
                                       placeholder="0812345678"
                                       maxlength="10"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <div id="phone-error" class="text-red-500 text-sm mt-1 hidden"></div>
                                <p class="text-xs text-gray-500 mt-1">เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก ขึ้นต้นด้วย 0</p>
                            </div>

                            <!-- ฟิลด์เฉพาะตาม Role -->
                            <?php if ($role === 'customer'): ?>
                                <!-- LINE ID (เฉพาะลูกค้า) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-lucide="message-circle" class="w-4 h-4 inline mr-1"></i>
                                        LINE ID
                                    </label>
                                    <input type="text"
                                           name="line_id"
                                           value="<?php echo e($lineId); ?>"
                                           placeholder="เช่น temptation.bike"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                    <p class="text-xs text-gray-500 mt-1">กรุณากรอก LINE ID เพื่อรับข้อมูลข่าวสาร</p>
                                </div>
                            <?php elseif ($role === 'employee'): ?>

                            <?php elseif ($role === 'owner'): ?>

                            <?php endif; ?>

                            <!-- วันที่สมัครสมาชิก -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                    วันที่สมัครสมาชิก
                                </label>
                                <input type="text"
                                       value="<?php echo e($memberSince); ?>"
                                       disabled
                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-500 cursor-not-allowed">
                            </div>

                            <!-- อัปเดตล่าสุด -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="refresh-cw" class="w-4 h-4 inline mr-1"></i>
                                    อัปเดตล่าสุด
                                </label>
                                <input type="text"
                                       value="<?php echo e($lastUpdated); ?>"
                                       disabled
                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-500 cursor-not-allowed">
                            </div>
                        </div>

                        <!-- ปุ่มบันทึก -->
                        <div class="flex justify-end pt-4 border-t border-gray-200">
                            <button type="submit"
                                    id="submit-btn"
                                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition flex items-center gap-2">
                                <i data-lucide="save" class="w-5 h-5"></i>
                                บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Security -->
                <div id="tab-security" class="p-6 hidden">
                    <div class="max-w-2xl">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">เปลี่ยนรหัสผ่าน</h3>

                        <form method="POST" action="" class="space-y-6">
<?php echo Csrf::field(); ?>
                            <input type="hidden" name="action" value="change_password">

                            <!-- รหัสผ่านปัจจุบัน -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="lock" class="w-4 h-4 inline mr-1"></i>
                                    รหัสผ่านปัจจุบัน <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="password"
                                           name="current_password"
                                           id="current_password"
                                           required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-10">
                                    <button type="button"
                                            onclick="togglePassword('current_password')"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- รหัสผ่านใหม่ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="key" class="w-4 h-4 inline mr-1"></i>
                                    รหัสผ่านใหม่ <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="password"
                                           name="new_password"
                                           id="new_password"
                                           required
                                           minlength="6"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-10">
                                    <button type="button"
                                            onclick="togglePassword('new_password')"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร</p>
                            </div>

                            <!-- ยืนยันรหัสผ่านใหม่ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i>
                                    ยืนยันรหัสผ่านใหม่ <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="password"
                                           name="confirm_password"
                                           id="confirm_password"
                                           required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-10">
                                    <button type="button"
                                            onclick="togglePassword('confirm_password')"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </button>
                                </div>
                                <div id="password-match-message" class="text-xs mt-1 hidden"></div>
                            </div>

                            <!-- ปุ่มเปลี่ยนรหัสผ่าน -->
                            <div class="flex justify-end pt-4 border-t border-gray-200">
                                <button type="submit"
                                        id="change-password-btn"
                                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition flex items-center gap-2">
                                    <i data-lucide="shield" class="w-5 h-5"></i>
                                    เปลี่ยนรหัสผ่าน
                                </button>
                            </div>
                        </form>

                        <!-- เคล็ดลับความปลอดภัย -->
                        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <div class="text-blue-600">
                                    <i data-lucide="info" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-blue-800 mb-1">เคล็ดลับความปลอดภัย</h4>
                                    <ul class="text-sm text-blue-700 space-y-1">
                                        <li>• ใช้รหัสผ่านที่คาดเดายาก มีทั้งตัวอักษร ตัวเลข และอักขระพิเศษ</li>
                                        <li>• ไม่ควรใช้รหัสผ่านเดียวกันกับเว็บอื่น</li>
                                        <li>• เปลี่ยนรหัสผ่านเป็นประจำทุก 3-6 เดือน</li>
                                        <li>• ห้ามแชร์รหัสผ่านให้ผู้อื่น</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function switchTab(tabName) {
            document.getElementById('tab-profile').classList.add('hidden');
            document.getElementById('tab-security').classList.add('hidden');
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            document.getElementById('tab-profile-btn').classList.remove('tab-active');
            document.getElementById('tab-security-btn').classList.remove('tab-active');
            document.getElementById('tab-' + tabName + '-btn').classList.add('tab-active');
            
            lucide.createIcons();
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Phone number validation
            const phoneInput = document.getElementById('phone');
            const phoneError = document.getElementById('phone-error');
            const submitBtn = document.getElementById('submit-btn');
            const profileForm = document.getElementById('profile-form');

            if (phoneInput) {
                // Restrict input to numbers only
                phoneInput.addEventListener('input', function(e) {
                    let value = this.value;
                    
                    // Remove non-numeric characters
                    value = value.replace(/[^0-9]/g, '');
                    
                    // Limit to 10 digits
                    if (value.length > 10) {
                        value = value.slice(0, 10);
                    }
                    
                    this.value = value;
                    
                    // Validate
                    validatePhone(value);
                });

                // Validate on blur (when leaving the field)
                phoneInput.addEventListener('blur', function() {
                    validatePhone(this.value);
                });

                function validatePhone(value) {
                    if (value.length === 0) {
                        phoneError.textContent = 'กรุณากรอกเบอร์โทรศัพท์';
                        phoneError.classList.remove('hidden');
                        return false;
                    } else if (value.length < 10) {
                        phoneError.textContent = 'เบอร์โทรศัพท์ต้องครบ 10 หลัก';
                        phoneError.classList.remove('hidden');
                        return false;
                    } else if (!value.startsWith('0')) {
                        phoneError.textContent = 'เบอร์โทรศัพท์ต้องขึ้นต้นด้วย 0';
                        phoneError.classList.remove('hidden');
                        return false;
                    } else {
                        phoneError.classList.add('hidden');
                        return true;
                    }
                }

                // Form validation before submit
                if (profileForm) {
                    profileForm.addEventListener('submit', function(e) {
                        const firstName = document.getElementById('first_name').value.trim();
                        const lastName = document.getElementById('last_name').value.trim();
                        const phone = phoneInput.value;
                        
                        // Check required fields
                        if (!firstName || !lastName) {
                            e.preventDefault();
                            alert('กรุณากรอกชื่อและนามสกุลให้ครบถ้วน');
                            return false;
                        }
                        
                        // Validate phone
                        if (phone.length === 0) {
                            e.preventDefault();
                            alert('กรุณากรอกเบอร์โทรศัพท์');
                            return false;
                        } else if (phone.length !== 10) {
                            e.preventDefault();
                            alert('เบอร์โทรศัพท์ต้องมี 10 หลัก');
                            return false;
                        } else if (!phone.startsWith('0')) {
                            e.preventDefault();
                            alert('เบอร์โทรศัพท์ต้องขึ้นต้นด้วย 0');
                            return false;
                        } else if (!/^[0-9]+$/.test(phone)) {
                            e.preventDefault();
                            alert('เบอร์โทรศัพท์ต้องเป็นตัวเลขเท่านั้น');
                            return false;
                        }
                    });
                }
            }

            // Password match validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const matchMessage = document.getElementById('password-match-message');
            const changePasswordBtn = document.getElementById('change-password-btn');

            function checkPasswordMatch() {
                if (confirmPassword && confirmPassword.value) {
                    if (newPassword.value === confirmPassword.value) {
                        matchMessage.textContent = 'รหัสผ่านตรงกัน';
                        matchMessage.className = 'text-xs mt-1 text-green-600';
                        matchMessage.classList.remove('hidden');
                        if (changePasswordBtn) changePasswordBtn.disabled = false;
                    } else {
                        matchMessage.textContent = 'รหัสผ่านไม่ตรงกัน';
                        matchMessage.className = 'text-xs mt-1 text-red-600';
                        matchMessage.classList.remove('hidden');
                        if (changePasswordBtn) changePasswordBtn.disabled = true;
                    }
                } else {
                    if (matchMessage) matchMessage.classList.add('hidden');
                    if (changePasswordBtn) changePasswordBtn.disabled = false;
                }
            }

            if (newPassword && confirmPassword) {
                newPassword.addEventListener('keyup', checkPasswordMatch);
                confirmPassword.addEventListener('keyup', checkPasswordMatch);
            }
        });
    </script>
