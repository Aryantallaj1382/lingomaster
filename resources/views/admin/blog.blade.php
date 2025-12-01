@include('admin.upad')

<div class="container py-4" dir="rtl" style="text-align: center;">
    <h4 class="mb-4">مدیریت بلاگ‌ها</h4>

    <!-- فرم افزودن بلاگ -->
    <form method="POST" action="{{ route('add_blog.add') }}" enctype="multipart/form-data" class="mb-5 border p-4 rounded bg-light text-center" style="direction: rtl;">
        @csrf
        <h5 class="mb-3">افزودن بلاگ جدید</h5>
        <div class="row g-3 justify-content-center">
            <div class="col-md-6 mx-auto">
                <input type="text" name="title" class="form-control text-center" placeholder="عنوان" required>
            </div>
            <div class="col-md-6 mx-auto">
                <input type="text" name="tags" class="form-control text-center" placeholder="تگ‌ها (با کاما جدا کنید)">
            </div>
            <div class="col-md-6 mx-auto">
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="col-md-6 mx-auto">
                <input type="text" name="time_for_read" class="form-control text-center" placeholder="مدت زمان مطالعه">
            </div>
            <div class="col-md-12 mx-auto">
                <input type="text" name="about_blog" class="form-control text-center" placeholder="توضیح کوتاه">
            </div>
            <div class="col-md-12 mx-auto">
                <div id="addBlogEditor" style="direction: rtl; text-align: right; min-height: 300px;"></div>
                <textarea name="text" id="addBlogEditorContent" class="d-none"></textarea>
            </div>
            <div class="col-md-4 mx-auto">
                <select name="status" class="form-select text-center" required>
                    <option value="pending">در حال بررسی</option>
                    <option value="approved">منتشر شده</option>
                    <option value="rejected">رد شده</option>
                </select>
            </div>
            <div class="col-md-12 text-center mt-3">
                <button type="submit" class="btn btn-success px-4">ثبت بلاگ</button>
            </div>
        </div>
    </form>

    <!-- تب وضعیت‌ها -->
    <ul class="nav nav-tabs mb-3 justify-content-center" id="blogTab" role="tablist" style="direction: rtl;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="true">منتشر شده</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">در حال بررسی</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab" aria-controls="rejected" aria-selected="false">رد شده</button>
        </li>
    </ul>

    <!-- تب محتوا -->
    <div class="tab-content" id="blogTabContent" style="direction: rtl;">
        @foreach(['approved' => $approvedBlogs, 'pending' => $pendingBlogs, 'rejected' => $rejectedBlogs] as $status => $blogs)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $status }}" role="tabpanel" aria-labelledby="{{ $status }}-tab">
                @if($blogs->isEmpty())
                    <p class="text-muted my-3">هیچ بلاگی در این بخش موجود نیست.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle">
                            <thead class="table-dark">
                            <tr>
                                <th>عنوان</th>
                                <th>ارسال‌کننده</th>
                                <th>تصویر</th>
                                <th>تگ‌ها</th>
                                <th>توضیح</th>
                                <th>زمان مطالعه</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($blogs as $blog)
                                @php
                                    $tagsArray = is_array($blog->tags) ? $blog->tags : explode(',', $blog->tags ?? '');
                                @endphp
                                <tr>
                                    <td>{{ $blog->title }}</td>
                                    <td>{{ $blog->user->name ?? 'کاربر ناشناس' }}</td>
                                    <td>
                                        @if($blog->image)
                                            <img src="{{ asset($blog->image) }}" alt="تصویر" width="60" class="rounded">
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($tagsArray))
                                            @foreach($tagsArray as $tag)
                                                <span class="badge bg-primary mx-1">{{ trim($tag) }}</span>
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $blog->about_blog }}</td>
                                    <td>{{ $blog->time_for_read }}</td>
                                    <td>{{ $blog->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info mb-1" data-bs-toggle="modal" data-bs-target="#viewModal{{ $blog->id }}">نمایش</button>
                                        <button class="btn btn-sm btn-warning mb-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $blog->id }}">ویرایش</button>
                                        <form action="{{ route('blog.destroy', $blog->id) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا مطمئن هستید؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger mb-1">حذف</button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal نمایش بلاگ -->
{{--                                <div class="modal fade" id="viewModal{{ $blog->id }}" tabindex="-1" aria-labelledby="viewModalLabel{{ $blog->id }}" aria-hidden="true" dir="rtl">--}}
{{--                                    <div class="modal-dialog modal-dialog-centered modal-lg">--}}
{{--                                        <div class="modal-content text-center" style="direction: rtl;">--}}
{{--                                            <div class="modal-header justify-content-center">--}}
{{--                                                <h5 class="modal-title" id="viewModalLabel{{ $blog->id }}">جزئیات بلاگ</h5>--}}
{{--                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>--}}
{{--                                            </div>--}}
{{--                                 --}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}

                                <!-- Modal دلیل رد -->
                                <div class="modal fade" id="rejectReasonModal{{ $blog->id }}" tabindex="-1" aria-labelledby="rejectReasonLabel{{ $blog->id }}" aria-hidden="true" dir="rtl">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-center" style="direction: rtl;">
                                            <form method="POST" action="{{ route('blog.reject', $blog->id) }}">
                                                @csrf
                                                <div class="modal-header justify-content-center">
                                                    <h5 class="modal-title" id="rejectReasonLabel{{ $blog->id }}">دلیل رد بلاگ</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <textarea name="reason" class="form-control" rows="3" placeholder="دلیل رد را وارد کنید..." required style="direction: rtl; text-align: right;"></textarea>
                                                </div>
                                                <div class="modal-footer justify-content-center">
                                                    <button class="btn btn-danger" type="submit">رد کردن</button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal ویرایش -->
                                <div class="modal fade" id="editModal{{ $blog->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $blog->id }}" aria-hidden="true" dir="rtl">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content text-center" style="direction: rtl;">
                                            <div class="modal-header justify-content-center">
                                                <h5 class="modal-title" id="editModalLabel{{ $blog->id }}">ویرایش بلاگ</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                                            </div>
                                            <form method="POST" action="{{ route('blog.update', $blog->id) }}" enctype="multipart/form-data" style="text-align: center;">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <input type="text" name="title" class="form-control text-center" value="{{ $blog->title }}" required placeholder="عنوان">
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="text" name="tags" class="form-control text-center" value="{{ implode(',', $tagsArray) }}" placeholder="تگ‌ها (با کاما جدا کنید)">
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="file" name="image" class="form-control" accept="image/*">
                                                        @if($blog->image)
                                                            <img src="{{ asset($blog->image) }}" alt="تصویر فعلی" class="img-fluid rounded mt-2" style="max-height: 150px;">
                                                        @endif
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="text" name="time_for_read" class="form-control text-center" value="{{ $blog->time_for_read }}" placeholder="مدت زمان مطالعه">
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="text" name="about_blog" class="form-control text-center" value="{{ $blog->about_blog }}" placeholder="توضیح کوتاه">
                                                    </div>
                                                    <div class="mb-3">
                                                        <div id="editBlogEditor{{ $blog->id }}" style="direction: rtl; text-align: right; min-height: 300px;"></div>
                                                        <textarea name="text" id="editBlogEditorContent{{ $blog->id }}" class="d-none">{{ $blog->text }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <select name="status" class="form-select text-center" required>
                                                            <option value="pending" @selected($blog->status == 'pending')>در حال بررسی</option>
                                                            <option value="approved" @selected($blog->status == 'approved')>منتشر شده</option>
                                                            <option value="rejected" @selected($blog->status == 'rejected')>رد شده</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer justify-content-center">
                                                    <button class="btn btn-primary px-4" type="submit">ذخیره تغییرات</button>
                                                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">انصراف</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<!-- CKEditor 5 JS -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/translations/fa.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // تابع راه‌اندازی CKEditor
        function initCKEditor(containerId, textareaId, initialContent = '') {
            ClassicEditor.create(document.querySelector(containerId), {
                language: 'fa',
                ui: {
                    direction: 'rtl'
                },
                toolbar: {
                    items: [
                        'heading', '|',
                        'fontSize', '|',
                        'bold', 'italic', 'strikethrough', '|',
                        'bulletedList', 'numberedList', '|',
                        'outdent', 'indent', '|',
                        'blockQuote', 'imageUpload', 'link', '|',
                        'code', 'codeBlock', '|',
                        'undo', 'redo'
                    ]
                },
                fontSize: {
                    options: [10, 12, 14, 'default', 16, 18, 20, 24, 30, 36],
                    supportAllValues: false
                },
                image: {
                    toolbar: ['imageTextAlternative', 'imageStyle:full', 'imageStyle:side']
                },
                ckfinder: {
                    uploadUrl: '{{ route("upload.image") }}?_token={{ csrf_token() }}'
                },
                placeholder: 'متن خود را اینجا وارد کنید...',
                removePlugins: ['MediaEmbed'],
            })
                .then(editor => {
                    if (initialContent) {
                        editor.setData(initialContent);
                    }
                    const textarea = document.querySelector(textareaId);
                    const form = textarea.closest('form');

                    form.addEventListener('submit', () => {
                        textarea.value = editor.getData();
                    });

                    editor.model.document.on('change', () => {
                        textarea.value = editor.getData();
                    });
                })
                .catch(error => {
                    console.error('Error initializing CKEditor:', error);
                });
        }

        // راه‌اندازی CKEditor برای فرم افزودن
        if (document.querySelector('#addBlogEditor')) {
            initCKEditor('#addBlogEditor', '#addBlogEditorContent');
        }

        // راه‌اندازی CKEditor برای مودال‌های ویرایش
        @foreach(array_merge($approvedBlogs->all(), $pendingBlogs->all(), $rejectedBlogs->all()) as $blog)
        document.querySelector('#editModal{{ $blog->id }}').addEventListener('shown.bs.modal', function () {
            const containerId = '#editBlogEditor{{ $blog->id }}';
            const textareaId = '#editBlogEditorContent{{ $blog->id }}';

            if (!document.querySelector(containerId).hasAttribute('data-ckeditor-initialized')) {
                initCKEditor(
                    containerId,
                    textareaId,
                    `{!! addslashes($blog->text) !!}`
                );
                document.querySelector(containerId).setAttribute('data-ckeditor-initialized', 'true');
            }
        });
        @endforeach
    });
</script>
