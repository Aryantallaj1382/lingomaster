<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function post(Request $request)
    {
        Newsletter::create([
            'user' => $request->email,
        ]);
        return api_response([],'با تشکر ایمیل شما جهت خبرنامه ثبت شد');

    }
}
