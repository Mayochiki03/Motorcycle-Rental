<?php
// views/pages/admin/sections/MotorcyclesManagement.php
// หน้าจัดการรถ (เพิ่ม/แก้ไข/ลบ/อัปโหลดรูป) ใช้ร่วมกันทั้งเจ้าของร้านและพนักงาน

// เจ้าของร้านเข้าผ่าน ?page=admin ส่วนพนักงานเข้าผ่าน ?page=employee
$currentPage = Auth::role() === 'owner' ? 'admin' : 'employee';
$baseUrl = "index.php?page={$currentPage}&section=motorcycles";

require_once APP_ROOT . '/src/Services/Admin/AdminService.php';
use Service\Admin\AdminService;

/* ===================== LOAD DATA ===================== */
try {
    $motorcycles = AdminService::getAllMotorcycles();
} catch (Throwable $e) {
    $motorcycles = [];
    $error = $e->getMessage();
}

/* ===================== HANDLE POST ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';

    try {
        if ($action === 'create') {
            $imageUrl = null;

            // จัดการอัปโหลดรูปภาพผ่าน ImgBB
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageUrl = ImageUploader::upload($_FILES['image'], 'motorcycle');
            }

            AdminService::createMotorcycle([
                'motorcycle_id' => $_POST['motorcycle_id'],
                'brand' => $_POST['brand'],
                'model' => $_POST['model'],
                'year' => (int) $_POST['year'],
                'license_plate' => $_POST['license_plate'],
                'color' => $_POST['color'],
                'engine_cc' => (int) $_POST['engine_cc'],
                'price_per_day' => (float) $_POST['price_per_day'],
                'description' => $_POST['description'] ?? null,
                'is_available' => 1, // ตั้งค่าเป็น 1 ตลอด
                'maintenance_status' => $_POST['maintenance_status'] ?? 'READY',
                'image_url' => $imageUrl,
            ]);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'เพิ่มรถเช่าเรียบร้อยแล้ว',
            ];
        }

        if ($action === 'edit') {
            $imageUrl = $_POST['existing_image'] ?? null;

            // จัดการอัปโหลดรูปภาพใหม่ (ถ้ามี)
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageUrl = ImageUploader::upload($_FILES['image'], 'motorcycle');
            }

            $updateData = [
                'brand' => $_POST['brand'],
                'model' => $_POST['model'],
                'year' => (int) $_POST['year'],
                'license_plate' => $_POST['license_plate'],
                'color' => $_POST['color'],
                'engine_cc' => (int) $_POST['engine_cc'],
                'price_per_day' => (float) $_POST['price_per_day'],
                'description' => $_POST['description'] ?? null,
                'is_available' => 1, // ตั้งค่าเป็น 1 ตลอด
                'maintenance_status' => $_POST['maintenance_status'] ?? 'READY',
            ];

            if ($imageUrl !== null) {
                $updateData['image_url'] = $imageUrl;
            }

            AdminService::updateMotorcycle($_POST['motorcycle_id'], $updateData);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'อัปเดตรถเช่าเรียบร้อยแล้ว',
            ];
        }

        if ($action === 'delete') {
            AdminService::deleteMotorcycle($_POST['motorcycle_id']);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'ลบรถเช่าเรียบร้อยแล้ว',
            ];
        }

        header("Location: {$baseUrl}");
        exit;

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

/* ===================== EDIT MODE ===================== */
$editMotorcycle = null;
if (($_GET['action'] ?? '') === 'edit' && isset($_GET['id'])) {
    foreach ($motorcycles as $m) {
        if ($m['motorcycleId'] === $_GET['id']) {
            $editMotorcycle = $m;
            break;
        }
    }
}

/* ===================== HELPERS ===================== */
function statusBadge(bool $available, string $status): string
{
    return match ($status) {
        'READY' => '<span class="px-3 py-1.5 rounded-full bg-green-100 text-green-700 text-xs font-medium">พร้อมใช้งาน</span>',
        'MAINTENANCE' => '<span class="px-3 py-1.5 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium">ซ่อมบำรุง</span>',
        'CLEANING' => '<span class="px-3 py-1.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">ทำความสะอาด</span>',
        'DAMAGED' => '<span class="px-3 py-1.5 rounded-full bg-red-100 text-red-700 text-xs font-medium">เสียหายรุนแรง</span>',
        'LOST' => '<span class="px-3 py-1.5 rounded-full bg-gray-900 text-white text-xs font-medium">สูญหาย</span>',
        'UNAVAILABLE' => '<span class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">ไม่พร้อมใช้งาน</span>',
        default => '<span class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">' . $status . '</span>',
    };
}

