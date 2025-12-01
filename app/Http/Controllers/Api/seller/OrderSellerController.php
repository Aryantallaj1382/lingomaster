<?php

namespace App\Http\Controllers\Api\seller;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Services\SmsServiceServices;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\order\Order;
use App\Models\order\OrderItem;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

class OrderSellerController extends Controller
{
    public function downloadInvoice(Order $order)
    {
        $order->load(['items.product', 'user', 'buyerAddress']);

        $html = view('pdf.invoice', compact('order'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'vazirmatn',
            'default_font_size' => 12,
            'directionality' => 'rtl',
            'default_font_directory' => resource_path('fonts')
        ]);

        $mpdf->WriteHTML($html);
        return response($mpdf->Output("invoice-{$order->order_code}.pdf", 'S'))
            ->header('Content-Type', 'application/pdf');
    }
    public function exportOrders(Request $request)
    {
        $sellerId = auth()->user()->seller->id;

        return Excel::download(new OrdersExport(
            $sellerId,
            $request->order_code,
            $request->status,
            $request->start_date,
            $request->end_date,
            $request->product_name,
            $request->category_id
        ), 'orders.xlsx');
    }

    public function sellerOrders()
    {
        $sellerId = auth()->user()->seller->id;
        $api = api::api ;

        $baseQuery = Order::where('seller_id', $sellerId)
            ->with(['user', 'items.product.images'])
            ->latest();

        $paid = (clone $baseQuery)
            ->where('status', 'paid')
            ->where('seller_approval_status', 'pending')
            ->take(3)
            ->get();


        $going_ready = (clone $baseQuery)
            ->where('status', 'paid')
            ->where('seller_approval_status', 'approved')
            ->take(3)
            ->get();


        $shipped = (clone $baseQuery)
            ->where('status', 'delivered')
            ->where('seller_approval_status', 'shipped')
            ->take(3)
            ->get();


        $formatOrders = function ($orders) use ($api) {
            return $orders->map(function ($order) use ($api) {
                return [
                    'order_id' => $order->order_code,
                    'tracking_code' => $order->tracking_code,
                    'order_date' => jdate($order->created_at)->format('Y/m/d'),
                    'total_price' => $order->total_price,
                    'address' => $order->address,
                    'ravesh' => $order->sending_method,
                    'customer_name' => $order->user->name,
                    'customer_phone' => $order->user->mobile,
                    'items' => $order->items->map(function ($item) use ($api) {
                        $imagePath = $item->product->images->first()?->path;
                        return [
                            'product_name' => $item->product->title,
                            'quantity' => $item->quantity,
                            'Volume_number' => $item->product->Volume_number,
                            'category' => $item->product->category->title,
                            'image' => $imagePath ? $api . $imagePath : null,
                        ];
                    }),
                ];
            });

        };
        return api_response([
            'paid' => $formatOrders($paid),
            'going_ready' => $formatOrders($going_ready),
            'shipped' => $formatOrders($shipped),
        ]);
    }

    public function send(Request $request , $id)
    {
       $order = Order::find($id);
       $order->update([
           'tracking_code' => $request->code,
           'status' => 'delivered',
           'seller_approval_status' => 'shipped',
       ]);

        $smsService = new SmsServiceServices();

        $n = $order->user->mobile;
        $smsService->sendPatternSms(
            'bcsl4v50u69vmzj',
            $n,
            [
                'name' => $order->user->full_name(),
                'order_code' => $order->order_code,

            ]
        );
       return api_response([],'ارسال شد');
    }

    public function approved(Request $request , $id)
    {
        $order = Order::find($id);
        $order->update([
            'seller_approval_status' => 'approved',
            'status' => 'approved',
        ]);
        $smsService = new SmsServiceServices();

        $n = $order->user->mobile;
        $smsService->sendPatternSms(
            'x7x7ey60mjil8ai',
            $n,
            [
                'name' => $order->user->full_name(),
                'order_code' => $order->order_code,

            ]
        );
        return api_response([],'ارسال شد');
    }

