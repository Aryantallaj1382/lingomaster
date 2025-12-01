<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LoginRequest extends FormRequest
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
            'user' => 'required',
            'password' => 'filled',
            'otp' => 'filled'
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('password') && $this->filled('otp')) {
                    $message = 'از بین رمزعبور و کد تایید باید یکی وارد شود';
                    $validator->errors()
                        ->add('password', $message)
                        ->add('otp', $message);
                }
            }
        ];
    }
}
