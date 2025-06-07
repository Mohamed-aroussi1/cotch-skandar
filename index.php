<?php
require_once 'config.php';
require_once 'functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

// جلب الأحداث للشهر الحالي (الخاصة بالمستخدم والعامة)
$current_year = date('Y');
$current_month = date('m');
$events = getEventsByMonth($pdo, $current_year, $current_month, $_SESSION['user_id']);

// تحويل الأحداث إلى مصفوفة مفهرسة بالتاريخ
$events_by_date = [];
foreach ($events as $event) {
    $date = $event['event_date'];
    if (!isset($events_by_date[$date])) {
        $events_by_date[$date] = [];
    }
    $events_by_date[$date][] = $event;
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقويم الرياضي - الصفحة الرئيسية</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏆 التقويم الرياضي</h1>
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

        <!-- Calendar Container -->
        <div class="calendar-container">
            <div class="calendar-header">
                <button class="calendar-nav prev-month">← الشهر السابق</button>
                <h2 class="calendar-title"></h2>
                <button class="calendar-nav next-month">الشهر التالي →</button>
            </div>

            <div class="calendar-grid">
                <!-- سيتم ملؤها بواسطة JavaScript -->
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button class="btn today-btn">العودة لليوم</button>
            </div>
        </div>

        <!-- Events List -->
        <div class="events-section" style="margin-top: 30px;">
            <h3 style="color: white; text-align: center; margin-bottom: 20px;">الأحداث القادمة</h3>
            
            <?php if (empty($events)): ?>
                <div class="alert alert-info">
                    لا توجد أحداث مجدولة لهذا الشهر
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-details">
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
                            <?php if ($event['assigned_user_name'] && $event['assigned_user_name'] === $_SESSION['full_name']): ?>
                                🎯 <strong>حدث شخصي لك</strong>
                            <?php elseif ($event['assigned_user_name']): ?>
                                🎯 مخصص لـ: <?php echo htmlspecialchars($event['assigned_user_name']); ?>
                            <?php endif; ?>
                            <?php if ($event['is_public']): ?>
                                | 🌍 حدث عام
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($event['description']): ?>
                            <div class="event-description">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($event['files']): ?>
                            <div class="media-gallery">
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
                                            <img src="<?php echo htmlspecialchars($file); ?>" 
                                                 alt="صورة الحدث" 
                                                 onclick="openModal('<?php echo htmlspecialchars($file); ?>')">
                                        <?php elseif ($is_video): ?>
                                            <video controls>
                                                <source src="<?php echo htmlspecialchars($file); ?>" type="video/mp4">
                                                متصفحك لا يدعم تشغيل الفيديو
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

    <!-- Modal للصور -->
    <div id="imageModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <img id="modalImage" src="" alt="صورة مكبرة" style="width: 100%; max-width: 800px;">
        </div>
    </div>

    <script src="calendar.js"></script>
    <script>
        // تمرير بيانات الأحداث إلى JavaScript
        const eventsData = <?php echo json_encode($events); ?>;
        
        // تحديث أحداث التقويم
        document.addEventListener('DOMContentLoaded', function() {
            if (window.sportCalendar) {
                window.sportCalendar.events = eventsData;
                window.sportCalendar.renderCalendar();
            }
        });

        // دالة لفتح الصورة في نافذة منبثقة
        function openModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'block';
        }

        // دالة لإغلاق النافذة المنبثقة
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // إغلاق النافذة عند النقر خارجها
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            position: relative;
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 90%;
            max-height: 90%;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }
    </style>
</body>
</html>
