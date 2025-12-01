<?php

namespace App\Http\Requests\shop;

use App\Models\AgeGroup;
use App\Models\Languages;
use App\Models\shop\Category;
use App\Models\shop\ProductType;
use App\Models\shop\Subjects;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'=>'required',
            'price'=>'required',
            'off_price'=>'nullable',
            'Volume_number'=>'nullable|integer',
            'page_number'=>'nullable|integer',
            'Publication'=>'nullable',
            'year_of_publication'=>'nullable',
            'Dimensions'=>'nullable',
            'Time_to_print'=>'nullable',
            'is-like'=>'nullable|boolean',
            'author'=>'nullable|string',
            'language_id' => ['nullable',
                Rule::exists(Languages::class, 'id')
            ],
            'age_group_id' => ['nullable',
                Rule::exists(AgeGroup::class, 'id')
            ],
            'product_type_id' => ['nullable',
                Rule::exists(ProductType::class, 'id')
            ],
            'subject_id' => ['nullable',
                Rule::exists(Subjects::class, 'id')
            ],
            'category_id' => ['nullable',
                Rule::exists(Category::class, 'id')
            ],
            'discount-percentage'=>'nullable',
            'Paper_type'=>'nullable',
            'Cover_type'=>'nullable',
            'shabak_number'=>'nullable',
            'fipa_number'=>'nullable',
            'weight'=>'nullable',
            'book_category'=>'nullable',
            'audio_file'=>'nullable',
            'video_file'=>'nullable',
            'video_accent'=>'nullable',
            'audio_accent'=>'nullable',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sample_images'=>'nullable|image'
        ];
    }

    public function attributes(): array
    {
        return [
            'title'=>'موضوع',
            'price'=>'قیمت',
            'off_price'=>'قیمت با تخفیف',
            'Volume_number'=>'تعداد جلد',
            'page_number'=>'تعداد صفحه',
            'Publication'=>'',
            'year_of_publication'=>'سال ',
            'Dimensions'=>'',
            'Time_to_print'=>'سال چاپ',
            'is-like'=>'لابک شده',
            'author'=>'نویسنده',
            'language_id'=>'زبان',
            'age_group_id'=>'رده سنی',
            'subject_id'=>'موضوع',
            'category_id'=>'دسته بندی',
            'product_type_id'=>'مدل محصول',
            'discount-percentage'=>'test',
            'Paper_type'=>'جنس صفحه',
            'Cover_type'=>'جنس جلد',
            'shabak_number'=>'شمارع شابک',
            'fipa_number'=>'شماره فیپا',
            'weight'=>'وزن',
        ];

    }
}
