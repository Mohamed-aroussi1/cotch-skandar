<?php
require_once '../config.php';
require_once '../functions.php';

// تعيين نوع المحتوى
header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'غير مصرح']);
    exit;
}

try {
    // جلب المعاملات
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    
    // جلب الأحداث حسب المعاملات (للمستخدم الحالي)
    $user_id = $_SESSION['user_id'];

    if ($date) {
        $events = getEventsByDate($pdo, $date, $user_id);
    } elseif ($year && $month) {
        $events = getEventsByMonth($pdo, $year, $month, $user_id);
    } else {
        $events = getUserEvents($pdo, $user_id);
    }
    
    // إرجاع البيانات
    echo json_encode($events, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطأ في الخادم: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