function getStatusText(string $status): string
{
    return match ($status) {
        'READY' => 'พร้อมใช้งาน',
        'MAINTENANCE' => 'ซ่อมบำรุง',
        'CLEANING' => 'ทำความสะอาด',
        'DAMAGED' => 'เสียหายรุนแรง',
        'LOST' => 'สูญหาย',
        'UNAVAILABLE' => 'ไม่พร้อมใช้งาน',
        default => $status,
    };
}

// ฟังก์ชันดึง URL รูปภาพ
function getImageUrl($motorcycle): string
{
    // SVG placeholder สำหรับรถที่ไม่มีรูป
    $placeholderImage = 'data:image/svg+xml;base64,' . base64_encode('
        <svg xmlns="http://www.w3.org/2000/svg" width="200" height="150" viewBox="0 0 200 150">
            <rect width="200" height="150" fill="#f3f4f6"/>
            <path d="M60,60 L140,60 L160,90 L140,120 L60,120 L40,90 Z" fill="white" stroke="#d1d5db" stroke-width="2"/>
            <circle cx="100" cy="90" r="15" fill="#9ca3af"/>
            <path d="M80,40 Q100,30 120,40" stroke="#9ca3af" stroke-width="2" fill="none"/>
            <text x="100" y="140" text-anchor="middle" fill="#6b7280" font-size="12" font-family="Arial">No Image</text>
        </svg>
    ');

    if (empty($motorcycle['imageUrl'])) {
        return $placeholderImage;
    }

    // ถ้าเป็น URL เต็ม
    if (filter_var($motorcycle['imageUrl'], FILTER_VALIDATE_URL)) {
        return $motorcycle['imageUrl'];
    }

    // ถ้าเป็น path ภายใน (สำหรับ compatibility)
    return $motorcycle['imageUrl'];
}
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">จัดการรถเช่า</h1>
            <p class="text-gray-600 mt-1">เพิ่ม แก้ไข และควบคุมสถานะรถเช่าทั้งหมด</p>
        </div>
        <?php if ($editMotorcycle): ?>
            <a href="<?php echo e($baseUrl); ?>"
                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                    </path>
                </svg>
                เพิ่มรถใหม่
            </a>
        <?php endif; ?>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="p-4 rounded-lg bg-green-50 text-green-700 border border-green-200 flex items-start">
            <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
            </svg>
            <div>
                <p class="font-medium">ดำเนินการสำเร็จ</p>
                <p class="text-sm"><?php echo e($_SESSION['flash_message']['message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-lg bg-red-50 text-red-700 border border-red-200 flex items-start">
            <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"></path>
            </svg>
            <div>
                <p class="font-medium">เกิดข้อผิดพลาด</p>
                <p class="text-sm"><?php echo e($error); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Form Panel -->
        <div class="lg:col-span-1">
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sticky top-6 max-h-[calc(100vh-6rem)] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-semibold text-lg text-gray-800">
                        <?php echo $editMotorcycle ? 'แก้ไขรถเช่า' : 'เพิ่มรถเช่าใหม่'; ?>
                    </h2>
                    <?php if ($editMotorcycle): ?>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                            โหมดแก้ไข
                        </span>
                    <?php endif; ?>
                </div>

                <form method="post"
                    action="index.php?page=<?php echo $currentPage; ?>&section=motorcycles&action=<?php echo $editMotorcycle ? 'edit' : 'create'; ?>"
                    class="space-y-4 pb-6" onsubmit="return validateForm()" enctype="multipart/form-data">
<?php echo Csrf::field(); ?>

                    <!-- รูปภาพรถ -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            รูปภาพรถ
                        </label>

                        <?php if ($editMotorcycle): ?>
                            <!-- แสดงรูปภาพปัจจุบัน (ถ้ามี) -->
                            <?php if (!empty($editMotorcycle['imageUrl'])): ?>
                                <div class="mb-3">
                                    <img src="<?php echo getImageUrl($editMotorcycle); ?>" alt="รูปภาพปัจจุบัน"
                                        class="w-full h-48 object-cover rounded-lg border border-gray-200 mb-2">
                                    <p class="text-xs text-gray-500 text-center">รูปภาพปัจจุบัน</p>
                                </div>
                            <?php endif; ?>
                            <input type="hidden" name="existing_image"
                                value="<?php echo e($editMotorcycle['imageUrl'] ?? ''); ?>">
                        <?php endif; ?>

                        <!-- อัปโหลดรูปภาพใหม่ -->
                        <div
                            class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition-colors">
                            <div class="space-y-2">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <div>
                                    <label for="image" class="cursor-pointer">
                                        <span class="text-blue-600 hover:text-blue-700 font-medium">
                                            <?php echo $editMotorcycle ? 'เปลี่ยนรูปภาพ' : 'เลือกรูปภาพ'; ?>
                                        </span>
                                        <span class="text-gray-500 text-sm block mt-1">PNG, JPG, GIF ขนาดไม่เกิน
                                            5MB</span>
                                    </label>
                                    <input type="file" id="image" name="image" accept="image/*" class="hidden"
                                        onchange="previewImage(this)">
                                </div>
                            </div>
                        </div>

                        <!-- Preview ของรูปใหม่ -->
                        <div id="imagePreview" class="hidden mt-3">
                            <img id="previewImage"
                                class="w-full h-48 object-cover rounded-lg border border-gray-200 mb-2">
                            <p class="text-xs text-gray-500 text-center">รูปภาพใหม่</p>
                            <button type="button" onclick="removeImage()"
                                class="mt-1 text-sm text-red-600 hover:text-red-700">
                                ลบรูปภาพนี้
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            รหัสรถเช่า
                        </label>
                        <?php if ($editMotorcycle): ?>
                            <div class="flex items-center">
                                <input type="hidden" name="motorcycle_id"
                                    value="<?php echo e($editMotorcycle['motorcycleId']); ?>">
                                <div class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
                                    <?php echo e($editMotorcycle['motorcycleId']); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <input name="motorcycle_id" placeholder="เช่น MC001, MC002"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                required>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                ยี่ห้อ
                            </label>
                            <input name="brand" placeholder="เช่น Honda, Yamaha"
                                value="<?php echo e($editMotorcycle['brand'] ?? ''); ?>"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                รุ่น
                            </label>
                            <input name="model" placeholder="เช่น CBR150R, NMAX"
                                value="<?php echo e($editMotorcycle['model'] ?? ''); ?>"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                ปี
                            </label>
                            <input type="number" name="year" placeholder="เช่น 2023" min="2000"
                                max="<?php echo date('Y') + 1; ?>"
                                value="<?php echo e($editMotorcycle['year'] ?? date('Y')); ?>"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                สี
                            </label>
                            <input name="color" placeholder="เช่น ดำ, แดง"
                                value="<?php echo e($editMotorcycle['color'] ?? ''); ?>"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                ขนาดเครื่อง (cc)
                            </label>
                            <input type="number" name="engine_cc" placeholder="เช่น 150, 300, 650" min="50" max="2000"
                                step="1" value="<?php echo e($editMotorcycle['engineCc'] ?? ''); ?>"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                ทะเบียนรถ
                            </label>
                            <input name="license_plate" placeholder="เช่น กข 1234 กรุงเทพมหานคร"
                                value="<?php echo e($editMotorcycle['licensePlate'] ?? ''); ?>"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                required>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            ราคาต่อวัน (บาท)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">฿</span>
                            <input type="number" step="0.01" min="0" name="price_per_day" placeholder="0.00"
                                value="<?php echo e($editMotorcycle['pricePerDay'] ?? ''); ?>"
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                required>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            รายละเอียดเพิ่มเติม
                        </label>
                        <textarea name="description" rows="3"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition resize-none"
                            placeholder="รายละเอียดเกี่ยวกับรถ..."><?php echo e($editMotorcycle['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            สถานะการบำรุงรักษา
                        </label>
                        <select name="maintenance_status"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <?php foreach ([
                                'READY' => 'พร้อมใช้งาน',
                                'MAINTENANCE' => 'ซ่อมบำรุง',
                                'CLEANING' => 'ทำความสะอาด',
                                'DAMAGED' => 'เสียหายรุนแรง',
                                'LOST' => 'สูญหาย',
                                'UNAVAILABLE' => 'ไม่พร้อมใช้งาน'
                            ] as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo (($editMotorcycle['maintenanceStatus'] ?? 'READY') === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hidden field for is_available (always 1) -->
                    <input type="hidden" name="is_available" value="1">

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 shadow-sm hover:shadow">
                            <div class="flex items-center justify-center">
                                <?php if ($editMotorcycle): ?>
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    บันทึกการแก้ไข
                                <?php else: ?>
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    เพิ่มรถเช่าใหม่
                                <?php endif; ?>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-lg text-gray-800">รายการรถเช่าทั้งหมด</h2>
                            <p class="text-sm text-gray-600 mt-1">
                                <?php echo count($motorcycles); ?> คัน
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="relative">
                                <input type="text" id="searchMotorcycle" placeholder="ค้นหารถเช่า..."
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    รูปภาพ
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    รหัสรถ
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    รายละเอียดรถ
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    ราคา/วัน
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    สถานะ
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider text-right">
                                    จัดการ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($motorcycles as $m): ?>
                                <tr class="hover:bg-gray-50 transition-colors <?php echo ($editMotorcycle && $editMotorcycle['motorcycleId'] === $m['motorcycleId']) ? 'bg-blue-50' : ''; ?>"
                                    data-search="<?php echo strtolower(e($m['motorcycleId'] . ' ' . $m['brand'] . ' ' . $m['model'] . ' ' . $m['licensePlate'] . ' ' . $m['engineCc'])); ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-16 h-16 rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                                            <img src="<?php echo getImageUrl($m); ?>"
                                                alt="<?php echo e($m['brand'] . ' ' . $m['model']); ?>"
                                                class="w-full h-full object-cover"
                                                onerror="this.onerror=null; this.src='<?php echo getImageUrl(['imageUrl' => '']); ?>';">
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 font-mono">
                                            <?php echo e($m['motorcycleId']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <?php echo e($m['brand'] . ' ' . $m['model']); ?>
                                                </div>
                                                <div
                                                    class="text-sm text-gray-600 mt-1 flex flex-wrap items-center gap-x-1.5">
                                                    <span
                                                        class="inline-block"><?php echo e($m['licensePlate']); ?></span>
                                                    <?php if (!empty($m['color'])): ?>
                                                        <span class="text-gray-400 inline-block">•</span>
                                                        <span
                                                            class="text-gray-600 inline-block"><?php echo e($m['color']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($m['engineCc'])): ?>
                                                        <span class="text-gray-400 inline-block">•</span>
                                                        <span
                                                            class="text-gray-600 inline-block"><?php echo number_format($m['engineCc']); ?>
                                                            cc</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($m['year'])): ?>
                                                        <span class="text-gray-400 inline-block">•</span>
                                                        <span class="text-gray-600 inline-block">ปี
                                                            <?php echo e($m['year']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($m['description'])): ?>
                                                    <div class="text-xs text-gray-500 mt-1 line-clamp-2">
                                                        <?php echo e($m['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">
                                            ฿<?php echo number_format($m['pricePerDay'], 2); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">ต่อวัน</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="space-y-2">
                                            <?php echo statusBadge($m['isAvailable'], $m['maintenanceStatus']); ?>
                                            <?php if ($m['isAvailable']): ?>
                                                <div class="text-xs text-green-600">พร้อมใช้งาน</div>
                                            <?php else: ?>
                                                <div class="text-xs text-gray-500">ไม่พร้อมใช้งาน</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="index.php?page=<?php echo $currentPage; ?>&section=motorcycles&action=edit&id=<?php echo e($m['motorcycleId']); ?>"
                                                class="inline-flex items-center px-3 py-1.5 border border-blue-300 rounded-lg text-blue-700 hover:bg-blue-50 transition-colors text-sm">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                                แก้ไข
                                            </a>
                                            <form method="post"
                                                action="index.php?page=<?php echo $currentPage; ?>&section=motorcycles&action=delete"
                                                onsubmit="return confirmDelete()" class="inline-block">
<?php echo Csrf::field(); ?>
                                                <input type="hidden" name="motorcycle_id"
                                                    value="<?php echo e($m['motorcycleId']); ?>">
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 border border-red-300 rounded-lg text-red-700 hover:bg-red-50 transition-colors text-sm">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                    ลบ
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($motorcycles)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            <p class="text-lg font-medium text-gray-500 mb-2">ยังไม่มีข้อมูลรถเช่า</p>
                                            <p class="text-gray-400">เริ่มต้นโดยเพิ่มรถเช่าคันแรก</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($motorcycles)): ?>
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <div>
                                แสดง <span class="font-semibold"><?php echo count($motorcycles); ?></span> รายการ
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center text-sm">
                                    <span class="w-3 h-3 rounded-full bg-green-100 mr-1"></span>
                                    พร้อมใช้งาน
                                    <span class="mx-2">•</span>
                                    <span class="w-3 h-3 rounded-full bg-yellow-100 mr-1"></span>
                                    ซ่อมบำรุง
                                    <span class="mx-2">•</span>
                                    <span class="w-3 h-3 rounded-full bg-red-100 mr-1"></span>
                                    เสียหายรุนแรง
                                    <span class="mx-2">•</span>
                                    <span class="w-3 h-3 rounded-full bg-gray-900 mr-1"></span>
                                    สูญหาย
                                    <span class="mx-2">•</span>
                                    <span class="w-3 h-3 rounded-full bg-gray-100 mr-1"></span>
                                    ไม่ว่าง
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Form Validation
    function validateForm() {
        const price = document.querySelector('input[name="price_per_day"]');
        if (price && parseFloat(price.value) <= 0) {
            alert('กรุณากรอกราคาต่อวันที่มากกว่า 0');
            price.focus();
            return false;
        }

        const engineCc = document.querySelector('input[name="engine_cc"]');
        if (engineCc && (parseInt(engineCc.value) < 50 || parseInt(engineCc.value) > 2000)) {
            alert('กรุณากรอกขนาดเครื่องยนต์ระหว่าง 50-2000 cc');
            engineCc.focus();
            return false;
        }

        // ตรวจสอบขนาดไฟล์รูปภาพ
        const fileInput = document.getElementById('image');
        if (fileInput && fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (file.size > maxSize) {
                alert('ไฟล์รูปภาพต้องมีขนาดไม่เกิน 5MB');
                return false;
            }

            // ตรวจสอบประเภทไฟล์
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('ไฟล์ต้องเป็นรูปภาพเท่านั้น (JPG, PNG, GIF, WebP)');
                return false;
            }
        }

        return true;
    }

    // Preview รูปภาพ
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function (e) {
                document.getElementById('previewImage').src = e.target.result;
                document.getElementById('imagePreview').classList.remove('hidden');

                // ซ่อนรูปภาพเก่า (ถ้ามี)
                const currentImage = document.querySelector('#currentImage');
                if (currentImage) {
                    currentImage.parentElement.style.display = 'none';
                }
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    // ลบรูปภาพที่เลือก
    function removeImage() {
        const fileInput = document.getElementById('image');
        const preview = document.getElementById('imagePreview');

        fileInput.value = '';
        preview.classList.add('hidden');

        // แสดงรูปภาพเก่าคืน (ถ้ามี)
        const currentImage = document.querySelector('#currentImage');
        if (currentImage) {
            currentImage.parentElement.style.display = 'block';
        }
    }

    // Delete Confirmation
    function confirmDelete() {
        return confirm('คุณแน่ใจว่าต้องการลบรถเช่าคันนี้?\nการกระทำนี้ไม่สามารถย้อนกลับได้');
    }

    // Search Functionality
    document.getElementById('searchMotorcycle')?.addEventListener('input', function (e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr[data-search]');

        rows.forEach(row => {
            const searchText = row.getAttribute('data-search');
            if (searchText.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Auto-focus on first input when in create mode
    <?php if (!$editMotorcycle): ?>
        document.addEventListener('DOMContentLoaded', function () {
            const firstInput = document.querySelector('input[name="motorcycle_id"]');
            if (firstInput) firstInput.focus();
        });
    <?php endif; ?>

    // Drag and drop for image upload
    document.addEventListener('DOMContentLoaded', function () {
        const uploadArea = document.querySelector('.border-2.border-dashed.border-gray-300');
        const fileInput = document.getElementById('image');

        if (uploadArea && fileInput) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                uploadArea.classList.add('border-blue-500', 'bg-blue-50');
            }

            function unhighlight() {
                uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
            }

            uploadArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                fileInput.files = files;
                previewImage(fileInput);
            }
        }
    });
</script>