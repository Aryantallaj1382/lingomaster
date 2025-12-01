@include('admin.upad')

<div class="container mx-auto p-6">
    <h1 class="text-2xl mb-4">لیست کیف پول‌ها</h1>

    <!-- فرم سرچ -->
    <form action="{{ route('admin.wallets') }}" method="GET" class="mb-4 flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجوی نام، نام خانوادگی یا موبایل"
               class="border p-2 rounded w-full">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">جستجو</button>
    </form>

    <!-- جدول کیف پول‌ها -->
    <table class="min-w-full bg-white border">
        <thead class="bg-gray-100">
        <tr>
            <th class="border px-4 py-2">ID</th>
            <th class="border px-4 py-2">کاربر</th>
            <th class="border px-4 py-2">شماره موبایل</th>
            <th class="border px-4 py-2">موجودی</th>
        </tr>
        </thead>
        <tbody>
        @forelse($wallets as $wallet)
            <tr>
                <td class="border px-4 py-2">{{ $wallet->id }}</td>
                <td class="border px-4 py-2">{{ $wallet->user->full_name() ?? '-' }}</td>
                <td class="border px-4 py-2">{{ $wallet->user->mobile ?? '-' }}</td>
                <td class="border px-4 py-2">{{ number_format($wallet->balance) }} تومان</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="border px-4 py-2 text-center">موردی یافت نشد.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $wallets->withQueryString()->links() }}
    </div>
</div>
