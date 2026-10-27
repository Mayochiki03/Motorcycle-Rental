<?php
// views/pages/HomePage.php
// ไม่ต้อง session_start() เพราะ index.php เรียกไปแล้ว

// --- (1) แปลงตรรกะ React/JS มาเป็น PHP ---

/**
 * คำนวณส่วนลด (ลด 50 บาท ทุกๆ 3 วัน)
 * @param int $days จำนวนวัน
 * @param float $pricePerDay ราคาต่อวัน
 * @return array ข้อมูลราคาสุทธิ
 */
function calculateDiscount($days, $pricePerDay)
{
    $normalPrice = $days * $pricePerDay;
    $discount = 0;
    if ($days >= 3) {
        $discount = floor($days / 3) * 50;
    }

    return [
        'normalPrice' => $normalPrice,
        'finalPrice' => $normalPrice - $discount,
        'discount' => $discount
    ];
}


$days = 3;
$pricePerDay = 950 / 3;

// เรียกใช้ฟังก์ชัน PHP
$promoData = calculateDiscount($days, $pricePerDay);

?>

<div class="min-h-screen">
    <!-- Hero Section -->
    <section class="relative bg-cover bg-center py-20"
        style="background-image: url('https://i.postimg.cc/bYLnLnRK/Cover-photo.jpg')">
        <div class="absolute inset-0 bg-black opacity-20"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6 text-blue-100 drop-shadow-lg">
                    ร้านมอเตอร์ไซค์ให้เช่า
                    <span class="block text-yellow-300 drop-shadow-lg">เทมป์เทชัน</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-blue-100 drop-shadow-sm">
                    บริการเช่ารถจักรยานยนต์คุณภาพ ครบครันทุกรุ่น ตั้งแต่ 110cc ถึง 700cc
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">

                    <a href="index.php?page=motorcycles"
                        class="bg-yellow-300 hover:bg-yellow-300 text-blue-900 px-8 py-4 rounded-lg text-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                        เลือกรถเช่า
                    </a>
                    <a href="#contact"
                        class="bg-white hover:bg-white text-blue-900 px-8 py-4 rounded-lg text-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                        ติดต่อเรา
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">ทำไมต้องเลือกเรา?</h2>
                <p class="text-lg text-gray-600">บริการครบครัน ปลอดภัย และน่าเชื่อถือ</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">

                <div class="text-center p-6 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors">
                    <div class="bg-blue-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="bike" class="h-8 w-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">รถหลากหลาย</h3>
                    <p class="text-gray-600">มีรถให้เลือกหลากหลายยี่ห้อและรุ่น ตั้งแต่ 110cc ถึง 700cc</p>
                </div>
                <div class="text-center p-6 rounded-lg bg-green-50 hover:bg-green-100 transition-colors">
                    <div class="bg-green-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="shield" class="h-8 w-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">ปลอดภัย</h3>
                    <p class="text-gray-600">รถทุกคันผ่านการตรวจสอบและบำรุงรักษาอย่างสม่ำเสมอ</p>
                </div>
                <div class="text-center p-6 rounded-lg bg-yellow-50 hover:bg-yellow-100 transition-colors">
                    <div class="bg-yellow-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="credit-card" class="h-8 w-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">ชำระง่าย</h3>
                    <p class="text-gray-600">รองรับการชำระเงินหลายช่องทาง พร้อม QR Code สะดวกรวดเร็ว</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Special Offers -->
    <section class="py-16 bg-gradient-to-r from-yellow-400 to-orange-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-8">โปรโมชั่นพิเศษ</h2>
            <div class="bg-white rounded-lg p-8 shadow-xl max-w-md mx-auto">
                <!-- 
                  (5) แปลงตัวแปร React {days} 
                  เป็น PHP <?php echo $days; ?> 
                -->
                <div class="text-4xl font-bold text-orange-600 mb-2">เช่า <?php echo $days; ?> วัน</div>
                <div class="text-2xl text-gray-800 mb-4">
                    <span class="line-through text-gray-500"><?php echo number_format($promoData['normalPrice'], 0); ?>
                        บาท</span>
                    <span
                        class="ml-2 text-green-600 font-bold"><?php echo number_format($promoData['finalPrice'], 0); ?>
                        บาท</span>
                </div>
                <p class="text-gray-600 mb-6">
                    <?php
                    // (6) แปลงตรรกะ Ternary ของ React เป็น if/else ของ PHP
                    if ($promoData['discount'] > 0):
                        ?>
                        ประหยัด <?php echo $promoData['discount']; ?> บาท สำหรับการเช่า <?php echo $days; ?> วัน (ลด 50 บาท
                        ทุกๆ 3 วัน เช่น 6 วันลด 100 บาท)
                    <?php else: ?>
                        เช่าน้อยกว่า 3 วันยังไม่มีส่วนลด
                    <?php endif; ?>
                </p>
                <a href="index.php?page=motorcycles"
                    class="bg-orange-500 hover:bg-orange-400 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    จองเลย!
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">ติดต่อเรา</h2>
                <p class="text-lg text-gray-600">พร้อมให้บริการทุกวัน</p>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white p-8 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold mb-6 text-gray-900">ข้อมูลร้าน</h3>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <i data-lucide="map-pin" class="h-5 w-5 text-blue-600 mt-1"></i>
                            <div>
                                <p class="font-medium">ที่อยู่</p>
                                <p class="text-gray-600"><?php echo e(SHOP_ADDRESS); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i data-lucide="phone" class="h-5 w-5 text-blue-600"></i>
                            <div>
                                <p class="font-medium">โทรศัพท์</p>
                                <p class="text-gray-600"><?php echo e(SHOP_PHONE); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i data-lucide="clock" class="h-5 w-5 text-blue-600"></i>
                            <div>
                                <p class="font-medium">เวลาทำการ</p>
                                <p class="text-gray-600"><?php echo e(SHOP_HOURS); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold mb-6 text-gray-900">บริการของเรา</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <i data-lucide="star" class="h-4 w-4 text-yellow-500"></i>
                            <span>รถจักรยานยนต์หลากหลายรุ่น</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="star" class="h-4 w-4 text-yellow-500"></i>
                            <span>บริการส่งรถถึงโรงแรม</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="star" class="h-4 w-4 text-yellow-500"></i>
                            <span>คืนรถที่สนามบินหาดใหญ่</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="star" class="h-4 w-4 text-yellow-500"></i>
                            <span>ประกันภัยครอบคลุม</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>