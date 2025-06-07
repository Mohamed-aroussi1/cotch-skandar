<?php
require_once 'config.php';
require_once 'functions.php';

// التحقق من تسجيل الدخول وصلاحيات الإدارة
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

$message = '';
$message_type = 'info';

// معالجة إضافة حدث جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $event_data = [
        'title' => sanitize($_POST['title']),
        'description' => sanitize($_POST['description']),
        'event_date' => $_POST['event_date'],
        'event_time' => $_POST['event_time'],
        'sport_type' => sanitize($_POST['sport_type']),
        'location' => sanitize($_POST['location']),
        'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
        'is_public' => isset($_POST['is_public']) ? 1 : 0
    ];
    
    // التحقق من صحة البيانات
    if (empty($event_data['title']) || empty($event_data['event_date'])) {
        $message = 'يرجى ملء العنوان والتاريخ على الأقل';
        $message_type = 'error';
    } else {
        $event_id = addEvent($pdo, $event_data);
        
        if ($event_id) {
            // رفع الملفات إن وجدت
            if (!empty($_FILES['event_files']['name'][0])) {
                foreach ($_FILES['event_files']['name'] as $key => $filename) {
                    if (!empty($filename)) {
                        $file = [
                            'name' => $_FILES['event_files']['name'][$key],
                            'type' => $_FILES['event_files']['type'][$key],
                            'tmp_name' => $_FILES['event_files']['tmp_name'][$key],
                            'size' => $_FILES['event_files']['size'][$key]
                        ];
                        
                        $upload_result = uploadFile($file, $event_id, $pdo);
                        if (!$upload_result['success']) {
                            $message .= ' تحذير: ' . $upload_result['message'];
                        }
                    }
                }
            }
            
            $message = 'تم إضافة الحدث بنجاح';
            $message_type = 'success';
        } else {
            $message = 'فشل في إضافة الحدث';
            $message_type = 'error';
        }
    }
}

// جلب جميع الأحداث
$all_events = getAllEvents($pdo);

