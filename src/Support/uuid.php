<?php

/**
 * gen_id(prefix, length)
 * ใช้สร้างรหัส ID ตามรูปแบบเดิม เช่น:
 *  gen_id("PAY", 10) → PAY_A7F92B3C11
 */
function gen_id($prefix = "ID", $length = 10) {
    // random hex string
    $random = bin2hex(random_bytes($length / 2)); // length = 10 → 5 bytes → 10 hex chars

    return $prefix . "_" . strtoupper($random);
}
