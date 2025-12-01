<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanDuration;

class PlanSeeder extends Seeder
{
    public function run()
    {
        // پلن‌ها و اطلاعات کامل‌شان
        $plans = [
            [
                'name' => 'پلن عادی',
                'features' => [
                    'کلاس آزمایشی' => 'آنلاین',
                    'تعیین سطح' => 'آنلاین',
                    'کلاس خصوصی' => 'آنلاین',
                    'کلاس گروهی' => false,
                    'وبینار' => false,
                    'ورکشاپ' => false,
                ],
                'durations' => [
                    ['months' => 1,  'price' => 300000,  'discount_percent' => 0],
                    ['months' => 3,  'price' => 810000,  'discount_percent' => 10],
                    ['months' => 6,  'price' => 1530000, 'discount_percent' => 15],
                    ['months' => 12, 'price' => 2700000, 'discount_percent' => 25],
                ]
            ],
            [
                'name' => 'پلن ویژه',
                'features' => [
                    'کلاس آزمایشی' => 'آنلاین و حضوری',
                    'تعیین سطح' => 'آنلاین و حضوری',
                    'کلاس خصوصی' => 'آنلاین و حضوری',
                    'کلاس گروهی' => 'آنلاین و حضوری',
                    'وبینار' => false,
                    'ورکشاپ' => false,
                ],
                'durations' => [
                    ['months' => 1,  'price' => 600000,  'discount_percent' => 0],
                    ['months' => 3,  'price' => 1620000, 'discount_percent' => 10],
                    ['months' => 6,  'price' => 3060000, 'discount_percent' => 15],
                    ['months' => 12, 'price' => 5400000, 'discount_percent' => 25],
                ]
            ],
            [
                'name' => 'پلن VIP',
                'features' => [
                    'کلاس آزمایشی' => 'آنلاین و حضوری',
                    'تعیین سطح' => 'آنلاین و حضوری',
                    'کلاس خصوصی' => 'آنلاین و حضوری',
                    'کلاس گروهی' => 'آنلاین و حضوری',
                    'وبینار' => true,
                    'ورکشاپ' => true,
                ],
                'durations' => [
                    ['months' => 1,  'price' => 900000,  'discount_percent' => 0],
                    ['months' => 3,  'price' => 2430000, 'discount_percent' => 10],
                    ['months' => 6,  'price' => 4590000, 'discount_percent' => 15],
                    ['months' => 12, 'price' => 8100000, 'discount_percent' => 25],
                ]
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::create([
                'name' => $planData['name'],
                'features' => json_encode($planData['features'], JSON_UNESCAPED_UNICODE),
            ]);

            foreach ($planData['durations'] as $duration) {
                PlanDuration::create([
                    'plan_id' => $plan->id,
                    'months' => $duration['months'],
                    'price' => $duration['price'],
                    'discount_percent' => $duration['discount_percent'],
                ]);
            }
        }
    }
}
