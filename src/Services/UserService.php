<?php
// src/Services/UserService.php

require_once dirname(__DIR__) . '/bootstrap.php';

class UserService
{
    /**
     * ดึงข้อมูลโปรไฟล์ผู้ใช้ตาม role
     */
    public static function getUserProfile(string $userId, string $role): ?array
    {
        $db = Database::connect();

        try {
            $table    = self::getTableName($role);
            $idColumn = self::getIdColumn($role);

            if (! $table || ! $idColumn) {
                error_log("UserService::getUserProfile - Invalid role: {$role}");
                return null;
            }

            $stmt = $db->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ?");
            $stmt->execute([$userId]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // แปลงชื่อคอลัมน์ให้ตรงกับที่ใช้ใน session
                if ($role === 'customer') {
                    $user['line_id'] = $user['line_id'] ?? '';
                } elseif ($role === 'employee') {
                    $user['position'] = $user['position'] ?? 'staff';
                }
                // owner ไม่มีฟิลด์พิเศษ
            }

            return $user ?: null;

        } catch (Exception $e) {
            error_log("UserService::getUserProfile - Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * อัปเดตข้อมูลโปรไฟล์ผู้ใช้
     */
    public static function updateUserProfile(string $userId, string $role, array $data): array
    {
        $db = Database::connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $table    = self::getTableName($role);
            $idColumn = self::getIdColumn($role);

            if (! $table || ! $idColumn) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ใช้',
                ];
            }

            // เตรียมข้อมูลสำหรับอัปเดต
            $updateFields = [];
            $params       = [];

            // ฟิลด์พื้นฐานที่มีทุก table
            if (isset($data['first_name'])) {
                $updateFields[] = "first_name = ?";
                $params[]       = $data['first_name'];
            }

            if (isset($data['last_name'])) {
                $updateFields[] = "last_name = ?";
                $params[]       = $data['last_name'];
            }

            if (isset($data['phone'])) {
                $updateFields[] = "phone = ?";
                $params[]       = $data['phone'];
            }

            // ฟิลด์เฉพาะตาม role
            if ($role === 'customer' && isset($data['line_id'])) {
                $updateFields[] = "line_id = ?";
                $params[]       = $data['line_id'];
            }

            if ($role === 'employee' && isset($data['position'])) {
                $updateFields[] = "position = ?";
                $params[]       = $data['position'];
            }

            // owner ไม่มีฟิลด์พิเศษ

            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'ไม่มีข้อมูลที่จะอัปเดต',
                ];
            }

            // เพิ่ม updated_at
            $updateFields[] = "updated_at = NOW()";

            // เพิ่ม userId ต่อท้าย params
            $params[] = $userId;

            $sql = "UPDATE {$table} SET " . implode(', ', $updateFields) . " WHERE {$idColumn} = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => 'อัปเดตข้อมูลสำเร็จ',
            ];

        } catch (Exception $e) {
            error_log("UserService::updateUserProfile - Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล',
            ];
        }
    }

    /**
     * เปลี่ยนรหัสผ่าน
     */
    public static function changePassword(string $userId, string $role, string $currentPassword, string $newPassword): array
    {
        $db = Database::connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $table    = self::getTableName($role);
            $idColumn = self::getIdColumn($role);

            if (! $table || ! $idColumn) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ใช้',
                ];
            }

            // ตรวจสอบรหัสผ่านปัจจุบัน
            $stmt = $db->prepare("SELECT password_hash FROM {$table} WHERE {$idColumn} = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $user) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ใช้',
                ];
            }

            // ตรวจสอบรหัสผ่าน
            if (! password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
                ];
            }

            // อัปเดตรหัสผ่านใหม่
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $db->prepare("UPDATE {$table} SET password_hash = ?, updated_at = NOW() WHERE {$idColumn} = ?");
            $stmt->execute([$newPasswordHash, $userId]);

            return [
                'success' => true,
                'message' => 'เปลี่ยนรหัสผ่านสำเร็จ',
            ];

        } catch (Exception $e) {
            error_log("UserService::changePassword - Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน',
            ];
        }
    }

    /**
     * ดึงชื่อตารางตาม role
     */
    private static function getTableName(string $role): ?string
    {
        return match ($role) {
            'customer' => 'customers',
            'employee' => 'employees',
            'owner'    => 'owners',
            default    => null,
        };
    }

    /**
     * ดึงชื่อคอลัมน์ ID ตาม role
     */
    private static function getIdColumn(string $role): ?string
    {
        return match ($role) {
            'customer' => 'customer_id',
            'employee' => 'employee_id',
            'owner'    => 'owner_id',
            default    => null,
        };
    }

    /**
     * ตรวจสอบว่าอีเมลมีอยู่ในระบบหรือไม่
     */
    public static function isEmailExists(string $email, ?string $excludeUserId = null, ?string $role = null): bool
    {
        $db = Database::connect();

        try {
            // ตรวจสอบใน customers
            $sql    = "SELECT customer_id FROM customers WHERE email = ?";
            $params = [$email];
            if ($excludeUserId && $role === 'customer') {
                $sql      .= " AND customer_id != ?";
                $params[]  = $excludeUserId;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if ($stmt->rowCount() > 0) {
                return true;
            }

            // ตรวจสอบใน employees
            $sql    = "SELECT employee_id FROM employees WHERE email = ?";
            $params = [$email];
            if ($excludeUserId && $role === 'employee') {
                $sql      .= " AND employee_id != ?";
                $params[]  = $excludeUserId;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if ($stmt->rowCount() > 0) {
                return true;
            }

            // ตรวจสอบใน owners
            $sql    = "SELECT owner_id FROM owners WHERE email = ?";
            $params = [$email];
            if ($excludeUserId && $role === 'owner') {
                $sql      .= " AND owner_id != ?";
                $params[]  = $excludeUserId;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("UserService::isEmailExists - Error: " . $e->getMessage());
            return false;
        }
    }
}