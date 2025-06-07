<?php
/**
 * ملف لتحديث كلمة مرور المدير الافتراضي
 * يجب حذف هذا الملف بعد الاستخدام لأسباب أمنية
 */

require_once 'config.php';

// كلمة المرور الجديدة
$new_password = 'password'; // غير هذه إلى كلمة المرور المرغوبة

try {
    // تشفير كلمة المرور
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // تحديث كلمة مرور المدير
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashed_password]);
    
    echo "تم تحديث كلمة مرور المدير بنجاح!<br>";
    echo "اسم المستخدم: admin<br>";
    echo "كلمة المرور: $new_password<br>";
    echo "<strong>تحذير: احذف هذا الملف فوراً لأسباب أمنية!</strong>";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
