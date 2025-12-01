@include('admin.upad')

<div class="container mx-auto p-6">
    <h1 class="text-2xl mb-6">لیست سفارش‌های پرداخت‌شده فروشنده</h1>

    <table class="w-full border-collapse border border-gray-300">
        <thead>
        <tr class="bg-gray-200">
            <th class="border px-3 py-2">ID</th>
            <th class="border px-3 py-2">کاربر</th>
            <th class="border px-3 py-2">کد پیگیری</th>
            <th class="border px-3 py-2">وضعیت مرجوعی</th>
            <th class="border px-3 py-2">عملیات</th>
        </tr>
        </thead>
        <tbody>
        @foreach($orders as $order)
            <tr>
                <td class="border px-3 py-2">{{ $order->id }}</td>
                <td class="border px-3 py-2">{{ $order->user->name ?? '-' }}</td>
                <td class="border px-3 py-2">{{ $order->tracking_code ?? '-' }}</td>
                <td class="border px-3 py-2">{{ $order->returned_status ?? 'ندارد' }}</td>
                <td class="border px-3 py-2">
                    <button
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1 rounded openModal"
                        data-order='@json($order)'
                    >
                        جزئیات
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="orderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded shadow max-w-4xl w-full relative overflow-auto max-h-[90vh]">
        <button id="closeModal" class="absolute top-2 right-2 text-gray-500 hover:text-black">&times;</button>
        <h2 class="text-xl mb-4 font-bold">جزئیات سفارش</h2>

        <div id="orderDetails" class="space-y-4"></div>

        <form method="POST" action="{{ route('admin.orders.update') }}" class="mt-4">
            @csrf
            <input type="hidden" name="order_id" id="order_id">

            <div class="mb-4">
                <label class="block mb-1">کد پیگیری</label>
                <input type="text" name="tracking_code" id="tracking_code"
                       class="w-full border px-4 py-2 rounded">
            </div>

            <div id="returnSection" class="mb-4 hidden">
                <h3 class="font-bold mb-2">درخواست مرجوعی</h3>
                <p><strong>دلیل مرجوعی کاربر:</strong> <span id="return_reason"></span></p>
                <p><strong>ویدیو مرجوعی:</strong> <a href="#" target="_blank" id="return_video_link">مشاهده</a></p>

                <label class="block mt-2 mb-1">دلیل تایید/رد مرجوعی</label>
                <textarea name="seller_rejection_reason" rows="3"
                          class="w-full border px-4 py-2 rounded"></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="submit"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    ذخیره تغییرات
                </button>
                <button type="button" id="cancelModal"
                        class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('orderModal');
    const closeModal = document.getElementById('closeModal');
    const cancelModal = document.getElementById('cancelModal');
    const openBtns = document.querySelectorAll('.openModal');

    openBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const order = JSON.parse(btn.dataset.order);

            // پر کردن فرم
            document.getElementById('order_id').value = order.id;
            document.getElementById('tracking_code').value = order.tracking_code ?? '';

            // اطلاعات کلی سفارش
            const orderInfoHtml = `
                <div class="border-b pb-2 mb-2">
                    <h3 class="font-bold mb-1">اطلاعات سفارش</h3>
                    <p><strong>کد سفارش:</strong> ${order.order_code ?? '-'}</p>
                    <p><strong>وضعیت پرداخت:</strong> ${order.payment_status ?? '-'}</p>
                    <p><strong>وضعیت تایید فروشنده:</strong> ${order.seller_approval_status ?? '-'}</p>
                    <p><strong>مجموع قیمت:</strong> ${order.total_price?.toLocaleString() ?? '-'} تومان</p>
                    <p><strong>قیمت نهایی:</strong> ${order.final_price?.toLocaleString() ?? '-'} تومان</p>
                    <p><strong>روش پرداخت:</strong> ${order.payment_method ?? '-'}</p>
                    <p><strong>روش ارسال:</strong> ${order.sending_method ?? '-'}</p>
                    <p><strong>تاریخ تحویل:</strong> ${order.delivery_date ?? '-'}</p>
                    <p><strong>تاریخ مرجوعی:</strong> ${order.returned_date ?? '-'}</p>
                    <p><strong>امتیاز محصول:</strong> ${order.product_satisfaction ?? '-'}</p>
                    <p><strong>امتیاز ارسال:</strong> ${order.delivery_rating ?? '-'}</p>
                </div>
            `;

            // محصولات
            const itemsHtml = (order.items || []).map(item => `
                <div class="border-b pb-2 mb-2">
                    <p><strong>محصول:</strong> ${item.product?.title ?? '-'}</p>
                    ${item.product?.image ? `<img src="${item.product.image}" class="w-20 h-20 object-cover">` : ''}
                    <p><strong>تعداد:</strong> ${item.quantity ?? '-'}</p>
                    <p><strong>قیمت واحد:</strong> ${item.price?.toLocaleString() ?? '-'} تومان</p>
                    <p><strong>قیمت کل آیتم:</strong> ${item.total_price?.toLocaleString() ?? '-'} تومان</p>
                </div>
            `).join('');

            // آدرس خریدار
            let buyerAddressHtml = '';
            if (order.buyer_address) {
                const a = order.buyer_address;
                buyerAddressHtml = `
                    <div class="border-b pb-2 mb-2">
                        <h3 class="font-bold mb-1">آدرس خریدار</h3>
                        <p><strong>عنوان:</strong> ${a.title ?? '-'}</p>
                        <p><strong>کشور:</strong> ${a.country_id ?? '-'}</p>
                        <p><strong>شهر:</strong> ${a.city_id ?? '-'}</p>
                        <p><strong>منطقه:</strong> ${a.region ?? '-'}</p>
                        <p><strong>آدرس:</strong> ${a.address ?? '-'}</p>
                        <p><strong>پلاک:</strong> ${a.plaque ?? '-'}</p>
                        <p><strong>واحد:</strong> ${a.unit ?? '-'}</p>
                        <p><strong>کد پستی:</strong> ${a.postal_code ?? '-'}</p>
                        <p><strong>موبایل:</strong> ${a.mobile ?? '-'}</p>
                        <p><strong>مختصات:</strong> ${a.latitude ?? '-'}, ${a.longitude ?? '-'}</p>
                    </div>
                `;
            }

            // کاربر
            let userHtml = '';
            if (order.user) {
                const u = order.user;
                userHtml = `
                    <div class="border-b pb-2 mb-2">
                        <h3 class="font-bold mb-1">اطلاعات کاربر</h3>
                        <p><strong>نام:</strong> ${u.name ?? '-'}</p>
                        <p><strong>ایمیل:</strong> ${u.email ?? '-'}</p>
                        <p><strong>موبایل:</strong> ${u.mobile ?? '-'}</p>
                    </div>
                `;
            }

            document.getElementById('orderDetails').innerHTML =
                userHtml + buyerAddressHtml + orderInfoHtml + `<h3 class="font-bold mb-2">محصولات:</h3>` + itemsHtml;

            // بخش مرجوعی
            if (order.returned_status === 'pending') {
                document.getElementById('returnSection').classList.remove('hidden');
                document.getElementById('return_reason').innerText = order.return_reason ?? '-';
                document.getElementById('return_video_link').href = order.return_video_path ?? '#';
            } else {
                document.getElementById('returnSection').classList.add('hidden');
            }

            modal.classList.remove('hidden');
        });
    });

    closeModal.addEventListener('click', () => modal.classList.add('hidden'));
    cancelModal.addEventListener('click', () => modal.classList.add('hidden'));

    window.addEventListener('click', e => {
        if (e.target === modal) modal.classList.add('hidden');
    });
</script>
