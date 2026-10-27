<?php
// src/Services/AuthService.php

require_once dirname(__DIR__) . '/bootstrap.php';

class AuthService
{
    // ข้อความเมื่อ login ไม่สำเร็จ (ใช้ข้อความเดียวกันทุกกรณี)
    public const LOGIN_FAILED = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';

    private static function db()
    {
        return Database::connect();
    }

    // --- ฟังก์ชัน Login ---
    public static function login($email, $password)
    {
        $db = self::db();

        $roles = [
            "customer" => "customers",
            "employee" => "employees",
            "owner"    => "owners",
        ];

        foreach ($roles as $role => $table) {
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (! $user) {
                continue;
            }

            if (! password_verify($password, $user['password_hash'])) {
                // รหัสไม่ตรง: ลองตารางถัดไป (กันกรณีอีเมลซ้ำข้ามตารางแล้วทำให้อีกบทบาท login ไม่ได้)
                continue;
            }

            if ($role === "customer" || $role === "employee") {
                $isActive = $user['is_active'] ?? 1;
                if ($isActive == 0) {
                    throw new Exception("บัญชีนี้ถูกระงับการใช้งาน");
                }
            }

            return [
                "id"        => $user[$role . "_id"],
                "email"     => $user["email"],
                "role"      => $role,
                "firstName" => $user["first_name"],
                "lastName"  => $user["last_name"],
                "table"     => $table,
            ];
        }

        // ไม่พบอีเมลหรือรหัสไม่ตรง: ใช้ข้อความเดียวกัน เพื่อไม่ให้เดาได้ว่าอีเมลนี้มีในระบบหรือไม่
        // (ตรวจกับ hash หลอกด้วย เพื่อให้เวลาตอบใกล้เคียงกันทุกกรณี)
        password_verify($password, '$2y$10$mYdRAmyd3rhRE/BRwQMH0uvrUgpbBdzP33nzTTsJgr7AdipNVwnNq');
        throw new Exception(self::LOGIN_FAILED);
    }

    // --- ฟังก์ชัน Register ---
    public static function register($data)
    {
        $db = self::db();

        // อีเมลต้องไม่ซ้ำกับผู้ใช้ทุกประเภท (ลูกค้า / พนักงาน / เจ้าของร้าน)
        if (self::findUserByEmail($data['email'])) {
            return false;
        }

        $newId        = gen_id("cust", 12);
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO customers (
                customer_id, email, password_hash, first_name, last_name, phone, line_id, is_active
            ) VALUES (
                :id, :email, :pass, :fname, :lname, :phone, :line_id, 1
            )";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id'      => $newId,
                ':email'   => $data['email'],
                ':pass'    => $passwordHash,
                ':fname'   => $data['firstName'],
                ':lname'   => $data['lastName'],
                ':phone'   => $data['phone'],
                ':line_id' => $data['lineId'],
            ]);

            return [
                "id"        => $newId,
                "email"     => $data['email'],
                "role"      => "customer",
                "firstName" => $data['firstName'],
                "lastName"  => $data['lastName'],
                "table"     => "customers",
            ];

        } catch (PDOException $e) {
            error_log("Register Error: " . $e->getMessage());
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
        }
    }

    // --- ฟังก์ชันใหม่: ค้นหาผู้ใช้จากอีเมล ---
    public static function findUserByEmail($email)
    {
        $db = self::db();

        $tables = [
            'customers' => 'customer_id',
            'employees' => 'employee_id',
            'owners'    => 'owner_id',
        ];

        foreach ($tables as $table => $idField) {
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return [
                    'id'       => $user[$idField],
                    'email'    => $user['email'],
                    'role'     => rtrim($table, 's'), // customers -> customer, employees -> employee, owners -> owner
                    'table'    => $table,
                    'id_field' => $idField,
                ];
            }
        }

        return null;
    }

    // --- ฟังก์ชันใหม่: อัปเดตรหัสผ่าน ---
    public static function updatePassword($userId, $role, $table, $newPassword)
    {
        $db = self::db();

        // หา id field ให้ตรงกับตาราง
        $idField = match ($table) {
            'customers' => 'customer_id',
            'employees' => 'employee_id',
            'owners'    => 'owner_id',
            default     => null
        };

        if (! $idField) {
            return false;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $db->prepare("UPDATE {$table} SET password_hash = ?, updated_at = NOW() WHERE {$idField} = ?");
            return $stmt->execute([$passwordHash, $userId]);
        } catch (Exception $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }
}
