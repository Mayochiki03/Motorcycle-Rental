<?php
// src/Support/Session.php
// จัดการ session ให้ปลอดภัย: cookie เป็น HttpOnly + SameSite และสร้าง session ID ใหม่ตอน login
//
// ข้อมูลผู้ใช้เก็บที่ $_SESSION['user'] รูปแบบเดียวเท่านั้น:
//   ['id', 'email', 'role' (customer|employee|owner), 'firstName', 'lastName', 'table']

class Session
{
    /** เริ่ม session (เรียกซ้ำได้ ไม่เกิดผลซ้ำ) */
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $isHttps = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_name('CARCARESESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** บันทึกผู้ใช้หลัง login สำเร็จ (สร้าง session ID ใหม่กัน session fixation) */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }

    /** ออกจากระบบ: ล้างข้อมูลและลบ cookie */
    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** เก็บข้อความแจ้งเตือน (flash) เพื่อแสดงในหน้าถัดไป type = success | error */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }
}
