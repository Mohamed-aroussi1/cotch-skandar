<?php
require_once 'config.php';
require_once 'functions.php';

// إعادة توجيه المستخدم المسجل دخوله
if (isLoggedIn()) {
    redirect('index.php');
}

$message = '';
$message_type = 'info';

// معالجة التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'full_name' => sanitize($_POST['full_name'])
    ];
    
    // التحقق من صحة البيانات
    $errors = [];
    
    if (empty($data['username']) || strlen($data['username']) < 3) {
        $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    if (empty($data['password']) || strlen($data['password']) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'كلمة المرور وتأكيدها غير متطابقتين';
    }
    
    if (empty($data['full_name'])) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    
    if (empty($errors)) {
        $register_result = registerUser($pdo, $data);
        $message = $register_result['message'];
        $message_type = $register_result['success'] ? 'success' : 'error';
        
        if ($register_result['success']) {
            // تسجيل دخول تلقائي بعد التسجيل
            $login_result = loginUser($pdo, $data['username'], $data['password']);
            if ($login_result['success']) {
                redirect('index.php');
            }
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التسجيل - التقويم الرياضي</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h1 class="auth-title">🏆 التقويم الرياضي</h1>
        <h2 class="auth-title">إنشاء حساب جديد</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="full_name">الاسم الكامل *</label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="username">اسم المستخدم *</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       minlength="3">
                <small>يجب أن يكون 3 أحرف على الأقل</small>
            </div>

            <div class="form-group">
                <label for="email">البريد الإلكتروني *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور *</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small>يجب أن تكون 6 أحرف على الأقل</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>

            <button type="submit" class="btn" style="width: 100%;">
                إنشاء الحساب
            </button>
        </form>

        <div class="auth-links">
            <p>لديك حساب بالفعل؟ <a href="login.php">سجل دخولك</a></p>
        </div>
    </div>

    <script>
        // التحقق من صحة النموذج
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // التحقق من الحقول المطلوبة
            if (!fullName || !username || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة');
                return false;
            }
            
            // التحقق من طول اسم المستخدم
            if (username.length < 3) {
                e.preventDefault();
                alert('اسم المستخدم يجب أن يكون 3 أحرف على الأقل');
                document.getElementById('username').focus();
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
            
            // التحقق من طول كلمة المرور
            if (password.length < 6) {
                e.preventDefault();
                alert('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
                document.getElementById('password').focus();
                return false;
            }
            
            // التحقق من تطابق كلمة المرور
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('كلمة المرور وتأكيدها غير متطابقتين');
                document.getElementById('confirm_password').focus();
                return false;
            }
        });

        // التحقق من تطابق كلمة المرور أثناء الكتابة
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('كلمة المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });

        // تركيز على حقل الاسم الكامل
        document.getElementById('full_name').focus();
    </script>
</body>
</html>
