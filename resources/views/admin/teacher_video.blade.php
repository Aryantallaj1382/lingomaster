@include('admin.upad')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">ویدیوهای استاد</h1>

    <!-- فرم جستجو و انتخاب زبان -->
    <form method="GET" action="{{ route('teacher_video') }}" class="mb-4">
        <div class="flex space-x-4">
            <input type="text" name="search" value="{{ $search }}" placeholder="جستجوی نام استاد" class="border p-2 rounded w-full">
            <select name="language_id" class="border p-2 rounded">
                @foreach (\App\Models\Languages::all() as $language)
                    <option value="{{ $language->id }}" {{ $languageId == $language->id ? 'selected' : '' }}>{{ $language->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-blue-500 text-white p-2 rounded">جستجو</button>
        </div>
    </form>

    <!-- لیست اساتید -->
    <div class="mb-4">
        <h2 class="text-xl font-semibold">اساتید</h2>
        @if ($professors->isEmpty())
            <p class="text-red-500">هیچ استادی با ویدیوی معرفی برای زبان انتخاب‌شده یافت نشد.</p>
        @else
            <ul class="list-disc pl-5">
                @foreach ($professors as $professor)
                    <li class="flex items-center space-x-4">
                        <span>{{ $professor->first_name }} {{ $professor->last_name }}</span>
                        <button class="bg-green-500 text-white px-3 py-1 rounded show-video"
                                data-type="about_video"
                                data-professor-id="{{ $professor->id }}"
                                data-language-id="{{ $languageId }}">
                            ویدیوی معرفی
                        </button>
                        <button class="bg-blue-500 text-white px-3 py-1 rounded show-video"
                                data-type="teaching_example_video"
                                data-professor-id="{{ $professor->id }}"
                                data-language-id="{{ $languageId }}">
                            ویدیوی نحوه آموزش
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <!-- نمایش ویدیوی معرفی (برای استاد انتخاب‌شده) -->
    @if ($aboutVideo)
        <div class="mb-4">
            <h2 class="text-xl font-semibold">ویدیوی معرفی استاد (زبان: {{ $aboutVideo->language->title ?? 'نامشخص' }})</h2>
            <video controls class="w-full">
                <source src="{{ asset($aboutVideo->path) }}" type="video/mp4">
                مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
            </video>
        </div>
    @else
        <p class="text-red-500">ویدیوی معرفی برای این استاد و زبان یافت نشد.</p>
    @endif

    <!-- نمایش ویدیوهای نحوه آموزش (برای استاد انتخاب‌شده) -->
    @if ($teachingVideos->isNotEmpty())
        <div>
            <h2 class="text-xl font-semibold">ویدیوهای نحوه آموزش (تعداد: {{ $teachingVideos->count() }})</h2>
            @foreach ($teachingVideos as $index => $video)
                <div class="mb-4">
                    <p>ویدیو {{ $index + 1 }}: {{ $video->path }} (زبان: {{ $video->language->title ?? 'نامشخص' }})</p>
                    <video controls class="w-full">
                        <source src="{{ asset($video->path) }}" type="video/mp4">
                        مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                    </video>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-red-500">ویدیوی نحوه آموزش برای این استاد و زبان یافت نشد.</p>
    @endif
</div>

<!-- مودال برای نمایش ویدیو -->
<div id="videoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg max-w-2xl w-full">
        <h2 id="modalTitle" class="text-xl font-semibold mb-4"></h2>
        <div id="modalContent"></div>
        <button id="closeModal" class="mt-4 bg-red-500 text-white px-4 py-2 rounded">بستن</button>
    </div>
</div>

<script>
    document.querySelectorAll('.show-video').forEach(button => {
        button.addEventListener('click', function() {
            const type = this.dataset.type;
            const professorId = this.dataset.professorId;
            const languageId = this.dataset.languageId;
            const modal = document.getElementById('videoModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');

            // تنظیم عنوان مودال
            const videoType = type === 'about_video' ? 'ویدیوی معرفی' : 'ویدیوی نحوه آموزش';

            // درخواست AJAX
            fetch(`/admindashboard/${type}?professor_id=${professorId}&language_id=${languageId}`)
                .then(response => response.json())
                .then(data => {
                    modalTitle.textContent = `${videoType} (زبان: ${data.language || 'نامشخص'})`;
                    let html = '';
                    if (data.status === 'success') {
                        if (type === 'about_video' && data.video) {
                            html = `<video controls class="w-full">
                                        <source src="${data.video.path}" type="video/mp4">
                                        مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                                    </video>`;
                        } else if (type === 'teaching_example_video' && data.videos && data.videos.length > 0) {
                            data.videos.forEach((video, index) => {
                                html += `<div>
                                            <p>ویدیو ${index + 1} (زبان: ${data.language || 'نامشخص'})</p>
                                            <video controls class="w-full mb-4">
                                                <source src="${video.path}" type="video/mp4">
                                                مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                                            </video>
                                         </div>`;
                            });
                        } else {
                            html = `<p class="text-red-500">هیچ ویدیویی برای این استاد و زبان یافت نشد.</p>`;
                        }
                        modalContent.innerHTML = html;
                        modal.classList.remove('hidden');
                    } else {
                        modalContent.innerHTML = `<p class="text-red-500">${data.message || 'هیچ ویدیویی یافت نشد.'} (زبان: ${data.language || 'نامشخص'})</p>`;
                        modal.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    modalContent.innerHTML = '<p class="text-red-500">خطا در بارگذاری ویدیو.</p>';
                    modal.classList.remove('hidden');
                });
        });
    });

    // بستن مودال
    document.getElementById('closeModal').addEventListener('click', () => {
        document.getElementById('videoModal').classList.add('hidden');
        document.getElementById('modalContent').innerHTML = '';
    });
</script>

