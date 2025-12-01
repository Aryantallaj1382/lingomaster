<?php

namespace App\Http\Requests\shop;

use Illuminate\Foundation\Http\FormRequest;

class BanerRequest extends FormRequest
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
//            'title'=>'nullable',
//            'price'=>'required',
//            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'product_id'=>'required'
        ];
    }

    public function attributes(): array
    {
        return [
//            'title'=>'اسم',
//            'image' => 'عکس',
//            'price'=>'قیمت',
            'product_id'=>'محصول'
        ];
    }
}
