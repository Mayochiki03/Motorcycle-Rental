<?php
// public/login.php
// หน้าเข้าสู่ระบบ (ลูกค้า / พนักงาน / เจ้าของร้าน ใช้ฟอร์มเดียวกัน ระบบตรวจ role จากอีเมลให้เอง)

require_once __DIR__ . '/../src/bootstrap.php';
require_once APP_ROOT . '/src/Services/AuthService.php';

// ข้อความแจ้งเตือนจากหน้าอื่น (เช่น ต้องเข้าสู่ระบบก่อนจอง)
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$error = '';
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $password = $_POST['password'] ?? '';

    if (LoginThrottle::isLocked($email)) {
        // กรอกผิดหลายครั้งเกินไป: ล็อกชั่วคราว
        $error = 'กรอกรหัสผ่านผิดหลายครั้งเกินไป กรุณารอ ' . ceil(LoginThrottle::retryAfter($email) / 60) . ' นาทีแล้วลองใหม่';
    } else {
        try {
            $user = AuthService::login($email, $password);

            LoginThrottle::clear($email);
            Session::login($user); // สร้าง session ID ใหม่ + เก็บข้อมูลผู้ใช้

            // พาไปหน้าตาม role
            switch ($user['role']) {
                case 'employee':
                    header('Location: index.php?page=employee');
                    exit;
                case 'owner':
                    header('Location: index.php?page=admin');
                    exit;
                default:
                    header('Location: index.php');
                    exit;
            }
        } catch (Exception $e) {
            LoginThrottle::recordFailure($email);
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="assets/vendor/lucide/lucide.min.js"></script>
</head>

<body class="min-h-screen bg-blue-50 flex items-center justify-center">

    <div class="max-w-md w-full bg-white p-8 shadow-lg rounded-xl">

        <h2 class="text-2xl font-bold text-center mb-6">เข้าสู่ระบบ</h2>

        <?php if ($flash && ! $error): ?>
            <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded mb-4">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <?php echo Csrf::field(); ?>

            <div>
                <label class="block font-medium mb-1">อีเมล</label>
                <input type="email" name="email" value="<?php echo e($email); ?>" required
                    class="w-full border px-3 py-2 rounded-lg">
            </div>

            <div>
                <label class="block font-medium mb-1">รหัสผ่าน</label>
                <input type="password" name="password" required class="w-full border px-3 py-2 rounded-lg">
            </div>


            <div class="text-right mb-2">
                <a href="forgot_password.php" class="text-sm text-blue-600 hover:underline">
                    ลืมรหัสผ่าน?
                </a>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                เข้าสู่ระบบ
            </button>
        </form>

        <p class="text-center text-sm mt-4">
            ยังไม่มีบัญชี?
            <a href="register.php" class="text-blue-600 font-medium">สมัครสมาชิก</a>
        </p>

    </div>

    <script>
        lucide.createIcons();
    </script>

</body>

</html>