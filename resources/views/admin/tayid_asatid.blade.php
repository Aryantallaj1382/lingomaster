@include('admin.upad')

<style>
    .text-success { color: green; }
    .text-danger { color: red; }
    .text-warning { color: orange; }
    .text-right { text-align: right; }
    .form-label { float: right; }
    .sort-icon::after {
        content: '';
        display: inline-block;
        width: 0;
        height: 0;
        margin-right: 5px;
        vertical-align: middle;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
    }
    .sort-asc::after { border-bottom: 4px solid #000; }
    .sort-desc::after { border-top: 4px solid #000; }
</style>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="flex  gap-3 mb-3">
    <div>
        <label for="language" class="form-label">فیلتر بر اساس زبان:</label>
        <select id="language" class="form-select w-auto d-inline-block"
                onchange="window.location.href='{{ route('professors.index') }}?status={{ $status }}&sort={{ $sort }}&direction={{ $direction }}&language=' + this.value">
            <option value="">همه زبان‌ها</option>
            @foreach($languages as $lang)
                <option value="{{ $lang->id }}" {{ $languageId == $lang->id ? 'selected' : '' }}>
                    {{ $lang->title }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div>
    <ul class="nav nav-pills" id="pill-myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="pill-pending-tab" data-bs-toggle="tab" href="#pill-tab-pending"
               role="tab" aria-controls="pill-tab-pending" aria-selected="true">در حال بررسی</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="pill-approved-tab" data-bs-toggle="tab" href="#pill-tab-approved"
               role="tab" aria-controls="pill-tab-approved" aria-selected="false">تایید شده</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="pill-rejected-tab" data-bs-toggle="tab" href="#pill-tab-rejected"
               role="tab" aria-controls="pill-tab-rejected" aria-selected="false">رد شده</a>
        </li>
    </ul>

    <div class="tab-content border p-3 mt-3 bg-white rounded-xl" id="pill-myTabContent">
        @foreach (['pending', 'approved', 'rejected'] as $status)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="pill-tab-{{ $status }}"
                 role="tabpanel" aria-labelledby="pill-{{ $status }}-tab">

                <div class="mb-3 text-right">
                    <label for="order-{{ $status }}" class="form-label">ترتیب نمایش:</label>
                    <select class="form-select w-auto d-inline-block" id="order-{{ $status }}"
                            onchange="window.location.href=this.value">
                        <option value="{{ route('professors.index', ['status' => $status, 'sort' => request('sort'), 'direction' => request('direction'), 'order' => 'desc']) }}"
                            {{ request('order') == 'desc' ? 'selected' : '' }}>جدیدترین</option>
                        <option value="{{ route('professors.index', ['status' => $status, 'sort' => request('sort'), 'direction' => request('direction'), 'order' => 'asc']) }}"
                            {{ request('order') == 'asc' ? 'selected' : '' }}>قدیمی‌ترین</option>
                    </select>
                </div>

                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped text-center align-middle">
                        <thead class="thead-dark">
                        <tr>
                            <th>عکس</th>
                            <th>نام</th>
                            <th>کد معرف</th>
                            <th>موبایل</th>
                            <th>جنسیت</th>
                            <th>پرداخت شده</th>
                            <th>قیمت</th>
                            <th>ایمیل</th>
                            <th>کد شناسایی</th>
                            <th>دانشجو</th>
                            <th>محل اقامت</th>
                            <th>تعداد وبینار</th>
                            <th>کلاس گروهی</th>
                            <th>کلاس خصوصی</th>
                            <th>دانش‌آموز</th>
                            <th>درباره</th>
                            <th>تولد</th>
                            <th>کشور</th>
                            <th>استان</th>
                            <th>شهر</th>
                            <th>سن</th>
                            <th>محل تولد</th>
                            <th>کد ملی</th>
                            <th>متن در باره</th>
                            <th>وضعیت</th>
                            <th>تاریخ ثبت‌نام</th>
                            <th>مدارک</th>
                            <th>اولویت</th>
                            <th>نمایش</th>
                            <th>عملیات</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach ($professors->where('status', $status) as $professor)
                            <tr>
                                <td>
                                    @if ($professor->profile)
                                        <img src="https://api.lingomasters.ir/public/{{ $professor->profile }}"
                                             class="rounded-circle"
                                             style="width:180px; aspect-ratio:1/1; object-fit:cover;">
                                    @else
                                        <span class="text-muted">ندارد</span>
                                    @endif
                                </td>




                                <td>{{ $professor->first_name }} {{ $professor->last_name }}</td>
                                <td>{{ $professor->professor_code ?? '---' }}</td>
                                <td>{{ $professor->mobile ?? '---' }}</td>
                                <td>{{ $professor->professors_gender ?? '---' }}</td>
                                <td>{{ $professor->is_paid ? 'پرداخت شده' : "پرداخت نشده" }}</td>
                                <td>{{ $professor->price ?? '---' }}</td>
                                <td>{{ $professor->email ?? '---' }}</td>
                                <td>{{ $professor->id ?? '---' }}</td>
                                <td>
                                    <span class="{{ $professor->is_student ? 'text-success' : 'text-danger' }}">
                                        {{ $professor->is_student ? 'بله' : 'خیر' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $professor->is_inside ? 'text-success' : 'text-danger' }}">
                                        {{ $professor->is_inside ? 'داخل کشور' : 'خارج کشور' }}
                                    </span>
                                </td>
                                <td>{{ $professor->webinars_count ?? 0 }}</td>
                                <td>{{ $professor->group_classes_count ?? 0 }}</td>
                                <td>{{ $professor->private_classes_count ?? 0 }}</td>
                                <td>{{ $professor->students_count ?? 0 }}</td>
                                <td>{{ Str::limit($professor->about_text, 50) }}</td>
                                <td>{{ $professor->birth ?? '---' }}</td>
                                <td>{{ $professor->country_id ?? '---' }}</td>
                                <td>{{ $professor->province_id ?? '---' }}</td>
                                <td>{{ $professor->city_id ?? '---' }}</td>
                                <td>{{ $professor->age ?? '---' }}</td>
                                <td>{{ $professor->place_of_birth ?? '---' }}</td>
                                <td>{{ $professor->national_code ?? '---' }}</td>
                                <td title="{{ $professor->aboutTexts->first()?->about_text ?? '' }}">
                                    {{ \Illuminate\Support\Str::limit($professor->aboutTexts->first()?->about_text ?? '---', 10) }}
                                </td>
                                <td>
                                    <span class="
                                        {{ $professor->status == 'approved' ? 'text-success' :
                                           ($professor->status == 'rejected' ? 'text-danger' : 'text-warning') }}">
                                        {{ $professor->status == 'approved' ? 'تایید شده' :
                                           ($professor->status == 'rejected' ? 'رد شده' : 'در حال بررسی') }}
                                    </span>
                                </td>
                                <td>{{ $professor->created_at ? $professor->created_at->format('Y-m-d H:i') : '---' }}</td>
                                <td>
                                    @php
                                        $categories = ['local_language_certificate', 'university_degree', 'student_card', 'foreign_language_certificate'];
                                    @endphp
                                    @foreach ($categories as $cat)
                                        @php $images = $professor->attachments->where('category', $cat); @endphp
                                        @if ($images->isNotEmpty())
                                            @foreach ($images as $img)
                                                <img src="https://api.lingomasters.ir/public/{{ $img->file_path }}"
                                                     style="width:80px; height:80px; margin:3px;">
                                            @endforeach
                                        @endif
                                    @endforeach
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('professors.updatePriority', $professor->id) }}" class="d-inline">
                                        @csrf @method('PUT')
                                        <input type="number" name="priority" value="{{ $professor->priority ?? 0 }}"
                                               class="form-control form-control-sm text-center" style="width:80px; display:inline-block;">
                                        <button type="submit" class="btn btn-sm btn-primary mt-1">ذخیره</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('professors.toggleVisibility', $professor->id) }}" class="d-inline">
                                        @csrf @method('PUT')
                                        <button type="submit" class="btn btn-sm {{ $professor->is_visible ? 'btn-success' : 'btn-secondary' }}">
                                            {{ $professor->is_visible ? 'نمایش' : 'عدم نمایش' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('delete.professor', $professor->id) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                    <form method="POST" action="{{ route('approve.professor', $professor->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">تایید</button>
                                    </form>
                                    <form method="POST" action="{{ route('reject.professor', $professor->id) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="reason" value="رد دستی از جدول">
                                        <button type="submit" class="btn btn-warning btn-sm">رد</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        @endforeach
    </div>
</div>

@include('admin.downad')
