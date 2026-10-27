<?php
// public/forgot_password.php
// หน้าแจ้งวิธีขอรีเซ็ตรหัสผ่าน
//
// ระบบนี้ไม่มีการส่งอีเมล จึงไม่เปิดให้รีเซ็ตรหัสผ่านเองด้วยการกรอกอีเมล (ใครรู้อีเมลก็เปลี่ยนรหัสคนอื่นได้)
// ลูกค้า   : ติดต่อร้าน แล้วพนักงาน/เจ้าของร้านรีเซ็ตให้ที่ จัดการลูกค้า > รายละเอียด > รีเซ็ตรหัสผ่าน
// พนักงาน  : เจ้าของร้านแก้รหัสให้ที่ หน้าจัดการพนักงาน
// เจ้าของร้าน: ผู้ดูแลเซิร์ฟเวอร์รันคำสั่ง php bin/account.php reset-password <อีเมล>

require_once __DIR__ . '/../src/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="assets/vendor/lucide/lucide.min.js"></script>
</head>

<body class="min-h-screen bg-blue-50 flex items-center justify-center px-4">

    <div class="max-w-md w-full bg-white p-8 shadow-lg rounded-xl">
        <div class="flex justify-center mb-4">
            <div class="bg-blue-600 p-3 rounded-full">
                <i data-lucide="key-round" class="h-8 w-8 text-white"></i>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-center mb-2">ลืมรหัสผ่าน</h2>
        <p class="text-center text-sm text-gray-600 mb-6">
            เพื่อความปลอดภัยของบัญชี ระบบไม่รีเซ็ตรหัสผ่านอัตโนมัติ<br>
            กรุณาติดต่อร้านเพื่อขอรีเซ็ตรหัสผ่านให้
        </p>

        <div class="space-y-3 text-sm text-gray-700">
            <div class="flex gap-3 bg-gray-50 rounded-lg p-4">
                <i data-lucide="phone" class="h-5 w-5 text-blue-600 flex-shrink-0"></i>
                <span>ติดต่อร้านตามเบอร์โทรหรืออีเมลท้ายหน้าเว็บ แล้วแจ้งอีเมลที่ใช้สมัคร พนักงานจะตั้งรหัสผ่านชั่วคราวให้</span>
            </div>
            <div class="flex gap-3 bg-gray-50 rounded-lg p-4">
                <i data-lucide="log-in" class="h-5 w-5 text-blue-600 flex-shrink-0"></i>
                <span>เมื่อเข้าสู่ระบบด้วยรหัสชั่วคราวแล้ว ให้เปลี่ยนรหัสผ่านใหม่ที่หน้าโปรไฟล์ทันที</span>
            </div>
        </div>

        <p class="text-center text-sm mt-6">
            <a href="login.php" class="text-blue-600 font-medium">กลับไปหน้าเข้าสู่ระบบ</a>
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
