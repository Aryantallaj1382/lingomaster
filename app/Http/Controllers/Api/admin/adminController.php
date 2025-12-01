<?php

namespace App\Http\Controllers\Api\admin;

use App\Http\Controllers\Controller;
use App\Models\contacts\header_banner;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class adminController extends Controller
{
    public function update_header_banner(Request $request)
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // ولیدیشن برای فایل تصویر
            'link' => 'nullable|url',
        ]);

        if (isset($validated['link'])) {
            SystemSetting::where('id', header_banner::header_banner_id)->update([
                'value' => $validated['link']
            ]);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/header_banner', 'public');

            SystemSetting::where('id', header_banner::header_banner_id)->update([
                'name' => $path
            ]);
        }

        $banner = [
            'link' => SystemSetting::where('id', header_banner::header_banner_id)->value('value'),
            'image' => asset(SystemSetting::where('id', header_banner::header_banner_id)->value('name')), // مسیر عمومی برای دسترسی به تصویر
        ];

        return api_response($banner, 'با موفقیت اپدیت شد');
    }
}
