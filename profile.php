<?php
require_once 'config.php';
require_once 'functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';
$message_type = 'info';

// جلب معلومات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // التحقق من الحقول الأساسية
    if (empty($full_name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    // التحقق من تغيير كلمة المرور
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'يرجى إدخال كلمة المرور الحالية';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'كلمة المرور الحالية غير صحيحة';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'كلمة المرور الجديدة وتأكيدها غير متطابقتين';
        }
    }
    
    // التحقق من تفرد البريد الإلكتروني
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'البريد الإلكتروني مستخدم من قبل مستخدم آخر';
        }
    }
    
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // تحديث مع كلمة المرور الجديدة
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $hashed_password, $_SESSION['user_id']]);
            } else {
                // تحديث بدون كلمة المرور
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $_SESSION['user_id']]);
            }
            
            // تحديث معلومات الجلسة
            $_SESSION['full_name'] = $full_name;
            
            $message = 'تم تحديث الملف الشخصي بنجاح';
            $message_type = 'success';
            
            // إعادة جلب معلومات المستخدم المحدثة
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
        } catch (Exception $e) {
            $message = 'خطأ في تحديث الملف الشخصي: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// جلب إحصائيات المستخدم
$stats = [];

// عدد الأحداث المضافة (للمدراء)
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events WHERE created_by = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['events_created'] = $stmt->fetch()['count'];
}

// عدد الأحداث المفضلة
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_calendar WHERE user_id = ? AND is_favorite = 1");
$stmt->execute([$_SESSION['user_id']]);
$stats['favorite_events'] = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - التقويم الرياضي</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏆 الملف الشخصي</h1>
            <p>مرحباً <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            
            <div class="nav">
                <a href="index.php">الرئيسية</a>
                <?php if (isAdmin()): ?>
                    <a href="admin.php">لوحة الإدارة</a>
                <?php endif; ?>
                <a href="profile.php">الملف الشخصي</a>
                <a href="logout.php">تسجيل الخروج</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- User Stats -->
        <div class="form-container">
            <h2>إحصائياتك</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <?php if (isAdmin()): ?>
                    <div style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <h3><?php echo $stats['events_created']; ?></h3>
                        <p>الأحداث المضافة</p>
                    </div>
                <?php endif; ?>
                
                <div style="background: linear-gradient(45deg, #ff6b6b, #ff5252); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3><?php echo $stats['favorite_events']; ?></h3>
                    <p>الأحداث المفضلة</p>
                </div>
                
                <div style="background: linear-gradient(45deg, #4ecdc4, #44a08d); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3><?php echo ucfirst($user['user_type']); ?></h3>
                    <p>نوع الحساب</p>
                </div>
            </div>
        </div>

        <!-- Profile Update Form -->
        <div class="form-container">
            <h2>تحديث الملف الشخصي</h2>
            
            <form method="POST" id="profileForm">
                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small>لا يمكن تغيير اسم المستخدم</small>
                </div>

                <div class="form-group">
                    <label for="full_name">الاسم الكامل *</label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="email">البريد الإلكتروني *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>

                <hr style="margin: 30px 0;">
                <h3>تغيير كلمة المرور (اختياري)</h3>

                <div class="form-group">
                    <label for="current_password">كلمة المرور الحالية</label>
                    <input type="password" id="current_password" name="current_password">
                </div>

                <div class="form-group">
                    <label for="new_password">كلمة المرور الجديدة</label>
                    <input type="password" id="new_password" name="new_password" minlength="6">
                    <small>اتركها فارغة إذا كنت لا تريد تغييرها</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                </div>

                <button type="submit" name="update_profile" class="btn">
                    تحديث الملف الشخصي
                </button>
            </form>
        </div>

        <!-- Account Info -->
        <div class="form-container">
            <h2>معلومات الحساب</h2>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <p><strong>تاريخ التسجيل:</strong> <?php echo arabicDate($user['created_at']); ?></p>
                <p><strong>آخر تحديث:</strong> <?php echo arabicDate($user['updated_at']); ?></p>
                <p><strong>نوع الحساب:</strong> <?php echo $user['user_type'] === 'admin' ? 'مدير' : 'مستخدم عادي'; ?></p>
            </div>
        </div>
    </div>

    <script>
        // التحقق من صحة النموذج
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // التحقق من الحقول المطلوبة
            if (!fullName || !email) {
                e.preventDefault();
                alert('يرجى ملء الاسم الكامل والبريد الإلكتروني');
                return false;
            }
            
            // التحقق من صحة البريد الإلكتروني
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('البريد الإلكتروني غير صحيح');
                document.getElementById('email').focus();
                return false;
            }
            
            // التحقق من كلمة المرور الجديدة
            if (newPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('يرجى إدخال كلمة المرور الحالية');
                    document.getElementById('current_password').focus();
                    return false;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل');
                    document.getElementById('new_password').focus();
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('كلمة المرور الجديدة وتأكيدها غير متطابقتين');
                    document.getElementById('confirm_password').focus();
                    return false;
                }
            }
        });

        // التحقق من تطابق كلمة المرور أثناء الكتابة
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('كلمة المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
