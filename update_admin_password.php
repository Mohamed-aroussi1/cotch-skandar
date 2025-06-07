<?php
/**
 * ملف لتحديث كلمة مرور المدير الافتراضي
 * يجب حذف هذا الملف بعد الاستخدام لأسباب أمنية
 */

require_once 'config.php';

// كلمة المرور الجديدة
$new_password = 'password'; // غير هذه إلى كلمة المرور المرغوبة

try {
    // تحديث كلمة مرور المدير (بدون تشفير)
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$new_password]);

    echo "تم تحديث كلمة مرور المدير بنجاح!<br>";
    echo "اسم المستخدم: admin<br>";
    echo "كلمة المرور: $new_password<br>";
    echo "<strong>تحذير: كلمة المرور مخزنة بدون تشفير!</strong><br>";
    echo "<strong>احذف هذا الملف فوراً لأسباب أمنية!</strong>";

} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
