<?php
// src/Support/ImageUploader.php
// อัปโหลดรูป (สลิปโอนเงิน / รูปรถ) ไปยัง ImgBB แล้วคืน URL ของรูป
// ใช้ร่วมกันทุกที่ เพื่อให้การตรวจไฟล์และการเชื่อมต่อ HTTPS อยู่ที่เดียว

class ImageUploader
{
    public const MAX_SIZE      = 5 * 1024 * 1024; // 5MB
    public const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** ตรวจไฟล์ที่อัปโหลด (error, ขนาด, ชนิดจริงของไฟล์) ถ้าไม่ผ่านจะ throw Exception */
    public static function validate(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (รหัส ' . ($file['error'] ?? '?') . ')');
        }

        if (! is_uploaded_file($file['tmp_name'] ?? '') && PHP_SAPI !== 'cli') {
            throw new Exception('ไฟล์ที่อัปโหลดไม่ถูกต้อง');
        }

        if (($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new Exception('ไฟล์มีขนาดใหญ่เกิน 5MB');
        }

        // ตรวจชนิดจากเนื้อไฟล์จริง ไม่เชื่อนามสกุลหรือ Content-Type ที่ผู้ใช้ส่งมา
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (! in_array($mimeType, self::ALLOWED_TYPES, true)) {
            throw new Exception('ประเภทไฟล์ไม่รองรับ (รองรับ: JPG, PNG, GIF, WebP)');
        }
    }

    /**
     * อัปโหลดไป ImgBB
     * @param array  $file       ค่า $_FILES['ชื่อช่อง']
     * @param string $namePrefix คำนำหน้าชื่อไฟล์ เช่น payment_slip หรือ motorcycle
     * @return string URL รูปบน ImgBB
     */
    public static function upload(array $file, string $namePrefix): string
    {
        if (! defined('IMGBB_API_KEY') || IMGBB_API_KEY === '') {
            throw new Exception('ยังไม่ได้ตั้งค่า IMGBB_API_KEY ในไฟล์ .env');
        }

        self::validate($file);

        $postData = [
            'key'   => IMGBB_API_KEY,
            'image' => base64_encode((string) file_get_contents($file['tmp_name'])),
            'name'  => $namePrefix . '_' . time() . '_' . bin2hex(random_bytes(3)),
        ];

        $ch      = curl_init('https://api.imgbb.com/1/upload');
        $options = [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        // Windows มักต้องระบุไฟล์ CA (cacert.pem) เอง: ตั้ง CA_BUNDLE ใน .env
        // หรือจะตั้ง curl.cainfo ใน php.ini แทนก็ได้ (แนะนำ) แล้วเว้น CA_BUNDLE ไว้ว่าง
        $caBundle = Env::get('CA_BUNDLE', '');
        if ($caBundle !== '' && is_file($caBundle)) {
            $options[CURLOPT_CAINFO] = $caBundle;
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('เชื่อมต่อ ImgBB ไม่สำเร็จ: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result   = json_decode($response, true);

        if ($httpCode !== 200 || empty($result['success'])) {
            $msg = $result['error']['message'] ?? 'Upload failed';
            throw new Exception('ImgBB Error: ' . $msg);
        }

        return $result['data']['url'];
    }
}
