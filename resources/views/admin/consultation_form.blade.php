@include('admin.upad')

<div class="container py-4" dir="rtl" style="text-align: center;">
    <h4 class="mb-4">مدیریت فرم‌های مشاوره</h4>

    <!-- جدول فرم‌های مشاوره -->
    <div class="table-responsive">
        @if($consultations->isEmpty())
            <p class="text-muted my-3">هیچ فرم مشاوره‌ای موجود نیست.</p>
        @else
            <table class="table table-bordered text-center align-middle">
                <thead class="table-dark">
                <tr>
                    <th>نام</th>
                    <th>موبایل</th>
                    <th>زمان</th>
                    <th>توضیحات</th>
                    <th>تاریخ ایجاد</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @foreach($consultations as $consultation)
                    <tr>
                        <td>{{ $consultation->name }}</td>
                        <td>{{ $consultation->mobile }}</td>
                        <td>{{ $consultation->time }}</td>
                        <td>{{ Str::limit($consultation->description, 50) }}</td>
                        <td>{{ $consultation->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <button class="btn btn-sm btn-info mb-1" data-bs-toggle="modal" data-bs-target="#viewModal{{ $consultation->id }}">نمایش</button>
                            <form action="{{ route('consultation.destroy', $consultation->id) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger mb-1">حذف</button>
                            </form>
                        </td>
                    </tr>

                    <!-- Modal نمایش جزئیات -->
                    <div class="modal fade" id="viewModal{{ $consultation->id }}" tabindex="-1" aria-labelledby="viewModalLabel{{ $consultation->id }}" aria-hidden="true" dir="rtl">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content text-center" style="direction: rtl;">
                                <div class="modal-header justify-content-center">
                                    <h5 class="modal-title" id="viewModalLabel{{ $consultation->id }}">جزئیات فرم مشاوره</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <p><strong>نام:</strong> {{ $consultation->name }}</p>
                                    <p><strong>موبایل:</strong> {{ $consultation->mobile }}</p>
                                    <p><strong>زمان:</strong> {{ $consultation->time }}</p>
                                    <p><strong>توضیحات:</strong></p>
                                    <div style="white-space: pre-line; text-align: justify;" class="text-start">{!! $consultation->description !!}</div>
                                    <p><strong>تاریخ ایجاد:</strong> {{ $consultation->created_at->format('Y-m-d H:i') }}</p>
                                </div>
                                <div class="modal-footer justify-content-center">
                                    <button type="button" class="btn btn-secondary mx-1" data-bs-dismiss="modal">بستن</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<!-- اسکریپت‌های موردنیاز -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
