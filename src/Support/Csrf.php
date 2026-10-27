<?php
// src/Support/Csrf.php
// ป้องกัน CSRF: ทุกฟอร์ม POST ต้องแนบ token (Csrf::field()) และ fetch() ส่ง header X-CSRF-Token
// (หน้า index.php ฝัง token ใน <meta name="csrf-token"> และมีสคริปต์แนบ header ให้ fetch อัตโนมัติ)

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** ช่อง hidden สำหรับใส่ในทุก <form method="POST"> */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /** ตรวจ token จากฟอร์ม (_csrf) หรือ header (X-CSRF-Token) */
    public static function verify(): bool
    {
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $real = $_SESSION['csrf_token'] ?? '';

        return is_string($sent) && $real !== '' && hash_equals($real, $sent);
    }

    /** ใช้กับหน้าเว็บ: ถ้า token ไม่ถูกต้องให้หยุดและแจ้งผู้ใช้ */
    public static function requireValid(): void
    {
        if (! self::verify()) {
            http_response_code(419);
            echo 'เซสชันหมดอายุหรือคำขอไม่ถูกต้อง กรุณากลับไปรีเฟรชหน้าแล้วลองใหม่อีกครั้ง';
            exit;
        }
    }
}
