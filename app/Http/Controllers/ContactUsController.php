<?php

namespace App\Http\Controllers;

use App\Models\ContactUs;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'nullable',
            'mobile' => 'nullable',
            'email' => 'nullable',
            'description' => 'nullable',
        ]);
        ContactUs::create($request->all());
        return api_response([],'با تشکر بزودی با شما تماس گرفته خواهد شد ');

    }
}
