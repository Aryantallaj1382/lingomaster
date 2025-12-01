<?php

namespace App\Http\Requests\shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationalProductRequest extends FormRequest
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
    public function rules()
{
    return [
        'age_group_id'    => 'nullable|exists:age_groups,id',
        'language_id'     => 'nullable|exists:languages,id',
        'price'           => 'required|numeric|min:0',
        'title'           => 'required',
        'off_price'=> 'nullable|numeric|min:0|lt:price', // مبلغ با تخفیف باید کمتر از مبلغ اصلی باشد
        'free_shipping'   => 'nullable|boolean',
        'today_shipping'  => 'nullable|boolean',
        'is_download'  => 'nullable',
    ];
}
}
