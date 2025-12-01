@include('admin.upad')

<div class="container mx-auto p-6">
    <h1 class="text-2xl mb-4">مدیریت انتشارات</h1>

    @if(session('success'))
        <div class="mb-4 p-2 bg-green-200 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Form Add -->
    <form action="{{ route('admin.publications.store') }}" method="POST" class="mb-6">
        @csrf
        <div class="flex gap-2">
            <input type="text" name="title" placeholder="عنوان انتشارات" class="border p-2 rounded w-full" required>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">افزودن</button>
        </div>
    </form>

    <!-- Table -->
    <table class="min-w-full bg-white border">
        <thead class="bg-gray-100">
        <tr>
            <th class="border px-4 py-2">ID</th>
            <th class="border px-4 py-2">عنوان</th>
            <th class="border px-4 py-2">عملیات</th>
        </tr>
        </thead>
        <tbody>
        @foreach($publications as $pub)
            <tr>
                <td class="border px-4 py-2">{{ $pub->id }}</td>
                <td class="border px-4 py-2">
                    <form action="{{ route('admin.publications.update', $pub->id) }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="text" name="title" value="{{ $pub->title }}" class="border p-1 rounded w-full" required>
                        <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded">ویرایش</button>
                    </form>
                </td>
                <td class="border px-4 py-2">
                    <form action="{{ route('admin.publications.delete', $pub->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded">حذف</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
