// JavaScript للتقويم الرياضي
class SportCalendar {
    constructor() {
        this.currentDate = new Date();
        this.events = [];
        this.init();
    }

    init() {
        this.loadEvents();
        this.renderCalendar();
        this.bindEvents();
    }

    // تحميل الأحداث من الخادم
    async loadEvents() {
        try {
            const response = await fetch('api/get_events.php');
            this.events = await response.json();
            this.renderCalendar();
        } catch (error) {
            console.error('خطأ في تحميل الأحداث:', error);
        }
    }

    // رسم التقويم
    renderCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        // تحديث عنوان الشهر
        const monthNames = [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];
        
        const titleElement = document.querySelector('.calendar-title');
        if (titleElement) {
            titleElement.textContent = `${monthNames[month]} ${year}`;
        }

        // رسم أيام الشهر
        this.renderDays(year, month);
    }

    // رسم أيام الشهر
    renderDays(year, month) {
        const calendarGrid = document.querySelector('.calendar-grid');
        if (!calendarGrid) return;

        // مسح المحتوى السابق (عدا رؤوس الأيام)
        const dayHeaders = calendarGrid.querySelectorAll('.calendar-day-header');
        calendarGrid.innerHTML = '';
        
        // إعادة إضافة رؤوس الأيام
        const dayNames = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        dayNames.forEach(dayName => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day-header';
            dayHeader.textContent = dayName;
            calendarGrid.appendChild(dayHeader);
        });

        // حساب اليوم الأول من الشهر
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());

        // رسم 42 يوم (6 أسابيع)
        for (let i = 0; i < 42; i++) {
            const currentDate = new Date(startDate);
            currentDate.setDate(startDate.getDate() + i);
            
            const dayElement = this.createDayElement(currentDate, month);
            calendarGrid.appendChild(dayElement);
        }
    }

    // إنشاء عنصر اليوم
    createDayElement(date, currentMonth) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        // إضافة كلاسات خاصة
        if (date.getMonth() !== currentMonth) {
            dayElement.classList.add('other-month');
        }
        
        if (this.isToday(date)) {
            dayElement.classList.add('today');
        }

        // رقم اليوم
        const dayNumber = document.createElement('div');
        dayNumber.className = 'day-number';
        dayNumber.textContent = date.getDate();
        dayElement.appendChild(dayNumber);

        // إضافة الأحداث
        const dayEvents = this.getEventsForDate(date);
        dayEvents.forEach(event => {
            const eventElement = document.createElement('div');
            eventElement.className = 'event-item';
            eventElement.textContent = event.title;
            eventElement.onclick = () => this.showEventDetails(event);
            dayElement.appendChild(eventElement);
        });

        // إضافة حدث النقر
        dayElement.onclick = () => this.selectDate(date);

        return dayElement;
    }

    // التحقق من كون التاريخ هو اليوم
    isToday(date) {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    }

    // جلب أحداث تاريخ معين
    getEventsForDate(date) {
        const dateString = date.toISOString().split('T')[0];
        return this.events.filter(event => event.event_date === dateString);
    }

    // عرض تفاصيل الحدث
    showEventDetails(event) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>${event.title}</h2>
                <p><strong>التاريخ:</strong> ${event.event_date}</p>
                <p><strong>الوقت:</strong> ${event.event_time || 'غير محدد'}</p>
                <p><strong>النوع:</strong> ${event.sport_type || 'غير محدد'}</p>
                <p><strong>المكان:</strong> ${event.location || 'غير محدد'}</p>
                <p><strong>الوصف:</strong> ${event.description || 'لا يوجد وصف'}</p>
                ${event.files ? this.renderEventMedia(event.files) : ''}
            </div>
        `;

        document.body.appendChild(modal);

        // إغلاق النافذة
        modal.querySelector('.close').onclick = () => {
            document.body.removeChild(modal);
        };

        modal.onclick = (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        };
    }

    // رسم وسائط الحدث
    renderEventMedia(files) {
        if (!files) return '';
        
        const fileList = files.split(',');
        let mediaHtml = '<div class="media-gallery">';
        
        fileList.forEach(file => {
            if (file.includes('images/')) {
                mediaHtml += `<div class="media-item"><img src="${file}" alt="صورة الحدث"></div>`;
            } else if (file.includes('videos/')) {
                mediaHtml += `<div class="media-item"><video controls><source src="${file}" type="video/mp4"></video></div>`;
            }
        });
        
        mediaHtml += '</div>';
        return mediaHtml;
    }

    // اختيار تاريخ
    selectDate(date) {
        console.log('تم اختيار التاريخ:', date);
        // يمكن إضافة المزيد من الوظائف هنا
    }

    // ربط الأحداث
    bindEvents() {
        // أزرار التنقل
        const prevBtn = document.querySelector('.prev-month');
        const nextBtn = document.querySelector('.next-month');

        if (prevBtn) {
            prevBtn.onclick = () => {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                this.renderCalendar();
            };
        }

        if (nextBtn) {
            nextBtn.onclick = () => {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                this.renderCalendar();
            };
        }

        // زر اليوم
        const todayBtn = document.querySelector('.today-btn');
        if (todayBtn) {
            todayBtn.onclick = () => {
                this.currentDate = new Date();
                this.renderCalendar();
            };
        }
    }

    // تحديث الأحداث
    updateEvents() {
        this.loadEvents();
    }
}

// تشغيل التقويم عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    window.sportCalendar = new SportCalendar();
});

// دوال مساعدة للنماذج
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    for (let input of inputs) {
        if (!input.value.trim()) {
            alert('يرجى ملء جميع الحقول المطلوبة');
            input.focus();
            return false;
        }
    }
    
    return true;
}

// دالة لمعاينة الصور قبل الرفع
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// دالة لإظهار/إخفاء كلمة المرور
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}
