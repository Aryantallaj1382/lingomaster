@include('admin.upad')

<div class="container mx-auto p-4">
    <h1 class="text-xl mb-4">مدیریت ویدیو بنرها</h1>

    <table class="min-w-full bg-white border">
        <thead>
        <tr>
            <th class="border px-4 py-2">ID</th>
            <th class="border px-4 py-2">عنوان</th>
            <th class="border px-4 py-2">مسیر ویدیو</th>
            <th class="border px-4 py-2">تاریخ ایجاد</th>
            <th class="border px-4 py-2">تاریخ بروزرسانی</th>
            <th class="border px-4 py-2">کاور</th>
            <th class="border px-4 py-2">عملیات</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($videoBaners as $baner)
            <tr>
                <td class="border px-4 py-2">{{ $baner->id }}</td>
                <td class="border px-4 py-2">{{ $baner->title }}</td>
                <td class="border px-4 py-2">
                    <a href="{{ asset($baner->path) }}" target="_blank">{{ $baner->path }}</a>
                </td>
                <td class="border px-4 py-2">{{ $baner->created_at }}</td>
                <td class="border px-4 py-2">{{ $baner->updated_at }}</td>
                <td class="border px-4 py-2">
                    @if ($baner->cover)
                        <img src="{{ asset($baner->cover) }}" alt="کاور" class="w-20 h-14 object-cover">
                    @else
                        ---
                    @endif
                </td>
                <td class="border px-4 py-2">
                    <button onclick='openModal(@json($baner))' class="bg-yellow-500 text-white px-3 py-1 rounded">ویرایش</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="videoBanerModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white p-6 rounded w-full max-w-lg relative">
        <h2 class="text-lg mb-4" id="modalTitle">ویرایش ویدیو بنر</h2>

        <form id="videoBanerForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <input type="hidden" name="id" id="banerId">

            <div class="mb-4">
                <label class="block mb-1">عنوان</label>
                <input type="text" name="title" id="banerTitle" class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1">آپلود ویدیو</label>
                <input type="file" name="video" accept="video/mp4,video/x-msvideo,video/quicktime,video/x-ms-wmv" class="w-full border p-2 rounded">
            </div>

            <div class="mb-4">
                <label class="block mb-1">آپلود کاور</label>
                <input type="file" name="cover" accept="image/jpeg,image/png,image/gif" class="w-full border p-2 rounded">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded">انصراف</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">ذخیره</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(baner) {
        const modal = document.getElementById('videoBanerModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        document.getElementById('banerId').value = baner.id || '';
        document.getElementById('banerTitle').value = baner.title || '';

        const form = document.getElementById('videoBanerForm');
        form.action = `/admindashboard/videoBanerUpdate/${baner.id}`;
    }

    function closeModal() {
        const modal = document.getElementById('videoBanerModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
</script>
