@include('admin.upad')
<div class="container mt-4">
    <h2 class="mb-4">لیست بانک آزمون‌ها</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('admin.bankexans.create') }}" class="btn btn-primary mb-3">افزودن آزمون جدید</a>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>نام آزمون</th>
            <th>مهارت</th>
            <th>زبان</th>
            <th>سطح</th>
            <th>نوع</th>
            <th>فعال</th>
        </tr>
        </thead>
        <tbody>
        @foreach($exams as $exam)
            <tr>
                <td>{{ $exam->name }}</td>
                <td>{{ $exam->skill->name ?? 'N/A' }}</td>
                <td>{{ $exam->language->title ?? 'N/A' }}</td>
                <td>{{ $exam->level }}</td>
                <td>{{ $exam->type }}</td>
                <td>{{ $exam->is_active ? 'بله' : 'خیر' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
