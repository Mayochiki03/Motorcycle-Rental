<?php
require_once dirname(__DIR__) . '/bootstrap.php';

class MotorcycleService
{
    private static function db()
    {
        return Database::connect();
    }

    // ดึงรถทั้งหมด (เฉพาะที่พร้อมใช้งาน)
    public static function getAllMotorcycles(): array
    {
        $db = self::db();

        $stmt = $db->query("
            SELECT *
            FROM motorcycles
            WHERE is_available = 1
              AND maintenance_status = 'ready'
            ORDER BY created_at DESC
        ");

        return self::mapFields($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // ดึงรถตาม ID
    public static function getMotorcycleById(string $id): ?array
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT *
            FROM motorcycles
            WHERE motorcycle_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);

        $bike = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $bike) {
            return null;
        }

        return self::mapFields([$bike])[0];
    }

    // ดึงรถที่ว่างตามช่วงวันที่
    public static function getAvailableMotorcycles(string $startDate, string $endDate): array
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT *
            FROM motorcycles m
            WHERE m.is_available = 1
              AND m.maintenance_status = 'ready'
              AND m.motorcycle_id NOT IN (
                    SELECT r.motorcycle_id
                    FROM reservations r
                    WHERE r.status IN ('pending','confirmed','active')
                      AND NOT (
                          r.end_datetime < :start
                          OR r.start_datetime > :end
                      )
              )
            ORDER BY m.created_at DESC
        ");

        $stmt->execute([
            ':start' => $startDate . ' 00:00:00',
            ':end'   => $endDate . ' 23:59:59',
        ]);

        return self::mapFields($stmt->fetchAll(PDO::FETCH_ASSOC), true);
    }

    // Search/filter (frontend ใช้ตัวนี้)
    public static function searchMotorcycles(array $filters = [], ?string $startDate = null, ?string $endDate = null): array
    {
        if ($startDate && $endDate) {
            $bikes = self::getAvailableMotorcycles($startDate, $endDate);
        } else {
            $bikes = self::getAllMotorcycles();
        }

        return array_values(array_filter($bikes, function ($bike) use ($filters) {

            if (! empty($filters['brand']) && strcasecmp($bike['brand'], $filters['brand']) !== 0) {
                return false;
            }

            if (! empty($filters['model'])) {
                $keyword = strtolower($filters['model']);
                $brand = strtolower($bike['brand'] ?? '');
                $model = strtolower($bike['model'] ?? '');

                if (strpos($brand . ' ' . $model, $keyword) === false) {
                    return false;
                }
            }

            if (isset($filters['minPrice']) && $bike['pricePerDay'] < $filters['minPrice']) {
                return false;
            }

            if (isset($filters['maxPrice']) && $bike['pricePerDay'] > $filters['maxPrice']) {
                return false;
            }

            if (! empty($filters['type'])) {
                $cc = (int) ($bike['engineCc'] ?? 0);
                if ($filters['type'] === 'small' && $cc > 150) {
                    return false;
                }

                if ($filters['type'] === 'medium' && ($cc <= 150 || $cc > 300)) {
                    return false;
                }

                if ($filters['type'] === 'large' && $cc <= 300) {
                    return false;
                }
            }

            return true;
        }));
    }

    // map field → ให้ frontend ใช้ต่อได้ทันที
    private static function mapFields(array $bikes, ?bool $isAvailableDefault = null): array
    {
        foreach ($bikes as &$bike) {
            $bike = [
                'motorcycleId'      => $bike['motorcycle_id'],
                'brand'             => $bike['brand'],
                'model'             => $bike['model'],
                'year'              => $bike['year'],
                'licensePlate'      => $bike['license_plate'],
                'color'             => $bike['color'],
                'engineCc'          => (int) $bike['engine_cc'],
                'pricePerDay'       => (float) $bike['price_per_day'],
                'imageUrl'          => $bike['image_url'],
                'description'       => $bike['description'],
                'maintenanceStatus' => $bike['maintenance_status'],
                'isAvailable'       => $isAvailableDefault ?? (bool) $bike['is_available'],
            ];
        }

        return $bikes;
    }
}