<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable',
            'time_of_print' => 'nullable',
            'category_id' => 'nullable',
            'book_status' => 'nullable',
            'quantity' => 'nullable',
            'price' => 'nullable',
            'author' => 'nullable',
            'Volume_number' =>'nullable',
            'page_number' =>'nullable',
            'language_id' =>'nullable',
            'publication_id'=>'nullable',
            'year_of_publication' =>'nullable',
            'age_group_id'=>'nullable',
            'level_id' =>'nullable',
            'subject_id' => 'nullable',
            'Time_to_print'=> 'nullable',
            'accent'=> 'nullable',
            'video_file'=>'nullable',
            'video_accent'=>'nullable',
            'video_file_path'=>'nullable',
            'video_accent_path'=>'nullable',
            'size'=>'nullable',
            'paper_type'=>'nullable',
            'cover_type'=>'nullable',
            'fipa_number'=>'nullable',
            'is_active'=>'nullable',
            'is_downlad'=>'nullable',
            'shabak_number'=>'nullable',
            'weight'=>'nullable',
            'image'=>'nullable',
            'video'=>'nullable',
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

