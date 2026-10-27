<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/DiscountService.php';

class BookingService
{
    private static function db()
    {
        return Database::connect();
    }

    /**
     * สร้างการจองใหม่
     */
    public static function createBooking(array $data)
    {
        $db = self::db();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $db->beginTransaction();

            // กันจองซ้อน: ล็อกแถวของรถคันนี้ไว้ แล้วตรวจช่วงเวลาซ้ำอีกครั้งในธุรกรรมเดียวกัน
            // (คำขอที่เข้ามาพร้อมกันจะรอคิวที่ล็อกนี้ จึงไม่มีสองคนจองคันเดียวกันในช่วงเดียวกันได้)
            $lock = $db->prepare('SELECT motorcycle_id FROM motorcycles WHERE motorcycle_id = ? FOR UPDATE');
            $lock->execute([$data['motorcycleId']]);
            if (! $lock->fetchColumn()) {
                throw new \Exception('ไม่พบรถที่เลือก');
            }

            // ช่วงเวลาเทียบแบบเดียวกับ MotorcycleService::getAvailableMotorcycles (ทั้งวันของวันเริ่มถึงวันคืน)
            $overlap = $db->prepare("
                SELECT COUNT(*) FROM reservations
                WHERE motorcycle_id = ?
                  AND status IN ('pending','confirmed','active')
                  AND NOT (end_datetime < ? OR start_datetime > ?)
            ");
            $overlap->execute([
                $data['motorcycleId'],
                substr($data['startDate'], 0, 10) . ' 00:00:00',
                substr($data['endDate'], 0, 10) . ' 23:59:59',
            ]);
            if ((int) $overlap->fetchColumn() > 0) {
                throw new \Exception('รถคันนี้ไม่ว่างในช่วงวันที่ที่เลือก กรุณาเลือกวันที่อื่น');
            }

            $reservationId = gen_id('RES', 10);
            $depositAmount = $data['depositAmount'] ?? DEPOSIT_AMOUNT; // ค่ามัดจำ ตั้งที่ DEPOSIT_AMOUNT ใน .env
            $shopDiscount  = self::calculateShopDiscount($data['totalDays']);

            // ส่วนลดจากโค้ด: คำนวณฝั่งเซิร์ฟเวอร์เสมอ ห้ามเชื่อยอดที่ส่งมาจากหน้าเว็บ
            // (ไม่งั้นลูกค้าแก้ค่า discount_amount ในฟอร์มแล้วจองฟรีได้)
            $promoDiscount = 0;
            $discountInfo  = null;
            if (! empty($data['discountCode'])) {
                $discountInfo = DiscountService::validateDiscount(
                    $data['discountCode'],
                    $data['totalDays'],
                    $data['totalPrice']
                );

                if (! $discountInfo['valid']) {
                    throw new \Exception($discountInfo['message']);
                }
                $promoDiscount = (float) $discountInfo['discountAmount'];
            }

            $totalDiscount = $shopDiscount + $promoDiscount;
            $finalPrice    = max($data['totalPrice'] - $totalDiscount, 0);

            // มัดจำต้องไม่เกินราคาที่ต้องจ่ายจริง (กรณีเช่าสั้นและรถราคาถูก)
            $depositAmount = min((float) $depositAmount, (float) $finalPrice);

            $stmt = $db->prepare("
                INSERT INTO reservations (
                    reservation_id,
                    customer_id,
                    motorcycle_id,
                    start_datetime,
                    end_datetime,
                    total_days,
                    total_price,
                    status,
                    deposit_amount,
                    discount_amount,
                    final_price,
                    pickup_location,
                    return_location,
                    pickup_details,
                    return_details,
                    created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW()
                )
            ");

            $stmt->execute([
                $reservationId,
                $data['customerId'],
                $data['motorcycleId'],
                $data['startDate'],
                $data['endDate'],
                $data['totalDays'],
                $data['totalPrice'],
                $depositAmount,
                $totalDiscount,
                $finalPrice,
                $data['pickupLocation'],
                $data['returnLocation'],
                $data['pickupDetails'] ?? null,
                $data['returnDetails'] ?? null,
            ]);

            // บันทึกการใช้โค้ดส่วนลด (ยอดที่บันทึกคือยอดที่เซิร์ฟเวอร์คำนวณเอง)
            if ($discountInfo && $promoDiscount > 0) {
                DiscountService::recordDiscountUsage(
                    $reservationId,
                    $discountInfo['discountId'],
                    $promoDiscount
                );
            }

            $db->commit();
            return $reservationId;

        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('BookingService::createBooking ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ดึงการจองทั้งหมดของลูกค้า
     */
    public static function getCustomerBookings($customerId)
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT
                r.reservation_id AS reservationId,
                r.customer_id    AS customerId,
                r.motorcycle_id  AS motorcycleId,
                r.start_datetime AS startDate,
                r.end_datetime   AS endDate,
                r.total_days     AS totalDays,
                r.total_price    AS totalPrice,
                r.deposit_amount AS depositAmount,
                r.discount_amount AS discountAmount,
                r.final_price    AS finalPrice,
                r.pickup_location AS pickupLocation,
                r.return_location AS returnLocation,
                r.status,
                r.created_at     AS createdAt,
                r.updated_at     AS updatedAt,

                m.brand,
                m.model,
                m.price_per_day  AS pricePerDay,
                m.engine_cc      AS engineCc,
                m.image_url      AS imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.customer_id = ?
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ดึงข้อมูลการจองตาม reservationId
     */
    public static function getBookingById($reservationId)
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT
                r.reservation_id AS reservationId,
                r.customer_id    AS customerId,
                r.motorcycle_id  AS motorcycleId,
                r.start_datetime AS startDate,
                r.end_datetime   AS endDate,
                r.total_days     AS totalDays,
                r.total_price    AS totalPrice,
                r.deposit_amount AS depositAmount,
                r.discount_amount AS discountAmount,
                r.final_price    AS finalPrice,
                r.pickup_location AS pickupLocation,
                r.return_location AS returnLocation,
                r.status,
                r.created_at     AS createdAt,
                r.updated_at     AS updatedAt,

                m.brand,
                m.model,
                m.price_per_day  AS pricePerDay,
                m.engine_cc      AS engineCc,
                m.image_url      AS imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.reservation_id = ?
            LIMIT 1
        ");

        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ดึงการจองล่าสุดของลูกค้า
     */
    public static function getLatestCustomerBooking($customerId)
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT
                r.reservation_id AS reservationId,
                r.start_datetime AS startDate,
                r.end_datetime   AS endDate,
                r.total_days     AS totalDays,
                r.final_price    AS finalPrice,
                r.status,
                r.created_at     AS createdAt,

                m.brand,
                m.model,
                m.price_per_day  AS pricePerDay,
                m.engine_cc      AS engineCc,
                m.image_url      AS imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.customer_id = ?
            ORDER BY r.created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * อัปเดตสถานะการจอง
     */
    public static function updateBookingStatus($reservationId, $status)
    {
        $db   = self::db();
        $stmt = $db->prepare("
            UPDATE reservations
            SET status = ?
            WHERE reservation_id = ?
        ");
        return $stmt->execute([$status, $reservationId]);
    }

    /**
     * ยกเลิกการจอง
     */
    public static function cancelBooking($reservationId)
    {
        return self::updateBookingStatus($reservationId, 'cancelled');
    }

    /**
     * ใช้เช็คช่วงวันที่ (หน้าโชว์รถ)
     */
    public static function getBookingsByDateRange(string $startDate, string $endDate): array
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT
                reservation_id,
                motorcycle_id AS motorcycleId,
                status
            FROM reservations
            WHERE NOT (
                end_datetime < :start
                OR start_datetime > :end
            )
        ");

        $stmt->execute([
            ':start' => $startDate . ' 00:00:00',
            ':end'   => $endDate . ' 23:59:59',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private static function calculateShopDiscount(int $totalDays): float
    {
        $group = intdiv($totalDays, 3); // ทุก 3 วัน
        return $group * 50;
    }

}
