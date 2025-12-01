@include('admin.upad')

<div class="container mt-4">
    <div class="mb-4">
        <ul class="nav nav-pills nav-fill">
            <li class="nav-item">
                <a class="nav-link active" href="#">مرحله ۱: اطلاعات آزمون</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" href="#">مرحله ۲: بخش‌های آزمون</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" href="#">مرحله ۳: سوالات</a>
            </li>
        </ul>
    </div>

    <h2 class="mb-4">ایجاد آزمون جدید - مرحله ۱: اطلاعات آزمون</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.bankexans.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="border rounded p-3 mb-4 bg-light">
            <h5>اطلاعات کلی آزمون</h5>

            <div class="form-group mb-3">
                <label for="name">نام آزمون</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="form-group mb-3">
                <label for="description">توضیحات</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group mb-3">
                <label for="skill_id">مهارت</label>
                <select name="skill_id" id="skill_id" class="form-control" required>
                    <option value="">-- انتخاب مهارت --</option>
                    @foreach($skills as $skill)
                        <option value="{{ $skill->id }}" {{ old('skill_id') == $skill->id ? 'selected' : '' }}>{{ $skill->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="language_id">زبان</label>
                <select name="language_id" id="language_id" class="form-control" required>
                    <option value="">-- انتخاب زبان --</option>
                    @foreach($languages as $language)
                        <option value="{{ $language->id }}" {{ old('language_id') == $language->id ? 'selected' : '' }}>{{ $language->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="level">سطح</label>
                <select name="level" id="level" class="form-control" required>
                    <option value="">-- انتخاب سطح --</option>
                    <option value="beginner" {{ old('level') == 'beginner' ? 'selected' : '' }}>مقدماتی</option>
                    <option value="intermediate" {{ old('level') == 'intermediate' ? 'selected' : '' }}>متوسط</option>
                    <option value="hard" {{ old('level') == 'hard' ? 'selected' : '' }}>سخت</option>
                    <option value="advanced" {{ old('level') == 'advanced' ? 'selected' : '' }}>حرفه‌ای</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="type">نوع</label>
                <select name="type" id="type" class="form-control" required>
                    <option value="">-- انتخاب نوع --</option>
                    <option value="konkur" {{ old('type') == 'konkur' ? 'selected' : '' }}>کنکور</option>
                    <option value="masters" {{ old('type') == 'masters' ? 'selected' : '' }}>ارشد</option>
                    <option value="phd" {{ old('type') == 'phd' ? 'selected' : '' }}>دکتری</option>
                    <option value="university" {{ old('type') == 'university' ? 'selected' : '' }}>دانشگاهی</option>
                    <option value="school" {{ old('type') == 'school' ? 'selected' : '' }}>مدرسه</option>
                    <option value="olympiad" {{ old('type') == 'olympiad' ? 'selected' : '' }}>المپیاد</option>
                    <option value="language" {{ old('type') == 'language' ? 'selected' : '' }}>زبان</option>
                    <option value="employment_exam" {{ old('type') == 'employment_exam' ? 'selected' : '' }}>امتحان استخدامی</option>
                    <option value="custom" {{ old('type') == 'custom' ? 'selected' : '' }}>سفارشی</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="duration_minutes">مدت زمان آزمون (دقیقه)</label>
                <select name="duration_minutes" id="duration_minutes" class="form-control" required>
                    <option value="">-- انتخاب مدت زمان --</option>
                    @for ($i = 10; $i <= 240; $i += 5)
                        <option value="{{ $i }}" {{ old('duration_minutes') == $i ? 'selected' : '' }}>{{ $i }} دقیقه</option>
                    @endfor
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="questions_count">تعداد سوالات</label>
                <input type="number" name="questions_count" id="questions_count" class="form-control" min="1" max="1000" value="{{ old('questions_count') }}" required>
            </div>

            <div class="form-group mb-3">
                <label for="is_foreign">نوع آزمون</label>
                <select name="is_foreign" id="is_foreign" class="form-control" required>
                    <option value="">-- انتخاب نوع آزمون --</option>
                    <option value="0" {{ old('is_foreign') == '0' ? 'selected' : '' }}>ایرانی</option>
                    <option value="1" {{ old('is_foreign') == '1' ? 'selected' : '' }}>خارجی</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="is_active">فعال باشد؟</label>
                <select name="is_active" id="is_active" class="form-control" required>
                    <option value="1" {{ old('is_active') == '1' ? 'selected' : '' }}>بله</option>
                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>خیر</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="image">تصویر آزمون</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*" required>
            </div>

            <div class="form-group mb-3">
                <label for="questions">سوالات (JSON)</label>
                <textarea name="questions" id="questions" class="form-control" rows="5" required>{{ old('questions') }}</textarea>
                <small class="form-text text-muted">لطفاً سوالات را به فرمت JSON وارد کنید.</small>
            </div>

            <div class="form-group mb-3">
                <label for="TimeToRead">زمان مطالعه</label>
                <input type="text" name="TimeToRead" id="TimeToRead" class="form-control" value="{{ old('TimeToRead') }}" required>
                <small class="form-text text-muted">لطفاً زمان مطالعه را به فرمت HH:MM:SS وارد کنید.</small>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-success">ثبت و رفتن به مرحله بعد</button>
            <a href="{{ route('admin.bankexans') }}" class="btn btn-secondary">بازگشت</a>
        </div>
    </form>
</div>
