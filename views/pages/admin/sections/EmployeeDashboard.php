<?php

    // หน้านี้เฉพาะเจ้าของร้านเท่านั้น
    if (! Auth::hasRole('owner')) {
    http_response_code(403);
    exit('Access denied');
    }

    require_once APP_ROOT . '/src/Services/Admin/AdminService.php';
    use Service\Admin\AdminService;

    $error = '';

    /* ================= POST ================= */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'add') {
            AdminService::createUser([
                'email'            => $_POST['email'],
                'password'         => $_POST['password'],
                'confirm_password' => $_POST['confirm_password'],
                'firstName'        => $_POST['first_name'],
                'lastName'         => $_POST['last_name'],
                'phone'            => $_POST['phone'],
                'position'         => $_POST['position'],
            ]);
        }

        if ($_POST['action'] === 'update') {
            AdminService::updateUser(
                $_POST['id'],
                $_POST['old_position'],
                $_POST['new_position'],
                [
                    'firstName'        => $_POST['first_name'],
                    'lastName'         => $_POST['last_name'],
                    'phone'            => $_POST['phone'],
                    'password'         => $_POST['password'] ?? null,
                    'confirm_password' => $_POST['confirm_password'] ?? null,
                ]
            );
        }

        // ลบผู้ใช้ (ส่งแบบ POST + CSRF token เท่านั้น ห้ามลบผ่านลิงก์ GET)
        if ($_POST['action'] === 'delete') {
            AdminService::deleteUser($_POST['id'], $_POST['position']);
        }

        header('Location: index.php?page=admin&section=employees');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    }

    $users = AdminService::getAllStaffWithOwners();

    /* ================= EDIT MODE ================= */
    $edit_mode = false;
    $edit_user = null;

    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    foreach ($users as $u) {
        if ((string) $u['id'] === (string) $_GET['id']) {
            $edit_user = $u;
            $edit_mode = true;
            break;
        }
    }

    if (! $edit_mode) {
        $error = 'ไม่พบผู้ใช้ที่ต้องการแก้ไข';
    }
    }

    /* ================= HELPER ================= */
    function getPositionBadge(string $position): string
    {
    return $position === 'owner'
        ? '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">เจ้าของร้าน</span>'
        : '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">พนักงาน</span>';
    }
?>


<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">จัดการพนักงาน</h1>

    <?php if (! empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">
            <?php echo $edit_mode ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่'; ?>
        </h2>

        <form method="POST" action="index.php?page=admin&section=employees" class="space-y-4">
<?php echo Csrf::field(); ?>
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'add'; ?>">

            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?php echo e($edit_user['id']); ?>">
                <input type="hidden" name="old_position" value="<?php echo e($edit_user['position']); ?>">
            <?php endif; ?>

            <!-- ชื่อ-นามสกุล -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                    <input type="text" name="first_name"
                           value="<?php echo e($edit_user['firstName'] ?? ''); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">นามสกุล</label>
                    <input type="text" name="last_name"
                           value="<?php echo e($edit_user['lastName'] ?? ''); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <!-- อีเมล + เบอร์โทร -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                    <input type="email" name="email"
                           value="<?php echo e($edit_user['email'] ?? ''); ?>"
                           <?php echo $edit_mode ? 'readonly' : 'required'; ?>
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg <?php echo $edit_mode ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone"
                           value="<?php echo e($edit_user['phone'] ?? ''); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <!-- รหัสผ่าน + ยืนยันรหัสผ่าน -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        รหัสผ่าน <?php echo $edit_mode ? '' : '<span class="text-red-500">*</span>'; ?>
                    </label>
                    <input type="password"
                           name="password"
                           id="password"
                           placeholder="<?php echo $edit_mode ? 'เว้นว่างไว้หากไม่ต้องการเปลี่ยน' : 'อย่างน้อย 8 ตัวอักษร'; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           <?php echo $edit_mode ? '' : 'required'; ?>>
                    <?php if ($edit_mode): ?>
                        <p class="text-xs text-gray-500 mt-1">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ยืนยันรหัสผ่าน <?php echo $edit_mode ? '' : '<span class="text-red-500">*</span>'; ?>
                    </label>
                    <input type="password"
                           name="confirm_password"
                           id="confirm_password"
                           placeholder="ยืนยันรหัสผ่าน"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           <?php echo $edit_mode ? '' : 'required'; ?>>
                    <p id="password-error" class="text-xs text-red-500 mt-1 hidden">รหัสผ่านไม่ตรงกัน</p>
                </div>
            </div>

            <!-- ตำแหน่ง -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง</label>
                <select name="<?php echo $edit_mode ? 'new_position' : 'position'; ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="staff" <?php echo($edit_user['position'] ?? '') === 'staff' ? 'selected' : ''; ?>>
                        พนักงาน
                    </option>
                    <option value="owner" <?php echo($edit_user['position'] ?? '') === 'owner' ? 'selected' : ''; ?>>
                        เจ้าของร้าน
                    </option>
                </select>
            </div>

            <!-- ปุ่ม -->
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    <?php echo $edit_mode ? 'อัปเดตข้อมูล' : 'เพิ่มพนักงาน'; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="index.php?page=admin&section=employees"
                       class="bg-gray-300 hover:bg-gray-400 px-6 py-2 rounded-lg">
                        ยกเลิก
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
    // ตรวจสอบรหัสผ่านตรงกัน
    function validatePassword() {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const errorEl = document.getElementById('password-error');

        // กรณีแก้ไขและไม่ได้กรอกรหัสผ่าน
        if (!password && !confirm) {
            errorEl.classList.add('hidden');
            return true;
        }

        if (password !== confirm) {
            errorEl.classList.remove('hidden');
            return false;
        }

        errorEl.classList.add('hidden');
        return true;
    }

    document.getElementById('password')?.addEventListener('input', validatePassword);
    document.getElementById('confirm_password')?.addEventListener('input', validatePassword);

    document.querySelector('form')?.addEventListener('submit', function(e) {
        if (!validatePassword()) {
            e.preventDefault();
            alert('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
        }
    });
    </script>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold">รายชื่อพนักงานทั้งหมด (<?php echo count($users); ?> คน)</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">ชื่อ-นามสกุล</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">อีเมล</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">เบอร์โทร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">ตำแหน่ง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <?php echo e($u['firstName'] . ' ' . $u['lastName']); ?>
                            </td>
                            <td class="px-6 py-4"><?php echo e($u['email']); ?></td>
                            <td class="px-6 py-4"><?php echo e($u['phone']); ?></td>
                            <td class="px-6 py-4"><?php echo getPositionBadge($u['position']); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <a class="text-blue-600 mr-3"
                                   href="index.php?page=admin&section=employees&action=edit&id=<?php echo urlencode($u['id']); ?>">
                                    แก้ไข
                                </a>
                                <?php if ($u['id'] !== Auth::id()): ?>
                                    <form method="POST" action="index.php?page=admin&section=employees" class="inline"
                                          onsubmit="return confirm('ยืนยันการลบผู้ใช้นี้?')">
                                        <?php echo Csrf::field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo e($u['id']); ?>">
                                        <input type="hidden" name="position" value="<?php echo e($u['position']); ?>">
                                        <button type="submit" class="text-red-600">ลบ</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-400">ลบ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>