{{-- admin/upad --}}
@include('admin.upad')

<!-- استایل Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .text-success { color: green; }
    .text-danger { color: red; }
    .text-warning { color: orange; }
    .map-container { height: 300px; width: 100%; border-radius: 8px; margin-top: 10px; }
</style>

<!-- پیام‌های موفقیت/خطا -->
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

<!-- تب‌های وضعیت -->
<ul class="nav nav-pills mb-3" id="pill-myTab" role="tablist">
    @foreach($statuses as $key => $title)
        <li class="nav-item">
            <a class="nav-link @if($loop->first) active @endif"
               id="pill-{{ $key }}-tab"
               data-bs-toggle="tab"
               href="#pill-tab-{{ $key }}"
               role="tab"
               aria-controls="pill-tab-{{ $key }}"
               aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ $title }}
            </a>
        </li>
    @endforeach
</ul>

<!-- محتوای تب‌ها -->
<div class="tab-content" id="myTabContent">
    @foreach($statuses as $key => $title)
        <div class="tab-pane fade @if($loop->first) show active @endif" id="pill-tab-{{ $key }}" role="tabpanel">
            <h2 class="mb-3">لیست کلاس‌ها - {{ $title }}</h2>

            @php
                $filtered = $groupclass->where('admin_status', $key);
            @endphp

            @if ($filtered->isEmpty())
                <p class="text-center mt-3">کلاسی برای نمایش وجود ندارد.</p>
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
                        @foreach ($filtered as $groupClass)
                            <tr>
                                <td>{{ $groupClass->subject->title ?? '---' }}</td>
                                <td>{{ $groupClass->professor->first_name . " " . $groupClass->professor->last_name ?? '---' }}</td>
                                <td>{{ $groupClass->start_date }}</td>
                                <td>{{ $groupClass->min_capacity }} تا {{ $groupClass->max_capacity }}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#reviewModal{{ $groupClass->id }}">
                                        بررسی
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal بررسی کلاس -->
                            <div class="modal fade" id="reviewModal{{ $groupClass->id }}" tabindex="-1" aria-labelledby="reviewModalLabel{{ $groupClass->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                                    <div class="modal-content text-right">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="reviewModalLabel{{ $groupClass->id }}">جزئیات کامل کلاس</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            <ul class="list-group list-group-flush text-right">
                                                <li class="list-group-item">عنوان کلاس: {{ $groupClass->subject->title ?? '---' }}</li>
                                                <li class="list-group-item">مدرس: {{ $groupClass->professor->first_name . " " . $groupClass->professor->last_name ?? '---' }}</li>
                                                <li class="list-group-item">نوع آموزش: {{ $groupClass->teachingType->title ?? $groupClass->teaching_type_id }}</li>
                                                <li class="list-group-item">جنسیت: {{ $groupClass->gender->title ?? $groupClass->gender_id }}</li>
                                                <li class="list-group-item">گروه سنی: {{ $groupClass->ageGroup->title ?? $groupClass->age_group_id }}</li>
                                                <li class="list-group-item">سطح زبان: {{ $groupClass->level->title ?? $groupClass->level_id }}</li>
                                                <li class="list-group-item">زبان: {{ $groupClass->language->title ?? $groupClass->language_id }}</li>
                                                <li class="list-group-item">ظرفیت: {{ $groupClass->min_capacity }} تا {{ $groupClass->max_capacity }}</li>
                                                <li class="list-group-item">تعداد جلسات: {{ $groupClass->sessions_count }}</li>
                                                <li class="list-group-item">هزینه ساعتی: {{ number_format($groupClass->hourly) }}</li>
                                                <li class="list-group-item">روزهای برگزاری: {{ $groupClass->days }}</li>
                                                <li class="list-group-item">تاریخ شروع: {{ $groupClass->start_date }}</li>
                                                <li class="list-group-item">تاریخ پایان: {{ $groupClass->end_date }}</li>
                                                <li class="list-group-item">مکان/پلتفرم: {{ $groupClass->address->title ?? $groupClass->platform }}</li>

                                                <li class="list-group-item">
                                                    لینک کلاس:
                                                    @if($groupClass->class_link)
                                                        <a href="{{ $groupClass->class_link }}" target="_blank">مشاهده</a>
                                                    @else
                                                        ندارد
                                                    @endif
                                                </li>

                                                <li class="list-group-item">
                                                    وضعیت: 
                                                    @switch($groupClass->admin_status)
                                                        @case('pending') در حال بررسی @break
                                                        @case('approved') تایید شده @break
                                                        @case('rejected') رد شده @break
                                                        @default نامشخص
                                                    @endswitch
                                                </li>

                                                <li class="list-group-item">برای استاد: {{ $groupClass->is_professors ? 'بله' : 'خیر' }}</li>
                                                <li class="list-group-item">درصد کل: {{ $groupClass->total_percentage }}%</li>
                                                <li class="list-group-item">قیمت کل: {{ number_format($groupClass->total_price) }} تومان</li>
                                                <li class="list-group-item">قیمت با تخفیف: {{ number_format($groupClass->new_total_price) }} تومان</li>

                                                <li class="list-group-item">
                                                    آدرس:<br>
                                                    {{ $groupClass->address->title ?? '---' }}<br>
                                                    {{ $groupClass->address->address ?? '' }}<br>
                                                    کشور: {{ $groupClass->address->country->title ?? '---' }}<br>
                                                    شهر: {{ $groupClass->address->city->name ?? '---' }}<br>
                                                    @if(!empty($groupClass->address->latitude) && !empty($groupClass->address->longitude))
                                                        مختصات:
                                                        <br>Latitude: {{ $groupClass->address->latitude }}
                                                        <br>Longitude: {{ $groupClass->address->longitude }}

                                                        <!-- نقشه -->
                                                        <div id="map{{ $groupClass->id }}" class="map-container"></div>
                                                    @else
                                                        مختصات ثبت نشده
                                                    @endif
                                                </li>

                                                <li class="list-group-item">
                                                    تصویر کلاس:
                                                    @if($groupClass->image)
                                                        <div class="mt-2">
                                                            <img src="{{ asset('storage/' . $groupClass->image) }}" alt="تصویر کلاس" class="img-fluid rounded" style="max-height: 200px;">
                                                        </div>
                                                    @else
                                                        ندارد
                                                    @endif
                                                </li>
                                            </ul>

                                            <!-- فرم تایید -->
                                            @if ($groupClass->admin_status === 'pending' || $groupClass->admin_status === 'rejected')
                                                <form action="{{ route('tayid_klas_goroohi.approve', $groupClass->id) }}" method="POST" class="mt-4">
                                                    @csrf
                                                    <div class="text-end">
                                                        <button type="submit" name="action" value="approve" class="btn btn-success">تایید</button>
                                                    </div>
                                                </form>
                                            @endif

                                            <!-- فرم رد -->
                                            @if ($groupClass->admin_status === 'pending' || $groupClass->admin_status === 'approved')
                                                <form action="{{ route('tayid_klas_goroohi.reject', $groupClass->id) }}" method="POST" class="mt-4">
                                                    @csrf
                                                    <div class="form-group mb-3">
                                                        <label for="reject_reason{{ $groupClass->id }}">دلیل رد (اجباری):</label>
                                                        <textarea name="reject_reason" id="reject_reason{{ $groupClass->id }}" class="form-control" rows="2" required></textarea>
                                                    </div>
                                                    <div class="text-end">
                                                        <button type="submit" name="action" value="reject" class="btn btn-danger">رد</button>
                                                    </div>
                                                </form>
                                            @endif
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

<!-- اسکریپت Leaflet -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        @foreach($groupclass as $groupClass)
            @if(!empty($groupClass->address->latitude) && !empty($groupClass->address->longitude))
                let map{{ $groupClass->id }};
                let initialized{{ $groupClass->id }} = false;
                const modal{{ $groupClass->id }} = document.getElementById('reviewModal{{ $groupClass->id }}');
                modal{{ $groupClass->id }}.addEventListener('shown.bs.modal', function () {
                    if (!initialized{{ $groupClass->id }}) {
                        map{{ $groupClass->id }} = L.map('map{{ $groupClass->id }}').setView(
                            [{{ $groupClass->address->latitude }}, {{ $groupClass->address->longitude }}], 15
                        );
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map{{ $groupClass->id }});
                        L.marker([{{ $groupClass->address->latitude }}, {{ $groupClass->address->longitude }}])
                            .addTo(map{{ $groupClass->id }})
                            .bindPopup("مکان کلاس").openPopup();
                        initialized{{ $groupClass->id }} = true;
                    }
                    setTimeout(() => { map{{ $groupClass->id }}.invalidateSize(); }, 300);
                });
            @endif
        @endforeach
    });
</script>