// جلب جميع المستخدمين
$all_users = getAllUsers($pdo);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإدارة - التقويم الرياضي</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏆 لوحة إدارة التقويم الرياضي</h1>
            <p>مرحباً <?php echo htmlspecialchars($_SESSION['full_name']); ?> - مدير النظام</p>
            
            <div class="nav">
                <a href="index.php">الرئيسية</a>
                <a href="admin.php">لوحة الإدارة</a>
                <a href="logout.php">تسجيل الخروج</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Event Form -->
        <div class="form-container">
            <h2>إضافة حدث رياضي جديد</h2>
            
            <form method="POST" enctype="multipart/form-data" id="eventForm">
                <div class="form-group">
                    <label for="title">عنوان الحدث *</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="description">وصف الحدث</label>
                    <textarea id="description" name="description" rows="4" 
                              placeholder="اكتب وصفاً مفصلاً للحدث الرياضي..."></textarea>
                </div>

                <div class="form-group">
                    <label for="event_date">تاريخ الحدث *</label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>

                <div class="form-group">
                    <label for="event_time">وقت الحدث</label>
                    <input type="time" id="event_time" name="event_time">
                </div>

                <div class="form-group">
                    <label for="sport_type">نوع الرياضة</label>
                    <select id="sport_type" name="sport_type">
                        <option value="">اختر نوع الرياضة</option>

                        <!-- الرياضات الجماعية -->
                        <optgroup label="🏈 الرياضات الجماعية">
                            <option value="كرة القدم">كرة القدم</option>
                            <option value="كرة السلة">كرة السلة</option>
                            <option value="كرة الطائرة">كرة الطائرة</option>
                            <option value="كرة اليد">كرة اليد</option>
                            <option value="الهوكي">الهوكي</option>
                        </optgroup>

                        <!-- الرياضات الفردية -->
                        <optgroup label="🎾 الرياضات الفردية">
                            <option value="تنس">تنس</option>
                            <option value="تنس الطاولة">تنس الطاولة</option>
                            <option value="الريشة الطائرة">الريشة الطائرة</option>
                            <option value="الاسكواش">الاسكواش</option>
                        </optgroup>

                        <!-- السباحة والرياضات المائية -->
                        <optgroup label="🏊 السباحة والرياضات المائية">
                            <option value="سباحة">سباحة</option>
                            <option value="الغوص">الغوص</option>
                            <option value="كرة الماء">كرة الماء</option>
                            <option value="التجديف">التجديف</option>
                        </optgroup>

                        <!-- الجري والمشي -->
                        <optgroup label="🏃 الجري والمشي">
                            <option value="جري">جري</option>
                            <option value="المشي السريع">المشي السريع</option>
                            <option value="ماراثون">ماراثون</option>
                            <option value="جري المسافات القصيرة">جري المسافات القصيرة</option>
                            <option value="جري المسافات الطويلة">جري المسافات الطويلة</option>
                        </optgroup>

                        <!-- ركوب الدراجات -->
                        <optgroup label="🚴 ركوب الدراجات">
                            <option value="ركوب الدراجات">ركوب الدراجات</option>
                            <option value="دراجات الجبال">دراجات الجبال</option>
                            <option value="دراجات الطريق">دراجات الطريق</option>
                            <option value="BMX">BMX</option>
                        </optgroup>

                        <!-- كمال الأجسام وتمارين القوة -->
                        <optgroup label="💪 كمال الأجسام وتمارين القوة">
                            <option value="كمال الأجسام">كمال الأجسام</option>
                            <option value="رفع الأثقال">رفع الأثقال</option>
                            <option value="تمارين القوة">تمارين القوة</option>
                            <option value="CrossFit">CrossFit</option>
                            <option value="Powerlifting">Powerlifting</option>
                            <option value="تمارين المقاومة">تمارين المقاومة</option>
                        </optgroup>

                        <!-- تمارين كامل الجسم -->
                        <optgroup label="🏋️ تمارين كامل الجسم">
                            <option value="تمارين كامل الجسم">تمارين كامل الجسم</option>
                            <option value="تمارين الكارديو">تمارين الكارديو</option>
                            <option value="تمارين HIIT">تمارين HIIT (عالية الكثافة)</option>
                            <option value="تمارين الدائرة">تمارين الدائرة (Circuit Training)</option>
                            <option value="تمارين وظيفية">تمارين وظيفية (Functional Training)</option>
                            <option value="تمارين الجسم بالوزن">تمارين الجسم بالوزن</option>
                            <option value="تمارين TRX">تمارين TRX</option>
                            <option value="تمارين Kettlebell">تمارين Kettlebell</option>
                            <option value="تمارين البيلاتس">تمارين البيلاتس</option>
                            <option value="تمارين الإطالة">تمارين الإطالة والمرونة</option>
                        </optgroup>

                        <!-- الفنون القتالية -->
                        <optgroup label="🥊 الفنون القتالية">
                            <option value="Boxing">Boxing (الملاكمة)</option>
                            <option value="Kanfo Boxing">Kanfo Boxing</option>
                            <option value="الكاراتيه">الكاراتيه</option>
                            <option value="التايكوندو">التايكوندو</option>
                            <option value="الجودو">الجودو</option>
                            <option value="الكيك بوكسينغ">الكيك بوكسينغ</option>
                            <option value="المواي تاي">المواي تاي</option>
                            <option value="الجيو جيتسو">الجيو جيتسو</option>
                            <option value="MMA">MMA (فنون قتالية مختلطة)</option>
                            <option value="الكونغ فو">الكونغ فو</option>
                        </optgroup>

                        <!-- اليوغا والتأمل -->
                        <optgroup label="🧘 اليوغا والتأمل">
                            <option value="يوغا">يوغا</option>
                            <option value="يوغا هاثا">يوغا هاثا</option>
                            <option value="يوغا فينياسا">يوغا فينياسا</option>
                            <option value="يوغا أشتانغا">يوغا أشتانغا</option>
                            <option value="التأمل">التأمل</option>
                            <option value="تاي تشي">تاي تشي</option>
                        </optgroup>

                        <!-- رياضات أخرى -->
                        <optgroup label="🎯 رياضات أخرى">
                            <option value="الجمباز">الجمباز</option>
                            <option value="ألعاب القوى">ألعاب القوى</option>
                            <option value="التسلق">التسلق</option>
                            <option value="التزلج">التزلج</option>
                            <option value="الرماية">الرماية</option>
                            <option value="الشطرنج الرياضي">الشطرنج الرياضي</option>
                            <option value="أخرى">أخرى</option>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location">مكان الحدث</label>
                    <input type="text" id="location" name="location"
                           placeholder="مثال: ملعب المدينة الرياضي">
                </div>

                <div class="form-group">
                    <label for="assigned_to">تخصيص الحدث لمستخدم معين</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">اختر المستخدم (اختياري)</option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                                (<?php echo htmlspecialchars($user['username']); ?>)
                                <?php if ($user['user_type'] === 'admin'): ?> - مدير<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>إذا لم تختر مستخدماً، سيكون الحدث عاماً لجميع المستخدمين</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="is_public" name="is_public" checked>
                        حدث عام (يظهر لجميع المستخدمين)
                    </label>
                    <small>إذا كان الحدث مخصص لمستخدم معين، يمكنك جعله عاماً أيضاً</small>
                </div>

                <div class="form-group">
                    <label for="event_files">الصور والفيديوهات</label>
                    <input type="file" id="event_files" name="event_files[]" 
                           multiple accept="image/*,video/*" onchange="previewFiles()">
                    <small>يمكنك اختيار عدة ملفات (صور وفيديوهات)</small>
                </div>

                <!-- معاينة الملفات -->
                <div id="file-preview" class="media-gallery" style="display: none;">
                    <h4>معاينة الملفات:</h4>
                </div>

                <button type="submit" name="add_event" class="btn" onclick="return validateForm('eventForm')">
                    إضافة الحدث
                </button>
            </form>
        </div>

        <!-- Events List -->
        <div class="form-container" style="margin-top: 30px;">
            <h2>الأحداث المضافة</h2>
            
            <?php if (empty($all_events)): ?>
                <div class="alert alert-info">
                    لا توجد أحداث مضافة بعد
                </div>
            <?php else: ?>
                <?php foreach ($all_events as $event): ?>
                    <div class="event-details">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <h4 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                                <div class="event-meta">
                                    📅 <?php echo arabicDate($event['event_date']); ?>
                                    <?php if ($event['event_time']): ?>
                                        | ⏰ <?php echo date('H:i', strtotime($event['event_time'])); ?>
                                    <?php endif; ?>
                                    <?php if ($event['sport_type']): ?>
                                        | 🏃 <?php echo htmlspecialchars($event['sport_type']); ?>
                                    <?php endif; ?>
                                    <?php if ($event['location']): ?>
                                        | 📍 <?php echo htmlspecialchars($event['location']); ?>
                                    <?php endif; ?>
                                    <br>
                                    👤 أضافه: <?php echo htmlspecialchars($event['creator_name']); ?>
                                    <?php if ($event['assigned_user_name']): ?>
                                        | 🎯 مخصص لـ: <?php echo htmlspecialchars($event['assigned_user_name']); ?>
                                    <?php endif; ?>
                                    | <?php echo $event['is_public'] ? '🌍 عام' : '🔒 خاص'; ?>
                                </div>
                                
                                <?php if ($event['description']): ?>
                                    <div class="event-description">
                                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-right: 20px;">
                                <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn" style="font-size: 12px; padding: 8px 15px;">
                                    تعديل
                                </a>
                                <a href="delete_event.php?id=<?php echo $event['id']; ?>" 
                                   class="btn btn-danger" 
                                   style="font-size: 12px; padding: 8px 15px; margin-top: 5px;"
                                   onclick="return confirm('هل أنت متأكد من حذف هذا الحدث؟')">
                                    حذف
                                </a>
                            </div>
                        </div>

                        <?php if ($event['files']): ?>
                            <div class="media-gallery" style="margin-top: 15px;">
                                <?php 
                                $files = explode(',', $event['files']);
                                foreach ($files as $file): 
                                    $file = trim($file);
                                    if (empty($file)) continue;
                                    
                                    $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif']);
                                    $is_video = in_array($file_extension, ['mp4', 'avi', 'mov']);
                                ?>
                                    <div class="media-item">
                                        <?php if ($is_image): ?>
                                            <img src="<?php echo htmlspecialchars($file); ?>" alt="صورة الحدث">
                                        <?php elseif ($is_video): ?>
                                            <video controls>
                                                <source src="<?php echo htmlspecialchars($file); ?>" type="video/mp4">
                                            </video>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="calendar.js"></script>
    <script>
        // معاينة الملفات المختارة
        function previewFiles() {
            const fileInput = document.getElementById('event_files');
            const preview = document.getElementById('file-preview');
            
            if (fileInput.files.length > 0) {
                preview.style.display = 'block';
                preview.innerHTML = '<h4>معاينة الملفات:</h4>';
                
                Array.from(fileInput.files).forEach(file => {
                    const reader = new FileReader();
                    const mediaItem = document.createElement('div');
                    mediaItem.className = 'media-item';
                    
                    reader.onload = function(e) {
                        if (file.type.startsWith('image/')) {
                            mediaItem.innerHTML = `<img src="${e.target.result}" alt="معاينة الصورة">`;
                        } else if (file.type.startsWith('video/')) {
                            mediaItem.innerHTML = `<video controls><source src="${e.target.result}" type="${file.type}"></video>`;
                        }
                        preview.appendChild(mediaItem);
                    };
                    
                    reader.readAsDataURL(file);
                });
            } else {
                preview.style.display = 'none';
            }
        }

        // تعيين التاريخ الحالي كافتراضي
        document.getElementById('event_date').valueAsDate = new Date();

        // تحديث حالة الحقل العام عند اختيار مستخدم
        document.getElementById('assigned_to').addEventListener('change', function() {
            const isPublicCheckbox = document.getElementById('is_public');
            const selectedUser = this.value;

            if (selectedUser) {
                // إذا تم اختيار مستخدم، اجعل الحدث خاص بشكل افتراضي
                isPublicCheckbox.checked = false;
            } else {
                // إذا لم يتم اختيار مستخدم، اجعل الحدث عام
                isPublicCheckbox.checked = true;
            }
        });
    </script>
</body>
</html>
