@include('admin.upad')

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    .text-success { color: green; }
    .text-danger { color: red; }
    .text-warning { color: orange; }
    .map-container { height: 300px; width: 100%; border-radius: 8px; margin-top: 10px; }
</style>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@php
    $statuses = [
        'pending' => 'در حال بررسی',
        'approved' => 'تایید شده',
        'rejected' => 'رد شده'
    ];
@endphp

<ul class="nav nav-pills mb-3" id="pill-myTab" role="tablist">
    @foreach($statuses as $key => $title)
        <li class="nav-item">
            <a class="nav-link @if($loop->first) active @endif" id="pill-{{ $key }}-tab" data-bs-toggle="tab"
               href="#pill-tab-{{ $key }}" role="tab" aria-controls="pill-tab-{{ $key }}"
               aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $title }}</a>
        </li>
    @endforeach
</ul>

<div class="tab-content" id="myTabContent">
    @foreach($statuses as $key => $title)
        <div class="tab-pane fade @if($loop->first) show active @endif" id="pill-tab-{{ $key }}" role="tabpanel">
            <h2 class="mb-3">لیست وبینارها - {{ $title }}</h2>

            @php
                $filtered = $groupclass->where('admin_status', $key);
            @endphp

            @if ($filtered->isEmpty())
                <p class="text-center mt-3">وبیناری برای نمایش وجود ندارد.</p>
            @else
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>عنوان</th>
                            <th>مدرس</th>
                            <th>تاریخ شروع</th>
                            <th>ظرفیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filtered as $webinar)
                            <tr>
                                <td>{{ $webinar->subject->title ?? '---' }}</td>
                                <td>{{ $webinar->professor->first_name . " " . $webinar->professor->last_name ?? '---' }}</td>
                                <td>{{ $webinar->start_date }}</td>
                                <td>{{ $webinar->min_capacity }} تا {{ $webinar->max_capacity }}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal{{ $webinar->id }}">
                                        بررسی
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal بررسی -->
                            <div class="modal fade" id="reviewModal{{ $webinar->id }}" tabindex="-1" aria-labelledby="reviewModalLabel{{ $webinar->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                                    <div class="modal-content text-right">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="reviewModalLabel{{ $webinar->id }}">جزئیات کامل وبینار</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <ul class="list-group list-group-flush text-right">
                                                <li class="list-group-item">عنوان: {{ $webinar->subject->title ?? '---' }}</li>
                                                <li class="list-group-item">مدرس: {{ $webinar->professor->first_name . " ". $webinar->professor->last_name ?? '---' }}</li>
                                                <li class="list-group-item">نوع آموزش: {{ $webinar->teachingType->title ?? $webinar->teaching_type_id }}</li>
                                                <li class="list-group-item">جنسیت: {{ $webinar->gender->title ?? $webinar->gender_id }}</li>
                                                <li class="list-group-item">گروه سنی: {{ $webinar->ageGroup->title ?? $webinar->age_group_id }}</li>
                                                <li class="list-group-item">سطح زبان: {{ $webinar->level->title ?? $webinar->level_id }}</li>
                                                <li class="list-group-item">زبان: {{ $webinar->language->title ?? $webinar->language_id }}</li>
                                                <li class="list-group-item">ظرفیت: {{ $webinar->min_capacity }} تا {{ $webinar->max_capacity }}</li>
                                                <li class="list-group-item">تعداد جلسات: {{ $webinar->sessions_count }}</li>
                                                <li class="list-group-item">بازه ساعتی: {{ number_format($webinar->hourly) }}</li>
                                                <li class="list-group-item">روزها: {{ $webinar->days }}</li>
                                                <li class="list-group-item">تاریخ شروع: {{ $webinar->start_date }}</li>
                                                <li class="list-group-item">تاریخ پایان: {{ $webinar->end_date }}</li>
                                                <li class="list-group-item">پلتفرم: {{ $webinar->platform ?? '---' }}</li>

                                                <li class="list-group-item">
                                                    لینک وبینار:
                                                    @if($webinar->class_link)
                                                        <a href="{{ $webinar->class_link }}" target="_blank">مشاهده</a>
                                                    @else
                                                        ندارد
                                                    @endif
                                                </li>

                                                <li class="list-group-item">وضعیت: {{ $statuses[$webinar->admin_status] ?? '---' }}</li>

                                                <li class="list-group-item">قیمت نهایی: {{ number_format($webinar->total_price) }} تومان</li>
                                                <li class="list-group-item">قیمت با تخفیف: {{ number_format($webinar->new_total_price) }} تومان</li>

                                                <li class="list-group-item">
                                                    تصویر:
                                                    @if($webinar->image)
                                                        <div class="mt-2">
                                                            <img src="{{ asset('storage/' . $webinar->image) }}" alt="تصویر" class="img-fluid rounded" style="max-height: 200px;">
                                                        </div>
                                                    @else
                                                        ندارد
                                                    @endif
                                                </li>
                                            </ul>

                                            <!-- فرم تایید -->
                                            <form action="{{ route('tayid_webinar.approve', $webinar->id) }}" method="POST" class="mt-4">
                                                @csrf
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-success">تایید</button>
                                                </div>
                                            </form>

                                            <!-- فرم رد -->
                                            <form action="{{ route('tayid_webinar.reject', $webinar->id) }}" method="POST" class="mt-3">
                                                @csrf
                                                <div class="form-group mb-3">
                                                    <label for="reason{{ $webinar->id }}">دلیل رد (اختیاری):</label>
                                                    <textarea name="reason" id="reason{{ $webinar->id }}" class="form-control" rows="2"></textarea>
                                                </div>
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-danger">رد</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</div>
