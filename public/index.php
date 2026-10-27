<?php
// public/index.php
// หน้าหลักของเว็บ (front controller): รับ ?page=... แล้วเลือกหน้าจาก views/pages มาแสดง

require_once __DIR__ . '/../src/bootstrap.php';

ob_start();

// ข้อความแจ้งเตือนจากหน้าก่อนหน้า (flash message) แสดงครั้งเดียวแล้วลบ
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']);
}

$page        = $_GET['page'] ?? 'home';
$currentPage = $page; // หน้าจัดการ (admin/employee) ใช้สร้างลิงก์ของตัวเอง

// ทุกคำขอแบบ POST ต้องมี CSRF token ที่ถูกต้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! Csrf::verify()) {
    http_response_code(419);
    if ($page === 'api') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['valid' => false, 'message' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้า']);
    } else {
        echo 'เซสชันหมดอายุหรือคำขอไม่ถูกต้อง กรุณากลับไปรีเฟรชหน้าแล้วลองใหม่อีกครั้ง';
    }
    exit;
}

// หน้า booking ต้อง login ก่อน
if ($page === 'booking' && ! Auth::check()) {
    Session::flash('error', 'กรุณาเข้าสู่ระบบก่อนทำการจอง');
    header('Location: login.php');
    exit;
}

// เข้า index.php เฉยๆ ให้พาไปหน้า home
if (empty($_GET['page'])) {
    header('Location: index.php?page=home');
    exit;
}

// ===== API ภายในเว็บ (ตรวจโค้ดส่วนลด) =====
if ($page === 'api') {
    require APP_ROOT . '/views/pages/api.php';
    exit;
}

// รายชื่อหน้าที่อนุญาต (whitelist): ชื่อใน URL => ไฟล์ใน views/pages
$pageMap = [
    'home'                 => 'HomePages.php',
    'motorcycles'          => 'MotorcyclesPages.php',
    'booking'              => 'BookingPages.php',
    'booking-confirmation' => 'TrackingPage.php',
    'tracking'             => 'TrackingPage.php',
    'payment'              => 'PaymentPages.php',
    'about'                => 'AboutPage.php',
    'contact'              => 'ContactPage.php',
    'my-bookings'          => 'MyBookings.php',
    'profile'              => 'ProfilePages.php',
    'employee'             => 'employee/EmployeeRouter.php',
    'admin'                => 'admin/AdminRouter.php',
];

if (array_key_exists($page, $pageMap)) {
    $contentFile = APP_ROOT . '/views/pages/' . $pageMap[$page];
} else {
    http_response_code(404);
    $contentFile = APP_ROOT . '/views/pages/404.php';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(Csrf::token()); ?>">
    <title>Motorcycle Booking</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="assets/vendor/lucide/lucide.min.js"></script>
    <script>
        // แนบ CSRF token ให้ fetch() ทุกคำขอที่ไม่ใช่ GET โดยอัตโนมัติ
        (function () {
            var token = document.querySelector('meta[name="csrf-token"]').content;
            var nativeFetch = window.fetch;
            window.fetch = function (input, init) {
                init = init || {};
                var method = (init.method || 'GET').toUpperCase();
                if (method !== 'GET' && method !== 'HEAD') {
                    var headers = new Headers(init.headers || {});
                    headers.set('X-CSRF-Token', token);
                    init.headers = headers;
                }
                return nativeFetch(input, init);
            };
        })();
    </script>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">

    <!-- Navbar -->
    <?php include APP_ROOT . '/views/components/Navbar.php'; ?>

    <!-- Flash Message -->
    <?php if ($flash_message): ?>
        <div class="fixed top-20 right-4 z-50 <?php echo $flash_message['type'] === 'error' ? 'bg-red-500' : 'bg-green-500'; ?> text-white px-6 py-3 rounded-lg shadow-lg">
            <?php echo e($flash_message['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1">
        <?php
            if (is_file($contentFile)) {
                include $contentFile;
            } else {
                echo "<p class='text-center text-red-500 p-4'>Error: Content file not found.</p>";
            }
        ?>
    </main>

    <!-- Footer -->
    <?php include APP_ROOT . '/views/components/Footer.php'; ?>

    <script>
        lucide.createIcons();

        // ซ่อนข้อความแจ้งเตือนอัตโนมัติหลัง 5 วินาที
        setTimeout(() => {
            const flashMsg = document.querySelector('.fixed.top-20');
            if (flashMsg) {
                flashMsg.remove();
            }
        }, 5000);
    </script>
</body>
</html>
