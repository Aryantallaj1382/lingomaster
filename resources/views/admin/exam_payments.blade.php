@include('admin.upad')

<div class="container mx-auto p-6">
    <h1 class="text-2xl mb-6">لیست پرداخت‌های آزمون</h1>

    <!-- فرم سرچ -->
    <form method="GET" action="{{ route('admin.examPayments') }}" class="mb-6">
        <input
            type="text"
            name="search"
            value="{{ old('search', $search) }}"
            placeholder="جستجو بر اساس نام کاربری، محل آزمون، توضیحات یا وضعیت..."
            class="border border-gray-300 rounded px-4 py-2 w-full max-w-md"
        />
    </form>

    <!-- تب ها -->
    <ul class="flex border-b mb-4">
        @php
            $statuses = ['pending' => 'در انتظار', 'paid' => 'پرداخت شده', 'unpaid' => 'پرداخت نشده'];
        @endphp
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

    @foreach($statuses as $key => $label)
        <div id="tab-{{ $key }}" class="tab-content hidden">
            <h2 class="text-xl mb-2">{{ $label }}</h2>

            @php
                $filtered = $payments->where('status', $key);
            @endphp

            @if($filtered->isEmpty())
                <p>هیچ داده‌ای موجود نیست.</p>
            @else
                <table class="w-full border-collapse border border-gray-300">
                    <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-gray-300 px-3 py-1">ID</th>
                        <th class="border border-gray-300 px-3 py-1">User ID</th>
                        <th class="border border-gray-300 px-3 py-1">Username</th>
                        <th class="border border-gray-300 px-3 py-1">Exam Location</th>
                        <th class="border border-gray-300 px-3 py-1">Exam Date</th>
                        <th class="border border-gray-300 px-3 py-1">Exam Time</th>
                        <th class="border border-gray-300 px-3 py-1">Description</th>
                        <th class="border border-gray-300 px-3 py-1">Status</th>
                        <th class="border border-gray-300 px-3 py-1">Created At</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($filtered as $payment)
                        <tr>
                            <td class="border border-gray-300 px-3 py-1">{{ $payment->id }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $payment->user_id }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $payment->username }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $payment->exam_location }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ \Carbon\Carbon::parse($payment->exam_date)->format('Y-m-d') }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ \Carbon\Carbon::parse($payment->exam_time)->format('H:i') }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $payment->description }}</td>
                            <td class="border border-gray-300 px-3 py-1 capitalize">{{ $payment->status }}</td>
                            <td class="border border-gray-300 px-3 py-1">{{ $payment->created_at }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</div>

<script>
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');

    function hideAll() {
        contents.forEach(c => c.classList.add('hidden'));
        tabs.forEach(t => {
            t.classList.remove('border', 'border-b-0', 'rounded-t', 'bg-white', 'font-bold');
        });
    }

    function showTab(tabName) {
        hideAll();
        document.getElementById('tab-' + tabName).classList.remove('hidden');
        const activeTab = Array.from(tabs).find(t => t.dataset.tab === tabName);
        if (activeTab) {
            activeTab.classList.add('border', 'border-b-0', 'rounded-t', 'bg-white', 'font-bold');
        }
    }

    // Default نمایش تب اول
    showTab('pending');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            showTab(tab.dataset.tab);
        });
    });
</script>
