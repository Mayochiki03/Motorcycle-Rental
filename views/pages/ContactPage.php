<?php
// views/pages/ContactPage.php
// หน้า "ติดต่อเรา" (ลิงก์ index.php?page=contact)
// ข้อมูลทั้งหมดมาจาก .env (SHOP_ADDRESS, SHOP_PHONE, SHOP_HOURS, SUPPORT_PHONE, SUPPORT_EMAIL)
?>
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">ติดต่อเรา</h1>
        <p class="text-gray-600 mb-8">สอบถามรถว่าง การจอง หรือการชำระเงินได้ตามช่องทางด้านล่าง</p>

        <div class="bg-white rounded-lg shadow p-6 space-y-6">
            <div class="flex items-start space-x-4">
                <i data-lucide="map-pin" class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0"></i>
                <div>
                    <p class="font-semibold text-gray-900">ที่อยู่</p>
                    <p class="text-gray-700"><?php echo e(SHOP_ADDRESS); ?></p>
                </div>
            </div>

            <div class="flex items-start space-x-4">
                <i data-lucide="phone" class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0"></i>
                <div>
                    <p class="font-semibold text-gray-900">โทรศัพท์ร้าน</p>
                    <p class="text-gray-700"><?php echo e(SHOP_PHONE); ?></p>
                </div>
            </div>

            <div class="flex items-start space-x-4">
                <i data-lucide="phone-call" class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0"></i>
                <div>
                    <p class="font-semibold text-gray-900">สายด่วนสนับสนุน</p>
                    <a href="tel:<?php echo e(preg_replace('/\D/', '', SUPPORT_PHONE)); ?>" class="text-blue-600 hover:underline">
                        <?php echo e(SUPPORT_PHONE); ?>
                    </a>
                </div>
            </div>

            <div class="flex items-start space-x-4">
                <i data-lucide="mail" class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0"></i>
                <div>
                    <p class="font-semibold text-gray-900">อีเมล</p>
                    <a href="mailto:<?php echo e(SUPPORT_EMAIL); ?>" class="text-blue-600 hover:underline">
                        <?php echo e(SUPPORT_EMAIL); ?>
                    </a>
                </div>
            </div>

            <div class="flex items-start space-x-4">
                <i data-lucide="clock" class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0"></i>
                <div>
                    <p class="font-semibold text-gray-900">เวลาทำการ</p>
                    <p class="text-gray-700"><?php echo e(SHOP_HOURS); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
