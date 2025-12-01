<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاکتور سفارش</title>

    <style>
        @font-face {
            font-family: vazirmatn;
            src: url('{{ resource_path("fonts/Vazirmatn-Regular.ttf") }}');
        }

        body {
            font-family: vazirmatn, sans-serif;
            direction: rtl;
            text-align: right;
            unicode-bidi: bidi-override;
            font-size: 13px;
            line-height: 1.7;
            color: #333;
            margin: 30px;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 5px;
            letter-spacing: 1px;
            color: #2c3e50;
        }

        h2 {
            text-align: center;
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 25px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            color: #555;
        }

        p {
            margin: 6px 0;
        }

        .section {
            margin-bottom: 20px;
        }

        .info {
            background: #f9f9f9;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .text-center {
            text-align: center;
        }

        .total {
            font-size: 15px;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h1>LINGOMASTERS</h1>
<h2>فاکتور سفارش</h2>

<div class="section info">
    <p><strong>کد سفارش:</strong> {{ $order->order_code }}</p>
    <p><strong>شماره فاکتور:</strong> {{ $order->id }}</p>
    <p><strong>تاریخ سفارش:</strong> {{ jdate($order->created_at)->format('%A %d %B %Y') }}</p>
    <p><strong>فروشنده:</strong> {{ $order->seller->name }}</p>
    <p><strong>شماره فروشنده:</strong> {{ $order->seller->mobile }}</p>
    <p><strong>آدرس فروشنده:</strong> {{ $order->seller?->address?->first()?->full_address ?? 'آدرس ندارد' }}</p>
    <p><strong>نام گیرنده:</strong> {{ $order->user->getFullName() }}</p>
    <p><strong>شماره تماس:</strong> {{ $order->user->mobile }}</p>
    <p><strong>آدرس:</strong> {{ $order->buyerAddress->full_address ?? '---' }}</p>
</div>

<table>
    <thead>
    <tr>
        <th class="text-center">ردیف</th>
        <th>نام محصول</th>
        <th class="text-center">تعداد</th>
        <th class="text-center">قیمت واحد</th>
        <th class="text-center">مبلغ کل</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($order->items as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $item->product->title ?? '-' }}</td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-center">{{ number_format($item->unit_price) }} تومان</td>
            <td class="text-center">{{ number_format($item->unit_price * $item->quantity) }} تومان</td>
        </tr>
    @endforeach
    </tbody>
</table>

<p class="total">مبلغ نهایی: {{ number_format($order->total_price) }} تومان</p>

</body>
</html>
