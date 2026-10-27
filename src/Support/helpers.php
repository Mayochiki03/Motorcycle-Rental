<?php
// src/Support/helpers.php
// ฟังก์ชันช่วยที่ใช้ทั่วโปรเจค

/**
 * escape ข้อความก่อนแสดงใน HTML (กัน XSS)
 * ใช้แทน htmlspecialchars แบบย่อ: <?= e($name) ?>
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
