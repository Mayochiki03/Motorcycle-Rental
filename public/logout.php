<?php
// public/logout.php
// ออกจากระบบ: ล้างข้อมูล session และ cookie แล้วกลับไปหน้า login

require_once __DIR__ . '/../src/bootstrap.php';

Session::logout();
header('Location: login.php');
exit;
