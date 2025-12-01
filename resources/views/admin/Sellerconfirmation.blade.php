@include('admin.upad')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container mx-auto p-6">
    <h1 class="text-2xl mb-6">لیست تأیید فروشندگان</h1>

    <!-- فرم سرچ -->
    <form method="GET" action="{{ route('Seller_confirmation') }}" class="mb-6">
        <input
            type="text"
            name="search"
            value="{{ old('search', $search) }}"
            placeholder="جستجو بر اساس نام، کد ملی، موبایل یا ایمیل..."
            class="border border-gray-300 rounded px-4 py-2 w-full max-w-md"
        />
    </form>

    <!-- تب‌ها -->
    <ul class="flex border-b mb-4 flex-wrap">
        @foreach($statuses as $key => $label)
            <li class="mr-1">
                <button
                    class="tab-btn px-4 py-2 font-semibold border rounded-t focus:outline-none"
                    data-tab="{{ $key }}"
                    type="button"
                >{{ $label }}</button>
            </li>
        @endforeach
    </ul>


    <!-- محتوای تب‌ها -->
    @foreach($statuses as $key => $label)
        <div id="tab-{{ $key }}" class="tab-content hidden">
            <h2 class="text-xl mb-2">{{ $label }}</h2>

            @php
                // تفکیک نوع و وضعیت
                [$type, $status] = explode('_', $key);
                $is_legal = ($type === 'individual') ? 0 : 1;
                $filtered = $sellers->where('status', $status)->where('is_legal', $is_legal);
            @endphp

            @if($filtered->isEmpty())
                <p>هیچ داده‌ای موجود نیست.</p>
            @else
                <table class="w-full border-collapse border border-gray-300">
                    <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-gray-300 px-3 py-1">ID</th>
                        <th class="border border-gray-300 px-3 py-1">نام</th>
                        <th class="border border-gray-300 px-3 py-1">کد ملی</th>
                        <th class="border border-gray-300 px-3 py-1">موبایل</th>
                        <th class="border border-gray-300 px-3 py-1">ایمیل</th>
                        <th class="border border-gray-300 px-3 py-1">وضعیت</th>
                        <th class="border border-gray-300 px-3 py-1">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($filtered as $seller)
                        <tr>
                            <td class="border border-gray-300 px-3 py-1">{{ $seller->id }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $seller->name ?? '-' }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $seller->national_code ?? '-' }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $seller->mobile ?? '-' }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $seller->email ?? '-' }}</td>
                            <td class="border border-gray-300 px-3 py-1 capitalize">{{ $seller->status }}</td>
                            <td class="border border-gray-300 px-3 py-1">
                                <button
                                    class="bg-blue-500 text-white px-2 py-1 rounded"
                                    onclick='openModal({{ json_encode([
                                            "id" => $seller->id,
                                            "name" => $seller->name,
                                            "national_code" => $seller->national_code,
                                            "mobile" => $seller->mobile,
                                            "email" => $seller->email,
                                            "national_card_file" => $seller->national_card_file,
                                            "business_license_file" => $seller->business_license_file,
                                            "status" => $seller->status
                                        ]) }})'
                                >مشاهده</button>
                                <button
                                    class="bg-red-500 text-white px-2 py-1 rounded"
                                    onclick="deleteSeller({{ $seller->id }})"
                                >حذف</button>

                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    <!-- پاپ‌آپ نمایش اطلاعات -->
    <div id="sellerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg max-w-lg w-full">
            <h2 class="text-xl mb-4">جزئیات فروشنده</h2>
            <div id="modalContent"></div>
            <div class="mt-4">
                <select id="statusSelect" class="border border-gray-300 rounded px-4 py-2 w-full">
                    <option value="pending">در حال بررسی</option>
                    <option value="approved">تأیید شده</option>
                    <option value="rejected">رد شده</option>
                </select>
            </div>
            <div class="mt-4 flex justify-end">
                <button id="saveStatus" class="bg-blue-500 text-white px-4 py-2 rounded mr-2">ذخیره</button>
                <button id="closeModal" class="bg-gray-300 px-4 py-2 rounded">بستن</button>
            </div>
        </div>
    </div>
</div>

<script>
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    const modal = document.getElementById('sellerModal');
    const modalContent = document.getElementById('modalContent');
    const closeModal = document.getElementById('closeModal');
    const saveStatus = document.getElementById('saveStatus');
    const statusSelect = document.getElementById('statusSelect');
    let currentSellerId = null;

    function hideAllTabs() {
        contents.forEach(c => c.classList.add('hidden'));
        tabs.forEach(t => t.classList.remove('border', 'border-b-0', 'rounded-t', 'bg-white', 'font-bold'));
    }

    function showTab(tabName) {
        hideAllTabs();
        document.getElementById('tab-' + tabName).classList.remove('hidden');
        const activeTab = Array.from(tabs).find(t => t.dataset.tab === tabName);
        if (activeTab) {
            activeTab.classList.add('border', 'border-b-0', 'rounded-t', 'bg-white', 'font-bold');
        }
    }
    function deleteSeller(id) {
        if (!confirm('آیا از حذف این فروشنده اطمینان دارید؟')) return;

        fetch(`/admindashboard/seller/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                window.location.reload();
            })
            .catch(error => console.error('Error:', error));
    }


    function openModal(seller) {
        modalContent.innerHTML = `
            <p><strong>نام:</strong> ${seller.name || '-'}</p>
            <p><strong>کد ملی:</strong> ${seller.national_code || '-'}</p>
            <p><strong>موبایل:</strong> ${seller.mobile || '-'}</p>
            <p><strong>ایمیل:</strong> ${seller.email || '-'}</p>
            ${seller.national_card_file ? `<p><strong>کارت ملی:</strong> <a href="/${seller.national_card_file}" target="_blank">مشاهده</a></p>` : ''}
            ${seller.business_license_file ? `<p><strong>مجوز کسب‌وکار:</strong> <a href="/${seller.business_license_file}" target="_blank">مشاهده</a></p>` : ''}
        `;
        statusSelect.value = seller.status;
        currentSellerId = seller.id;
        modal.classList.remove('hidden');
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            showTab(tab.dataset.tab);
        });
    });

    closeModal.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    saveStatus.addEventListener('click', () => {
        fetch(`/admindashboard/seller/${currentSellerId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: statusSelect.value })
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                window.location.reload();
            })
            .catch(error => console.error('Error:', error));
    });

    // نمایش تب پیش‌فرض
    showTab('individual_pending');
</script>
