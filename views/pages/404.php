<?php
// views/pages/404.php
http_response_code(404);
?>
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center">
        <div class="flex justify-center mb-4">
            <i data-lucide="file-question" class="h-24 w-24 text-gray-400"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">404</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">ไม่พบหน้าที่คุณต้องการ</h2>
        <p class="text-gray-600 mb-8">ขออภัย หน้าที่คุณค้นหาไม่มีอยู่หรือถูกลบไปแล้ว</p>
        <a href="index.php?page=home" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2 transition-colors">
            <i data-lucide="home" class="h-5 w-5"></i>
            กลับหน้าหลัก
        </a>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>