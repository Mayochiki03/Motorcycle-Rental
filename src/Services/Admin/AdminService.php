<?php
namespace Service\Admin;

require_once dirname(__DIR__, 2) . '/bootstrap.php';

class AdminService
{
    private static function db()
    {
        return \Database::connect();
    }

    /* ===================== DASHBOARD ===================== */
    public static function getDashboardStats(): array
    {
        $db = self::db();

        // คำนวณรายได้เฉพาะ reservation ที่ status = 'completed' เท่านั้น
        $totalRevenueQuery = $db->query("
        SELECT COALESCE(SUM(r.final_price), 0) as total_revenue
        FROM reservations r
        WHERE r.status = 'completed'
    ");

        $revenueResult = $totalRevenueQuery->fetch(\PDO::FETCH_ASSOC);
        $totalRevenue  = (float) ($revenueResult['total_revenue'] ?? 0);

        error_log("Dashboard Revenue - เฉพาะสถานะ completed: ฿" . number_format($totalRevenue, 2));

        return [
            'totalBookings'        => (int) $db
                ->query("SELECT COUNT(*) FROM reservations")
                ->fetchColumn(),

            'pendingBookings'      => (int) $db
                ->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")
                ->fetchColumn(),

            'activeBookings'       => (int) $db
                ->query("
                SELECT COUNT(*)
                FROM reservations
                WHERE status IN ('confirmed', 'active')
            ")
                ->fetchColumn(),

            'totalRevenue'         => $totalRevenue,

            'availableMotorcycles' => (int) $db
                ->query("
                SELECT COUNT(*)
                FROM motorcycles
                WHERE is_available = 1
            ")
                ->fetchColumn(),
        ];
    }

    public static function getRecentReservations(int $limit = 5): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            r.reservation_id,
            r.status,
            r.final_price AS total_price,
            DATE(r.start_datetime) AS start_date,
            DATE(r.end_datetime)   AS end_date,
            m.brand,
            m.model,
            c.first_name,
            c.last_name,
            c.email
        FROM reservations r
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        JOIN customers c ON r.customer_id = c.customer_id
        ORDER BY r.created_at DESC
        LIMIT ?
    ");

        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function autoCancelExpiredUnpaidBookings(): int
    {
        $db = self::db();

        $stmt = $db->prepare("
        UPDATE reservations r
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id
        SET r.status = 'cancelled'
        WHERE r.status = 'pending'
          AND r.created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
          AND (p.payment_status IS NULL OR p.payment_status NOT IN ('paid', 'verified'))
    ");

        $stmt->execute();
        return $stmt->rowCount();
    }

    /* ===================== AUTH ===================== */

    private static function requireOwner(): void
    {
        if (! isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'owner') {
            throw new \Exception('Access denied');
        }
    }

    /* ===================== GET ALL USERS ===================== */

    public static function getAllStaffWithOwners(): array
    {
        self::requireOwner();
        $db = self::db();

        $stmt = $db->query("
            SELECT
                owner_id        AS id,
                email,
                first_name      AS firstName,
                last_name       AS lastName,
                phone,
                'owner'         AS position,
                1               AS isActive,
                created_at
            FROM owners

            UNION ALL

            SELECT
                employee_id     AS id,
                email,
                first_name      AS firstName,
                last_name       AS lastName,
                phone,
                'staff'         AS position,
                is_active       AS isActive,
                created_at
            FROM employees

            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ===================== CREATE ===================== */

    public static function createUser(array $data): void
    {
        self::requireOwner();
        $db = self::db();

        // ตรวจสอบรหัสผ่านและยืนยันรหัสผ่าน
        if (empty($data['password'])) {
            throw new \Exception('กรุณากรอกรหัสผ่าน');
        }

        if (strlen($data['password']) < 8) {
            throw new \Exception('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
        }

        if ($data['password'] !== ($data['confirm_password'] ?? '')) {
            throw new \Exception('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
        }

        if ($data['position'] === 'owner') {
            $stmt = $db->prepare("
            INSERT INTO owners (
                owner_id, email, password_hash,
                first_name, last_name, phone
            ) VALUES (
                UUID(), :email, :password,
                :first_name, :last_name, :phone
            )
        ");

            $stmt->execute([
                ':email'      => $data['email'],
                ':password'   => password_hash($data['password'], PASSWORD_BCRYPT),
                ':first_name' => $data['firstName'],
                ':last_name'  => $data['lastName'],
                ':phone'      => $data['phone'],
            ]);
            return;
        }

        // staff
        $stmt = $db->prepare("
        INSERT INTO employees (
            employee_id, owner_id, email, password_hash,
            first_name, last_name, phone, position, is_active
        ) VALUES (
            UUID(), :owner_id, :email, :password,
            :first_name, :last_name, :phone, 'staff', 1
        )
    ");

        $stmt->execute([
            ':owner_id'   => $_SESSION['user']['id'],
            ':email'      => $data['email'],
            ':password'   => password_hash($data['password'], PASSWORD_BCRYPT),
            ':first_name' => $data['firstName'],
            ':last_name'  => $data['lastName'],
            ':phone'      => $data['phone'],
        ]);
    }

    public static function updateUser(
        string $id,
        string $oldPosition,
        string $newPosition,
        array $data
    ): void {
        self::requireOwner();
        $db = self::db();

        $db->beginTransaction();

        try {
            // ตรวจสอบรหัสผ่าน (ถ้ามีการเปลี่ยน)
            if (! empty($data['password'])) {
                if (strlen($data['password']) < 8) {
                    throw new \Exception('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
                }
                if ($data['password'] !== ($data['confirm_password'] ?? '')) {
                    throw new \Exception('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
                }
            }

            // ================= SAME ROLE =================
            if ($oldPosition === $newPosition) {

                $table = $oldPosition === 'owner' ? 'owners' : 'employees';
                $idCol = $oldPosition === 'owner' ? 'owner_id' : 'employee_id';

                $fields = "
            first_name = :first_name,
            last_name  = :last_name,
            phone      = :phone
        ";

                $params = [
                    ':first_name' => $data['firstName'],
                    ':last_name'  => $data['lastName'],
                    ':phone'      => $data['phone'],
                    ':id'         => $id,
                ];

                if (! empty($data['password'])) {
                    $fields              .= ", password_hash = :password";
                    $params[':password']  = password_hash($data['password'], PASSWORD_BCRYPT);
                }

                $stmt = $db->prepare("
            UPDATE {$table}
            SET {$fields}
            WHERE {$idCol} = :id
        ");
                $stmt->execute($params);

                $db->commit();
                return;
            }

            // ================= OWNER -> STAFF =================
            if ($oldPosition === 'owner' && $newPosition === 'staff') {

                // 1. ดึง owner เดิม
                $stmt = $db->prepare("SELECT * FROM owners WHERE owner_id = ?");
                $stmt->execute([$id]);
                $owner = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (! $owner) {
                    throw new \Exception('ไม่พบ owner');
                }

                // 2. insert employee
                $stmt = $db->prepare("
            INSERT INTO employees (
                employee_id, owner_id, email, password_hash,
                first_name, last_name, phone, position, is_active
            ) VALUES (
                UUID(), :owner_id, :email, :password,
                :first_name, :last_name, :phone, 'staff', 1
            )
        ");

                $stmt->execute([
                    ':owner_id'   => $_SESSION['user']['id'],
                    ':email'      => $owner['email'],
                    ':password'   => ! empty($data['password'])
                        ? password_hash($data['password'], PASSWORD_BCRYPT)
                        : $owner['password_hash'],
                    ':first_name' => $data['firstName'],
                    ':last_name'  => $data['lastName'],
                    ':phone'      => $data['phone'],
                ]);

                // 3. delete owner
                $db->prepare("DELETE FROM owners WHERE owner_id = ?")->execute([$id]);

                $db->commit();
                return;
            }

            // ================= STAFF -> OWNER =================
            if ($oldPosition === 'staff' && $newPosition === 'owner') {

                $stmt = $db->prepare("SELECT * FROM employees WHERE employee_id = ?");
                $stmt->execute([$id]);
                $emp = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (! $emp) {
                    throw new \Exception('ไม่พบพนักงาน');
                }

                $stmt = $db->prepare("
            INSERT INTO owners (
                owner_id, email, password_hash,
                first_name, last_name, phone
            ) VALUES (
                UUID(), :email, :password,
                :first_name, :last_name, :phone
            )
        ");

                $stmt->execute([
                    ':email'      => $emp['email'],
                    ':password'   => ! empty($data['password'])
                        ? password_hash($data['password'], PASSWORD_BCRYPT)
                        : $emp['password_hash'],
                    ':first_name' => $data['firstName'],
                    ':last_name'  => $data['lastName'],
                    ':phone'      => $data['phone'],
                ]);

                $db->prepare("DELETE FROM employees WHERE employee_id = ?")->execute([$id]);

                $db->commit();
                return;
            }

            throw new \Exception('การเปลี่ยนตำแหน่งไม่ถูกต้อง');

        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /* ===================== DELETE ===================== */

    public static function deleteUser(string $id, string $position): void
    {
        self::requireOwner();

        if ($id === ($_SESSION['user']['id'] ?? '')) {
            throw new \Exception('ไม่สามารถลบตัวเองได้');
        }

        $db    = self::db();
        $table = $position === 'owner' ? 'owners' : 'employees';
        $idCol = $position === 'owner' ? 'owner_id' : 'employee_id';

        $stmt = $db->prepare("DELETE FROM {$table} WHERE {$idCol} = ?");
        $stmt->execute([$id]);
    }

    /* ===================== MOTORCYCLES ===================== */

    public static function getAllMotorcycles(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            motorcycle_id      AS motorcycleId,
            brand,
            model,
            year,
            license_plate      AS licensePlate,
            color,
            engine_cc as engineCc,
            price_per_day      AS pricePerDay,
            description,
            is_available       AS isAvailable,
            maintenance_status AS maintenanceStatus,
            image_url          AS imageUrl,
            created_at
        FROM motorcycles
        ORDER BY created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function createMotorcycle(array $data): void
    {
        $db = self::db();

        $stmt = $db->prepare("
            INSERT INTO motorcycles (
                motorcycle_id,
                brand,
                model,
                year,
                license_plate,
                color,
                engine_cc,
                price_per_day,
                description,
                is_available,
                maintenance_status,
                image_url

            ) VALUES (
                :id,
                :brand,
                :model,
                :year,
                :license_plate,
                :color,
                :engine_cc,
                :price_per_day,
                :description,
                :is_available,
                :maintenance_status,
                :image_url
            )
        ");

        $stmt->execute([
            ':id'                 => $data['motorcycle_id'],
            ':brand'              => $data['brand'],
            ':model'              => $data['model'],
            ':year'               => $data['year'],
            ':license_plate'      => $data['license_plate'],
            ':color'              => $data['color'],
            ':engine_cc'          => $data['engine_cc'] ?? 0,
            ':price_per_day'      => $data['price_per_day'],
            ':description'        => $data['description'] ?? null,
            ':is_available'       => ! empty($data['is_available']) ? 1 : 0,
            ':maintenance_status' => strtoupper($data['maintenance_status'] ?? 'READY'),
            ':image_url'          => $data['image_url'] ?? null,
        ]);
    }

    public static function updateMotorcycle(string $id, array $data): void
    {
        $db = self::db();

        $fields = "
        brand = :brand,
        model = :model,
        year = :year,
        license_plate = :license_plate,
        color = :color,
        engine_cc = :engine_cc,
        price_per_day = :price_per_day,
        description = :description,
        is_available = :is_available,
        maintenance_status = :maintenance_status
    ";

        $params = [
            ':id'                 => $id,
            ':brand'              => $data['brand'],
            ':model'              => $data['model'],
            ':year'               => $data['year'],
            ':license_plate'      => $data['license_plate'],
            ':color'              => $data['color'],
            ':engine_cc'          => $data['engine_cc'] ?? 0,
            ':price_per_day'      => $data['price_per_day'],
            ':description'        => $data['description'] ?? null,
            ':is_available'       => 1,
            ':maintenance_status' => strtoupper($data['maintenance_status'] ?? 'READY'),
        ];

        if (isset($data['image_url'])) {
            $fields               .= ", image_url = :image_url";
            $params[':image_url']  = $data['image_url'];
        }

        $stmt = $db->prepare("
        UPDATE motorcycles
        SET {$fields}
        WHERE motorcycle_id = :id
    ");

        $stmt->execute($params);
    }

    public static function deleteMotorcycle(string $id): void
    {
        $db = self::db();

        $stmt = $db->prepare("
            DELETE FROM motorcycles
            WHERE motorcycle_id = ?
        ");

        $stmt->execute([$id]);
    }

    /* ===================== CUSTOMERS ===================== */

    public static function getAllCustomers(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            c.customer_id   AS customerId,
            c.first_name    AS firstName,
            c.last_name     AS lastName,
            c.email,
            c.phone,
            c.line_id       AS line_id,
            c.is_active     AS isActive,
            c.created_at    AS createdAt,
            COUNT(r.reservation_id) AS totalBookings,
            COALESCE(SUM(r.final_price), 0) AS totalSpent
        FROM customers c
        LEFT JOIN reservations r ON r.customer_id = c.customer_id
        GROUP BY c.customer_id, c.first_name, c.last_name, c.email, c.phone, c.line_id, c.is_active, c.created_at
        ORDER BY c.created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ===================== CUSTOMER RESERVATIONS ===================== */

    public static function getCustomerReservations(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            r.reservation_id        AS reservationId,
            DATE(r.start_datetime)  AS startDate,
            DATE(r.end_datetime)    AS endDate,
            r.status,
            r.final_price,
            r.pickup_location,
            r.return_location,
            r.pickup_details,
            r.return_details,
            r.total_days,

            m.brand,
            m.model,
            m.license_plate,
            m.price_per_day,

            p.payment_status,
            p.payment_method,
            p.payment_date

        FROM reservations r
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id

        WHERE r.customer_id = ?
        ORDER BY r.created_at DESC
    ");

        $stmt->execute([$customerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ===================== DISCOUNTS ===================== */

    public static function getAllDiscounts(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            discount_id      AS discountId,
            discount_code    AS discountCode,
            discount_type    AS discountType,
            discount_value   AS discountValue,
            min_rental_days  AS minDays,
            max_discount_amount AS maxDiscount,
            start_date       AS startDate,
            end_date         AS endDate,
            usage_limit      AS usageLimit,
            used_count       AS usedCount,
            is_active        AS isActive,
            created_at
        FROM discounts
        ORDER BY created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function createDiscount(array $data): bool
    {
        self::requireOwner();
        $db = self::db();

        $stmt = $db->prepare("
        INSERT INTO discounts (
            discount_id,
            discount_code,
            discount_type,
            discount_value,
            min_rental_days,
            max_discount_amount,
            start_date,
            end_date,
            usage_limit,
            created_by,
            is_active
        ) VALUES (
            UUID(),
            :code,
            :type,
            :value,
            :min_days,
            :max_discount,
            :start_date,
            :end_date,
            :usage_limit,
            :created_by,
            1
        )
    ");

        return $stmt->execute([
            ':code'         => $data['discountCode'],
            ':type'         => $data['discountType'],
            ':value'        => $data['discountValue'],
            ':min_days'     => $data['minDays'],
            ':max_discount' => $data['maxDiscount'],
            ':start_date'   => $data['startDate'],
            ':end_date'     => $data['endDate'],
            ':usage_limit'  => $data['usageLimit'],
            ':created_by'   => $_SESSION['user']['id'],
        ]);
    }

    public static function updateDiscount(string $discountId, array $data): bool
    {
        self::requireOwner();
        $db = self::db();

        $stmt = $db->prepare("
        UPDATE discounts
        SET
            discount_type = :type,
            discount_value = :value,
            min_rental_days = :min_days,
            max_discount_amount = :max_discount,
            start_date = :start_date,
            end_date = :end_date,
            usage_limit = :usage_limit
        WHERE discount_id = :id
    ");

        return $stmt->execute([
            ':id'           => $discountId,
            ':type'         => $data['discountType'],
            ':value'        => $data['discountValue'],
            ':min_days'     => $data['minDays'],
            ':max_discount' => $data['maxDiscount'],
            ':start_date'   => $data['startDate'],
            ':end_date'     => $data['endDate'],
            ':usage_limit'  => $data['usageLimit'],
        ]);
    }

    public static function deleteDiscount(string $discountId): bool
    {
        self::requireOwner();
        $db = self::db();

        $stmt = $db->prepare("
        DELETE FROM discounts
        WHERE discount_id = ?
    ");

        return $stmt->execute([$discountId]);
    }

    public static function getRecentActivities(int $limit = 10): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT type, description, createdAt
        FROM (
            SELECT
                'booking' AS type,
                CONCAT('การจอง ', r.reservation_id, ' โดย ', c.first_name, ' ', c.last_name) AS description,
                r.created_at AS createdAt
            FROM reservations r
            JOIN customers c ON r.customer_id = c.customer_id

            UNION ALL

            SELECT
                'payment' AS type,
                CONCAT('ชำระเงิน ', p.reservation_id, ' จำนวน ', p.amount, ' บาท') AS description,
                p.payment_date AS createdAt
            FROM payments p
            WHERE p.payment_status = 'paid'
        ) x
        ORDER BY createdAt DESC
        LIMIT ?
    ");

        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getCustomerSummary(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            COUNT(r.reservation_id)                         AS totalBookings,
            COALESCE(SUM(r.final_price), 0)                 AS totalSpent,
            MAX(r.created_at)                               AS lastBookingAt,
            COALESCE(AVG(r.total_days), 0)                  AS avgDays
        FROM reservations r
        LEFT JOIN payments p
            ON p.reservation_id = r.reservation_id
           AND p.payment_status = 'paid'
        WHERE r.customer_id = ?
    ");

        $stmt->execute([$customerId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'totalBookings' => 0,
                'totalSpent'    => 0,
                'lastBookingAt' => null,
                'avgDays'       => 0,
            ];
        }

        return $result;
    }

    public static function getAllReservationsDetailed(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            r.reservation_id        AS reservationId,
            r.customer_id,
            r.motorcycle_id,
            r.status,
            r.final_price,
            r.total_price,
            r.discount_amount,
            r.pickup_location,
            r.pickup_details,
            r.return_location,
            r.return_details,
            r.total_days,
            r.created_at,
            DATE(r.start_datetime)  AS startDate,
            DATE(r.end_datetime)    AS endDate,

            CONCAT(c.first_name, ' ', c.last_name) AS customerName,
            c.email   AS customerEmail,
            c.phone   AS customerPhone,

            m.brand,
            m.model,
            m.year,
            m.color,
            m.license_plate,
            m.price_per_day,

            p.payment_status,
            p.payment_method,
            p.payment_date,
            p.amount

        FROM reservations r
        JOIN customers c   ON r.customer_id = c.customer_id
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id
        ORDER BY r.created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getBookingDetail(string $reservationId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            r.reservation_id AS reservationId,
            r.customer_id,
            r.motorcycle_id,
            r.status,
            r.final_price,
            r.total_price,
            r.discount_amount,
            r.deposit_amount,
            r.total_days,
            r.pickup_location,
            r.pickup_details,
            r.return_location,
            r.return_details,
            r.return_condition,
            r.return_motorcycle_status,
            r.return_checked_at,
            r.return_checked_by,
            r.updated_at as updatedAt,
            DATE(r.start_datetime) AS startDate,
            DATE(r.end_datetime)   AS endDate,
            r.created_at,

            c.first_name AS firstName,
            c.last_name  AS lastName,
            c.phone      AS phone,
            c.email      AS email,

            m.brand,
            m.model,
            m.color,
            m.year,
            m.license_plate,
            m.price_per_day,
            m.engine_cc,

            p.payment_status,
            p.payment_method,
            p.payment_date,
            p.amount,
            p.transaction_id,
            p.slip_image_url,
            p.notes

        FROM reservations r
        JOIN customers c   ON r.customer_id = c.customer_id
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id
        WHERE r.reservation_id = ?
        LIMIT 1
    ");

        $stmt->execute([$reservationId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'reservationId'            => $reservationId,

                'return_condition'         => '',
                'return_motorcycle_status' => '',
                'return_checked_at'        => null,
                'return_checked_by'        => null,
                'updatedAt'                => null,

            ];
        }

        return $result;
    }

    public static function verifyPayment(string $reservationId): bool
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            // 1. อัปเดต payment status เป็น 'verified'
            $stmt = $db->prepare("
            UPDATE payments
            SET payment_status = 'verified',
                payment_date = NOW()
            WHERE reservation_id = ?
        ");
            $stmt->execute([$reservationId]);

            // 2. อัปเดต reservation status เป็น 'confirmed'
            $stmt = $db->prepare("
            UPDATE reservations
            SET status = 'confirmed'
            WHERE reservation_id = ?
        ");
            $stmt->execute([$reservationId]);

            $db->commit();
            return true;

        } catch (\Exception $e) { // แก้จาก Exception เป็น \Exception
            $db->rollBack();
            error_log("Verify payment error: " . $e->getMessage());
            return false;
        }
    }

    public static function rejectPayment(string $reservationId, string $reason = ''): bool
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            // 1. อัปเดต payment status เป็น 'rejected'
            $stmt = $db->prepare("
            UPDATE payments
            SET payment_status = 'rejected',
                notes = ?
            WHERE reservation_id = ?
        ");
            $stmt->execute([$reason, $reservationId]);

            // 2. อัปเดต reservation status เป็น 'cancelled'
            $stmt = $db->prepare("
            UPDATE reservations
            SET status = 'cancelled'
            WHERE reservation_id = ?
        ");
            $stmt->execute([$reservationId]);

            $db->commit();
            return true;

        } catch (\Exception $e) { // แก้จาก Exception เป็น \Exception
            $db->rollBack();
            error_log("Reject payment error: " . $e->getMessage());
            return false;
        }
    }

    /* ===================== COMPLETE BOOKING WITH CONDITION ===================== */
    public static function completeBooking(string $reservationId, string $returnCondition = '', string $motorcycleStatus = 'READY'): bool
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            // แปลงสถานะเป็นข้อความภาษาไทย
            $statusText = [
                'READY'       => 'สภาพปกติ',
                'MAINTENANCE' => 'มีรอย/เสียหายเล็กน้อย', // เพิ่มตรงนี้
                'DAMAGED'     => 'เสียหายรุนแรง',
                'LOST'        => 'สูญหาย/ไม่คืนรถ',
            ];

            $statusLabel = $statusText[$motorcycleStatus] ?? $motorcycleStatus;

            // อัปเดต reservations
            $stmt = $db->prepare("
            UPDATE reservations
            SET
                status = 'completed',
                return_details = CONCAT(
                    IFNULL(return_details, ''),
                    ?,
                    '\n[คืนรถ] สภาพ: ', ?,
                    '\nรายละเอียด: ', ?
                ),
                return_condition = ?,
                return_motorcycle_status = ?,
                return_checked_at = NOW(),
                return_checked_by = ?
            WHERE reservation_id = ?
            AND status IN ('confirmed', 'active', 'pending')
        ");

            $employeeId = $_SESSION['user']['id'] ?? null;

            $stmt->execute([
                $returnCondition ? "\n\n--- คืนรถ " . date('d/m/Y H:i') . " ---" : '',
                $statusLabel,
                $returnCondition ?: 'ไม่มีรายละเอียดเพิ่มเติม',
                $returnCondition,
                $motorcycleStatus,
                $employeeId,
                $reservationId,
            ]);

            // อัปเดตสถานะมอเตอร์ไซค์
            if ($motorcycleStatus === 'READY') {
                // สภาพดี → พร้อมใช้งาน
                $stmt = $db->prepare("
                UPDATE motorcycles m
                JOIN reservations r ON m.motorcycle_id = r.motorcycle_id
                SET
                    m.is_available = 1,
                    m.maintenance_status = 'READY'
                WHERE r.reservation_id = ?
            ");
                $stmt->execute([$reservationId]);
            } else {
                // มีปัญหา → ไม่พร้อมใช้งาน
                $stmt = $db->prepare("
                UPDATE motorcycles m
                JOIN reservations r ON m.motorcycle_id = r.motorcycle_id
                SET
                    m.is_available = 0,
                    m.maintenance_status = ?
                WHERE r.reservation_id = ?
            ");

                // Map สถานะให้ตรงกับตาราง motorcycles
                $maintenanceStatus = match ($motorcycleStatus) {
                    'MAINTENANCE' => 'MAINTENANCE', // ซ่อมบำรุง
                    'DAMAGED'     => 'DAMAGED',     // เสียหายรุนแรง
                    'LOST'        => 'LOST',        // สูญหาย
                    default       => 'MAINTENANCE',
                };

                $stmt->execute([$maintenanceStatus, $reservationId]);
            }

            $db->commit();
            return true;

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Complete booking error: " . $e->getMessage());
            return false;
        }
    }

    public static function getEnhancedRevenueReport(string $period = 'monthly'): array
    {
        $db = self::db();

        $groupBy      = '';
        $selectFormat = '';

        switch ($period) {
            case 'daily':
                $selectFormat = 'DATE(r.updated_at)';
                $groupBy      = "DATE(r.updated_at)";
                break;
            case 'monthly':
                $selectFormat = 'DATE_FORMAT(r.updated_at, \'%Y-%m\')';
                $groupBy      = "DATE_FORMAT(r.updated_at, '%Y-%m')";
                break;
            case 'yearly':
                $selectFormat = 'YEAR(r.updated_at)';
                $groupBy      = "YEAR(r.updated_at)";
                break;
            default:
                $selectFormat = 'DATE_FORMAT(r.updated_at, \'%Y-%m\')';
                $groupBy      = "DATE_FORMAT(r.updated_at, '%Y-%m')";
        }

        $sql = "
        SELECT
            {$selectFormat} as period,
            {$selectFormat} as label,
            COALESCE(SUM(r.final_price), 0) as revenue,
            COUNT(DISTINCT r.reservation_id) as bookingCount,
            COALESCE(AVG(r.final_price), 0) as avgBookingValue
        FROM reservations r
        WHERE r.status = 'completed'
        AND r.updated_at IS NOT NULL
        GROUP BY {$groupBy}
        ORDER BY {$groupBy} ASC
    ";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

/* ===================== GET ALL MOTORCYCLES WITH IMAGES ===================== */
    public static function getAllMotorcyclesWithImages(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            motorcycle_id AS motorcycleId,
            brand,
            model,
            year,
            license_plate AS licensePlate,
            color,
            engine_cc AS engineCc,
            price_per_day AS pricePerDay,
            image_url AS imageUrl,
            is_available AS isAvailable,
            created_at AS createdAt
        FROM motorcycles
        ORDER BY created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getTopMotorcyclesEnhanced(int $limit = 5, string $period = 'all_time'): array
    {
        $db = self::db();

        $dateCondition = '';
        switch ($period) {
            case 'daily':
                $dateCondition = "AND DATE(r.updated_at) = CURDATE()";
                break;
            case 'monthly':
                $dateCondition = "AND YEAR(r.updated_at) = YEAR(CURDATE())
                  AND MONTH(r.updated_at) = MONTH(CURDATE())";
                break;
            case 'yearly':
                $dateCondition = "AND YEAR(r.updated_at) = YEAR(CURDATE())";
                break;
            case 'all_time':
            default:
                $dateCondition = "";
                break;
        }

        $sql = "
        SELECT
            m.motorcycle_id AS motorcycleId,
            m.brand,
            m.model,
            m.year,
            m.license_plate AS licensePlate,
            m.color,
            m.engine_cc AS engineCc,
            m.price_per_day AS pricePerDay,
            m.image_url AS imageUrl,
            m.is_available AS isAvailable,

            -- นับจำนวนการจองที่เสร็จสิ้นแล้วของรถคันนี้
            COALESCE((
                SELECT COUNT(*)
                FROM reservations r
                WHERE r.motorcycle_id = m.motorcycle_id
                AND r.status = 'completed'
                AND r.updated_at IS NOT NULL
                {$dateCondition}
            ), 0) AS bookingCount,

            -- คำนวณรายได้จากการจองที่เสร็จสิ้นแล้วของรถคันนี้
            COALESCE((
                SELECT SUM(r.final_price)
                FROM reservations r
                WHERE r.motorcycle_id = m.motorcycle_id
                AND r.status = 'completed'
                AND r.updated_at IS NOT NULL
                {$dateCondition}
            ), 0) AS totalRevenue

        FROM motorcycles m
        ORDER BY bookingCount DESC, totalRevenue DESC
        LIMIT ?
    ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getCustomerReservationStats(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            COUNT(CASE WHEN r.status IN ('completed', 'confirmed', 'active') THEN 1 END) AS successful_bookings,
            COUNT(CASE WHEN r.status IN ('cancelled', 'rejected') THEN 1 END) AS failed_bookings,
            COUNT(CASE WHEN r.status = 'pending' THEN 1 END) AS pending_bookings
        FROM reservations r
        WHERE r.customer_id = ?
    ");

        $stmt->execute([$customerId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'successful_bookings' => 0,
                'failed_bookings'     => 0,
                'pending_bookings'    => 0,
            ];
        }

        return $result;
    }

// แก้ไข method getCustomerProfile ให้รวมข้อมูลเพิ่มเติม
    public static function getCustomerProfile(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            c.customer_id   AS customerId,
            c.first_name    AS firstName,
            c.last_name     AS lastName,
            c.email,
            c.phone,
            c.line_id       AS lineId,
            c.is_active     AS isActive,
            c.created_at    AS createdAt,
            COUNT(r.reservation_id) AS totalBookings,
            COALESCE(SUM(r.final_price), 0) AS totalSpent,
            MAX(r.created_at) AS lastBookingAt
        FROM customers c
        LEFT JOIN reservations r ON r.customer_id = c.customer_id
        WHERE c.customer_id = ?
        GROUP BY c.customer_id, c.first_name, c.last_name, c.email, c.phone, c.line_id, c.is_active, c.created_at
        LIMIT 1
    ");

        $stmt->execute([$customerId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'customerId'    => $customerId,
                'firstName'     => '',
                'lastName'      => '',
                'email'         => '',
                'phone'         => '',
                'lineId'        => '',
                'isActive'      => 1,
                'createdAt'     => '',
                'totalBookings' => 0,
                'totalSpent'    => 0,
                'lastBookingAt' => null,
            ];
        }

        // ดึงสถิติการจองเพิ่มเติม
        $stats = self::getCustomerReservationStats($customerId);

        return array_merge($result, $stats);
    }

}
