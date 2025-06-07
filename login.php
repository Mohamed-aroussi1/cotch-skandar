<?php
require_once 'config.php';
require_once 'functions.php';

// إعادة توجيه المستخدم المسجل دخوله
if (isLoggedIn()) {
    redirect('index.php');
}

$message = '';
$message_type = 'info';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $message = 'يرجى ملء جميع الحقول';
        $message_type = 'error';
    } else {
        $login_result = loginUser($pdo, $username, $password);
        $message = $login_result['message'];
        $message_type = $login_result['success'] ? 'success' : 'error';
        
        if ($login_result['success']) {
            redirect('index.php');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - التقويم الرياضي</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h1 class="auth-title">🏆 التقويم الرياضي</h1>
        <h2 class="auth-title">تسجيل الدخول</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">اسم المستخدم أو البريد الإلكتروني</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn" style="width: 100%;">
                تسجيل الدخول
            </button>
        </form>

        <div class="auth-links">
            <p>ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
        </div>

        <!-- معلومات تجريبية -->
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; font-size: 14px;">
            <h4 style="color: #333; margin-bottom: 10px;">حسابات تجريبية:</h4>
            <p><strong>مدير النظام:</strong><br>
            اسم المستخدم: admin<br>
            كلمة المرور: password</p>
            
            <p style="margin-top: 15px;"><strong>مستخدم عادي:</strong><br>
            يمكنك إنشاء حساب جديد من خلال التسجيل</p>
        </div>
    </div>

    <script>
        // التحقق من صحة النموذج
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
                return false;
            }
        });

        // تركيز على حقل اسم المستخدم
        document.getElementById('username').focus();
    </script>
</body>
</html>
