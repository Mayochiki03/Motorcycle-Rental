<!-- components/Footer.php -->
<footer class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-xl font-bold mb-4">ร้านมอเตอร์ไซค์ให้เช่า เทมป์เทชัน</h3>
                <p class="text-gray-300 mb-4">
                    ให้บริการเช่ารถจักรยานยนต์คุณภาพ ครบครันทุกรุ่น ตั้งแต่ 110cc ถึง 700cc 
                    พร้อมบริการส่งรถถึงโรงแรมและคืนรถที่สนามบินหาดใหญ่
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-300 hover:text-blue-400 transition-colors">
                        <!-- ไอคอน Facebook (lucide เวอร์ชันใหม่ไม่มีไอคอนแบรนด์ จึงใช้ SVG ตรงๆ) -->
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-label="Facebook"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12z"/></svg>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-green-400 transition-colors">
                        <i data-lucide="message-circle" class="h-6 w-6"></i>
                    </a>
                </div>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="text-lg font-semibold mb-4">ติดต่อเรา</h4>
                <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <i data-lucide="map-pin" class="h-5 w-5 text-blue-400 mt-1 flex-shrink-0"></i>
                        <div>
                            <p class="text-gray-300"><?php echo e(SHOP_ADDRESS); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <i data-lucide="phone" class="h-5 w-5 text-blue-400"></i>
                        <span class="text-gray-300"><?php echo e(SHOP_PHONE); ?></span>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <i data-lucide="mail" class="h-5 w-5 text-blue-400"></i>
                        <span class="text-gray-300"><?php echo e(SUPPORT_EMAIL); ?></span>
                    </div>
                </div>
            </div>

            <!-- Business Hours -->
            <div>
                <h4 class="text-lg font-semibold mb-4">เวลาทำการ</h4>
                <div class="space-y-2">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="clock" class="h-5 w-5 text-blue-400"></i>
                        <div>
                            <p class="text-gray-300"><?php echo e(SHOP_HOURS); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <h5 class="font-semibold mb-2">บริการพิเศษ</h5>
                    <ul class="text-sm text-gray-300 space-y-1">
                        <li>• บริการส่งรถถึงโรงแรม</li>
                        <li>• คืนรถที่สนามบินหาดใหญ่</li>
                        <li>• ประกันภัยครอบคลุม</li>
                        <li>• บริการ 24 ชั่วโมง</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    <!-- แปลง © 2024 เป็น PHP date('Y') เพื่อให้เป็นปีปัจจุบันเสมอ -->
                    © <?php echo date('Y'); ?> ร้านมอเตอร์ไซค์ให้เช่า เทมป์เทชัน. สงวนลิขสิทธิ์.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">
                        นโยบายความเป็นส่วนตัว
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">
                        เงื่อนไขการใช้งาน
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">
                        คำถามที่พบบ่อย
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>