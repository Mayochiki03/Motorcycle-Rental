<?php
// src/Database.php
// เชื่อมต่อฐานข้อมูล MySQL/MariaDB ด้วย PDO (ใช้ connection เดียวต่อหนึ่ง request)
// ค่าเชื่อมต่ออ่านจากไฟล์ .env (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET)

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $host    = Env::get('DB_HOST', 'localhost');
        $port    = Env::get('DB_PORT', '3306');
        $name    = Env::get('DB_NAME', 'motorcycle_rental_new');
        $user    = Env::get('DB_USER', 'root');
        $pass    = Env::get('DB_PASS', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        $dsn     = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            return self::$pdo;
        } catch (PDOException $e) {
            // บันทึกสาเหตุลง log แต่ไม่แสดงรายละเอียด (อาจมีรหัสผ่าน) ให้ผู้ใช้เห็น
            error_log('DB Connection error: ' . $e->getMessage());
            throw $e;
        }
    }
}
