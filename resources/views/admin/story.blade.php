@include('admin.upad')

<div class="container mx-auto p-4">
    <h1 class="text-2xl mb-6">مدیریت استوری‌ها</h1>

    @if(session('success'))
        <div class="mb-4 p-2 bg-green-200 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="flex mb-4 border-b">
        <button id="approvedTab" onclick="showTab('approved')" class="px-4 py-2 border-b-2 border-blue-600">تایید شده‌ها</button>
        <button id="pendingTab" onclick="showTab('pending')" class="px-4 py-2">رد شده / در انتظار</button>
    </div>

    <!-- Approved -->
    <div id="approvedContent" class="tab-content">
        <table class="min-w-full border bg-white">
            <thead>
            <tr class="bg-gray-100">
                <th class="border px-4 py-2">ID</th>
                <th class="border px-4 py-2">عنوان</th>
                <th class="border px-4 py-2">ویدیو</th>
                <th class="border px-4 py-2">کاور</th>
                <th class="border px-4 py-2">تاریخ</th>
                <th class="border px-4 py-2">عملیات</th>
            </tr>
            </thead>
            <tbody>
            @foreach($stories->where('is_approved', 1) as $story)
                <tr>
                    <td class="border px-4 py-2">{{ $story->id }}</td>
                    <td class="border px-4 py-2">{{ $story->title }}</td>
                    <td class="border px-4 py-2">
                        <video src="{{ asset($story->video) }}" controls class="w-32"></video>
                    </td>
                    <td class="border px-4 py-2">
                        @if($story->cover)
                            <img src="{{ asset($story->cover) }}" alt="کاور" class="w-20 h-12 object-cover">
                        @else
                            ---
                        @endif
                    </td>
                    <td class="border px-4 py-2">{{ $story->created_at }}</td>
                    <td class="border px-4 py-2">
                        <form action="{{ route('story.reject', $story->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded">رد</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pending or Rejected -->
    <div id="pendingContent" class="tab-content hidden">
        <table class="min-w-full border bg-white">
            <thead>
            <tr class="bg-gray-100">
                <th class="border px-4 py-2">ID</th>
                <th class="border px-4 py-2">عنوان</th>
                <th class="border px-4 py-2">ویدیو</th>
                <th class="border px-4 py-2">کاور</th>
                <th class="border px-4 py-2">وضعیت</th>
                <th class="border px-4 py-2">تاریخ</th>
                <th class="border px-4 py-2">عملیات</th>
            </tr>
            </thead>
            <tbody>
            @foreach($stories->whereIn('is_approved', [0, null]) as $story)
                <tr>
                    <td class="border px-4 py-2">{{ $story->id }}</td>
                    <td class="border px-4 py-2">{{ $story->title }}</td>
                    <td class="border px-4 py-2">
                        <video src="{{ asset($story->video) }}" controls class="w-32"></video>
                    </td>
                    <td class="border px-4 py-2">
                        @if($story->cover)
                            <img src="{{ asset($story->cover) }}" alt="کاور" class="w-20 h-12 object-cover">
                        @else
                            ---
                        @endif
                    </td>
                    <td class="border px-4 py-2">
                        @if(is_null($story->is_approved))
                            در انتظار
                        @else
                            رد شده
                        @endif
                    </td>
                    <td class="border px-4 py-2">{{ $story->created_at }}</td>
                    <td class="border px-4 py-2 flex gap-2">
                        <form action="{{ route('story.approve', $story->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-green-500 text-white rounded">تایید</button>
                        </form>
                        @if(is_null($story->is_approved))
                            <form action="{{ route('story.reject', $story->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded">رد</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    function showTab(tab) {
        document.getElementById('approvedContent').classList.add('hidden');
        document.getElementById('pendingContent').classList.add('hidden');

        document.getElementById('approvedTab').classList.remove('border-blue-600');
        document.getElementById('pendingTab').classList.remove('border-blue-600');

        if (tab === 'approved') {
            document.getElementById('approvedContent').classList.remove('hidden');
            document.getElementById('approvedTab').classList.add('border-blue-600');
        } else {
            document.getElementById('pendingContent').classList.remove('hidden');
            document.getElementById('pendingTab').classList.add('border-blue-600');
        }
    }

    // نمایش پیش‌فرض
    showTab('approved');
</script>
