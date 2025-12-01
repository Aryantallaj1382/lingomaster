<?php

namespace App\Exports;

use App\Models\order\OrderItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $sellerId, $orderCode, $status, $startDate, $endDate, $productName, $categoryId;
    protected $rows = [];

    public function __construct($sellerId, $orderCode = null, $status = null, $startDate = null, $endDate = null, $productName = null, $categoryId = null)
    {
        $this->sellerId = $sellerId;
        $this->orderCode = $orderCode;
        $this->status = $status;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->productName = $productName;
        $this->categoryId = $categoryId;
    }

    public function collection()
    {
        $itemsQuery = OrderItem::whereRelation('order', 'seller_id', $this->sellerId)
            ->whereRelation('order', 'payment_status', 'paid')
            ->with(['product.category', 'order.buyerAddress', 'order.user']);

        if ($this->orderCode) {
            $itemsQuery->whereRelation('order', 'order_code', $this->orderCode);
        }

        if ($this->status) {
            $itemsQuery->whereRelation('order', 'status', $this->status);
        }

        if ($this->startDate) {
            $itemsQuery->whereRelation('order', 'created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $itemsQuery->whereRelation('order', 'created_at', '<=', $this->endDate);
        }
        $items = $itemsQuery->get();

        // فیلتر بر اساس نام محصول یا دسته‌بندی
        $filtered = $items->filter(function ($item) {
            if ($this->productName && stripos($item->product->title ?? '', $this->productName) === false) {
                return false;
            }
            if ($this->categoryId && ($item->product->category_id ?? null) != $this->categoryId) {
                return false;
            }

            return true;
        });

        // ذخیره ردیف‌ها برای رنگ‌بندی
        $rowIndex = 2;
        foreach ($filtered as $item) {
            $this->rows[$rowIndex] = $item->order_id;
            $rowIndex++;
        }

        return $filtered->values();
    }

    // بقیه متدها مثل headings، map، styles تغییر نمی‌کنن


    public function headings(): array
    {
        return [
            'ID',
            'قیمت',
            'تعداد',
            'نام محصول',
            'دسته بندی محصول',
            'آدرس',
            'کد سفارش',
            'نام گیرنده',
            'شماره گیرنده',
            'شماره سفارش'
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->total_price,
            $order->quantity,
            optional($order->product)->title,
            optional($order->product->category)->title,
            optional($order->order->buyerAddress)->full_address,
            optional($order->order)->order_code,
            optional($order->order->user)->getFullName(),
            optional($order->order->user)->mobile,
            $order->order_id,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $colors = ['#FCE4D6', '#D9EAD3', '#D0E0E3', '#F9CB9C', '#EAD1DC', '#FFF2CC'];
        $orderGroups = [];

        // گروهبندی بر اساس order_id
        foreach ($this->rows as $rowIndex => $orderId) {
            $orderGroups[$orderId][] = $rowIndex;
        }

        $i = 0;
        foreach ($orderGroups as $rows) {
            $color = $colors[$i % count($colors)];
            foreach ($rows as $row) {
                $sheet->getStyle("A{$row}:J{$row}")->getFill()->setFillType('solid')->getStartColor()->setRGB(ltrim($color, '#'));
            }
            $i++;
        }

        return [];
    }
}
