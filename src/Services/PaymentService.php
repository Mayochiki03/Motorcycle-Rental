<?php
// src/Services/PaymentService.php

require_once dirname(__DIR__) . '/bootstrap.php';

class PaymentService
{
    /**
     * สร้าง payment สำหรับมัดจำ (ยอดตาม deposit_amount ของการจอง ซึ่งตั้งค่าได้ที่ DEPOSIT_AMOUNT ใน .env)
     */
    public static function createPayment(array $data)
    {
        $db = Database::connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            // 1. ตรวจสอบ reservation และดึง deposit_amount (มัดจำ)
            $stmt = $db->prepare(
                "SELECT reservation_id, customer_id, deposit_amount, final_price, status
                 FROM reservations
                 WHERE reservation_id = ?"
            );
            $stmt->execute([$data['reservationId']]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $reservation) {
                throw new Exception("ไม่พบข้อมูลการจอง");
            }

            // 1.1 ตรวจว่าการจองนี้เป็นของลูกค้าคนที่กำลังชำระเงินจริง (กันจ่าย/แนบสลิปแทนการจองของคนอื่น)
            if (isset($data['customerId']) && (string) $reservation['customer_id'] !== (string) $data['customerId']) {
                throw new Exception("ไม่มีสิทธิ์ชำระเงินของการจองนี้");
            }

            // 1.2 ชำระมัดจำได้เฉพาะการจองที่ยังรอชำระ (pending) เท่านั้น
            //     กันการจ่ายซ้ำกับการจองที่ถูกยกเลิก/ยืนยัน/เสร็จสิ้นแล้ว ซึ่งจะทำให้สถานะย้อนกลับเป็น pending
            if ($reservation['status'] !== 'pending') {
                throw new Exception("การจองนี้ไม่สามารถชำระมัดจำได้ (สถานะ: " . $reservation['status'] . ")");
            }

            // 2. ตรวจสอบว่ามีการชำระมัดจำแล้วหรือยัง
            $existingPayment = self::getPaymentByReservation($data['reservationId']);
            if ($existingPayment) {
                throw new Exception("การจองนี้ได้ชำระมัดจำแล้ว");
            }

            // 3. validate payment method (เหลือแค่ PROMPTPAY)
            $allowedMethods = ['PROMPTPAY'];
            if (! in_array($data['paymentMethod'], $allowedMethods)) {
                throw new Exception("ต้องชำระผ่านพร้อมเพย์เท่านั้น");
            }

            // 4. ใช้ deposit_amount (มัดจำ) เป็นยอดชำระ
            $paymentId = gen_id('PAY', 10);
            $amount    = $reservation['deposit_amount'];

            $stmt = $db->prepare("
                INSERT INTO payments
                (payment_id, reservation_id, amount, payment_method, payment_status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([
                $paymentId,
                $reservation['reservation_id'],
                $amount, // ใช้ยอดมัดจำ ไม่ใช่ราคาเต็ม
                $data['paymentMethod'],
            ]);

            // 5. อัปเดตสถานะการจองเป็น 'pending'
            $stmt = $db->prepare("
                UPDATE reservations
                SET status = 'pending'
                WHERE reservation_id = ?
            ");
            $stmt->execute([$data['reservationId']]);

            // 6. return payment
            return self::getPaymentByReservation($data['reservationId']);

        } catch (Exception $e) {
            error_log("PaymentService::createPayment - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ดึง payment ล่าสุดของ reservation
     */
    public static function getPaymentByReservation(string $reservationId)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT
                p.payment_id     AS paymentId,
                p.reservation_id AS reservationId,
                p.amount,
                p.payment_method AS paymentMethod,
                p.payment_status AS paymentStatus,
                p.payment_date   AS paymentDate,
                p.transaction_id AS transactionId,
                p.slip_image_url AS slipImageUrl,
                p.notes,
                p.created_at     AS createdAt
            FROM payments p
            WHERE p.reservation_id = ?
            LIMIT 1
        ");
        $stmt->execute([$reservationId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * ดึง payment ทั้งหมดของ customer
     */
    public static function getCustomerPayments(string $customerId)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT
                p.*,
                r.reservation_id,
                m.brand,
                m.model
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.customer_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$customerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * อัปเดตสถานะ payment
     */
    public static function updatePaymentStatus(string $paymentId, string $status)
    {
        $allowedStatus = ['pending', 'verified', 'rejected', 'completed'];
        if (! in_array($status, $allowedStatus)) {
            throw new Exception("สถานะการชำระเงินไม่ถูกต้อง");
        }

        $db = Database::connect();

        $stmt = $db->prepare("
            UPDATE payments
            SET payment_status = ?,
                payment_date = CASE
                    WHEN ? = 'verified' THEN NOW()
                    ELSE payment_date
                END,
                updated_at = NOW()
            WHERE payment_id = ?
        ");

        $stmt->execute([$status, $status, $paymentId]);

        return self::getPaymentById($paymentId);
    }

    /**
     * ยืนยันการชำระเงิน (admin)
     */
    public static function confirmPayment(string $paymentId, ?string $adminId = null)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            UPDATE payments
            SET payment_status = 'verified',
                payment_date = NOW(),
                updated_at = NOW()
            WHERE payment_id = ?
        ");

        $success = $stmt->execute([$paymentId]);

        if ($success) {
            // อัปเดตสถานะการจองเป็น 'confirmed'
            $stmt = $db->prepare("
                UPDATE reservations r
                JOIN payments p ON r.reservation_id = p.reservation_id
                SET r.status = 'confirmed'
                WHERE p.payment_id = ?
            ");
            $stmt->execute([$paymentId]);
        }

        return $success;
    }

    /**
     * อัพโหลดสลิปการชำระเงิน
     */
    public static function uploadPaymentSlip(string $paymentId, array $file)
    {
        if (! isset($file['tmp_name']) || ! is_uploaded_file($file['tmp_name'])) {
            throw new Exception("ไม่มีไฟล์สลิปที่อัปโหลด");
        }

        // ตรวจไฟล์ + อัปโหลดไป ImgBB (ImageUploader ตรวจชนิดจากเนื้อไฟล์จริงและขนาดไม่เกิน 5MB)
        $imageUrl = ImageUploader::upload($file, 'payment_slip');

        // บันทึก URL ลง DB
        $db   = Database::connect();
        $stmt = $db->prepare("
        UPDATE payments
        SET slip_image_url = ?
        WHERE payment_id = ?
    ");
        $stmt->execute([$imageUrl, $paymentId]);

        return $imageUrl;
    }

    /**
     * อัพโหลดสลิปจากหน้าชำระเงิน (ใช้กับหน้า payment.php)
     */
    public static function uploadSlipFromPayment(string $reservationId, array $file)
    {
        // ดึง paymentId จาก reservationId
        $payment = self::getPaymentByReservation($reservationId);
        if (! $payment) {
            throw new Exception("ไม่พบข้อมูลการชำระเงิน");
        }

        return self::uploadPaymentSlip($payment['paymentId'], $file);
    }

    /**
     * ดึง payment ด้วย id
     */
    public static function getPaymentById(string $paymentId)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT
                p.payment_id,
                p.reservation_id,
                p.amount,
                p.payment_method,
                p.payment_status,
                p.slip_image_url,
                p.created_at,
                r.customer_id,
                r.final_price,
                r.deposit_amount,
                m.brand,
                m.model
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE p.payment_id = ?
            LIMIT 1
        ");
        $stmt->execute([$paymentId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ดึงบัญชีธนาคาร (ถ้าไม่มีตาราง ให้คืนค่า array ว่าง)
     */
    public static function getBankAccounts()
    {
        $db = Database::connect();

        try {
            // ตรวจสอบว่ามีตาราง bank_accounts หรือไม่
            $stmt = $db->query("SHOW TABLES LIKE 'bank_accounts'");
            if ($stmt->rowCount() == 0) {
                return [];
            }

            $stmt = $db->prepare("
                SELECT *
                FROM bank_accounts
                WHERE is_active = 1
                ORDER BY bank_name ASC
            ");
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Bank account table missing: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ตรวจสอบว่าชำระมัดจำแล้วหรือยัง
     */
    public static function isDepositPaid(string $reservationId): bool
    {
        $payment = self::getPaymentByReservation($reservationId);
        if (! $payment) {
            return false;
        }

        return $payment['paymentStatus'] === 'verified' || $payment['paymentStatus'] === 'pending';
    }

    /**
     * ดึงรายการ payment ที่ต้องตรวจสอบ (สำหรับ admin)
     */
    public static function getPendingPayments()
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT
                p.*,
                r.customer_id,
                r.start_datetime,
                r.end_datetime,
                c.first_name,
                c.last_name,
                c.email,
                m.brand,
                m.model
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            JOIN customers c ON r.customer_id = c.customer_id
            JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE p.payment_status = 'pending'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPaymentsForUser(string $customerId): array
    {
        $db = Database::connect();

        try {
            $stmt = $db->prepare("
                SELECT
                    p.payment_id,
                    p.reservation_id,
                    p.amount,
                    p.payment_method,
                    p.payment_status,
                    p.payment_date,
                    p.transaction_id,
                    p.slip_image_url,
                    p.notes,
                    p.created_at,
                    r.reservation_id,
                    r.customer_id,
                    r.total_price,
                    r.final_price,
                    r.status as booking_status
                FROM payments p
                JOIN reservations r ON p.reservation_id = r.reservation_id
                WHERE r.customer_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$customerId]);

            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // แปลงเป็น associative array โดยใช้ reservation_id เป็น key
            $paymentMap = [];
            foreach ($payments as $payment) {
                $paymentMap[$payment['reservation_id']] = $payment;
            }

            return $paymentMap;

        } catch (Exception $e) {
            error_log("PaymentService::getPaymentsForUser - Error: " . $e->getMessage());
            return [];
        }
    }

}
