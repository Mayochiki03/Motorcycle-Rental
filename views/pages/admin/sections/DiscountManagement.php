<?php
    require_once APP_ROOT . '/src/Services/Admin/AdminService.php';
    use Service\Admin\AdminService;

    /* ================= LOAD DATA ================= */
    $allDiscounts = AdminService::getAllDiscounts();

    /* ================= EDIT MODE ================= */
    $editDiscount = null;
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    foreach ($allDiscounts as $d) {
        if ($d['discountId'] === $_GET['id']) {
            $editDiscount = $d;
            break;
        }
    }
    }

    /* ================= HANDLE FORM ================= */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';

    // ข้อมูลฟอร์มมีเฉพาะตอนสร้าง/แก้ไข (ตอนลบจะส่งมาแค่ discount_id)
    $data = [];
    if (in_array($action, ['create', 'edit'], true)) {
    $data = [
        'discountCode'  => strtoupper(trim($_POST['code'] ?? '')),
        'discountType'  => $_POST['type'],
        'discountValue' => floatval($_POST['value']),
        'minDays'       => intval($_POST['min_days'] ?? 1),
        'maxDiscount'   => ($_POST['max_discount'] ?? '') !== '' ? floatval($_POST['max_discount']) : null,
        'startDate'     => $_POST['start_date'],
        'endDate'       => $_POST['end_date'],
        'usageLimit'    => ($_POST['usage_limit'] ?? '') !== '' ? intval($_POST['usage_limit']) : null,
    ];
    }

    if ($action === 'create') {
        AdminService::createDiscount($data);
    }

    if ($action === 'edit' && isset($_POST['discount_id'])) {
        AdminService::updateDiscount($_POST['discount_id'], $data);
    }

    if ($action === 'delete' && isset($_POST['discount_id'])) {
        AdminService::deleteDiscount($_POST['discount_id']);
    }

    header('Location: index.php?page=admin&section=discounts');
    exit;
    }

    /* ================= STATS ================= */
    $now = time();

    $activeDiscounts = array_filter($allDiscounts, function ($d) use ($now) {
    return strtotime($d['startDate']) <= $now
    && strtotime($d['endDate']) >= $now
        && $d['isActive'];
    });

    $expiredDiscounts = array_filter($allDiscounts, function ($d) use ($now) {
    return strtotime($d['endDate']) < $now;
    });
?>




