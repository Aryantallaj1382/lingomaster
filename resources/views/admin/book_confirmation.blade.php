@include('admin.upad')

<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6 text-center">مدیریت تأیید کتاب‌ها</h1>

    <!-- Tabs -->
    <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="myTab" role="tablist">
            <li class="mr-2" role="presentation">
                <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-blue-600 hover:border-blue-600 tab-button" id="confirmed-tab" data-tabs-target="#confirmed" type="button" role="tab" aria-controls="confirmed" aria-selected="true">تأیید شده</button>
            </li>
            <li class="mr-2" role="presentation">
                <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-blue-600 hover:border-blue-600 tab-button" id="rejected-tab" data-tabs-target="#rejected" type="button" role="tab" aria-controls="rejected" aria-selected="false">رد شده</button>
            </li>
            <li class="mr-2" role="presentation">
                <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-blue-600 hover:border-blue-600 tab-button" id="pending-tab" data-tabs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">در حال بررسی</button>
            </li>
        </ul>
    </div>

    <!-- Tab Content -->
    <div id="myTabContent">
        <!-- Confirmed Books -->
        <div class="p-4 bg-white rounded-lg tab-pane" id="confirmed" role="tabpanel" aria-labelledby="confirmed-tab">
            <table class="w-full text-right border-collapse">
                <thead>
                <tr class="bg-gray-200">
                    <th class="p-2 border">عنوان</th>
                    <th class="p-2 border">نویسنده</th>
                    <th class="p-2 border">دسته‌بندی</th>
                    <th class="p-2 border">زبان</th>
                    <th class="p-2 border">وضعیت</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($confirmedBooks as $book)
                    <tr class="hover:bg-gray-100">
                        <td class="p-2 border">{{ $book->title ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->author ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->category->name ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->language->name ?? 'نامشخص' }}</td>
                        <td class="p-2 border text-green-600">تأیید شده</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-2 text-center">هیچ کتاب تأیید شده‌ای یافت نشد.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            {{ $confirmedBooks->links() }}
        </div>

        <!-- Rejected Books -->
        <div class="hidden p-4 bg-white rounded-lg tab-pane" id="rejected" role="tabpanel" aria-labelledby="rejected-tab">
            <table class="w-full text-right border-collapse">
                <thead>
                <tr class="bg-gray-200">
                    <th class="p-2 border">عنوان</th>
                    <th class="p-2 border">نویسنده</th>
                    <th class="p-2 border">دسته‌بندی</th>
                    <th class="p-2 border">زبان</th>
                    <th class="p-2 border">وضعیت</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rejectedBooks as $book)
                    <tr class="hover:bg-gray-100">
                        <td class="p-2 border">{{ $book->title ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->author ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->category->name ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->language->name ?? 'نامشخص' }}</td>
                        <td class="p-2 border text-red-600">رد شده</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-2 text-center">هیچ کتاب رد شده‌ای یافت نشد.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            {{ $rejectedBooks->links() }}
        </div>

        <!-- Pending Books -->
        <div class="hidden p-4 bg-white rounded-lg tab-pane" id="pending" role="tabpanel" aria-labelledby="pending-tab">
            <table class="w-full text-right border-collapse">
                <thead>
                <tr class="bg-gray-200">
                    <th class="p-2 border">عنوان</th>
                    <th class="p-2 border">نویسنده</th>
                    <th class="p- пассажи

System: 2 border">دسته‌بندی</th>
                    <th class="p-2 border">زبان</th>
                    <th class="p-2 border">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($pendingBooks as $book)
                    <tr class="hover:bg-gray-100">
                        <td class="p-2 border">{{ $book->title ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->author ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->category->name ?? 'نامشخص' }}</td>
                        <td class="p-2 border">{{ $book->language->name ?? 'نامشخص' }}</td>
                        <td class="p-2 border">
                            <button class="bg-green-500 text-white px-3 py-1 rounded confirm-book" data-id="{{ $book->id }}">تأیید</button>
                            <button class="bg-red-500 text-white px-3 py-1 rounded reject-book" data-id="{{ $book->id }}">رد</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-2 text-center">هیچ کتاب در حال بررسی یافت نشد.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            {{ $pendingBooks->links() }}

            <!-- Rejection Reason Modal -->
            <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-1/3">
                    <h2 class="text-lg font-bold mb-4">دلیل رد کتاب</h2>
                    <form id="rejectForm" method="POST">
                        @csrf
                        <input type="hidden" name="book_id" id="book_id">
                        <textarea name="reason" id="reason" class="w-full p-2 border rounded" rows="4" placeholder="دلیل رد کتاب را وارد کنید..." required></textarea>
                        <div class="mt-4 flex justify-end">
                            <button type="button" id="cancelReject" class="bg-gray-300 text-black px-4 py-2 rounded mr-2">لغو</button>
                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded">ارسال</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Tab switching logic
        $('#confirmed-tab').addClass('border-blue-600 text-blue-600');
        $('#confirmed').removeClass('hidden');

        $('.tab-button').click(function () {
            $('.tab-pane').addClass('hidden');
            $($(this).data('tabs-target')).removeClass('hidden');
            $('.tab-button').removeClass('border-blue-600 text-blue-600');
            $(this).addClass('border-blue-600 text-blue-600');
        });

        // Confirm book
        $('.confirm-book').click(function () {
            const bookId = $(this).data('id');
            if (confirm('آیا مطمئن هستید که می‌خواهید این کتاب را تأیید کنید؟')) {
                $.ajax({
                    url: '/admindashboard/book/confirm/' + bookId,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        alert('کتاب با موفقیت تأیید شد.');
                        location.reload();
                    },
                    error: function (xhr) {
                        alert('خطا در تأیید کتاب: ' + xhr.responseJSON.message);
                    }
                });
            }
        });

        // Show reject modal
        $('.reject-book').click(function () {
            const bookId = $(this).data('id');
            $('#book_id').val(bookId);
            $('#rejectModal').removeClass('hidden');
        });

        // Cancel reject modal
        $('#cancelReject').click(function () {
            $('#rejectModal').addClass('hidden');
            $('#reason').val('');
            $('#book_id').val('');
        });

        // Submit rejection reason
        $('#rejectForm').submit(function (e) {
            e.preventDefault();
            const bookId = $('#book_id').val();
            const reason = $('#reason').val();

            $.ajax({
                url: '/admindashboard/book/reject/' + bookId,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', reason: reason },
                success: function (response) {
                    alert('کتاب با موفقیت رد شد و اعلان ارسال شد.');
                    $('#rejectModal').addClass('hidden');
                    $('#reason').val('');
                    $('#book_id').val('');
                    location.reload();
                },
                error: function (xhr) {
                    alert('خطا در رد کتاب: ' + xhr.responseJSON.message);
                }
            });
        });
    });
</script>
