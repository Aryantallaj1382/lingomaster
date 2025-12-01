<?php
namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\DiscountForStudent;
use App\Models\Exam_question\Exam;
use App\Models\Professors\GroupClass;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Professor;
use App\Models\Professors\Webinar;
use App\Models\Professors\WorkShop;
use App\Models\shop\Product;
use Illuminate\Http\Request;

class DiscountForStudentController extends Controller
{

    public function index(Request $request)
    {
        $typeFilter = $request->query('type');

        $query = DiscountForStudent::with('discountable');

        // فیلتر بر اساس نوع
        switch ($typeFilter) {
            case 'classes':
                $query->whereIn('discountable_type', [
                    Webinar::class,
                    WorkShop::class,
                    GroupClass::class,
                    Professor::class
                ]);
                break;

            case 'products':
                $query->where('discountable_type', Product::class);
                break;

            case 'exams':
                // فقط اگه مدل آزمون‌ها وجود داره (مثلاً PlacementTest)
                $query->where('discountable_type',Exam::class);
                break;

            case 'all':
            default:
                // بدون فیلتر
                break;
        }

        $discounts = $query->latest()->paginate();

        $discounts->getCollection()->transform(function ($item) {
            $discountable = $item->discountable;
            $type = $item->discountable_type;
            $title = null;

            switch ($type) {
                case Webinar::class:
                    $title =  'وبینار';
                    break;
                case WorkShop::class:
                $title =  'ورکشاپ';
                break;
                case GroupClass::class:
                    $title = 'کلاس گروهی';
                    break;

                case Product::class:
                    $title = $discountable->title ?? 'کتاب ها';
                    break;

                case Professor::class:
                    $title =  'کلاس خصوصی';
                    break;

                default:
                    $title = 'آژمون ها';
            }

            return [
                'id' => $item->id,
                'code' => $item->code,
                'percentage' => $item->percentage,
                'limit' => $item->limit,
                'expires_at' => $item->expires_at,
                'title' => $title,
            ];
        });

        return api_response($discounts);
    }



    public function store(Request $request)
    {
        $code = $request->input('code');
        $id = $request->input('id');
        $model = $request->input('model');

        if ($model === 'webinar') {
            $webinar = Webinar::find($id);
            $discount = DiscountForStudent::where('discountable_type' , Webinar::class)->where('code', $code)->first();
            if (!$discount) {
                return api_response([],'تخفیفی پیدا نشد' , 400);
            }
            if ($discount->expires_at < now() )
            {
                return api_response([],'کد تخفیف منقضی شده ' , 400 );
            }
            if ($discount->limit <= 0)
            {
                return api_response([],'تعداد کد تخفیف تمام شده ' , 400 );
            }
            $price =$webinar->new_total_price;
            $percentage =$discount->percentage;
            $finalPrice = $price - ($price * $percentage / 100);

            return api_response([
                'price' => $finalPrice,
                'percentage' => $percentage,
            ], 'تخفیف اعمال شد' , 200);
        }
        if ($model === 'workshop') {
            $webinar = WorkShop::find($id);
            $discount = DiscountForStudent::where('discountable_type' , WorkShop::class)->where('code', $code)->first();
            if (!$discount) {
                return api_response([],'تخفیفی پیدا نشد', 400);
            }
            if ($discount->expires_at < now() )
            {
                return api_response([],'کد تخفیف منقضی شده ' , 400 );
            }
            if ($discount->limit <= 0)
            {
                return api_response([],'تعداد کد تخفیف تمام شده ' , 400 );
            }
            $price =$webinar->new_total_price;
            $percentage =$discount->percentage;
            $finalPrice = $price - ($price * $percentage / 100);

            return api_response([
                'price' => $finalPrice,
                'percentage' => $percentage,
            ], 'تخفیف اعمال شد');
        }
        if ($model === 'group_class') {
            $webinar = GroupClass::find($id);
            $discount = DiscountForStudent::where('discountable_type' , GroupClass::class)->where('code', $code)->first();
            if (!$discount) {
                return api_response([],'تخفیفی پیدا نشد', 400);
            }
            if ($discount->expires_at < now() )
            {
                return api_response([],'کد تخفیف منقضی شده ' , 400 );
            }
            if ($discount->limit <= 0)
            {
                return api_response([],'تعداد کد تخفیف تمام شده ' , 400 );
            }
            $price =$webinar->new_total_price;
            $percentage =$discount->percentage;
            $finalPrice = $price - ($price * $percentage / 100);

            return api_response([
                'price' => $finalPrice,
                'percentage' => $percentage,
            ], 'تخفیف اعمال شد');
        }
        if ($model === 'private') {
            $webinar = PrivetClassReservation::find($id);
            $discount = DiscountForStudent::where('discountable_type' , Professor::class)->where('code', $code)->first();
            if (!$discount) {
                return api_response([],'تخفیفی پیدا نشد', 400);
            }
            if ($discount->expires_at < now() )
            {
                return api_response([],'کد تخفیف منقضی شده ' , 400 );
            }
            if ($discount->limit <= 0)
            {
                return api_response([],'تعداد کد تخفیف تمام شده ' , 400 );
            }
            $price =$webinar->price;
            $percentage =$discount->percentage;
            $finalPrice = $price - ($price * $percentage / 100);

            return api_response([
                'price' => round($finalPrice),
                'percentage' => $percentage,
            ], 'تخفیف اعمال شد');
        }

    }
}
