@include('admin.upad')

<div class="container mt-4">
    <div class="mb-4">
        <ul class="nav nav-pills nav-fill">
            <li class="nav-item">
                <a class="nav-link disabled" href="#">مرحله ۱: اطلاعات آزمون</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="#">مرحله ۲: بخش‌های آزمون</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" href="#">مرحله ۳: سوالات</a>
            </li>
        </ul>
    </div>

    <h2 class="mb-4">ایجاد بخش جدید برای آزمون</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.bankexans.parts.store') }}">
        @csrf
        <div class="form-group mb-3">
            <label for="exam_id">انتخاب آزمون</label>
            <select name="exam_id" id="exam_id" class="form-control" required>
                <option value="">-- انتخاب آزمون --</option>
                @foreach($exams as $exam)
                    <option value="{{ $exam->id }}" {{ old('exam_id', $exam_id) == $exam->id ? 'selected' : '' }}>{{ $exam->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="border rounded p-3 mb-4 bg-light">
            <h5>اطلاعات بخش آزمون</h5>

            <div class="form-group mb-3">
                <label for="number">شماره بخش</label>
                <input type="number" name="number" id="number" class="form-control" min="1" value="{{ old('number') }}" required>
                <small class="form-text text-muted">شماره بخش برای ترتیب نمایش (مثلاً 1 برای اولین بخش).</small>
            </div>

            <div class="form-group mb-3">
                <label for="title">عنوان بخش</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
            </div>

            <div class="form-group mb-3">
                <label for="questionType">نوع سوال</label>
                <select name="questionType" id="questionType" class="form-control" required>
                    <option value="">-- انتخاب نوع سوال --</option>
                    <option value="welcome" {{ old('questionType') == 'welcome' ? 'selected' : '' }}>Welcome</option>
                    <option value="cloze_test" {{ old('questionType') == 'cloze_test' ? 'selected' : '' }}>Cloze Test</option>
                    <option value="listening" {{ old('questionType') == 'listening' ? 'selected' : '' }}>Listening</option>
                    <option value="reading" {{ old('questionType') == 'reading' ? 'selected' : '' }}>Reading</option>
                    <option value="speaking" {{ old('questionType') == 'speaking' ? 'selected' : '' }}>Speaking</option>
                    <option value="writing" {{ old('questionType') == 'writing' ? 'selected' : '' }}>Writing</option>
                </select>
            </div>

            <div class="form-group mb-3" id="passenger_group" style="display: none;">
                <label for="passenger">متن ریدینگ</label>
                <textarea name="passenger" id="passenger" class="form-control" rows="4">{{ old('passenger') }}</textarea>
            </div>

            <div class="form-group mb-3">
                <label for="description">توضیحات</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group mb-3">
                <label for="center_message">پیام مرکزی (اختیاری)</label>
                <textarea name="center_message" id="center_message" class="form-control" rows="2">{{ old('center_message') }}</textarea>
            </div>

            <div class="form-group mb-3">
                <label for="duration">مدت زمان (HH:MM:SS)</label>
                <input type="time" name="duration" id="duration" class="form-control" step="1" value="{{ old('duration') }}" required>
            </div>

            <div class="form-group mb-3">
                <label for="order">ترتیب نمایش</label>
                <input type="number" name="order" id="order" class="form-control" value="{{ old('order', 1) }}" min="1" required>
            </div>

            <div class="form-group mb-3">
                <label for="passenger_title">عنوان مخصوص سوال خواندنی (اختیاری)</label>
                <textarea name="passenger_title" id="passenger_title" class="form-control" rows="2">{{ old('passenger_title') }}</textarea>
            </div>

            <div class="form-group mb-3">
                <label for="questions_title">عنوان سوالات (اختیاری)</label>
                <textarea name="questions_title" id="questions_title" class="form-control" rows="2">{{ old('questions_title') }}</textarea>
            </div>

            <div class="form-group mb-3">
                <label for="multiple_correct">چند پاسخ درست دارد؟</label>
                <select name="multiple_correct" id="multiple_correct" class="form-control" required>
                    <option value="0" {{ old('multiple_correct') == '0' ? 'selected' : '' }}>خیر</option>
                    <option value="1" {{ old('multiple_correct') == '1' ? 'selected' : '' }}>بله</option>
                </select>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-success">ثبت بخش و افزودن سوال</button>
            <a href="{{ route('admin.bankexans') }}" class="btn btn-secondary">بازگشت به لیست آزمون‌ها</a>
        </div>
    </form>
</div>

<script>
    document.getElementById('questionType').addEventListener('change', function () {
        const passengerGroup = document.getElementById('passenger_group');
        if (this.value === 'reading') {
            passengerGroup.style.display = 'block';
            document.getElementById('passenger').setAttribute('required', 'required');
        } else {
            passengerGroup.style.display = 'none';
            document.getElementById('passenger').removeAttribute('required');
        }
    });
    document.getElementById('questionType').dispatchEvent(new Event('change'));
</script>
@include('admin.upad')

