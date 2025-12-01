@include('admin.upad')

<div class="container py-4">
    <h2 class="mb-4">مدیریت تیکت‌ها</h2>

    <ul class="nav nav-tabs" id="departmentTabs" role="tablist">
        @foreach($departments as $index => $department)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="tab-{{ $index }}-tab" data-bs-toggle="tab" data-bs-target="#tab-{{ $index }}" type="button" role="tab">
                    {{ $department }}
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content mt-3" id="departmentTabsContent">
        @foreach($departments as $index => $department)
            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="tab-{{ $index }}" role="tabpanel">

                <ul class="nav nav-pills mb-3" id="statusTabs-{{ $index }}" role="tablist">
                    @foreach($statuses as $sIndex => $status)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $sIndex === 0 ? 'active' : '' }}" id="status-{{ $index }}-{{ $sIndex }}-tab"
                                    data-bs-toggle="tab" data-bs-target="#status-{{ $index }}-{{ $sIndex }}"
                                    type="button" role="tab">
                                {{ strtoupper($status) }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    @foreach($statuses as $sIndex => $status)
                        <div class="tab-pane fade {{ $sIndex === 0 ? 'show active' : '' }}" id="status-{{ $index }}-{{ $sIndex }}" role="tabpanel">
                            @php $tickets = $ticketsGrouped[$department][$status]; @endphp

                            @if($tickets->isEmpty())
                                <div class="alert alert-info">تیکتی یافت نشد.</div>
                            @else
                                <table class="table table-striped table-bordered">
                                    <thead>
                                    <tr>
                                        <th>شماره تیکت</th>
                                        <th>نام کاربر</th>
                                        <th>موضوع</th>
                                        <th>وضعیت</th>
                                        <th>تاریخ ایجاد</th>
                                        <th>عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($tickets as $ticket)
                                        <tr>
                                            <td>{{ $ticket->ticket_number }}</td>
                                            <td>
                                                @isset($ticket->user)
                                                    {{ $ticket->user->first_name ?? '' }} {{ $ticket->user->last_name ?? '' }}
                                                @else
                                                    کاربر ناشناس
                                                @endisset
                                            </td>                                            <td>{{ $ticket->subject }}</td>
                                            <td>{{ $ticket->status }}</td>
                                            <td>{{ jdate($ticket->created_at)->format('Y/m/d H:i') }}</td>
                                            <td>
                                                <button class="btn btn-primary btn-sm" onclick="showTicketModal({{ $ticket->id }})">مشاهده</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @endforeach
                </div>

            </div>
        @endforeach
    </div>
</div>

<!-- Modal نمایش چت تیکت -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketModalLabel">تیکت <span id="modalTicketNumber"></span> - <span id="modalSubject"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body" id="messagesContainer" style="min-height:300px; max-height:400px; overflow-y:auto;">
                <!-- پیام‌ها اینجا لود می‌شوند -->
            </div>
            <div class="modal-footer">
                <form id="replyForm" method="POST" action="">
                    @csrf
                    <div class="mb-3">
                        <textarea class="form-control" id="replyMessage" name="message" rows="3" placeholder="پاسخ خود را وارد کنید"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">ارسال پاسخ</button>
                </form>
                <form id="closeTicketForm" method="POST" action="">
                    @csrf
                    <button type="submit" class="btn btn-danger">بستن تیکت</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentTicketId = null;

    function showTicketModal(ticketId) {
        currentTicketId = ticketId;

        fetch(`/admin/tickets/${ticketId}`)
            .then(res => {
                if (!res.ok) throw new Error('تیکت پیدا نشد');
                return res.json();
            })
            .then(data => {
                document.getElementById('modalTicketNumber').innerText = data.ticket_number;
                document.getElementById('modalSubject').innerText = data.subject;

                const messagesContainer = document.getElementById('messagesContainer');
                messagesContainer.innerHTML = '';

                data.messages.forEach(msg => {
                    const msgDiv = document.createElement('div');
                    msgDiv.classList.add('message');
                    msgDiv.style.textAlign = msg.is_support_reply ? 'right' : 'left';
                    msgDiv.style.margin = '10px';
                    msgDiv.style.padding = '8px';
                    msgDiv.style.borderRadius = '8px';
                    msgDiv.style.backgroundColor = msg.is_support_reply ? '#d1e7dd' : '#f8d7da';

                    if (msg.file) {
                        if (/\.(jpeg|jpg|gif|png|bmp|webp)$/i.test(msg.file)) {
                            const img = document.createElement('img');
                            img.src = msg.file;
                            img.style.maxWidth = '200px';
                            img.style.display = 'block';
                            img.style.marginBottom = '5px';
                            img.onerror = () => {
                                img.alt = 'تصویر بارگذاری نشد';
                                img.src = '/path/to/fallback-image.jpg';
                            };
                            msgDiv.appendChild(img);
                        } else {
                            const fileLink = document.createElement('a');
                            fileLink.href = msg.file;
                            fileLink.innerText = 'فایل پیوست';
                            fileLink.target = '_blank';
                            fileLink.style.display = 'block';
                            fileLink.style.marginBottom = '5px';
                            msgDiv.appendChild(fileLink);
                        }
                    }

                    const text = document.createElement('p');
                    text.innerText = msg.message;
                    msgDiv.appendChild(text);

                    const info = document.createElement('small');
                    info.innerText = `${msg.user_name} - ${msg.created_at}`;
                    msgDiv.appendChild(info);

                    messagesContainer.appendChild(msgDiv);
                });

                // تنظیم آدرس فرم‌ها
                document.getElementById('closeTicketForm').action = `/admin/tickets/${ticketId}/close`;
                document.getElementById('replyForm').action = `/admin/tickets/${ticketId}/reply`;

                var modal = new bootstrap.Modal(document.getElementById('ticketModal'));
                modal.show();
            })
            .catch(err => {
                alert(err.message);
            });
    }

    // ارسال بستن تیکت Ajax
    document.getElementById('closeTicketForm').addEventListener('submit', function(e) {
        e.preventDefault();

        if (!confirm("آیا مطمئن هستید می‌خواهید تیکت را ببندید؟")) return;

        fetch(this.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'تیکت با موفقیت بسته شد.');
                location.reload();
            })
            .catch(() => alert('خطا در بستن تیکت.'));
    });

    // ارسال پاسخ Ajax
    document.getElementById('replyForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const message = document.getElementById('replyMessage').value.trim();
        if (!message) {
            alert('لطفا پیام خود را وارد کنید.');
            return;
        }

        fetch(this.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message }),
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('پیام شما ارسال شد.');
                    document.getElementById('replyMessage').value = '';
                    showTicketModal(currentTicketId); // رفرش پیام‌ها
                } else {
                    alert(data.message || 'خطا در ارسال پیام');
                }
            })
            .catch(() => alert('خطا در ارسال پیام'));
    });
</script>
