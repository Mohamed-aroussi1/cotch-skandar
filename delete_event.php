<?php
require_once 'config.php';
require_once 'functions.php';

// التحقق من تسجيل الدخول وصلاحيات الإدارة
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

// التحقق من وجود معرف الحدث
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('admin.php');
}

$event_id = (int)$_GET['id'];

try {
    // جلب معلومات الحدث أولاً
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        $_SESSION['message'] = 'الحدث غير موجود';
        $_SESSION['message_type'] = 'error';
        redirect('admin.php');
    }
    
    // جلب الملفات المرتبطة بالحدث
    $stmt = $pdo->prepare("SELECT file_path FROM uploads WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $files = $stmt->fetchAll();
    
    // حذف الملفات من الخادم
    foreach ($files as $file) {
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
    }
    
    // حذف الحدث من قاعدة البيانات (سيتم حذف الملفات تلقائياً بسبب CASCADE)
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    
    $_SESSION['message'] = 'تم حذف الحدث بنجاح';
    $_SESSION['message_type'] = 'success';
    
} catch (Exception $e) {
    $_SESSION['message'] = 'خطأ في حذف الحدث: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

redirect('admin.php');
?>
