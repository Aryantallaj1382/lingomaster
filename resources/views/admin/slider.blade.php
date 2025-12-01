@include('admin.upad')

<link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://unpkg.com/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="container mx-auto p-4">
    <h1 class="text-xl mb-4">مدیریت اسلایدر</h1>

    <!-- دکمه افزودن -->
    <button onclick="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded">افزودن اسلایدر</button>

    <!-- جدول اسلایدرها -->
    <table class="min-w-full mt-6 bg-white">
        <thead>
        <tr>
            <th class="border px-4 py-2">#</th>
            <th class="border px-4 py-2">لینک</th>
            <th class="border px-4 py-2">صفحه</th>
            <th class="border px-4 py-2">موقعیت</th>
            <th class="border px-4 py-2">تصویر موبایل</th>
            <th class="border px-4 py-2">عملیات</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($sliders as $slider)
            <tr>
                <td class="border px-4 py-2">{{ $slider->id }}</td>
                <td class="border px-4 py-2">{{ $slider->link }}</td>
                <td class="border px-4 py-2">{{ $slider->where_page }}</td>
                <td class="border px-4 py-2">{{ $slider->location }}</td>
                <td class="border px-4 py-2">
                    @if ($slider->mobile_image)
                        <img src="{{ asset($slider->mobile_image) }}" alt="" class="w-20">
                    @endif
                </td>
                <td class="border px-4 py-2 flex gap-2">
                    <button
                        onclick='openModal(@json($slider))'
                        class="bg-yellow-500 text-white px-3 py-1 rounded">ویرایش</button>

                    <form method="POST" action="{{ route('admin.slider.destroy', $slider->id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="bg-red-600 text-white px-3 py-1 rounded"
                                onclick="return confirm('آیا مطمئنید؟')">حذف</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="sliderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center" style="height: 100vh;">
    <div class="bg-white p-6 rounded w-full max-w-lg relative">
        <h2 class="text-lg mb-4" id="modalTitle">افزودن / ویرایش اسلایدر</h2>

        <form id="sliderForm" action="{{ route('admin.slider.store_update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" id="sliderId">

            <div class="mb-4">
                <label class="block mb-1">لینک</label>
                <input type="text" name="link" id="sliderLink" class="w-full border p-2 rounded">
            </div>

            <div class="mb-4">
                <label class="block mb-1">صفحه</label>
                <select name="where_page" id="sliderWherePage" required class="w-full border p-2 rounded">
                    <option value="">انتخاب کنید</option>
                    <option value="landing">Landing</option>
                    <option value="shop">Shop</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block mb-1">موقعیت</label>
                <select name="location" id="sliderLocation" required class="w-full border p-2 rounded">
                    <option value="">انتخاب کنید</option>
                    <option value="mainPage">mainPage</option>
                    <option value="shop">shop</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block mb-1">تصویر موبایل</label>
                <input type="file" name="mobile_image" class="w-full border p-2 rounded">
            </div>

            <div class="mb-4">
                <label class="block mb-1">تصویر تبلت</label>
                <input type="file" name="tablet_image" class="w-full border p-2 rounded">
            </div>

            <div class="mb-4">
                <label class="block mb-1">تصویر لپ‌تاپ</label>
                <input type="file" name="laptop_image" class="w-full border p-2 rounded">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded">انصراف</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">ذخیره</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(slider = null) {
        const modal = document.getElementById('sliderModal');
        modal.classList.remove('hidden');
        // force display:flex to override hidden if needed (optional)
        modal.style.display = 'flex';

        if (slider) {
            document.getElementById('sliderId').value = slider.id || '';
            document.getElementById('sliderLink').value = slider.link || '';
            document.getElementById('sliderWherePage').value = slider.where_page || '';
            document.getElementById('sliderLocation').value = slider.location || '';
        } else {
            document.getElementById('sliderId').value = '';
            document.getElementById('sliderLink').value = '';
            document.getElementById('sliderWherePage').value = '';
            document.getElementById('sliderLocation').value = '';
        }
    }

    function closeModal() {
        const modal = document.getElementById('sliderModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
</script>
