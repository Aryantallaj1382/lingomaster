<?php

namespace App\Http\Requests\shop;

use Illuminate\Foundation\Http\FormRequest;

class SliderRequest extends FormRequest
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
            'link'=>'required',
            'mobil_image'=>'nullable',
            'tablet_image'=>'nullable',
            'laptop_image'=>'nullable',
        ];
    }
}
