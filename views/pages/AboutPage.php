<?php
// views/pages/AboutPage.php
// หน้า "เงื่อนไขการเช่า" (ลิงก์ index.php?page=about)
// เนื้อหาในหน้านี้อ้างอิงเฉพาะสิ่งที่ระบบทำจริง ถ้าร้านมีเงื่อนไขเพิ่มเติม (เอกสารที่ต้องใช้ ประกัน ค่าปรับ ฯลฯ)
// ให้เพิ่มเป็นข้อๆ ในรายการด้านล่างได้เลย
?>
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">เงื่อนไขการเช่า</h1>
        <p class="text-gray-600 mb-8">ขั้นตอนและเงื่อนไขการจองรถจักรยานยนต์กับทางร้าน</p>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">ขั้นตอนการจอง</h2>
            <ol class="list-decimal pl-5 space-y-2 text-gray-700">
                <li>เลือกวันที่รับ-คืนรถ แล้วเลือกรถที่ว่างในช่วงเวลานั้น</li>
                <li>ระบุสถานที่รับ-คืนรถ แล้วกดยืนยันการจอง (ต้องเข้าสู่ระบบก่อน)</li>
                <li>ชำระมัดจำ <?php echo number_format(DEPOSIT_AMOUNT); ?> บาท ผ่าน QR พร้อมเพย์ แล้วอัปโหลดสลิปโอนเงิน</li>
                <li>ทางร้านตรวจสลิปและยืนยันการจอง คุณติดตามสถานะได้ที่หน้า "การจองของฉัน"</li>
                <li>ส่วนที่เหลือชำระที่ร้านตอนรับรถ</li>
            </ol>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">ราคาและส่วนลด</h2>
            <ul class="list-disc pl-5 space-y-2 text-gray-700">
                <li>ราคาคิดเป็นรายวัน นับรวมวันที่รับรถและวันที่คืนรถ</li>
                <li>เช่า 3 วันขึ้นไป ลดให้ 50 บาททุกๆ 3 วัน</li>
                <li>ใช้โค้ดส่วนลดเพิ่มได้ในหน้าจอง ตามเงื่อนไขของแต่ละโค้ด (ระบบตรวจให้เมื่อกดยืนยันการจอง)</li>
                <li>ค่ามัดจำจะไม่เกินราคาที่ต้องชำระจริง</li>
            </ul>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">การยกเลิกและเปลี่ยนแปลง</h2>
            <p class="text-gray-700">
                หากต้องการยกเลิกหรือเปลี่ยนแปลงการจอง กรุณาติดต่อทางร้านโดยตรง
                พร้อมแจ้งรหัสการจองจากหน้า "การจองของฉัน"
            </p>
        </div>

        <div class="text-center mt-8">
            <a href="index.php?page=contact"
               class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                ติดต่อสอบถาม
            </a>
        </div>
    </div>
</div>
