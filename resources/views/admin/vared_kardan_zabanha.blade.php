@include('admin.upad')

<div class="container mt-5">
    <h1 class="mb-4 text-center">اضافه کردن زبان</h1>

    <form action="{{ route('languages.store') }}" method="POST" class="mb-5" enctype="multipart/form-data">
        @csrf
        <div class="form-group mb-3">
            <label for="title">عنوان زبان:</label>
            <input type="text" name="title" class="form-control" placeholder="مثلاً: فارسی" required>
        </div>
        <div class="form-group mb-4">
            <label for="flag">پرچم:</label>
            <input type="file" name="flag" class="form-control" accept=".svg" required>
        </div>
        <button type="submit" class="btn btn-success">اضافه کردن زبان</button>
    </form>

    <h2 class="mb-3">لیست زبان‌ها</h2>
    <table class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>عنوان</th>
                <th>پرچم</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($languages as $language)
                <tr>
                    <td>{{ $language->id }}</td>
                    <td>{{ $language->title }}</td>
                    <td>
                        <img src="{{ asset($language->flag) }}" alt="{{ $language->title }}" style="width: 40px; height: 25px;" class="rounded border">
                    </td>
                    <td>
                        <button class="btn btn-primary btn-sm me-1" onclick="openEditModal({{ $language->id }}, '{{ $language->title }}', '{{ asset($language->flag) }}')">ویرایش</button>
                        <form action="{{ route('languages.destroy', $language->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این زبان مطمئن هستید؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal برای ویرایش زبان -->
<div class="modal fade" id="editLanguageModal" tabindex="-1" aria-labelledby="editLanguageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLanguageModalLabel">ویرایش زبان</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editLanguageForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">عنوان زبان:</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_flag" class="form-label">پرچم:</label>
                        <input type="file" class="form-control" id="edit_flag" name="flag" accept=".svg">
                    </div>
                    <img id="current_flag" src="" alt="پرچم" style="width: 40px; height: 25px;" class="rounded border mb-3">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                </form>
            </div>
        </div>
    </div>
</div>

@include('admin.downad')

<script>
    function openEditModal(id, title, flag) {
        // تنظیم داده‌ها در مدال
        document.getElementById('edit_title').value = title;
        document.getElementById('current_flag').src = flag;
        document.getElementById('editLanguageForm').action = '/admindashboard/languages/' + id; // مسیر ویرایش

        // نمایش مدال
        var editModal = new bootstrap.Modal(document.getElementById('editLanguageModal'));
        editModal.show();
    }
</script>

