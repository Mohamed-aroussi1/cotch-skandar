-- قاعدة بيانات موقع التقويم الرياضي
-- Sport Calendar Database

CREATE DATABASE IF NOT EXISTS sport_calendar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sport_calendar;

-- جدول المستخدمين
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول الأحداث الرياضية
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    sport_type VARCHAR(100),
    location VARCHAR(200),
    created_by INT,
    assigned_to INT,
    is_public BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول الملفات المرفوعة (صور وفيديوهات)
CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول تقويم المستخدمين الشخصي
CREATE TABLE user_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    reminder_set BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id)
);

-- إدراج مستخدم إداري افتراضي (كلمة المرور: password)
INSERT INTO users (username, email, password, full_name, user_type) VALUES
('admin', 'admin@sport.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin');

-- إدراج بعض الأحداث التجريبية
INSERT INTO events (title, description, event_date, event_time, sport_type, location, created_by, assigned_to, is_public) VALUES
('مباراة كرة القدم', 'مباراة ودية بين الفرق المحلية', '2024-01-15', '16:00:00', 'كرة القدم', 'ملعب المدينة', 1, 1, TRUE),
('بطولة السباحة', 'بطولة السباحة السنوية', '2024-01-20', '10:00:00', 'سباحة', 'مسبح الأولمبي', 1, 1, TRUE),
('ماراثون الجري', 'ماراثون نصف المسافة', '2024-01-25', '07:00:00', 'جري', 'شوارع المدينة', 1, 1, TRUE);
