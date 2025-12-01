<div class="flex flex-col gap-4 p-4 bg-gray-100 min-h-screen w-64 font-sans">
    <!-- Dashboard Section -->
    <div class="group">
        <button class="flex items-center justify-between w-full py-2 px-3 bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-600 text-gray-800 hover:bg-blue-200 transition-colors rounded">
            <div class="flex items-center gap-2">
                <i class="bi bi-speedometer2 text-lg"></i>
                <span class="text-sm font-medium">پیشخوان</span>
            </div>
        </button>
    </div>

    <!-- Approvals Section -->
    <div class="group">
        <button class="flex items-center justify-between w-full py-2 px-3 bg-gray-50 border-l-4 border-blue-600 text-gray-800 hover:bg-blue-100 transition-colors rounded" onclick="toggleSection('approvals')">
            <div class="flex items-center gap-2">
                <i class="bi bi-person-check text-lg"></i>
                <span class="text-sm font-medium">تاییدها</span>
            </div>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div id="approvals" class="flex flex-col gap-2 mt-2 hidden">
            <a href="{{ route('tayid_asatid') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-person-check text-lg"></i>
                <span class="text-sm">تایید اساتید</span>
            </a>
            <a href="{{ route('Seller_confirmation') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-person-check text-lg"></i>
                <span class="text-sm">تایید فروشنده</span>
            </a>
            <a href="{{ url('/admindashboard/tayid_klas_goroohi') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-people text-lg"></i>
                <span class="text-sm"> تایید کلاس گروهی</span>
            </a>
            <a href="{{ url('/admindashboard/workshops') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-easel text-lg"></i>
                <span class="text-sm">تایید ورکشاپ </span>
            </a>
            <a href="{{ url('/admindashboard/story') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm"> تایید استوری</span>
            </a>



            <a href="{{ route('book_confirmation') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-book text-lg"></i>
                <span class="text-sm">تایید کتاب</span>
            </a>
            <a href="{{ url('/admindashboard/tayid_webinar') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-camera-video text-lg"></i>
                <span class="text-sm">تایید وبینار</span>
            </a>
            <a href="{{ url('/admindashboard/tayid_kament') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-chat-square-text text-lg"></i>
                <span class="text-sm">تایید کامنت</span>
            </a>
        </div>
    </div>

    <!-- Content Management Section -->
    <div class="group">
        <button class="flex items-center justify-between w-full py-2 px-3 bg-gray-50 border-l-4 border-blue-600 text-gray-800 hover:bg-blue-100 transition-colors rounded" onclick="toggleSection('content')">
            <div class="flex items-center gap-2">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm font-medium">مدیریت محتوا</span>
            </div>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div id="content" class="flex flex-col gap-2 mt-2 hidden">
            <a href="{{ url('/admindashboard/teacher_video') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">ویدیوهای استاد</span>
            </a>
            <a href="{{ url('/admin/exams') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">آزمون ها</span>
            </a>
            <a href="{{ url('/admindashboard/vared_kardan_zabanha') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-translate text-lg"></i>
                <span class="text-sm">زبان‌ها</span>
            </a>
            <a href="{{ url('/admindashboard/vared_kardan_lahje') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-chat-dots text-lg"></i>
                <span class="text-sm">لهجه‌ها</span>
            </a>

            <a href="{{ url('/admindashboard/add_book') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-book-plus text-lg"></i>
                <span class="text-sm">افزودن کتاب</span>
            </a>
            <a href="{{ url('/admindashboard/educational-products') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">اضافه کردن کمک آموزشی</span>
            </a>
            <a href="{{ url('/admindashboard/questionnaires') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">پرسشنامه</span>
            </a>
            <a href="{{ url('/admindashboard/blog') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">وبلاگ</span>
            </a>
            <a href="{{ url('/admindashboard/news') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">اخبار</span>
            </a>
            <a href="{{ url('/admindashboard/slider') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">اسلایدر اصلی</span>
            </a>
            <a href="{{ url('/admindashboard/Baner') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">بنر سایت</span>
            </a>
            <a href="{{ url('/admindashboard/VideoBaner') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">ویدیو بنر</span>
            </a>

            <a href="{{ url('/admindashboard/publications') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">انتشارات</span>
            </a>
            <a href="{{ url('/admindashboard/bankexans') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-journal-plus text-lg"></i>
                <span class="text-sm">بانک سوالات</span>
            </a>
        </div>
    </div>

    <!-- Financial Section -->
    <div class="group">
        <button class="flex items-center justify-between w-full py-2 px-3 bg-gray-50 border-l-4 border-blue-600 text-gray-800 hover:bg-blue-100 transition-colors rounded" onclick="toggleSection('financial')">
            <div class="flex items-center gap-2">
                <i class="bi bi-wallet text-lg"></i>
                <span class="text-sm font-medium">مالی</span>
            </div>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div id="financial" class="flex flex-col gap-2 mt-2 hidden">
            <a href="{{ url('/admindashboard/discount') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-percent text-lg"></i>
                <span class="text-sm">کد تخفیف</span>
            </a>
            <a href="{{ url('/admindashboard/wallets') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-wallet text-lg"></i>
                <span class="text-sm">کیف پول</span>
            </a>
            <a href="{{ url('/admindashboard/exam_payments') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-credit-card text-lg"></i>
                <span class="text-sm">آزمون پرداخت</span>
            </a>
            <a href="{{ url('/admindashboard/orders') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-cart text-lg"></i>
                <span class="text-sm">رد سفارش</span>
            </a>
            <a href="{{ url('/admindashboard/transactions') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-cash text-lg"></i>
                <span class="text-sm">برداشت پول</span>
            </a>
            <a href="{{ url('/admindashboard/Pay_the_teacher') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-person-plus text-lg"></i>
                <span class="text-sm">پرداخت به استاد</span>
            </a>
        </div>
    </div>

    <!-- Support Section -->
    <div class="group">
        <button class="flex items-center justify-between w-full py-2 px-3 bg-gray-50 border-l-4 border-blue-600 text-gray-800 hover:bg-blue-100 transition-colors rounded" onclick="toggleSection('support')">
            <div class="flex items-center gap-2">
                <i class="bi bi-headset text-lg"></i>
                <span class="text-sm font-medium">پشتیبانی</span>
            </div>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div id="support" class="flex flex-col gap-2 mt-2 hidden">
            <a href="{{ url('/admindashboard/conversations') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-chat text-lg"></i>
                <span class="text-sm">چت کاربران</span>
            </a>
            <a href="{{ url('/admin/tickets') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-ticket text-lg"></i>
                <span class="text-sm">تیکت‌ها</span>
            </a>
            <a href="{{ url('/admindashboard/consultation_form') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-file-earmark-text text-lg"></i>
                <span class="text-sm">فرم مشاوره</span>
            </a>
            <a href="{{ url('/admin/contacts') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-telephone text-lg"></i>
                <span class="text-sm">تماس با ما</span>
            </a>
        </div>
    </div>

    <!-- Settings Section -->
    <div class="group">
        <button class="flex items-center justify-between w-full py-2 px-3 bg-gray-50 border-l-4 border-blue-600 text-gray-800 hover:bg-blue-100 transition-colors rounded" onclick="toggleSection('settings')">
            <div class="flex items-center gap-2">
                <i class="bi bi-gear text-lg"></i>
                <span class="text-sm font-medium">تنظیمات</span>
            </div>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div id="settings" class="flex flex-col gap-2 mt-2 hidden">
            <a href="{{ url('/admindashboard/settings') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-gear text-lg"></i>
                <span class="text-sm">تنظیمات سایت</span>
            </a>
            <a href="{{ url('/admindashboard/categoryicon') }}" class="flex items-center gap-2 py-2 px-3 bg-white hover:bg-blue-50 border-l-2 border-blue-400 rounded">
                <i class="bi bi-list text-lg"></i>
                <span class="text-sm">تغییر دسته‌بندی</span>
            </a>
        </div>
    </div>

    <!-- Canceled Classes -->
    <div class="group">
        <a href="{{ url('/admindashboard/canceled_classes') }}" class="flex items-center gap-2 py-2 px-3 bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-600 text-gray-800 hover:bg-red-200 transition-colors rounded">
            <i class="bi bi-x-circle text-lg"></i>
            <span class="text-sm font-medium">لغو کلاس‌ها</span>
        </a>
    </div>
</div>

<script>
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        section.classList.toggle('hidden');
    }
</script>

<style>
    /* Ensure Persian text is rendered correctly */
    body {
        font-family: 'Vazirmatn', sans-serif;
        direction: rtl;
    }
</style>
