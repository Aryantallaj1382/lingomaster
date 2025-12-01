
    @include('admin.upad')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- فرم افزودن کد تخفیف --}}
    <div class="card mb-4">
        <div class="card-header">افزودن کد تخفیف جدید</div>
        <div class="card-body">
            <form action="{{ route('discount.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="code" class="form-label">کد تخفیف</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label">نوع تخفیف</label>
                        <select name="type" class="form-select" required>
                            <option value="percentage">درصدی</option>
                            <option value="amount">ریالی</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="value" class="form-label">مقدار</label>
                        <input type="number" name="value" class="form-control" required min="1">
                    </div>
                    <div class="col-md-2">
                        <label for="usage_limit" class="form-label">حداکثر استفاده</label>
                        <input type="number" name="usage_limit" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">فعال؟</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="start_at" class="form-label">تاریخ شروع</label>
                        <input type="date" name="start_at" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="expires_at" class="form-label">تاریخ پایان</label>
                        <input type="date" name="expires_at" class="form-control">
                    </div>
                    <div class="col-md-6 d-flex align-items-end justify-content-end">
                        <button type="submit" class="btn btn-primary mt-2">افزودن</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- لیست کدهای تخفیف --}}
    <div class="card">
        <div class="card-header">لیست کدهای تخفیف</div>
        <div class="card-body">
            @if($discounts->isEmpty())
                <p class="text-center">هیچ کد تخفیفی وجود ندارد.</p>
            @else
                <table class="table table-striped text-center align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>کد</th>
                        <th>نوع</th>
                        <th>مقدار</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ پایان</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($discounts as $discount)
                        <tr>
                            <td>{{ $discount->code }}</td>
                            <td>{{ $discount->type == 'percentage' ? 'درصدی' : 'ریالی' }}</td>
                            <td>{{ $discount->value }} {{ $discount->type == 'percentage' ? '%' : 'ریال' }}</td>
                            <td>{{ $discount->start_at ?? '---' }}</td>
                            <td>{{ $discount->expires_at ?? '---' }}</td>
                            <td>{{ $discount->is_active ? 'فعال' : 'غیرفعال' }}</td>
                            <td>
                                <!-- دکمه ویرایش -->
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#editModal{{ $discount->id }}">
                                    ویرایش
                                </button>

                                <!-- فرم حذف -->
                                <form action="{{ route('discount.destroy', $discount->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>

                        {{-- Modal ویرایش --}}
                        <div class="modal fade" id="editModal{{ $discount->id }}" tabindex="-1"
                             aria-labelledby="editModalLabel{{ $discount->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="{{ route('discount.update', $discount->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel{{ $discount->id }}">ویرایش کد تخفیف</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body row">
                                            <div class="col-md-4">
                                                <label class="form-label">کد</label>
                                                <input type="text" name="code" class="form-control" value="{{ $discount->code }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">نوع</label>
                                                <select name="type" class="form-select" required>
                                                    <option value="percentage" @if($discount->type == 'percentage') selected @endif>درصدی</option>
                                                    <option value="amount" @if($discount->type == 'amount') selected @endif>ریالی</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">مقدار</label>
                                                <input type="number" name="value" class="form-control" value="{{ $discount->value }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">تاریخ شروع</label>
                                                <input type="date" name="start_at" class="form-control" value="{{ $discount->start_at }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">تاریخ پایان</label>
                                                <input type="date" name="expires_at" class="form-control" value="{{ $discount->expires_at }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">محدودیت استفاده</label>
                                                <input type="number" name="usage_limit" class="form-control" value="{{ $discount->usage_limit }}">
                                            </div>
                                            <div class="col-md-4 mt-3">
                                                <div class="form-check form-switch mt-4">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                                           @if($discount->is_active) checked @endif>
                                                    <label class="form-check-label">فعال</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                                            <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        {{-- پایان modal --}}
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

