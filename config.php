<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'sport_calendar');
define('DB_USER', 'root');
define('DB_PASS', '');

// إعدادات الموقع
define('SITE_URL', 'http://localhost/sport');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// إعدادات الجلسة
session_start();

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// دالة للتحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// دالة للتحقق من صلاحيات الإدارة
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// دالة لإعادة التوجيه
function redirect($url) {
    header("Location: $url");
    exit();
}

// دالة لتنظيف البيانات
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// دالة لعرض الرسائل
function showMessage($message, $type = 'info') {
    return "<div class='alert alert-$type'>$message</div>";
}

// إنشاء مجلد الرفع إذا لم يكن موجوداً
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
    mkdir(UPLOAD_PATH . 'images/', 0777, true);
    mkdir(UPLOAD_PATH . 'videos/', 0777, true);
}
?>
