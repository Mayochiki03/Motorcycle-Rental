<?php
// src/Support/LoginThrottle.php
// จำกัดจำนวนครั้งที่กรอกรหัสผ่านผิด (กันเดาสุ่มรหัส): ผิดเกิน MAX_ATTEMPTS ครั้ง จะถูกล็อกชั่วคราว
// เก็บสถิติเป็นไฟล์ใน storage/ratelimit/ แยกตาม (IP + อีเมล)

class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;     // ผิดได้กี่ครั้ง
    private const WINDOW       = 900;   // นับภายในกี่วินาที (15 นาที)

    private static function file(string $email): string
    {
        $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'cli') . '|' . strtolower(trim($email)));
        return APP_ROOT . '/storage/ratelimit/' . $key . '.json';
    }

    private static function read(string $email): array
    {
        $file = self::file($email);
        if (! is_file($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);
        $now  = time();

        return is_array($data)
            ? array_values(array_filter($data, fn($t) => is_int($t) && $t > $now - self::WINDOW))
            : [];
    }

    /** true = ถูกล็อก ต้องรอก่อน */
    public static function isLocked(string $email): bool
    {
        return count(self::read($email)) >= self::MAX_ATTEMPTS;
    }

    /** จำนวนวินาทีที่ต้องรอ */
    public static function retryAfter(string $email): int
    {
        $attempts = self::read($email);
        return $attempts ? max(1, min($attempts) + self::WINDOW - time()) : 0;
    }

    public static function recordFailure(string $email): void
    {
        $attempts   = self::read($email);
        $attempts[] = time();
        @file_put_contents(self::file($email), json_encode($attempts), LOCK_EX);
    }

    public static function clear(string $email): void
    {
        @unlink(self::file($email));
    }
}
