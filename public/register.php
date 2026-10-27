<?php
// public/register.php
// หน้าสมัครสมาชิก (สำหรับลูกค้า)

require_once __DIR__ . '/../src/bootstrap.php';
require_once APP_ROOT . '/src/Services/AuthService.php';

$error           = '';
$firstName       = '';
$lastName        = '';
$email           = '';
$phone           = '';
$lineId          = '';

// ล็อกอินอยู่แล้วไม่ต้องสมัครซ้ำ
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $firstName       = trim($_POST['firstName'] ?? '');
    $lastName        = trim($_POST['lastName'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $lineId          = trim($_POST['lineId'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
        $error = 'กรุณากรอกข้อมูลที่จำเป็น (*) ให้ครบถ้วน';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 8) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง (เช่น name@example.com)';
    } elseif (! preg_match('/^0[0-9]{9}$/', $phone)) {
        $error = 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0';
    } else {
        try {
            $user = AuthService::register([
                'email'     => $email,
                'password'  => $password,
                'firstName' => $firstName,
                'lastName'  => $lastName,
                'phone'     => $phone,
                'lineId'    => $lineId ?: null,
            ]);

            if ($user) {
                Session::login($user);
                header('Location: index.php');
                exit;
            }

            $error = 'การสมัครสมาชิกล้มเหลว หรืออีเมลนี้มีผู้ใช้แล้ว';
        } catch (Exception $e) {
            // ไม่เขียนรหัสผ่านลง log และไม่แสดงรายละเอียดภายในให้ผู้ใช้เห็น
            error_log('Register error: ' . $e->getMessage());
            $error = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="assets/vendor/lucide/lucide.min.js"></script>
</head>

<body
    class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">

    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="flex justify-center">
                <div class="bg-blue-600 p-3 rounded-full">
                    <i data-lucide="bike" class="h-8 w-8 text-white"></i>
                </div>
            </div>
            <h2 class="mt-6 text-3xl font-bold text-gray-900">สมัครสมาชิก</h2>
            <p class="mt-2 text-sm text-gray-600">สร้างบัญชีเพื่อเริ่มจองรถจักรยานยนต์</p>
        </div>

        <div class="bg-white py-8 px-6 shadow-lg rounded-lg">
            <form class="space-y-6" method="POST" action="register.php">
                <?php echo Csrf::field(); ?>
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i data-lucide="alert-circle" class="h-5 w-5 mr-2"></i>
                            <?php echo e($error); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">ชื่อ *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="user" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input id="firstName" name="firstName" type="text" required value="<?php echo e($firstName); ?>"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="ชื่อ" />
                        </div>
                    </div>

                    <div>
                        <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">นามสกุล *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="user" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input id="lastName" name="lastName" type="text" required value="<?php echo e($lastName); ?>"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="นามสกุล" />
                        </div>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">อีเมล *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="<?php echo e($email); ?>"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกอีเมลของคุณ" />
                    </div>
                    <div id="email-warning" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทรศัพท์ *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="phone" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input id="phone" name="phone" type="tel" required
                            value="<?php echo e($phone); ?>"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกเบอร์โทรศัพท์ของคุณ" />
                    </div>
                    <div id="phone-warning" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>

                <div>
                    <label for="lineId" class="block text-sm font-medium text-gray-700 mb-2">Line ID (ไม่บังคับ)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="message-circle" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input id="lineId" name="lineId" type="text" value="<?php echo e($lineId); ?>"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอก Line ID ของคุณ" />
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่าน *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                            class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)" />
                        <button type="button" id="toggle-password-btn"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i data-lucide="eye" id="eye-icon-1" class="h-5 w-5 text-gray-400"></i>
                            <i data-lucide="eye-off" id="eye-off-icon-1" class="h-5 w-5 text-gray-400 hidden"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">ยืนยันรหัสผ่าน
                        *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input id="confirmPassword" name="confirmPassword" type="password" autocomplete="new-password"
                            required
                            class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกรหัสผ่านอีกครั้ง" />
                        <button type="button" id="toggle-confirm-password-btn"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i data-lucide="eye" id="eye-icon-2" class="h-5 w-5 text-gray-400"></i>
                            <i data-lucide="eye-off" id="eye-off-icon-2" class="h-5 w-5 text-gray-400 hidden"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i data-lucide="user-plus" class="h-4 w-4 mr-2"></i>
                        สมัครสมาชิก
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">หรือ</span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">มีบัญชีอยู่แล้ว?
                        <a href="login.php"
                            class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200">
                            <i data-lucide="log-in" class="h-4 w-4 inline mr-1"></i>
                            เข้าสู่ระบบ
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        document.addEventListener("DOMContentLoaded", function () {
            // Toggle 1: Password
            const toggleBtn1 = document.getElementById('toggle-password-btn');
            const passwordInput1 = document.getElementById('password');
            const eyeIcon1 = document.getElementById('eye-icon-1');
            const eyeOffIcon1 = document.getElementById('eye-off-icon-1');

            if (toggleBtn1 && passwordInput1 && eyeIcon1 && eyeOffIcon1) {
                toggleBtn1.addEventListener('click', function () {
                    const type = passwordInput1.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput1.setAttribute('type', type);
                    eyeIcon1.classList.toggle('hidden');
                    eyeOffIcon1.classList.toggle('hidden');
                });
            }

            // Toggle 2: Confirm Password
            const toggleBtn2 = document.getElementById('toggle-confirm-password-btn');
            const passwordInput2 = document.getElementById('confirmPassword');
            const eyeIcon2 = document.getElementById('eye-icon-2');
            const eyeOffIcon2 = document.getElementById('eye-off-icon-2');

            if (toggleBtn2 && passwordInput2 && eyeIcon2 && eyeOffIcon2) {
                toggleBtn2.addEventListener('click', function () {
                    const type = passwordInput2.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput2.setAttribute('type', type);
                    eyeIcon2.classList.toggle('hidden');
                    eyeOffIcon2.classList.toggle('hidden');
                });
            }

            // Real-time email validation
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('input', function () {
                    const email = this.value;
                    const warningEl = document.getElementById('email-warning');
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (email.length > 0 && !emailRegex.test(email)) {
                        warningEl.textContent = 'รูปแบบอีเมลไม่ถูกต้อง (เช่น name@example.com)';
                        warningEl.classList.remove('hidden');
                    } else {
                        warningEl.classList.add('hidden');
                    }
                });
            }

            // Real-time phone validation
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function (e) {
                    // จำกัดให้พิมพ์ได้เฉพาะตัวเลข
                    this.value = this.value.replace(/[^0-9]/g, '');

                    // จำกัดความยาวไม่เกิน 10 ตัว
                    if (this.value.length > 10) {
                        this.value = this.value.slice(0, 10);
                    }

                    // แสดง/ซ่อน warning
                    const warningEl = document.getElementById('phone-warning');

                    if (this.value.length > 0) {
                        if (!/^0[0-9]{0,9}$/.test(this.value)) {
                            warningEl.textContent = 'เบอร์โทรศัพท์ต้องขึ้นต้นด้วย 0';
                            warningEl.classList.remove('hidden');
                        } else if (this.value.length < 10 && this.value.length > 0) {
                            warningEl.textContent = 'เบอร์โทรศัพท์ต้องครบ 10 หลัก';
                            warningEl.classList.remove('hidden');
                        } else {
                            warningEl.classList.add('hidden');
                        }
                    } else {
                        warningEl.classList.add('hidden');
                    }
                });
            }

            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;
                    const phone = document.getElementById('phone').value;
                    const email = document.getElementById('email').value;
                    const firstName = document.getElementById('firstName').value;
                    const lastName = document.getElementById('lastName').value;

                    // ตรวจสอบชื่อ-นามสกุล
                    if (!firstName.trim() || !lastName.trim()) {
                        e.preventDefault();
                        alert('กรุณากรอกชื่อและนามสกุลให้ครบถ้วน');
                        return false;
                    }

                    // ตรวจสอบอีเมล
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        e.preventDefault();
                        alert('กรุณากรอกอีเมลให้ถูกต้อง (เช่น name@example.com)');
                        return false;
                    }

                    // ตรวจสอบเบอร์โทรศัพท์
                    const phoneRegex = /^0[0-9]{9}$/;
                    if (!phoneRegex.test(phone)) {
                        e.preventDefault();
                        if (phone.length !== 10) {
                            alert('เบอร์โทรศัพท์ต้องมี 10 หลัก');
                        } else if (!/^[0-9]+$/.test(phone)) {
                            alert('เบอร์โทรศัพท์ต้องเป็นตัวเลขเท่านั้น');
                        } else if (!phone.startsWith('0')) {
                            alert('เบอร์โทรศัพท์ต้องขึ้นต้นด้วย 0');
                        } else {
                            alert('รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
                        }
                        return false;
                    }

                    if (password.length < 8) {
                        e.preventDefault();
                        alert('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
                        return false;
                    }

                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('รหัสผ่านไม่ตรงกัน');
                        return false;
                    }
                });
            }
        });
    </script>
</body>

</html>