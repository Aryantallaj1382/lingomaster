<?php

namespace App\Http\Controllers\Api\seller;

use App\Http\Controllers\Controller;
use App\Models\Buyer\Buyer;
use App\Models\contacts\api;
use App\Models\order\Order;
use App\Models\Seller\ProductSeller;
use App\Models\Seller\Seller;
use App\Models\Seller\SellerAddress;
use App\Models\SellerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SellerProfileController extends Controller
{
    public function uploadProfileImage(Request $request)
    {
        $id = auth()->user()->seller->id;
        $user = auth()->user();
        $request->validate([
            'profile' => 'required|file|image|max:2048', // بهتر است اعتبارسنجی تصویر و سایز هم اضافه شود
        ]);

        $Seller = Seller::findOrFail($id);

        if ($request->hasFile('profile')) {
            if ($Seller->profile) {
                Storage::disk('public')->delete($Seller->profile);
            }
            $path = $request->file('profile')->store('profiles/seller', 'public');

            // ذخیره مسیر در دیتابیس
            $Seller->profile = $path;
            $user->profile_image = $path;
            $Seller->save();
            $user->save();
        }

        return api_response([], 'ذخیره شد');
    }


    public function info()
    {
        $user = auth()->user();

        $seller = auth()->user()->seller;
        $item = Seller::find($seller->id);

        return api_response([
            'name' => $item->name,
            'id' => $item->id,
            'experience' => $item->experience,
            'status' => $item->status,
            'is_legal' => $item->is_legal,
            'balance' => $user->wallet->balance ?? 0,
            'profile' => $item->profile ? api::api . $item->profile : null,

        ]);
    }

    public function index()
    {
        $seller = auth()->user()->seller;
        $item = Seller::find($seller->id);

        return api_response([
            'name' => $item->name,
            'national_code' => $item->national_code,
            'mobile' => $item->mobile,
            'landline' => $item->landline,
            'email' => $item->email,
            'is_legal' => (string)$item->is_legal,
            'national_card_file' => $item->national_card_file ? api::api . $item->national_card_file : null,
            'business_license_file' => $item->business_license_file ? api::api . $item->business_license_file : null,
        ]);
    }

    public function store(Request $request)
    {
        $seller = auth()->user()->seller;
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'national_code' => 'required|string|max:20',
            'mobile' => 'required|string|max:20',
            'landline' => 'nullable|string|max:20',
            'email' => 'nullable',
            'is_legal' => 'required',
            'national_card_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
            'business_license_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        if ($request->hasFile('national_card_file')) {
            if ($seller->national_card_file) {
                Storage::disk('public')->delete($seller->national_card_file);
            }
            $path = $request->file('national_card_file')->store('sellers/national_cards', 'public');
            $data['national_card_file'] = $path;
        }

        if ($request->hasFile('business_license_file')) {
            if ($seller->business_license_file) {
                Storage::disk('public')->delete($seller->business_license_file);
            }
            $path = $request->file('business_license_file')->store('sellers/business_licenses', 'public');
            $data['business_license_file'] = $path;
        }

        $seller->update($data);
        $user->update([
            'first_name' => $data['name'],
            'mobile' => $data['mobile'],
//            'email' => $data['email'],
        ]);

        return api_response([], 'اطلاعات فروشنده با موفقیت ذخیره شد.',
        );
    }


    public function mediaStore(Request $request)
    {
        $seller = auth()->user()->seller;
        $request->validate([
            'telegram_id' => 'nullable',
            'web_id' => 'nullable',
            'whatsapp_id' => 'nullable',
        ]);

        $seller->update($request->all());
        return api_response([], 'آپدیت شد');

    }

    public function media()
    {
        $seller = auth()->user()->seller;

        return api_response([
            'web_id' => $seller->web_id,
            'telegram_id' => $seller->telegram_id,
            'whatsapp_id' => $seller->whatsapp_id,
        ]);

    }

    public function storeAddress(Request $request)
    {
        $sellerId = auth()->user()->seller->id;

        $validated = $request->validate([
            'id' => 'nullable|exists:seller_addresses,id',
            'title' => 'required',
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:iran_cities,id',
            'region' => 'nullable|string|max:255',
            'address' => 'required|string',
            'plaque' => 'nullable|string|max:20',
            'unit' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if (!empty($validated['id'])) {
            $address = SellerAddress::where('id', $validated['id'])
                ->where('seller_id', $sellerId)
                ->firstOrFail();

            $address->update($validated);
        } else {
            $validated['seller_id'] = $sellerId;
            $address = SellerAddress::create($validated);
        }

        return api_response([], 'آدرس با موفقیت ذخیره شد.');
    }

    public function getAddress(Request $request)
    {
        $sellerId = auth()->user()->seller->id;
        $address1 = SellerAddress::where('seller_id', $sellerId)->where('title', 1)->first();
        $address2 = SellerAddress::where('seller_id', $sellerId)->where('title', 2)->first();

        return api_response([
            'address1' => $address1 ?? null,
            'address2' => $address2 ?? null,
        ]);
    }


    public function storeAccount(Request $request)
    {
        $seller = auth()->user()->seller;

        $request->validate([
            'account_number' => 'nullable|string|max:50',
            'bank_name' => 'required|string|max:100',
            'shaba' => 'nullable',
        ]);

        $account = SellerAccount::create([
            'seller_id' =>$seller->id ,
            'account_number' => $request->account_number,
            'bank_name' => $request->bank_name,
            'shaba' => $request->shaba,
        ]);

        return api_response([],'حساب با موفقیت ذخیره شد.');
    }
    public function is_default(Request $request, $id)
    {
        $seller = auth()->user()->seller;

        $account = SellerAccount::where('seller_id', $seller->id)->where('id', $id)->firstOrFail();

        SellerAccount::where('seller_id', $seller->id)->update(['is_default' => false]);

        $account->update(['is_default' => true]);

        return api_response([],'این حساب به عنوان حساب پیش‌فرض ثبت شد.',);
    }

    public function deleteAccount($id)
    {
        $seller = auth()->user()->seller;

        $account = SellerAccount::where('seller_id', $seller->id)
            ->where('id', $id)
            ->firstOrFail();

        $account->delete();

        return api_response([],  'حساب با موفقیت حذف شد.',);
    }


    public function indexAccount(Request $request)
    {
        $seller = auth()->user()->seller;

        $accounts = SellerAccount::where('seller_id', $seller->id)->get();

        return api_response($accounts);
    }


    public function score()
    {
        $seller = auth()->user()->seller;

        $orders = Order::where('seller_id', $seller->id)
            ->whereNotNull('product_satisfaction')
            ->whereNotNull('delivery_rating')
            ->get();

        $productSatisfactionAvg = $orders->avg('product_satisfaction') ?? 0;
        $deliveryRatingAvg = $orders->avg('delivery_rating') ?? 0;

        $totalOrders = Order::where('seller_id', $seller->id)->count();
        $notReturnedOrders = Order::where('seller_id', $seller->id)
            ->where(function ($query) {
                $query->whereNull('is_returned')->orWhere('is_returned', '!=', 1);
            })
            ->count();

        $returnRate = $totalOrders > 0 ? round(($notReturnedOrders / $totalOrders) * 100, 1) : 0;
        $returnScore = round(($returnRate / 100) * 5, 2); // درصد مرجوع نشده به نمره بین 1 تا 5

        // محاسبه میانگین نهایی بین 1 تا 5
        $finalScore = round((
                $productSatisfactionAvg +
                $deliveryRatingAvg +
                $returnScore
            ) / 3, 2);

        return api_response([
            'satisfaction' => round($productSatisfactionAvg * 10, 1), // درصد
            'rating' => round($deliveryRatingAvg * 10, 1), // درصد
            'not_returned_percent' => $returnRate, // درصد مرجوع نشده
            'number' => $notReturnedOrders,
            'experience' => $seller->experience,
            'average_score' => $finalScore, // عددی بین 1 تا 5
        ]);
    }

    public function products(Request $request)
    {
        $seller = auth()->user()->seller;

        $query = ProductSeller::where('seller_id', $seller->id)
            ->with(['product.category', 'product.ratings']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('title')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->title . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $products = $query->paginate();

        // محاسبه شماره ردیف بر اساس صفحه فعلی و تعداد در هر صفحه
        $startIndex = ($products->currentPage() - 1) * $products->perPage() + 1;

        $products->getCollection()->transform(function ($item, $index) use ($startIndex) {
            $ratings = $item->product->ratings;

            $highRatingsCount = $ratings->where('rating', '>', 2.5)->count();
            $lowRatingsCount = $ratings->where('rating', '<=', 2.5)->count();

            return [
                'row' => $startIndex + $index,
                'id' => $item->product->id,
                'title' => $item->product->title,
                'image' => $item->product->first_image,
                'category' => $item->product->category->title,
                'is_active' => $item->is_active,
                'like' => $highRatingsCount,
                'dislike' => $lowRatingsCount,
            ];
        });

        return api_response($products);
    }



}
