@include('admin.upad')

<!-- اضافه کردن استایل Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container mt-5">
    <h1 class="mb-4 text-center">مدیریت لهجه‌ها</h1>

    <form action="{{ route('lahje.store') }}" method="POST" class="mb-5">
        @csrf
        <div class="form-group mb-3">
            <label for="title">عنوان لهجه:</label>
            <input type="text" name="title" class="form-control" placeholder="مثلاً: آمریکایی" required>
        </div>
        <div class="form-group mb-4">
            <label for="languages_id">زبان:</label>
            <select name="languages_id" id="languages_id" class="form-control select2" required>
                <option value="">انتخاب زبان</option>
                @foreach($languages as $language)
                    <option value="{{ $language->id }}">{{ $language->title }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-success">اضافه کردن لهجه</button>
    </form>

    <h2 class="mb-3">لیست لهجه‌ها</h2>
    <table class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>عنوان</th>
                <th>زبان</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lahjes as $lahje)
                <tr>
                    <td>{{ $lahje->id }}</td>
                    <td>{{ $lahje->title }}</td>
                    <td>{{ $lahje->language->title ?? 'نامشخص' }}</td>
                    <td>
                        <button class="btn btn-primary btn-sm me-1"
                            onclick="openEditModal({{ $lahje->id }}, '{{ $lahje->title }}', {{ $lahje->languages_id }})">
                            ویرایش
                        </button>
                        <form action="{{ route('lahje.destroy', $lahje->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این لهجه مطمئن هستید؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal برای ویرایش لهجه -->
<div class="modal fade" id="editLahjeModal" tabindex="-1" aria-labelledby="editLahjeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLahjeModalLabel">ویرایش لهجه</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editLahjeForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">عنوان لهجه:</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_languages_id" class="form-label">زبان:</label>
                        <select name="languages_id" class="form-control select2" id="edit_languages_id" required>
                            <option value="">انتخاب زبان</option>
                            @foreach($languages as $language)
                                <option value="{{ $language->id }}">{{ $language->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                </form>
            </div>
        </div>
    </div>
</div>

@include('admin.downad')

<!-- جاوااسکریپت‌های Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // فعال‌سازی Select2
    $(document).ready(function () {
        $('.select2').select2({
            width: '100%',
            placeholder: "انتخاب زبان",
            dir: "rtl"
        });
    });

    // باز کردن مدال ویرایش
    function openEditModal(id, title, languages_id) {
        $('#edit_title').val(title);
        $('#edit_languages_id').val(languages_id).trigger('change');
        $('#editLahjeForm').attr('action', '/admindashboard/vared_kardan_lahje/' + id);

        var editModal = new bootstrap.Modal(document.getElementById('editLahjeModal'));
        editModal.show();
    }
</script>
