-- تحديث قاعدة البيانات لدعم التقويم الشخصي
-- يجب تشغيل هذا الملف إذا كانت قاعدة البيانات موجودة مسبقاً

USE sport_calendar;

-- إضافة العمود الجديد assigned_to إذا لم يكن موجوداً
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS assigned_to INT,
ADD FOREIGN KEY IF NOT EXISTS (assigned_to) REFERENCES users(id) ON DELETE CASCADE;

-- تغيير القيمة الافتراضية لـ is_public إلى FALSE
ALTER TABLE events 
MODIFY COLUMN is_public BOOLEAN DEFAULT FALSE;

-- تحديث الأحداث الموجودة لتكون عامة
UPDATE events SET is_public = TRUE WHERE assigned_to IS NULL;

-- إضافة فهرس لتحسين الأداء
CREATE INDEX IF NOT EXISTS idx_events_assigned_to ON events(assigned_to);
CREATE INDEX IF NOT EXISTS idx_events_public ON events(is_public);
CREATE INDEX IF NOT EXISTS idx_events_date_user ON events(event_date, assigned_to);

-- عرض النتائج
SELECT 'تم تحديث قاعدة البيانات بنجاح!' as message;
