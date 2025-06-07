<?php
require_once 'config.php';

// دالة لجلب جميع الأحداث (للمدراء)
function getAllEvents($pdo) {
    $stmt = $pdo->query("
        SELECT e.*, u.full_name as creator_name, au.full_name as assigned_user_name,
               GROUP_CONCAT(up.file_path) as files
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN users au ON e.assigned_to = au.id
        LEFT JOIN uploads up ON e.id = up.event_id
        WHERE e.status = 'active'
        GROUP BY e.id
        ORDER BY e.event_date ASC
    ");
    return $stmt->fetchAll();
}

// دالة لجلب الأحداث الخاصة بمستخدم معين
function getUserEvents($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT e.*, u.full_name as creator_name, au.full_name as assigned_user_name,
               GROUP_CONCAT(up.file_path) as files
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN users au ON e.assigned_to = au.id
        LEFT JOIN uploads up ON e.id = up.event_id
        WHERE e.status = 'active' AND (e.assigned_to = ? OR e.is_public = TRUE)
        GROUP BY e.id
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// دالة لجلب أحداث شهر معين للمستخدم
function getEventsByMonth($pdo, $year, $month, $user_id = null) {
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT e.*, u.full_name as creator_name, au.full_name as assigned_user_name,
                   GROUP_CONCAT(up.file_path) as files
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN users au ON e.assigned_to = au.id
            LEFT JOIN uploads up ON e.id = up.event_id
            WHERE YEAR(e.event_date) = ? AND MONTH(e.event_date) = ?
            AND e.status = 'active' AND (e.assigned_to = ? OR e.is_public = TRUE)
            GROUP BY e.id
            ORDER BY e.event_date ASC
        ");
        $stmt->execute([$year, $month, $user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT e.*, u.full_name as creator_name, au.full_name as assigned_user_name,
                   GROUP_CONCAT(up.file_path) as files
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN users au ON e.assigned_to = au.id
            LEFT JOIN uploads up ON e.id = up.event_id
            WHERE YEAR(e.event_date) = ? AND MONTH(e.event_date) = ?
            AND e.status = 'active'
            GROUP BY e.id
            ORDER BY e.event_date ASC
        ");
        $stmt->execute([$year, $month]);
    }
    return $stmt->fetchAll();
}

// دالة لجلب أحداث يوم معين للمستخدم
function getEventsByDate($pdo, $date, $user_id = null) {
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT e.*, u.full_name as creator_name, au.full_name as assigned_user_name,
                   GROUP_CONCAT(up.file_path) as files
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN users au ON e.assigned_to = au.id
            LEFT JOIN uploads up ON e.id = up.event_id
            WHERE e.event_date = ? AND e.status = 'active' AND (e.assigned_to = ? OR e.is_public = TRUE)
            GROUP BY e.id
            ORDER BY e.event_time ASC
        ");
        $stmt->execute([$date, $user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT e.*, u.full_name as creator_name, au.full_name as assigned_user_name,
                   GROUP_CONCAT(up.file_path) as files
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN users au ON e.assigned_to = au.id
            LEFT JOIN uploads up ON e.id = up.event_id
            WHERE e.event_date = ? AND e.status = 'active'
            GROUP BY e.id
            ORDER BY e.event_time ASC
        ");
        $stmt->execute([$date]);
    }
    return $stmt->fetchAll();
}

// دالة لإضافة حدث جديد
function addEvent($pdo, $data) {
    $stmt = $pdo->prepare("
        INSERT INTO events (title, description, event_date, event_time, sport_type, location, created_by, assigned_to, is_public)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['title'],
        $data['description'],
        $data['event_date'],
        $data['event_time'],
        $data['sport_type'],
        $data['location'],
        $_SESSION['user_id'],
        $data['assigned_to'],
        $data['is_public']
    ]);
    return $pdo->lastInsertId();
}

// دالة لجلب جميع المستخدمين
function getAllUsers($pdo) {
    $stmt = $pdo->query("SELECT id, username, full_name, user_type FROM users ORDER BY full_name ASC");
    return $stmt->fetchAll();
}

// دالة لرفع الملفات
function uploadFile($file, $event_id, $pdo) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi'];
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'نوع الملف غير مدعوم'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
    }
    
    $file_type = strpos($file['type'], 'image') !== false ? 'image' : 'video';
    $upload_dir = UPLOAD_PATH . $file_type . 's/';
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $stmt = $pdo->prepare("
            INSERT INTO uploads (event_id, file_name, file_path, file_type, file_size, mime_type, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $event_id,
            $file['name'],
            $file_path,
            $file_type,
            $file['size'],
            $file['type'],
            $_SESSION['user_id']
        ]);
        
        return ['success' => true, 'file_path' => $file_path];
    }
    
    return ['success' => false, 'message' => 'فشل في رفع الملف'];
}

// دالة لتسجيل المستخدم
function registerUser($pdo, $data) {
    // التحقق من وجود المستخدم
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['email']]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'اسم المستخدم أو البريد الإلكتروني موجود مسبقاً'];
    }
    
    // إضافة المستخدم الجديد
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name)
        VALUES (?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$data['username'], $data['email'], $hashed_password, $data['full_name']])) {
        return ['success' => true, 'message' => 'تم التسجيل بنجاح'];
    }
    
    return ['success' => false, 'message' => 'فشل في التسجيل'];
}

// دالة لتسجيل الدخول
function loginUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'];
        return ['success' => true, 'message' => 'تم تسجيل الدخول بنجاح'];
    }
    
    return ['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'];
}

// دالة لتسجيل الخروج
function logoutUser() {
    session_destroy();
    redirect('login.php');
}

// دالة لتحويل التاريخ إلى العربية
function arabicDate($date) {
    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    ];
    
    $day = date('d', strtotime($date));
    $month = $months[(int)date('m', strtotime($date))];
    $year = date('Y', strtotime($date));
    
    return "$day $month $year";
}
?>