<!-- ส่วน HTML ต่อไปนี้เหมือนเดิม แต่ใช้ข้อมูลจาก $allDiscounts แทน $_SESSION['discounts'] -->
<div class="space-y-6">

    <!-- Header -->
    <div class="mb-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">จัดการโค้ดส่วนลด</h1>
        <p class="text-gray-600">สร้างและจัดการโค้ดส่วนลดสำหรับการจอง</p>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i data-lucide="check-circle" class="h-5 w-5"></i>
            <span><?php echo e($_SESSION['flash_message']['message']); ?></span>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i data-lucide="alert-circle" class="h-5 w-5"></i>
            <span><?php echo e($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Form Section (เหมือนเดิม) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-8">
                <h2 class="text-xl font-bold mb-4">
                    <?php echo $editDiscount ? 'แก้ไขส่วนลด' : 'สร้างส่วนลดใหม่'; ?>
                </h2>

                <form method="POST" action="index.php?page=admin&section=discounts&action=<?php echo $editDiscount ? 'edit' : 'create'; ?>">
<?php echo Csrf::field(); ?>

                    <?php if ($editDiscount): ?>
                        <input type="hidden" name="discount_id" value="<?php echo e($editDiscount['discountId'] ?? $editDiscount['id']); ?>">
                    <?php endif; ?>

                    <!-- Code -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">โค้ดส่วนลด</label>
                        <input type="text" name="code"
                            <?php echo $editDiscount ? 'readonly ' : ''; ?>
                            value="<?php echo e($editDiscount['discountCode'] ?? $editDiscount['code'] ?? ''); ?>"
                            placeholder="เช่น SUMMER50"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent
                            <?php echo $editDiscount ? 'bg-gray-100 cursor-not-allowed' : ''; ?>"
                            required>
                        <p class="text-xs text-gray-500 mt-1">ตัวอักษรและตัวเลขเท่านั้น, สูงสุด 20 ตัว</p>
                    </div>

                    <!-- Type -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภทส่วนลด</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            <option value="">เลือกประเภท...</option>
                            <option value="percentage"                                                                                                             <?php echo($editDiscount['discountType'] ?? $editDiscount['type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>เปอร์เซ็นต์ (%)</option>
                            <option value="fixed"                                                                                                   <?php echo($editDiscount['discountType'] ?? $editDiscount['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>จำนวนคงที่ (฿)</option>
                        </select>
                    </div>

                    <!-- Value -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">มูลค่าส่วนลด</label>
                        <input type="number" name="value" step="0.01" min="0"
                            value="<?php echo e($editDiscount['discountValue'] ?? $editDiscount['value'] ?? ''); ?>"
                            placeholder="เช่น 50 หรือ 500"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Min Days -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนวันเช่าขั้นต่ำ</label>
                        <input type="number" name="min_days" min="1"
                            value="<?php echo e($editDiscount['minDays'] ?? $editDiscount['min_days'] ?? '1'); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Max Discount -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ส่วนลดสูงสุด (฿)</label>
                        <input type="number" name="max_discount" step="0.01" min="0"
                            value="<?php echo e($editDiscount['maxDiscount'] ?? $editDiscount['max_discount'] ?? ''); ?>"
                            placeholder="ไม่บังคับ - เว้นว่างไว้หากไม่จำกัด"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Start Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">วันที่เริ่มต้น</label>
                        <input type="date" name="start_date"
                            value="<?php echo e($editDiscount['startDate'] ?? $editDiscount['start_date'] ?? date('Y-m-d')); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- End Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">วันที่สิ้นสุด</label>
                        <input type="date" name="end_date"
                            value="<?php echo e($editDiscount['endDate'] ?? $editDiscount['end_date'] ?? date('Y-m-d', strtotime('+30 days'))); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Usage Limit -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">จำกัดการใช้งาน</label>
                        <input type="number" name="usage_limit" min="1"
                            value="<?php echo e($editDiscount['usageLimit'] ?? $editDiscount['usage_limit'] ?? ''); ?>"
                            placeholder="ไม่บังคับ - เว้นว่างไว้หากไม่จำกัด"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            <?php echo $editDiscount ? 'อัปเดต' : 'สร้าง'; ?>
                        </button>
                        <?php if ($editDiscount): ?>
                            <a href="index.php?page=admin&section=discounts" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-900 font-semibold py-2 px-4 rounded-lg text-center transition">
                                ยกเลิก
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- List Section -->
        <div class="lg:col-span-2">
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">โค้ดทั้งหมด</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo count($allDiscounts); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">ใช้งานได้</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo count($activeDiscounts); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">หมดอายุ</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo count($expiredDiscounts); ?></p>
                </div>
            </div>

            <!-- Discounts Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">โค้ด</th>
                                <th class="px-4 py-3 text-left font-semibold">ส่วนลด</th>
                                <th class="px-4 py-3 text-left font-semibold">ช่วงเวลาใช้งาน</th>
                                <th class="px-4 py-3 text-left font-semibold">การใช้งาน</th>
                                <th class="px-4 py-3 text-left font-semibold">สถานะ</th>
                                <th class="px-4 py-3 text-left font-semibold">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($allDiscounts as $discount): ?>
                                <?php
                                    $discountId        = $discount['discountId'] ?? $discount['id'];
                                    $discountCode      = $discount['discountCode'] ?? $discount['code'];
                                    $discountType      = $discount['discountType'] ?? $discount['type'];
                                    $discountValue     = $discount['discountValue'] ?? $discount['value'];
                                    $minDays           = $discount['minDays'] ?? $discount['min_days'] ?? 1;
                                    $maxDiscount       = $discount['maxDiscount'] ?? $discount['max_discount'] ?? null;
                                    $startDate         = $discount['startDate'] ?? $discount['start_date'];
                                    $endDate           = $discount['endDate'] ?? $discount['end_date'];
                                    $usageLimit        = $discount['usageLimit'] ?? $discount['usage_limit'] ?? null;
                                    $usedCount         = $discount['usedCount'] ?? $discount['used_count'] ?? 0;
                                    $isActive          = $discount['isActive'] ?? $discount['is_active'] ?? true;
                                    $usageLimitReached = $usageLimit !== null && $usedCount >= $usageLimit;

                                    $isActiveStatus = strtotime($startDate) <= time() && time() <= strtotime($endDate) && $isActive;
                                    $isExpired      = time() > strtotime($endDate);
                                    $isUpcoming     = strtotime($startDate) > time();
                                    $usagePercent   = $usageLimit ? ($usedCount / $usageLimit * 100) : 0;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono font-bold text-blue-600">
                                        <?php echo e($discountCode); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php
                                            if ($discountType === 'percentage') {
                                                echo $discountValue . ' % ';
                                            } else {
                                                echo '฿' . number_format($discountValue, 0);
                                            }
                                            if ($maxDiscount) {
                                                echo '(สูงสุด ฿' . number_format($maxDiscount, 0) . ')';
                                            }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div><?php echo date('d / m / Y', strtotime($startDate)); ?></div>
                                        <div class="text-gray-500">ถึง</div>
                                        <div><?php echo date('d / m / Y', strtotime($endDate)); ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm"><?php echo e($usedCount); ?>/<?php echo e($usageLimit ?: 'ไม่จำกัด'); ?></div>
                                        <?php if ($usageLimit): ?>
                                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width:                                                                                                                                                                                                               <?php echo min($usagePercent, 100); ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($usageLimitReached): ?>
                                            <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full">
                                                ใช้สิทธิ์ครบแล้ว
                                            </span>

                                        <?php elseif ($isActiveStatus): ?>
                                            <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                                ใช้งานได้
                                            </span>

                                        <?php elseif ($isExpired): ?>
                                            <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                                หมดอายุ
                                            </span>

                                        <?php elseif ($isUpcoming): ?>
                                            <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                                เร็วๆ นี้
                                            </span>

                                        <?php else: ?>
                                            <span class="inline-block px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">
                                                ปิดใช้งาน
                                            </span>
                                        <?php endif; ?>

                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <a href="index.php?page=admin&section=discounts&action=edit&id=<?php echo e($discountId); ?>"
                                               class="px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold">
                                                แก้ไข
                                            </a>
                                            <form method="POST" action="index.php?page=admin&section=discounts&action=delete" style="display:inline;">
<?php echo Csrf::field(); ?>
                                                <input type="hidden" name="discount_id" value="<?php echo e($discountId); ?>">
                                                <button type="submit" onclick="return confirm('ลบส่วนลดนี้หรือไม่ ? ')"
                                                        class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs font-semibold">
                                                    ลบ
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($allDiscounts)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>ยังไม่มีโค้ดส่วนลด</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