    public function rejected(Request $request , $id)
    {
        $order = Order::find($id);
        $order->update([
            'seller_approval_status' => 'rejected',
            'seller_rejection_reason' => $request->reason ,
        ]);

        $smsService = new SmsServiceServices();

        $n = $order->user->mobile;
         $smsService->sendPatternSms(
            '5vbbj83ogcn5p4g',
            $n,
            [
                'name' => $order->user->full_name(),
                'order_code' => $order->order_code,

            ]
        );
        return api_response([],'ارسال شد');
    }
    public function transaction(Request $request)
    {
        $sellerId = auth()->user()->seller->id;
        $ordersQuery = Order::where('seller_id', $sellerId)
            ->with(['items.product.category', 'user'])
            ->where(function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->latest();

        if ($request->filled('order_code')) {
            $ordersQuery->where('order_code', '=', $request->order_code);
        }

        if ($request->filled('status')) {
            $ordersQuery->where('status', '=', $request->status);
        }

        if ($request->filled('start_date')) {
            $ordersQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $ordersQuery->whereDate('created_at', '<=', $request->end_date);
        }

        $orders = $ordersQuery->paginate();

        $products = $orders->getCollection()->flatMap(function ($order) use ($request) {
            return $order->items->map(function ($item) use ($request) {
                if ($request->filled('product_name') && stripos($item->product->title, $request->product_name) === false) {
                    return null;
                }

                if ($request->filled('category_id') && $item->product->category_id != $request->category_id) {
                    return null;
                }

                return [
                    'title' => $item->product->title,
                    'id' => $item->id,
                    'image' => $item->product->first_image,
                    'product_id' => $item->product->id,
                    'order_id' => $item->order->order_code,
                    'category' => $item->product->category->title,
                    'quantity' => $item->quantity,
                    'price' => $item->total_price,
                    'order_code' => $item->order->order_code,
                    'name' => $item->order->user->first_name . ' ' . $item->order->user->last_name,
                    'order_date' => jdate($item->created_at)->format('Y/m/d'),
                    'status' => $item->order->status,
                ];
            })->filter();
        });

        // افزودن ردیف‌ها (row) به محصولات
        $currentPage = $orders->currentPage(); // شماره صفحه فعلی
        $perPage = $orders->perPage(); // تعداد آیتم‌ها در هر صفحه
        $products = $products->values()->map(function ($product, $index) use ($currentPage, $perPage) {
            $product['row'] = ($currentPage - 1) * $perPage + ($index + 1);
            return $product;
        });

        $paginatedProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $products->forPage($orders->currentPage(), $orders->perPage()),
            $products->count(),
            $orders->perPage(),
            $orders->currentPage(),
            ['path' => $request->url()]
        );

        return response()->json(['response' => $paginatedProducts]);
    }

    public function show($id)
    {
        $item = OrderItem::find($id);
        $order = $item->order_id;
        $a = Order::find($order);
        $items = OrderItem::where('order_id', $order)->get();
        $sending_price = SystemSetting::where('name', 'post')->value('value');
       if ($a->sending_method  == 'post'){
           $p = $sending_price;
       }
       else{
           $p = 'پرداخت در محل';
       }
        $all = [
            'order_id' => $a->order_code,
            'id' => $a->id,
            'order_date' => jdate($a->created_at)->format('Y/m/d'),
            'mobile' => $a->buyerAddress->mobile,
            'address' => $a->buyerAddress->full_address,
            'status' => $a->status,
            'sending_price' => (int)$p,
            'ravesh' => $a->sending_method,
            'total_price' => $a->final_price,
            'items' => $items->map(function ($order) {
                return [
                    'title' => $order->product->title,
                    'quantity' => $order->quantity,
                    'Volume_number' => $order->product->Volume_number,
                    'category' => $order->product->category->title,
                    'image' => $order->product->first_image,
                    'price' => $order->unit_price,
                    'status' => $order->order->status,
                    'id' => $order->product->id,

                ];
            }),

            'returned' => $a->returned_date ? [
                'date' => jdate($a->returned_date)->format('Y/m/d'),
                'video_path' => $a->return_video_path,
                'reason' => $a->return_reason,
                'order_code' => $a->order_code,
            ] : null,
            'cancelled' => $a->status == 'cancelled' ? [
                'date' => jdate($a->updated_at)->format('Y/m/d'),
            ] : null,

        ];
        return api_response($all);

    }
}
