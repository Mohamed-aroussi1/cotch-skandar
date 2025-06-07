-- إزالة تشفير كلمات المرور من قاعدة البيانات الموجودة
-- تحذير: هذا يقلل من أمان النظام!

USE sport_calendar;

-- تحديث كلمة مرور المدير إلى نص عادي
UPDATE users SET password = 'password' WHERE username = 'admin';

-- إذا كان لديك مستخدمين آخرين، يمكنك تحديث كلمات مرورهم هنا
-- مثال:
-- UPDATE users SET password = 'user123' WHERE username = 'user1';
-- UPDATE users SET password = 'test123' WHERE username = 'testuser';

-- عرض جميع المستخدمين وكلمات مرورهم (للتحقق)
SELECT id, username, full_name, password, user_type FROM users;

-- رسالة تأكيد
SELECT 'تم إزالة تشفير كلمات المرور بنجاح!' as message;
SELECT 'تحذير: كلمات المرور أصبحت مخزنة كنص عادي!' as warning;
