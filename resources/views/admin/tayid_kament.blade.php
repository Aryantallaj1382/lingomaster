@include('admin.upad')

<link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://unpkg.com/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@php
    $types = [
        'text' => 'کامنت متنی',
        'audio' => 'کامنت صوتی',
        'video' => 'کامنت ویدیویی',
    ];
@endphp

<ul class="nav nav-tabs mb-3" id="commentTabs" role="tablist">
    @foreach($types as $key => $title)
        <li class="nav-item" role="presentation">
            <button class="nav-link @if($loop->first) active @endif" id="tab-{{ $key }}-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-{{ $key }}" type="button" role="tab"
                    aria-controls="tab-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ $title }}
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content" id="commentTabContent">
    @foreach($types as $key => $title)
        <div class="tab-pane fade @if($loop->first) show active @endif" id="tab-{{ $key }}" role="tabpanel">
            <h4 class="mb-3">{{ $title }}</h4>

            @php $comments = $grouped[$key]; @endphp

            @if ($comments->isEmpty())
                <p class="text-muted text-center">کامنتی برای نمایش وجود ندارد.</p>
            @else
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>کاربر</th>
                        <th>محتوا</th>
                        <th>مربوط به</th>
                        <th>تاریخ</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($comments as $comment)
                        <tr>
                            <td>{{ optional(optional($comment->user)->student)->full_name() ?? '---' }}</td>
                            <td>
                                @if($key === 'text')
                                    {{ $comment->body }}
                                @elseif($key === 'audio')
                                    <audio controls src="{{ asset('storage/' . $comment->voice_url) }}"></audio>
                                @elseif($key === 'video')
                                    <video width="200" controls>
                                        <source src="{{ asset('storage/' . $comment->video_url) }}" type="video/mp4">
                                        مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                                    </video>
                                @endif
                            </td>
                            <td>{{ class_basename($comment->commentable_type) }} #{{ $comment->commentable_id }}</td>
                            <td>{{ \Carbon\Carbon::parse($comment->created_at)->format('Y/m/d H:i') }}</td>
                            <td>
                                @if($comment->admin_status === 'approved')
                                    <span class="text-success">تایید شده</span>
                                @elseif($comment->admin_status === 'rejected')
                                    <span class="text-danger">رد شده</span>
                                @else
                                    <span class="text-warning">در حال بررسی</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('admin.comments.approve', $comment->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">تایید</button>
                                </form>
                                <form action="{{ route('admin.comments.reject', $comment->id) }}" method="POST" class="d-inline ms-2">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="d-flex justify-content-center mt-3">
                    {{ $comments->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    @endforeach
</div>
