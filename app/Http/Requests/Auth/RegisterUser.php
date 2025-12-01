<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUser extends FormRequest
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
            'first_name' => 'nullable|string|max:40',
            'last_name' => 'nullable|string|max:40',
            'identification_code' => 'nullable',
            'email' => 'nullable|string|email|max:255|unique:' . User::class . ',email',
            'mobile' => 'nullable|digits:11|ir_mobile:zero|unique:' . User::class . ',mobile',
            'password' => 'required',
        ];
    }

    public function attributes(): array
    {

        return[
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'email' => 'ایمیل',
            'mobile' => 'شماره موبایل',
            'password' => 'رمز عبور',
        ];

    }
}
