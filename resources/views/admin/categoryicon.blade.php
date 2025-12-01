@include('admin.upad')

    <div class="container mx-auto p-6">
        <h1 class="text-2xl mb-6">لیست دسته‌بندی‌ها</h1>

        <!-- فرم سرچ -->
        <form method="GET" action="{{ route('admin.categoryicon') }}" class="mb-6">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="جستجو بر اساس عنوان..."
                class="border border-gray-300 rounded px-4 py-2 w-full max-w-md"
            />
        </form>

        <table class="w-full border-collapse border border-gray-300">
            <thead>
            <tr class="bg-gray-200">
                <th class="border border-gray-300 px-3 py-2">ID</th>
                <th class="border border-gray-300 px-3 py-2">عنوان</th>
                <th class="border border-gray-300 px-3 py-2">آیکون</th>
                <th class="border border-gray-300 px-3 py-2">عملیات</th>
            </tr>
            </thead>
            <tbody>
            @foreach($categories as $category)
                <tr>
                    <td class="border border-gray-300 px-3 py-2">{{ $category->id }}</td>
                    <td class="border border-gray-300 px-3 py-2">{{ $category->title }}</td>
                    <td class="border border-gray-300 px-3 py-2">
                        @if($category->icon)
                            <img src="{{ asset( $category->icon) }}" alt="Icon" class="w-12 h-12">
                        @else
                            <span>بدون آیکون</span>
                        @endif
                    </td>
                    <td class="border border-gray-300 px-3 py-2">
                        <button
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1 rounded modal-btn"
                            data-id="{{ $category->id }}"
                            data-title="{{ $category->title }}"
                        >
                            تغییر آیکون
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded shadow max-w-md w-full relative">
            <button id="closeModal" class="absolute top-2 right-2 text-gray-500 hover:text-black">&times;</button>
            <h2 class="text-xl mb-4 font-bold">تغییر آیکون</h2>
            <form method="POST" action="{{ route('admin.categoryicon.update') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="category_id" id="modal_category_id">
                <p class="mb-4">برای دسته‌بندی: <span id="modal_category_title" class="font-semibold"></span></p>

                <input type="file" name="icon" accept="image/*" required class="mb-4 w-full border border-gray-300 rounded px-4 py-2">

                <div class="flex justify-end gap-2">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        ذخیره تغییرات
                    </button>
                    <button type="button" id="cancelModal" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script>
        const modal = document.getElementById('updateModal');
        const closeModal = document.getElementById('closeModal');
        const cancelModal = document.getElementById('cancelModal');
        const modalBtns = document.querySelectorAll('.modal-btn');

        modalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const title = btn.dataset.title;

                document.getElementById('modal_category_id').value = id;
                document.getElementById('modal_category_title').innerText = title;

                modal.classList.remove('hidden');
            });
        });

        closeModal.addEventListener('click', () => modal.classList.add('hidden'));
        cancelModal.addEventListener('click', () => modal.classList.add('hidden'));

        window.addEventListener('click', (e) => {
            if (e.target == modal) modal.classList.add('hidden');
        });
    </script>
