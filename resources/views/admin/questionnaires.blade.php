@php
    // تابع کمکی برای نمایش ایمن آرایه‌ها، رشته‌ها، یا سایر انواع داده
    function safeDisplay($value, $default = '-')
    {
        if (is_null($value)) {
            return $default;
        }
        if (is_array($value)) {
            // مدیریت آرایه‌های چندبعدی
            $flattened = Arr::flatten($value);
            return !empty($flattened) ? implode(', ', array_map('strval', $flattened)) : $default;
        }
        if (is_object($value)) {
            // اگر شیء باشد، به رشته تبدیل شود
            return method_exists($value, '__toString') ? (string) $value : $default;
        }
        return is_scalar($value) ? (string) $value : $default;
    }
@endphp

@include('admin.upad')

<div class="container py-4" dir="rtl" style="text-align: center;">
    <h4 class="mb-4">مدیریت پرسشنامه‌ها</h4>

    <!-- فرم جستجو -->
    <form method="GET" action="{{ route('questionnaires.index') }}" class="mb-4 border p-4 rounded bg-light text-center" style="direction: rtl;">
        <div class="row g-3 justify-content-center">
            <div class="col-md-6 mx-auto">
                <input type="text" name="search" class="form-control text-center" placeholder="جستجو بر اساس نام، تلفن یا کد" value="{{ request('search') }}">
            </div>
            <div class="col-md-2 mx-auto">
                <button type="submit" class="btn btn-primary w-100">جستجو</button>
            </div>
        </div>
    </form>

    <!-- جدول پرسشنامه‌ها -->
    <div class="table-responsive">
        @if($questionnaires->isEmpty())
            <p class="text-muted my-3">هیچ پرسشنامه‌ای یافت نشد.</p>
        @else
            <table class="table table-bordered text-center align-middle">
                <thead class="table-dark">
                <tr>
                    <th>نام</th>
                    <th>تلفن</th>
                    <th>شهر</th>
                    <th>رشته</th>
                    <th>کد</th>
                    <th>تاریخ ثبت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @foreach($questionnaires as $questionnaire)
                    <tr>
                        <td>{{ safeDisplay($questionnaire->first_name) }} {{ safeDisplay($questionnaire->last_name) }}</td>
                        <td>{{ safeDisplay($questionnaire->phone) }}</td>
                        <td>{{ safeDisplay($questionnaire->city) }}</td>
                        <td>{{ safeDisplay($questionnaire->field_of_study) }}</td>
                        <td>{{ safeDisplay($questionnaire->code) }}</td>
                        <td>{{ $questionnaire->created_at->format('Y-m-d') }}</td>
                        <td>
                            <button class="btn btn-sm btn-info mb-1" data-bs-toggle="modal" data-bs-target="#viewModal{{ $questionnaire->id }}">نمایش</button>
                            <form action="{{ route('questionnaires.destroy', $questionnaire->id) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger mb-1">حذف</button>
                            </form>
                        </td>
                    </tr>

                    <!-- Modal نمایش پرسشنامه -->
                    <div class="modal fade" id="viewModal{{ $questionnaire->id }}" tabindex="-1" aria-labelledby="viewModalLabel{{ $questionnaire->id }}" aria-hidden="true" dir="rtl">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content text-center" style="direction: rtl;">
                                <div class="modal-header justify-content-center">
                                    <h5 class="modal-title" id="viewModalLabel{{ $questionnaire->id }}">جزئیات پرسشنامه</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <h4 class="mb-3">{{ safeDisplay($questionnaire->first_name) }} {{ safeDisplay($questionnaire->last_name) }}</h4>
                                    <p><strong>تلفن:</strong> {{ safeDisplay($questionnaire->phone) }}</p>
                                    <p><strong>شهر:</strong> {{ safeDisplay($questionnaire->city) }}</p>
                                    <p><strong>مقطع تحصیلی:</strong> {{ safeDisplay($questionnaire->degree) }}</p>
                                    <p><strong>دانشگاه:</strong> {{ safeDisplay($questionnaire->university_name) }}</p>
                                    <p><strong>تاریخ فارغ‌التحصیلی:</strong> {{ safeDisplay($questionnaire->graduation_date) }}</p>
                                    <p><strong>رشته تحصیلی:</strong> {{ safeDisplay($questionnaire->field_of_study) }}</p>
                                    <p><strong>تخصص‌ها:</strong>
                                        @if($questionnaire->english_teaching) تدریس زبان، @endif
                                        @if($questionnaire->english_literature) ادبیات انگلیسی، @endif
                                        @if($questionnaire->english_translation) ترجمه انگلیسی، @endif
                                        {{ safeDisplay($questionnaire->other_fields) }}
                                    </p>
                                    <p><strong>پلتفرم‌های تدریس آنلاین:</strong> {{ safeDisplay($questionnaire->online_teaching) }}</p>
                                    <p><strong>چالش‌های آنلاین:</strong> {{ safeDisplay($questionnaire->online_challenges) }}</p>
                                    <p><strong>تجربه آفلاین:</strong> {{ safeDisplay($questionnaire->offline_experience) }}</p>
                                    <p><strong>همکاری فعلی:</strong> {{ $questionnaire->is_cooperating_now ? 'بله' : 'خیر' }}</p>
                                    <p><strong>پلتفرم همکاری:</strong> {{ safeDisplay($questionnaire->cooperation_platform) }}</p>
                                    <p><strong>جزئیات همکاری:</strong> {{ safeDisplay($questionnaire->cooperation_details) }}</p>
                                    <p><strong>حقوق مورد انتظار:</strong> {{ safeDisplay($questionnaire->expected_salary) }}</p>
                                    <p><strong>نوع همکاری:</strong> {{ $questionnaire->is_fulltime ? 'تمام‌وقت' : 'پاره‌وقت' }}</p>
                                    <p><strong>ساعات تدریس:</strong> {{ safeDisplay($questionnaire->teaching_hours) }}</p>
                                    <p><strong>گروه‌های سنی:</strong> {{ safeDisplay($questionnaire->age_groups) }}</p>
                                    <p><strong>سطوح تدریس:</strong> {{ safeDisplay($questionnaire->teaching_levels) }}</p>
                                    <p><strong>نوع کلاس:</strong> {{ safeDisplay($questionnaire->class_types) }}</p>
                                    <p><strong>منابع تدریس:</strong> {{ safeDisplay($questionnaire->books_taught) }}</p>
                                    <p><strong>تجربه TTC:</strong> {{ safeDisplay($questionnaire->ttc_experience) }}</p>
                                    <p><strong>مایل به TTC:</strong> {{ $questionnaire->is_willing_ttc ? 'بله' : 'خیر' }}</p>
                                    <p><strong>نوع TTC:</strong> {{ safeDisplay($questionnaire->ttc_type) }}</p>
                                    <p><strong>شروع همکاری:</strong> {{ safeDisplay($questionnaire->available_from) }}</p>
                                    <p><strong>اهداف کوتاه‌مدت:</strong> {{ safeDisplay($questionnaire->short_term_goals) }}</p>
                                    <p><strong>اهداف میان‌مدت:</strong> {{ safeDisplay($questionnaire->mid_term_goals) }}</p>
                                    <p><strong>اهداف بلندمدت:</strong> {{ safeDisplay($questionnaire->long_term_goals) }}</p>
                                    <p><strong>نقاط ضعف:</strong> {{ safeDisplay($questionnaire->weaknesses) }}</p>
                                    <p><strong>نقاط قوت:</strong> {{ safeDisplay($questionnaire->strengths) }}</p>
                                    <p><strong>تدریس عمومی:</strong> {{ $questionnaire->can_teach_general ? 'بله' : 'خیر' }}</p>
                                    <p><strong>تدریس تخصصی:</strong> {{ $questionnaire->can_teach_specialized ? 'بله' : 'خیر' }}</p>
                                    <p><strong>مایل به ارتقا:</strong> {{ $questionnaire->wants_improvement ? 'بله' : 'خیر' }}</p>
                                    <p><strong>کد:</strong> {{ safeDisplay($questionnaire->code) }}</p>
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
