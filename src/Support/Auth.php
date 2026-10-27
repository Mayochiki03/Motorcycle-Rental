<?php
// src/Support/Auth.php
// ตรวจสอบการล็อกอินและสิทธิ์ (role) ในที่เดียว ใช้ทั้งหน้าเว็บและ API
// role ที่มีในระบบ: customer (ลูกค้า), employee (พนักงาน), owner (เจ้าของร้าน)

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function id(): string
    {
        return (string) ($_SESSION['user']['id'] ?? '');
    }

    public static function role(): string
    {
        return strtolower((string) ($_SESSION['user']['role'] ?? ''));
    }

    public static function hasRole(string ...$roles): bool
    {
        return self::check() && in_array(self::role(), $roles, true);
    }

    /** สำหรับ "หน้าเว็บ": ถ้าไม่มีสิทธิ์ให้ redirect พร้อมข้อความแจ้งเตือน */
    public static function requireRole(array $roles, string $redirect = 'index.php?page=home'): void
    {
        if (! self::check()) {
            Session::flash('error', 'กรุณาเข้าสู่ระบบก่อน');
            header('Location: login.php');
            exit;
        }

        if (! in_array(self::role(), $roles, true)) {
            Session::flash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            header('Location: ' . $redirect);
            exit;
        }
    }

    /**
     * สำหรับ "API (JSON)": ถ้าไม่มีสิทธิ์ตอบ 401/403 แล้วหยุดทำงาน
     * ถ้าเป็นคำขอแบบ POST จะตรวจ CSRF token ให้ด้วย
     */
    public static function requireApiRole(array $roles): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (! self::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'unauthorized', 'message' => 'กรุณาเข้าสู่ระบบ']);
            exit;
        }

        if (! in_array(self::role(), $roles, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'forbidden', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && ! Csrf::verify()) {
            http_response_code(419);
            echo json_encode(['success' => false, 'error' => 'csrf', 'message' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่']);
            exit;
        }
    }
}
