@include('admin.upad')

<div class="container mx-auto p-4">
    <h1 class="text-xl mb-4">مدیریت بنرها</h1>

    <table class="min-w-full bg-white border">
        <thead>
        <tr>
            <th class="border px-4 py-2">#</th>
            <th class="border px-4 py-2">عنوان</th>
            <th class="border px-4 py-2">لینک</th>
            <th class="border px-4 py-2">محصول مرتبط</th>
            <th class="border px-4 py-2">تصویر کاور محصول</th>
            <th class="border px-4 py-2">عملیات</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($baners as $baner)
            <tr>
                <td class="border px-4 py-2">{{ $baner->id }}</td>
                <td class="border px-4 py-2">{{ $baner->title }}</td>
                <td class="border px-4 py-2"><a href="{{ $baner->url }}" target="_blank">{{ $baner->url }}</a></td>
                <td class="border px-4 py-2">{{ $baner->product?->title ?? '---' }}</td>
                <td class="border px-4 py-2">
                    @if ($baner->product && $baner->product->first_image)
                        <img src="{{ asset($baner->product->first_image) }}" alt="کاور" class="w-16 h-10 object-cover">
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

<!-- Modal فقط ویرایش -->
<div id="banerModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white p-6 rounded w-full max-w-lg relative">
        <h2 class="text-lg mb-4" id="modalTitle">ویرایش بنر</h2>

        <form id="banerForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" id="banerId" required>

            <div class="mb-4">
                <label class="block mb-1">عنوان بنر</label>
                <input type="text" name="title" id="banerTitle" class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1">لینک بنر</label>
                <input type="url" name="url" id="banerUrl" class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1">انتخاب محصول</label>
                <select name="product_id" id="banerProduct" class="w-full border p-2 rounded">
                    <option value="">انتخاب کنید</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-image="{{ asset($product->first_image ?? '') }}">
                            {{ $product->title }}
                        </option>
                    @endforeach
                </select>
                <div id="productImagePreview" class="mt-2">
                    <!-- تصویر کاور محصول انتخاب شده نمایش داده میشه -->
                </div>
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
        const modal = document.getElementById('banerModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        document.getElementById('banerId').value = baner.id || '';
        document.getElementById('banerTitle').value = baner.title || '';
        document.getElementById('banerUrl').value = baner.url || '';
        document.getElementById('banerProduct').value = baner.product_id || '';

        // ست کردن آدرس اکشن فرم به صورت داینامیک با id بنر
        const form = document.getElementById('banerForm');
        form.action = `/admindashboard/Baner_update/${baner.id}`;

        updateProductImagePreview();
    }

    function closeModal() {
        const modal = document.getElementById('banerModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    document.getElementById('banerProduct').addEventListener('change', updateProductImagePreview);

    function updateProductImagePreview() {
        const select = document.getElementById('banerProduct');
        const selectedOption = select.options[select.selectedIndex];
        const imageUrl = selectedOption.getAttribute('data-image');
        const previewDiv = document.getElementById('productImagePreview');

        if(imageUrl){
            previewDiv.innerHTML = `<img src="${imageUrl}" alt="تصویر محصول" class="w-32 h-20 object-cover rounded border">`;
        } else {
            previewDiv.innerHTML = '';
        }
    }
</script>
