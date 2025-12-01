@include('admin.upad')
<div class="container py-4">
    <h4 class="mb-4">لیست گفتگوها</h4>

    <!-- فرم جستجو -->
    <form method="GET" action="{{ route('admin.conversations.index') }}" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="جستجو بر اساس نام کاربران..." value="{{ request('search') }}">
            <button class="btn btn-primary" type="submit">جستجو</button>
        </div>
    </form>

    <!-- جدول گفتگوها -->
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover text-center">
                <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>کاربر 1</th>
                    <th>کاربر 2</th>
                    <th>آخرین به‌روزرسانی</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @foreach($conversations as $index => $conversation)
                    <tr>
                        <td>{{ $index + $conversations->firstItem() }}</td>
                        <td>{{ $conversation->user1 ? $conversation->user1->first_name . ' ' . $conversation->user1->last_name : 'ناشناس' }}</td>
                        <td>{{ $conversation->user2 ? $conversation->user2->first_name . ' ' . $conversation->user2->last_name : 'ناشناس' }}</td>
                        <td>{{ $conversation->updated_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                    data-bs-target="#chatModal"
                                    onclick="loadMessages({{ $conversation->id }})">
                                نمایش چت
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <!-- صفحه‌بندی -->
            <div class="d-flex justify-content-center mt-3">
                {{ $conversations->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal برای نمایش چت -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">پیام‌های گفتگو</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body" id="chatBox" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center text-muted">در حال بارگذاری...</div>
            </div>
        </div>
    </div>
</div>

<script>
    function loadMessages(conversationId) {
        const chatBox = document.getElementById('chatBox');
        chatBox.innerHTML = '<div class="text-center text-muted">در حال بارگذاری...</div>';

        fetch(`/admindashboard/conversations/${conversationId}/messages`)
            .then(response => response.json())
            .then(data => {
                if (!data.messages.length) {
                    chatBox.innerHTML = '<p class="text-center text-muted">هیچ پیامی یافت نشد.</p>';
                    return;
                }

                chatBox.innerHTML = '';

                data.messages.forEach(msg => {
                    // مشخص می‌کنیم پیام از کاربر 1 است یا 2
                    let alignment, bubbleColor;

                    if (msg.sender_id === data.user1_id) {
                        // پیام کاربر 1 - سمت چپ
                        alignment = 'text-start';
                        bubbleColor = 'bg-light text-dark';
                    } else if (msg.sender_id === data.user2_id) {
                        // پیام کاربر 2 - سمت راست
                        alignment = 'text-end';
                        bubbleColor = 'bg-primary text-white';
                    } else {
                        // اگر فرستنده ناشناس یا غریبه بود وسط چین نمایش بده
                        alignment = 'text-center';
                        bubbleColor = 'bg-secondary text-white';
                    }

                    let content = '';

                    if (msg.type === 'text') {
                        content = `<p class="mb-1">${msg.message}</p>`;
                    } else if (msg.type === 'file' && msg.file) {
                        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
                        const fileExtension = msg.file.split('.').pop().toLowerCase();

                        if (imageExtensions.includes(fileExtension)) {
                            content = `<img src="${msg.file}" alt="تصویر پیوست" class="img-fluid rounded" style="max-height: 200px;">`;
                        } else {
                            content = `<a href="${msg.file}" target="_blank" class="text-info">دانلود فایل</a>`;
                        }
                    } else if (msg.type === 'voice' && msg.voice) {
                        content = `<audio controls src="${msg.voice}" class="w-100"></audio>`;
                    }

                    chatBox.innerHTML += `
                    <div class="mb-3 ${alignment}">
                        <div class="d-inline-block p-2 rounded ${bubbleColor}" style="max-width: 70%;">
                            <strong>${msg.sender}</strong>
                            ${content}
                            <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">${msg.created_at}</small>
                        </div>
                    </div>
                `;
                });

                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => {
                chatBox.innerHTML = '<p class="text-danger">خطا در بارگذاری پیام‌ها.</p>';
                console.error('خطا:', error);
            });
    }
</script>
