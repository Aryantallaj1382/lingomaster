@include('admin.upad')

<style>
    /* استایل فرم */
    .news-form {
        border: 1px solid #ccc;
        padding: 20px;
        margin-bottom: 30px;
        background: #f9f9f9;
        border-radius: 8px;
    }
    .news-form h2 {
        margin-bottom: 15px;
        font-weight: bold;
        color: #333;
    }
    .news-form label {
        font-weight: 600;
        display: block;
        margin-bottom: 5px;
        color: #555;
    }
    .news-form input[type="text"],
    .news-form select,
    .news-form textarea,
    .news-form input[type="number"] {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        margin-bottom: 15px;
        font-size: 14px;
        font-family: inherit;
        color: #333;
        resize: vertical;
    }
    .news-form input[type="file"] {
        margin-bottom: 15px;
    }
    .news-form button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 18px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .news-form button:hover {
        background-color: #0056b3;
    }
    .news-form a.cancel-btn {
        margin-left: 15px;
        color: #555;
        text-decoration: none;
        font-weight: 600;
    }
    .news-form a.cancel-btn:hover {
        text-decoration: underline;
    }

    /* استایل پیام موفقیت */
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 12px 20px;
        border-radius: 6px;
        margin-bottom: 25px;
        border: 1px solid #c3e6cb;
        font-weight: 600;
    }

    /* استایل جدول */
    table.news-table {
        width: 100%;
        border-collapse: collapse;
        font-family: inherit;
    }
    table.news-table th,
    table.news-table td {
        border: 1px solid #ddd;
        padding: 12px 15px;
        text-align: center;
        vertical-align: middle;
    }
    table.news-table th {
        background-color: #f1f1f1;
        font-weight: 700;
        color: #333;
    }
    table.news-table tr:nth-child(even) {
        background-color: #fafafa;
    }
    table.news-table tr:hover {
        background-color: #f0f8ff;
    }
    table.news-table img {
        max-width: 100px;
        border-radius: 4px;
    }

    /* دکمه حذف داخل جدول */
    .delete-button {
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        font-weight: 700;
        font-size: 14px;
        padding: 0;
    }
    .delete-button:hover {
        text-decoration: underline;
    }

    /* لینک ویرایش داخل جدول */
    .edit-link {
        color: #007bff;
        font-weight: 600;
        text-decoration: none;
        margin-right: 8px;
    }
    .edit-link:hover {
        text-decoration: underline;
    }
</style>

<h1>مدیریت اخبار</h1>

@if(session('success'))
    <div class="success-message">{{ session('success') }}</div>
@endif

{{-- فرم ایجاد/ویرایش --}}
<div class="news-form">
    <h2>{{ isset($editNews) ? 'ویرایش خبر' : 'ایجاد خبر جدید' }}</h2>

    <form action="{{ route('news.store_update') }}" method="POST" enctype="multipart/form-data" id="news-form">
        @csrf

        @if(isset($editNews))
            <input type="hidden" name="id" value="{{ $editNews->id }}">
        @endif

        <label>عنوان:</label>
        <input type="text" name="title" value="{{ old('title', $editNews->title ?? '') }}" required>

        <label>تصویر (jpg, png):</label>
        <input type="file" name="image" accept="image/*">
        @if(isset($editNews) && $editNews->image)
            <br>
            <img src="{{ $editNews->image }}" alt="Current Image">
        @endif

        <label>برچسب‌ها (با کاما جدا کنید):</label>
        <input type="text" id="tags-input" name="tag" value="{{ old('tag', isset($editNews->tag) ? implode(', ', json_decode($editNews->tag, true) ?? []) : '') }}" placeholder="برچسب‌ها را با کاما جدا کنید">

        <label>متن خبر:</label>
        <textarea id="text-editor" name="text" rows="8">{{ old('text', $editNews->text ?? '') }}</textarea>

        <label>اولویت (1 تا 10):</label>
        <select name="order" required>
            @php
                $currentOrder = old('order', $editNews->order ?? 1);
            @endphp
            @for($i = 1; $i <= 10; $i++)
                <option value="{{ $i }}" {{ $currentOrder == $i ? 'selected' : '' }}>{{ $i }}</option>
            @endfor
        </select>

        <button type="submit">{{ isset($editNews) ? 'به‌روزرسانی' : 'ذخیره' }}</button>
        @if(isset($editNews))
            <a href="{{ route('news') }}" class="cancel-btn">انصراف</a>
        @endif
    </form>
</div>

{{-- لیست اخبار --}}
<table class="news-table">
    <thead>
    <tr>
        <th>ردیف</th>
        <th>عنوان</th>
        <th>تصویر</th>
        <th>برچسب‌ها</th>
        <th>اولویت</th>
        <th>بازدید</th>
        <th>عملیات</th>
    </tr>
    </thead>
    <tbody>
    @forelse($news as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->title }}</td>
            <td>
                @if($item->image)
                    <img src="{{ $item->image }}" alt="image">
                @else
                    ندارد
                @endif
            </td>
            <td>
                @if($item->tag)
                    @php
                        $tags = json_decode($item->tag, true) ?? [];
                    @endphp
                    @foreach($tags as $tag)
                        <span style="display:inline-block; background:#e0e0e0; border-radius:4px; padding:2px 6px; margin:2px; font-size:12px;">{{ trim($tag) }}</span>
                    @endforeach
                @else
                    ندارد
                @endif
            </td>
            <td>{{ $item->order }}</td>
            <td>{{ $item->view }}</td>
            <td>
                <a href="{{ route('news', $item->id) }}" class="edit-link">ویرایش</a> |
                <form action="{{ route('news.destroy', $item->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('آیا مطمئنید؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="delete-button">حذف</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" style="text-align: center;">هیچ خبری وجود ندارد.</td></tr>
    @endforelse
    </tbody>
</table>

{{-- CKEditor با غیرفعال کردن آپلود ویدیو --}}
<script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
<script>
    CKEDITOR.replace('text-editor', {
        removePlugins: 'video,flash',
        extraAllowedContent: 'img[!src,alt,width,height];p;h1;h2;h3;strong;em;ul;ol;li;blockquote',
        filebrowserUploadUrl: '', // غیرفعال کردن آپلود فایل
        filebrowserUploadMethod: 'form'
    });

    // تبدیل تگ‌ها به JSON قبل از ارسال فرم
    document.getElementById('news-form').addEventListener('submit', function(e) {
        const tagsInput = document.getElementById('tags-input');
        const tags = tagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
        tagsInput.value = JSON.stringify(tags);
    });
</script>
