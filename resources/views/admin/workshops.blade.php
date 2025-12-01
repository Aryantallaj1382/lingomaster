@include('admin.upad')

<style>
    .text-success { color: green; }
    .text-danger { color: red; }
    .text-warning { color: orange; }
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

<ul class="nav nav-pills mb-3" id="workshopTabs" role="tablist">
    @foreach($statuses as $key => $label)
        <li class="nav-item">
            <a class="nav-link @if($loop->first) active @endif"
               id="tab-{{ $key }}"
               data-bs-toggle="tab"
               href="#content-{{ $key }}"
               role="tab"
               aria-controls="content-{{ $key }}"
               aria-selected="{{ $loop->first ? 'true' : 'false' }}">
               {{ $label }}
            </a>
        </li>
    @endforeach
</ul>

<div class="tab-content" id="workshopTabContent">
    @foreach($statuses as $key => $label)
        <div class="tab-pane fade @if($loop->first) show active @endif"
             id="content-{{ $key }}"
             role="tabpanel"
             aria-labelledby="tab-{{ $key }}">

            <h4 class="mb-3">لیست ورکشاپ‌ها - {{ $label }}</h4>

            @php
                $filtered = $workshops->where('admin_status', $key);
            @endphp

            @if ($filtered->isEmpty())
                <p class="text-center text-muted mt-3">هیچ ورکشاپی در این وضعیت وجود ندارد.</p>
            @else
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>عنوان</th>
                            <th>ظرفیت</th>
                            <th>قیمت</th>
                            <th>تاریخ</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filtered as $item)
                            <tr>
                                <td>{{ $item->title ?? '---' }}</td>
                                <td>{{ $item->min_capacity }} تا {{ $item->max_capacity }}</td>
                                <td>{{ number_format($item->total_price) }} تومان</td>
                                <td>{{ $item->date ?? '---' }}</td>
                                <td>
                                    @switch($item->admin_status)
                                        @case('approved') <span class="text-success">تایید شده</span> @break
                                        @case('pending') <span class="text-warning">در حال بررسی</span> @break
                                        @case('rejected') <span class="text-danger">رد شده</span> @break
                                        @default <span class="text-muted">نامشخص</span>
                                    @endswitch
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal{{ $item->id }}">
                                        مشاهده
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal جزئیات ورکشاپ -->
                            <div class="modal fade" id="modal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">جزئیات ورکشاپ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-end">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item">عنوان کلاس: {{ $item->title ?? '---' }}</li>
                                                <li class="list-group-item">مدرس: {{ $item->professor->first_name . " " . $item->professor->last_name ?? '---' }}</li>
                                                <li class="list-group-item">نوع آموزش: {{ $item->teachingType->title ?? '---' }}</li>
                                                <li class="list-group-item">جنسیت: {{ $item->gender->title ?? '---' }}</li>
                                                <li class="list-group-item">گروه سنی: {{ $item->ageGroup->title ?? '---' }}</li>
                                                <li class="list-group-item">سطح زبان: {{ $item->level->title ?? '---' }}</li>
                                                <li class="list-group-item">زبان: {{ $item->language->title ?? '---' }}</li>
                                                <li class="list-group-item">ظرفیت: {{ $item->min_capacity }} تا {{ $item->max_capacity }}</li>
                                                <li class="list-group-item">بازه ساعتی: {{$item->hour }} ساعت</li>
                                                <li class="list-group-item">روزهای برگزاری: {{ $item->date }}</li>
                                                <li class="list-group-item">مکان/پلتفرم: {{ $item->address->title ?? $item->platform }}</li>

                                                <li class="list-group-item">
                                                    لینک کلاس:
                                                    @if($item->class_link)
                                                        <a href="{{ $item->class_link }}" target="_blank">مشاهده</a>
                                                    @else
                                                        ندارد
                                                    @endif
                                                </li>

                                                <li class="list-group-item">
                                                    وضعیت:
                                                    @switch($item->admin_status)
                                                        @case('pending') در انتظار @break
                                                        @case('approved') تایید شده @break
                                                        @case('rejected') رد شده @break
                                                        @case('reserved') رزرو شده @break
                                                        @case('progress') در حال پیشرفت @break
                                                        @case('completed') تکمیل شده @break
                                                        @case('deleted') حذف شده @break
                                                        @default وضعیت نامشخص
                                                    @endswitch
                                                </li>

                                                <li class="list-group-item">آیا برای استاد است؟ {{ $item->is_professors ? 'بله' : 'خیر' }}</li>
                                                <li class="list-group-item">درصد کل: {{ $item->total_percentage }}%</li>
                                                <li class="list-group-item">قیمت نهایی: {{ number_format($item->total_price) }} تومان</li>
                                                <li class="list-group-item">قیمت نهایی با تخفیف: {{ number_format($item->new_total_price) }} تومان</li>

                                                <li class="list-group-item">
                                                    آدرس:<br>
                                                    {{ $item->address->title ?? '---' }}<br>
                                                    {{ $item->address->address ?? '' }}<br>
                                                    کشور: {{ $item->address->country->title ?? '---' }}<br>
                                                    شهر: {{ $item->address->city->name ?? '---' }}<br>
                                                    @if(!empty($item->address->latitude) && !empty($item->address->longitude))
                                                        مختصات:
                                                        <br>Latitude: {{ $item->address->latitude }}
                                                        <br>Longitude: {{ $item->address->longitude }}

                                                        <!-- نقشه -->
                                                        <div id="map{{ $item->id }}" class="map-container"></div>
                                                    @else
                                                        مختصات ثبت نشده
                                                    @endif
                                                </li>

                                                <li class="list-group-item">
                                                    تصویر کلاس:
                                                    @if($item->image)
                                                        <div class="mt-2">
                                                            <img src="{{ asset('storage/' . $item->image) }}" alt="تصویر کلاس" class="img-fluid rounded" style="max-height: 200px;">
                                                        </div>
                                                    @else
                                                        ندارد
                                                    @endif
                                                </li>
                                            </ul>

                                            <form method="POST" action="{{ route('workshops.approve', $item->id) }}" class="mt-3">
                                                @csrf
                                                <button type="submit" name="action" value="approve" class="btn btn-success">تایید</button>
                                            </form>

                                            <form method="POST" action="{{ route('workshops.reject', $item->id) }}" class="mt-3">
                                                @csrf
                                                <div class="form-group mb-2">
                                                    <label for="reason{{ $item->id }}">دلیل رد:</label>
                                                    <textarea name="reason" id="reason{{ $item->id }}" class="form-control" rows="2"></textarea>
                                                </div>
                                                <button type="submit" name="action" value="reject" class="btn btn-danger">رد</button>
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