@include('admin.upad')

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">لیست تراکنش‌ها</h1>

    <div>
        <ul class="flex border-b">
            @foreach ($types as $type)
                <li class="-mb-px mr-1">
                    <a href="#tab-{{ $type }}" class="bg-white inline-block border-l border-t border-r rounded-t py-2 px-4 text-blue-700 font-semibold">{{ ucfirst($type) }}</a>
                </li>
            @endforeach
        </ul>

        @foreach ($types as $type)
            <div id="tab-{{ $type }}" class="tab-content hidden">
                <div class="mt-4">
                    <ul class="flex space-x-4 mb-4">
                        @foreach ($statuses as $status)
                            <li>
                                <button onclick="showStatus('{{ $type }}', '{{ $status }}')" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                                    {{ ucfirst($status) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    @foreach ($statuses as $status)
                        <div id="tab-{{ $type }}-{{ $status }}" class="status-tab hidden">
                            <table class="min-w-full bg-white border border-gray-200">
                                <thead>
                                <tr>
                                    <th class="border px-4 py-2">#</th>
                                    <th class="border px-4 py-2">نوع</th>
                                    <th class="border px-4 py-2">مبلغ</th>
                                    <th class="border px-4 py-2">مانده بعد</th>
                                    <th class="border px-4 py-2">شرح</th>
                                    <th class="border px-4 py-2">شماره پیگیری</th>
                                    <th class="border px-4 py-2">صاحب حساب</th>
                                    <th class="border px-4 py-2">شماره حساب</th>
                                    <th class="border px-4 py-2">شناسه کیف پول</th>
                                    <th class="border px-4 py-2">نام کاربر</th>
                                    <th class="border px-4 py-2">تاریخ</th>
                                    <th class="border px-4 py-2">عملیات</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($transactions[$type][$status] as $trx)
                                    <tr>
                                        <td class="border px-4 py-2">{{ $trx->id }}</td>
                                        <td class="border px-4 py-2">{{ $trx->type }}</td>
                                        <td class="border px-4 py-2">{{ number_format($trx->amount) }}</td>
                                        <td class="border px-4 py-2">{{ number_format($trx->balance_after) }}</td>
                                        <td class="border px-4 py-2">{{ $trx->description }}</td>
                                        <td class="border px-4 py-2">{{ $trx->authority ?? '-' }}</td>
                                        <td class="border px-4 py-2">{{ $trx->account_owner_name ?? '-' }}</td>
                                        <td class="border px-4 py-2">{{ $trx->account_number ?? '-' }}</td>
                                        <td class="border px-4 py-2">{{ $trx->wallet_id ?? '-' }}</td>
                                        <td class="border px-4 py-2">
                                            {{ $trx->wallet && $trx->wallet->user ? $trx->wallet->user->first_name . ' ' . $trx->wallet->user->last_name : '-' }}
                                        </td>
                                        <td class="border px-4 py-2">{{ jdate($trx->created_at)->format('Y/m/d H:i') }}</td>
                                        <td class="border px-4 py-2">
                                            <button class="px-2 py-1 bg-blue-500 text-white rounded openEditModal"
                                                    data-id="{{ $trx->id }}"
                                                    data-status="{{ $trx->status }}"
                                                    data-authority="{{ $trx->authority ?? '' }}"
                                                    data-account-owner-name="{{ $trx->account_owner_name ?? '' }}"
                                                    data-account-number="{{ $trx->account_number ?? '' }}"
                                                    data-wallet-id="{{ $trx->wallet_id ?? '' }}"
                                                    data-user-name="{{ $trx->wallet && $trx->wallet->user ? $trx->wallet->user->first_name . ' ' . $trx->wallet->user->last_name : '' }}">
                                                ویرایش
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="border px-4 py-2 text-center">تراکنشی وجود ندارد</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded shadow max-w-md w-full relative">
        <button id="closeEditModal" class="absolute top-2 right-2 text-gray-500 hover:text-black">&times;</button>
        <h2 class="text-xl mb-4 font-bold">ویرایش تراکنش</h2>

        <form method="POST" action="{{ route('transactions.update') }}">
            @csrf
            <input type="hidden" name="transaction_id" id="edit_transaction_id">

            <div class="mb-4">
                <label class="block mb-1">وضعیت</label>
                <select name="status" id="edit_status" class="w-full border px-4 py-2 rounded">
                    <option value="pending">در انتظار</option>
                    <option value="approved">تایید شده</option>
                    <option value="rejected">رد شده</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block mb-1">شماره پیگیری</label>
                <input type="text" name="authority" id="edit_authority" class="w-full border px-4 py-2 rounded">
            </div>

            <div class="mb-4">
                <label class="block mb-1">صاحب حساب</label>
                <input type="text" id="edit_account_owner_name" class="w-full border px-4 py-2 rounded bg-gray-100" readonly>
            </div>

            <div class="mb-4">
                <label class="block mb-1">شماره حساب</label>
                <input type="text" id="edit_account_number" class="w-full border px-4 py-2 rounded bg-gray-100" readonly>
            </div>

            <div class="mb-4">
                <label class="block mb-1">شناسه کیف پول</label>
                <input type="number" id="edit_wallet_id" class="w-full border px-4 py-2 rounded bg-gray-100" readonly>
            </div>

            <div class="mb-4">
                <label class="block mb-1">نام کاربر</label>
                <input type="text" id="edit_user_name" class="w-full border px-4 py-2 rounded bg-gray-100" disabled>
            </div>

            <div class="flex justify-end gap-2">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    ذخیره تغییرات
                </button>
                <button type="button" id="cancelEditModal" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const tabs = document.querySelectorAll('.tab-content');
    const links = document.querySelectorAll('ul.flex.border-b a');

    links.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            tabs.forEach(tab => tab.classList.add('hidden'));
            links.forEach(l => l.classList.remove('border-blue-500'));
            const id = link.getAttribute('href').substring(1);
            document.getElementById(id).classList.remove('hidden');
            link.classList.add('border-blue-500');
        });
    });

    function showStatus(type, status) {
        document.querySelectorAll(`#tab-${type} .status-tab`).forEach(tab => tab.classList.add('hidden'));
        document.getElementById(`tab-${type}-${status}`).classList.remove('hidden');
    }

    if (tabs.length) {
        tabs[0].classList.remove('hidden');
        showStatus('{{ $types[0] }}', '{{ $statuses[0] }}');
    }

    const editModal = document.getElementById('editModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const cancelEditModal = document.getElementById('cancelEditModal');
    const openEditBtns = document.querySelectorAll('.openEditModal');

    openEditBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const status = btn.dataset.status;
            const authority = btn.dataset.authority;
            const accountOwnerName = btn.dataset.accountOwnerName || '';
            const accountNumber = btn.dataset.accountNumber || '';
            const walletId = btn.dataset.walletId || '';
            const userName = btn.dataset.userName || '';

            document.getElementById('edit_transaction_id').value = id;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_authority').value = authority;
            document.getElementById('edit_account_owner_name').value = accountOwnerName;
            document.getElementById('edit_account_number').value = accountNumber;
            document.getElementById('edit_wallet_id').value = walletId;
            document.getElementById('edit_user_name').value = userName;

            editModal.classList.remove('hidden');
        });
    });

    closeEditModal.addEventListener('click', () => editModal.classList.add('hidden'));
    cancelEditModal.addEventListener('click', () => editModal.classList.add('hidden'));
    window.addEventListener('click', e => { if (e.target == editModal) editModal.classList.add('hidden'); });
</script>
