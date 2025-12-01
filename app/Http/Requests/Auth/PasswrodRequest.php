<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class PasswrodRequest extends FormRequest
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
            'password' => ['required' , 'min:4'],
            'old_password' => 'nullable',
            'otp' => 'filled',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                if (filled($this->user()->password)) {
                    if (!($this->filled('old_password') || $this->filled('otp'))) {
                        $message = 'رمزعبور قدیمی را وارد شود';
                        $validator->errors()
                            ->add('password', $message)
                            ->add('otp', $message);

                    } elseif ($this->filled('old_password')) {

                        if (!Hash::check($this->input('old_password'), $this->user()->password)) {
                            $validator->errors()
                                ->add('old_password', 'رمزعبور قدیمی نادرست است');
                        }
                    } else {
                        $user = $this->user();

                        if ($user->sms_sent_code != $this->input('otp'))
                            $validator->errors()
                                ->add('otp', 'رمز یکبار مصرف نادرست است');

                        if (($user->sms_sent_date?->diffInSeconds() > 120) || ($user->sms_sent_tries > 3))
                            $validator->errors()
                                ->add('otp', 'رمز یکبار منقضی شده است دوباره تلاش کنید');
                    }
                } else {
                    if (!$this->filled('password')) {
                        $validator->errors()
                            ->add('password', 'رمزعبور را وارد کنید');
                    }
                }

            }
        ];
    }

}
