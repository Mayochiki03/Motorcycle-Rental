<?php
// src/Services/BookingTrackingService.php

require_once dirname(__DIR__) . '/bootstrap.php';

class BookingTrackingService
{
    /**
     * ดึงข้อมูลการจองพร้อมสถานะสำหรับลูกค้า
     */
    public static function getCustomerBookingWithStatus(string $reservationId, string $customerId): ?array
    {
        $db = Database::connect();

        try {
            $stmt = $db->prepare("
                SELECT
                    r.reservation_id AS reservationId,
                    r.customer_id AS customerId,
                    r.motorcycle_id AS motorcycleId,
                    r.start_datetime AS startDatetime,
                    r.end_datetime AS endDatetime,
                    r.total_days AS totalDays,
                    r.total_price AS totalPrice,
                    r.deposit_amount AS depositAmount,
                    r.discount_amount AS discountAmount,
                    r.final_price AS finalPrice,
                    r.pickup_location AS pickupLocation,
                    r.return_location AS returnLocation,
                    r.pickup_details AS pickupDetails,
                    r.return_details AS returnDetails,
                    r.status,
                    r.created_at AS createdAt,
                    r.updated_at AS updatedAt,
                    m.brand AS motorcycleBrand,
                    m.model AS motorcycleModel,
                    m.engine_cc AS motorcycleEngineCc,
                    m.license_plate AS licensePlate,
                    m.color AS motorcycleColor,
                    m.image_url AS motorcycleImageUrl,
                    m.price_per_day AS pricePerDay,
                    c.first_name AS customerFirstName,
                    c.last_name AS customerLastName,
                    c.email AS customerEmail,
                    c.phone AS customerPhone
                FROM reservations r
                JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
                JOIN customers c ON r.customer_id = c.customer_id
                WHERE r.reservation_id = ? AND r.customer_id = ?
            ");

            $stmt->execute([$reservationId, $customerId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $booking) {
                return null;
            }

            // ดึงข้อมูลการชำระเงิน
            require_once __DIR__ . '/PaymentService.php';
            $payment = PaymentService::getPaymentByReservation($reservationId);

            if ($payment) {
                $booking['payment']       = $payment;
                $booking['hasPayment']    = true;
                $booking['paymentStatus'] = $payment['paymentStatus'] ?? 'pending';
            } else {
                $booking['hasPayment']    = false;
                $booking['paymentStatus'] = 'pending';
            }

            // คำนวณเวลาสำหรับยกเลิก / แก้ไข
            $startTime = new DateTime($booking['startDatetime']);
            $now       = new DateTime();
            $hoursDiff = ($startTime->getTimestamp() - $now->getTimestamp()) / 3600;
            $daysDiff  = floor($hoursDiff / 24);

            // แก้ไขเงื่อนไข: ยกเลิกและแก้ไขได้ถ้าเวลายังเหลือ >= 24 ชม.
            // และสถานะไม่อยู่ในสถานะที่ยกเลิก/เสร็จสิ้นแล้ว
            $booking['canCancel']     = false;
            $booking['canEdit']       = false;
            $booking['canPayDeposit'] = false;

            // เช็คว่าสถานะที่อนุญาตให้ยกเลิก/แก้ไขได้ (ยกเว้นสถานะที่เสร็จสิ้นหรือยกเลิกไปแล้ว)
            $allowedStatuses = ['pending', 'confirmed', 'in_progress'];

            if (in_array($booking['status'], $allowedStatuses) && $hoursDiff >= 24) {
                $booking['canCancel']      = true;
                $booking['canEdit']        = true;
                $booking['cancelDeadline'] = "สามารถยกเลิกได้ก่อนวันที่: " . date('d/m/Y H:i', $startTime->getTimestamp() - (24 * 3600));
            }

            // สามารถชำระมัดจำได้เมื่อ:
            // 1. ยังไม่มีการชำระเงิน และ
            // 2. สถานะเป็น pending หรือ confirmed และ
            // 3. ยังเหลือเวลา >= 24 ชม.
            if (! $booking['hasPayment'] && in_array($booking['status'], ['pending', 'confirmed']) && $hoursDiff >= 24) {
                $booking['canPayDeposit'] = true;
            }

            $booking['hoursUntilPickup'] = $hoursDiff;
            $booking['daysUntilPickup']  = $daysDiff;

            return $booking;

        } catch (Exception $e) {
            error_log("BookingTrackingService::getCustomerBookingWithStatus - Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ยกเลิกการจองโดยลูกค้า
     */
    public static function cancelBookingByCustomer(string $reservationId, string $customerId, string $reason): array
    {
        $db = Database::connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            // ตรวจสอบสิทธิ์
            $stmt = $db->prepare("
                SELECT status, start_datetime
                FROM reservations
                WHERE reservation_id = ? AND customer_id = ?
            ");
            $stmt->execute([$reservationId, $customerId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $booking) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลการจองหรือคุณไม่มีสิทธิ์ยกเลิก',
                ];
            }

            // แก้ไข: ตรวจสอบเวลาที่ยกเลิกได้ (>= 24 ชม.)
            $startTime = new DateTime($booking['start_datetime']);
            $now       = new DateTime();
            $hoursDiff = ($startTime->getTimestamp() - $now->getTimestamp()) / 3600;

            if ($hoursDiff < 24) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถยกเลิกการจองได้ เนื่องจากเหลือเวลาไม่ถึง 24 ชั่วโมง',
                ];
            }

            // แก้ไข: อนุญาตให้ยกเลิกได้หลายสถานะ (pending, confirmed, in_progress)
            $allowedStatuses = ['pending', 'confirmed', 'in_progress'];
            if (! in_array($booking['status'], $allowedStatuses)) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถยกเลิกการจองในสถานะนี้ได้',
                ];
            }

            $db->beginTransaction();

            // อัปเดตสถานะการจอง
            $stmt = $db->prepare("
                UPDATE reservations
                SET status = 'cancelled',
                    updated_at = NOW()
                WHERE reservation_id = ? AND customer_id = ?
            ");
            $stmt->execute([$reservationId, $customerId]);

            // ถ้ามี payment → อัปเดต payment
            require_once __DIR__ . '/PaymentService.php';
            $payment = PaymentService::getPaymentByReservation($reservationId);

            if ($payment) {
                $cancellationNote =
                "ยกเลิกโดยลูกค้า: " . date('Y-m-d H:i:s') .
                    "\nเหตุผล: " . $reason;

                $stmt = $db->prepare("
                    UPDATE payments
                    SET payment_status = 'refund_pending',
                        notes = CONCAT(COALESCE(notes, ''), '\n', ?)
                    WHERE reservation_id = ?
                ");
                $stmt->execute([$cancellationNote, $reservationId]);
            }

            $db->commit();

            return [
                'success' => true,
                'message' => 'ยกเลิกการจองสำเร็จ',
            ];

        } catch (Exception $e) {
            $db->rollBack();
            error_log("BookingTrackingService::cancelBookingByCustomer - Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการยกเลิก',
            ];
        }
    }

    /**
     * อัปเดตรายละเอียดการจองโดยลูกค้า (ไม่รวมวันเวลา)
     */
    public static function updateBookingDetailsByCustomer(
        string $reservationId,
        string $customerId,
        array $data
    ): array {
        $db = Database::connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $stmt = $db->prepare("
                SELECT status, start_datetime
                FROM reservations
                WHERE reservation_id = ? AND customer_id = ?
            ");
            $stmt->execute([$reservationId, $customerId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $booking) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลการจองหรือคุณไม่มีสิทธิ์แก้ไข',
                ];
            }

            // แก้ไข: อนุญาตให้แก้ไขได้หลายสถานะ (pending, confirmed, in_progress)
            $allowedStatuses = ['pending', 'confirmed', 'in_progress'];
            if (! in_array($booking['status'], $allowedStatuses)) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถแก้ไขการจองในสถานะนี้ได้',
                ];
            }

            $startTime = new DateTime($booking['start_datetime']);
            $now       = new DateTime();
            $hoursDiff = ($startTime->getTimestamp() - $now->getTimestamp()) / 3600;

            if ($hoursDiff < 24) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถแก้ไขการจองได้ เนื่องจากเหลือเวลาไม่ถึง 24 ชั่วโมง',
                ];
            }

            if (empty($data['pickup_location']) || empty($data['return_location'])) {
                return [
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
                ];
            }

            // ถ้าเลือกสถานที่เป็น "ร้านเทมป์เทชัน" ให้ลบ pickup_details ออก
            $pickupDetails = $data['pickup_details'] ?? null;
            if ($data['pickup_location'] === 'ร้านเทมป์เทชัน') {
                $pickupDetails = null;
            }

            // ถ้าเลือกสถานที่คืนเป็น "ร้านเทมป์เทชัน" ให้ลบ return_details ออก
            $returnDetails = $data['return_details'] ?? null;
            if ($data['return_location'] === 'ร้านเทมป์เทชัน') {
                $returnDetails = null;
            }

            $stmt = $db->prepare("
                UPDATE reservations
                SET pickup_location = ?,
                    return_location = ?,
                    pickup_details = ?,
                    return_details = ?,
                    updated_at = NOW()
                WHERE reservation_id = ? AND customer_id = ?
            ");

            $stmt->execute([
                $data['pickup_location'],
                $data['return_location'],
                $pickupDetails,
                $returnDetails,
                $reservationId,
                $customerId,
            ]);

            return [
                'success' => true,
                'message' => 'อัปเดตรายละเอียดการจองสำเร็จ',
            ];

        } catch (Exception $e) {
            error_log("BookingTrackingService::updateBookingDetailsByCustomer - Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดต',
            ];
        }
    }

    /**
     * ดึงประวัติสถานะการจอง
     */
    public static function getBookingStatusHistory(string $reservationId): array
    {
        $db = Database::connect();

        try {
            $stmt = $db->prepare("SHOW TABLES LIKE 'reservation_status_history'");
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("
                    SELECT status, changed_at, changed_by, notes
                    FROM reservation_status_history
                    WHERE reservation_id = ?
                    ORDER BY changed_at DESC
                ");
                $stmt->execute([$reservationId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $history = [];

            $stmt = $db->prepare("
                SELECT
                    status,
                    updated_at AS changed_at,
                    'system' AS changed_by,
                    'อัปเดตสถานะ' AS notes
                FROM reservations
                WHERE reservation_id = ?
            ");
            $stmt->execute([$reservationId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT
                    CONCAT('payment_', payment_status) AS status,
                    COALESCE(payment_date, created_at) AS changed_at,
                    'system' AS changed_by,
                    CONCAT('การชำระเงิน: ', payment_status) AS notes
                FROM payments
                WHERE reservation_id = ?
            ");
            $stmt->execute([$reservationId]);

            return array_merge($history, $stmt->fetchAll(PDO::FETCH_ASSOC));

        } catch (Exception $e) {
            error_log("BookingTrackingService::getBookingStatusHistory - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ตรวจสอบสิทธิ์
     */
    public static function checkPermission(string $reservationId, string $customerId, string $action): array
    {
        $db = Database::connect();

        try {
            $stmt = $db->prepare("
                SELECT status, start_datetime
                FROM reservations
                WHERE reservation_id = ? AND customer_id = ?
            ");
            $stmt->execute([$reservationId, $customerId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $booking) {
                return ['allowed' => false, 'message' => 'ไม่พบข้อมูลหรือไม่มีสิทธิ์'];
            }

            $startTime = new DateTime($booking['start_datetime']);
            $now       = new DateTime();
            $hoursDiff = ($startTime->getTimestamp() - $now->getTimestamp()) / 3600;

            if ($action === 'cancel' || $action === 'edit') {
                // แก้ไข: อนุญาตให้ยกเลิก/แก้ไขได้หลายสถานะ
                $allowedStatuses = ['pending', 'confirmed', 'in_progress'];

                if (! in_array($booking['status'], $allowedStatuses)) {
                    return ['allowed' => false, 'message' => 'ไม่สามารถดำเนินการกับสถานะนี้ได้'];
                }

                if ($hoursDiff < 24) {
                    return ['allowed' => false, 'message' => 'เหลือเวลาไม่ถึง 24 ชั่วโมง ไม่สามารถดำเนินการได้'];
                }

                return ['allowed' => true, 'message' => 'อนุญาต'];
            }

            if ($action === 'pay_deposit') {
                require_once __DIR__ . '/PaymentService.php';
                $payment = PaymentService::getPaymentByReservation($reservationId);

                if ($payment) {
                    return ['allowed' => false, 'message' => 'ชำระเงินไปแล้ว'];
                }

                if (! in_array($booking['status'], ['pending', 'confirmed'])) {
                    return ['allowed' => false, 'message' => 'ไม่สามารถชำระเงินในสถานะนี้'];
                }

                if ($hoursDiff < 24) {
                    return ['allowed' => false, 'message' => 'เหลือเวลาไม่ถึง 24 ชั่วโมง ไม่สามารถชำระเงินได้'];
                }

                return ['allowed' => true, 'message' => 'อนุญาต'];
            }

            return ['allowed' => false, 'message' => 'ไม่ทราบการดำเนินการ'];

        } catch (Exception $e) {
            error_log("BookingTrackingService::checkPermission - Error: " . $e->getMessage());
            return ['allowed' => false, 'message' => 'เกิดข้อผิดพลาด'];
        }
    }
}
