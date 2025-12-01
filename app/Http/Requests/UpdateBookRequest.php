<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|nullable',
            'category_id' => 'sometimes|nullable',
            'book_status' => 'sometimes|nullable',
            'stock' => 'sometimes|nullable',
            'price' => 'sometimes|nullable',
            'off_price' => 'sometimes|nullable',
            'author' => 'sometimes|nullable',
            'Volume_number' =>'sometimes|nullable',
            'page_number' =>'sometimes|nullable',
            'language_id' =>'sometimes|nullable',
            'publication_id'=>'sometimes|nullable',
            'year_of_publication' =>'sometimes|nullable',
            'age_group_id'=>'sometimes|nullable',
            'language_levels_id' =>'sometimes|nullable',
            'learning_subgoal_id' => 'sometimes|nullable',
            'Time_to_print'=> 'sometimes|nullable',
            'is_second_hand' =>'sometimes|nullable',
            'video_file'=>'sometimes|nullable',
            'video_accent'=>'sometimes|nullable',
            'video_file_path'=>'nullable',
            'video_accent_path'=>'nullable',
            'Dimensions'=>'sometimes|nullable',
            'Paper_type'=>'sometimes|nullable',
            'Cover_type'=>'sometimes|nullable',
            'fipa_number'=>'sometimes|nullable',
            'shabak_number'=>'sometimes|nullable',
            'weight'=>'sometimes|nullable',
            'image'=>'nullable|file|mimes:jpg,jpeg,png',
            'is_download'=>'sometimes|nullable',
            'discount_expiration'=>'sometimes|nullable',
            'video'=>'nullable|file|mimes:mp4,mov,avi,mkv',
            'sample_images' => 'nullable|array',
            'sample_images.*' => 'file|mimes:jpg,jpeg,png',
            'cover' => 'nullable|file|mimes:jpg,jpeg,png',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'عنوان کتاب',
            'category_id' => 'دسته‌بندی',
            'book_status' => 'وضعیت کتاب',
            'stock' => 'موجودی',
            'price' => 'قیمت',
            'off_price' => 'قیمت با تخفیف',
            'author' => 'نویسنده',
            'Volume_number' => 'شماره جلد',
            'page_number' => 'تعداد صفحات',
            'language_id' => 'زبان',
            'publication_id' => 'ناشر',
            'year_of_publication' => 'سال انتشار',
            'age_group_id' => 'گروه سنی',
            'language_levels_id' => 'سطح زبان',
            'learning_subgoal_id' => 'هدف یادگیری',
            'Time_to_print' => 'مدت زمان چاپ',
            'is_second_hand' => 'کتاب دست دوم است؟',
            'video_file' => 'فایل ویدیو',
            'video_accent' => 'لهجه ویدیو',
            'video_file_path' => 'مسیر فایل ویدیو',
            'video_accent_path' => 'مسیر لهجه ویدیو',
            'Dimensions' => 'ابعاد',
            'Paper_type' => 'نوع کاغذ',
            'Cover_type' => 'نوع جلد',
            'fipa_number' => 'شماره فیپا',
            'shabak_number' => 'شماره شابک',
            'weight' => 'وزن',
            'image' => 'تصویر اصلی',
            'is_download' => 'قابل دانلود بودن',
            'discount_expiration' => 'تاریخ انقضای تخفیف',
            'video' => 'ویدیو معرفی',
            'sample_images' => 'تصاویر نمونه',
            'sample_images.*' => 'فایل‌های تصاویر نمونه',
            'cover' => 'جلد کتاب',
        ];
    }

}
