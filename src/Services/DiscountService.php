<?php
// src/Services/DiscountService.php

require_once dirname(__DIR__) . '/bootstrap.php';

class DiscountService
{
    /**
     * ตรวจสอบโค้ดส่วนลด
     */
    public static function validateDiscount($discountCode, $rentalDays, $totalPrice)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT * FROM discounts
            WHERE discount_code = ?
            AND is_active = 1
            AND start_date <= CURDATE()
            AND end_date >= CURDATE()
            AND (usage_limit IS NULL OR used_count < usage_limit)
            AND min_rental_days <= ?
            LIMIT 1
        ");

        $stmt->execute([$discountCode, $rentalDays]);
        $discount = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $discount) {
            return [
                'valid'          => false,
                'message'        => 'โค้ดส่วนลดไม่ถูกต้องหรือหมดอายุ',
                'discountAmount' => 0,
            ];
        }

        // คำนวณส่วนลด
        $discountAmount = 0;

        $type = strtoupper($discount['discount_type']);

        // หน้าจัดการส่วนลดบันทึกประเภทเป็น 'percentage' / 'fixed' (รองรับ 'percent' ที่เคยใช้ด้วย)
        if ($type === 'PERCENT' || $type === 'PERCENTAGE') {
            $discountAmount = $totalPrice * ($discount['discount_value'] / 100);
        } else if ($type === 'FIXED') {
            $discountAmount = $discount['discount_value'];
        }

        // จำกัดจำนวนส่วนลดสูงสุด
        if ($discount['max_discount_amount'] && $discountAmount > $discount['max_discount_amount']) {
            $discountAmount = $discount['max_discount_amount'];
        }

        return [
            'valid'          => true,
            'message'        => 'ใช้โค้ดส่วนลดได้ ส่วนลด ' . $discountAmount . ' บาท',
            'discountAmount' => $discountAmount,
            'discountId'     => $discount['discount_id'],
        ];
    }

    /**
     * บันทึกการใช้งานส่วนลด
     */
    public static function recordDiscountUsage($reservationId, $discountId, $appliedAmount)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
        INSERT INTO reservation_discounts (reservation_id, discount_id, applied_amount)
        VALUES (?, ?, ?)
    ");
        $stmt->execute([$reservationId, $discountId, $appliedAmount]);

        // อัปเดตจำนวนการใช้
        $stmt = $db->prepare("
        UPDATE discounts
        SET used_count = used_count + 1
        WHERE discount_id = ?
    ");
        $stmt->execute([$discountId]);

        return true;
    }

}
