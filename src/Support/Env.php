<?php
// src/Support/Env.php
// อ่านค่าตั้งค่าจากไฟล์ .env (รูปแบบ KEY=VALUE) แล้วให้เรียกใช้ผ่าน Env::get()
// ไฟล์ .env ห้าม commit ขึ้น Git (มี .env.example ให้คัดลอกไปแก้แทน)

class Env
{
    private static array $values = [];

    /** โหลดไฟล์ .env (ถ้าไม่มีไฟล์ จะใช้ค่าจาก environment ของเครื่องแทน) */
    public static function load(string $file): void
    {
        if (! is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // ตัดเครื่องหมายคำพูดครอบค่า เช่น "abc" หรือ 'abc'
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
                $value = substr($value, 1, -1);
            }

            self::$values[$key] = $value;
        }
    }

    /** คืนค่าตั้งค่า ถ้าไม่มีให้คืนค่า $default */
    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $fromServer = getenv($key);
        return $fromServer !== false ? $fromServer : $default;
    }

    /** คืนค่า true/false จากค่าเช่น 1, true, yes, on */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}
