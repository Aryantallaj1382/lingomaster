{{-- resources/views/admin/contacts/index.blade.php --}}
@include('admin.upad')

<link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://unpkg.com/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="container py-4">
    <h3 class="mb-4">پیام‌های تماس با ما</h3>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
        <tr>
            <th>نام</th>
            <th>موبایل</th>
            <th>ایمیل</th>
            <th>وضعیت</th>
            <th>تاریخ</th>
            <th>عملیات</th>
        </tr>
        </thead>
        <tbody>
        @foreach($contacts as $contact)
            <tr>
                <td>{{ $contact->first_name }} {{ $contact->last_name }}</td>
                <td>{{ $contact->mobile ?? '---' }}</td>
                <td>{{ $contact->email ?? '---' }}</td>
                <td>
                    @if($contact->status === 'answered')
                        <span class="text-success">دیده شده</span>
                    @else
                        <span class="text-warning">جدید</span>
                    @endif
                </td>
                <td>{{ $contact->created_at->format('Y/m/d H:i') }}</td>
                <td>
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal"
                            data-bs-target="#contactModal{{ $contact->id }}">
                        مشاهده
                    </button>

                    <!-- Modal -->
                    <div class="modal fade" id="contactModal{{ $contact->id }}" tabindex="-1"
                         aria-labelledby="contactModalLabel{{ $contact->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-end">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="contactModalLabel{{ $contact->id }}">
                                        جزئیات پیام
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="بستن"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>نام:</strong> {{ $contact->first_name }} {{ $contact->last_name }}</p>
                                    <p><strong>موبایل:</strong> {{ $contact->mobile ?? '---' }}</p>
                                    <p><strong>ایمیل:</strong> {{ $contact->email ?? '---' }}</p>
                                    <p><strong>متن پیام:</strong><br>{{ $contact->description ?? '---' }}</p>
                                </div>
                                <div class="modal-footer">
                                    <form action="{{ route('admin.contacts.markSeen', $contact->id) }}" method="POST">
                                        @csrf
                                        @if($contact->status === 'new')
                                            <button type="submit" class="btn btn-success">تایید و بستن</button>
                                        @else
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-center mt-4">
        {{ $contacts->links() }}
    </div>
</div>
