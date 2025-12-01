<?php

namespace App\Http\Controllers\Api\buyer;

use App\Http\Controllers\Controller;
use App\Models\Buyer\Buyer;
use App\Models\contacts\api;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BuyerController extends Controller
{

    public function uploadProfileImage(Request $request)
    {
        $id = auth()->user()->buyer->id;
        $request->validate([
            'profile' => 'required',
        ]);

        $student = Buyer::findOrFail($id);

        if ($student->profile) {
            Storage::delete('public/profiles/buyer/' . basename($student->profile));
        }

        $imageName = time() . '.' . $request->profile->extension();
        $request->profile->move(public_path('profiles/students'), $imageName);

        $student->profile = 'profiles/students/' . $imageName;
        $student->save();
        $student->user->profile_image = 'profiles/professor/' . $imageName;
        $student->user->save();
        return api_response([], 'ذخیره شد');
    }

    public function show()
    {
        $user = Auth::user();

        $buyer = Buyer::where('user_id', $user->id)->first();

        if (!$buyer) {
            return response()->json(['message' => 'خریدار یافت نشد.'], 404);
        }

        return api_response($buyer);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $buyer = Buyer::where('user_id', $user->id)->first();

        if (!$buyer) {
            return api_response([] , 'خریدار یافت نشد.');
        }

        $validated = $request->validate([
            'first_name'     => 'nullable|string|max:255',
            'last_name'      => 'nullable|string|max:255',
            'national_code'  => ['nullable', 'string', 'size:10', Rule::unique('buyers')->ignore($buyer->id)],
            'email'          => ['nullable', 'email', Rule::unique('buyers')->ignore($buyer->id)],
            'mobile'         => ['nullable', 'string', Rule::unique('buyers')->ignore($buyer->id)],
            'referral_code'  => 'nullable|string|max:255',
            'date_of_birth'        => 'nullable', // اگر پروفایل تصویر باشد
        ]);

        if ($request->hasFile('profile')) {
            $path = $request->file('profile')->store('profiles', 'public');
            $buyer->profile = $path;
        }

        $buyer->update($validated);
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'],
        ]);

        return api_response($buyer, 'اطلاعات با موفقیت به‌روزرسانی شد.',);
    }

    public function listAddresses()
    {
        $user = Auth::user();
        $buyer = Buyer::where('user_id', $user->id)->first();

        if (!$buyer) {
            return response()->json(['message' => 'خریدار یافت نشد.'], 404);
        }

        $addresses = $buyer->addresses()->with('country', 'city')->get();
        $a = $addresses->map(function ($address) {
            return [
                'id' => $address->id,
                'country' => $address->country->title,
                'country_id' => $address->country->id,
                'city_id' => $address->city->id,
                'city' => $address->city->name,
                'address' => $address->address,
                'mobile' => $address->mobile,
                'unit' => $address->unit,
                'region' => $address->region,
                'title' => $address->title,
                'plaque' => $address->plaque,
                'postal_code' => $address->postal_code,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,


            ];
    });

        return api_response($a);
    }

    public function storeAddress(Request $request)
    {
        $user = Auth::user();
        $buyer = Buyer::where('user_id', $user->id)->first();

        if (!$buyer) {
            return response()->json(['message' => 'خریدار یافت نشد.'], 404);
        }


        $validated = $request->validate([
            'id' => 'nullable',
            'title'       => 'required|string|max:255',
            'country_id'  => 'required',
            'city_id'     => 'required',
            'region'      => 'nullable|string|max:255',
            'address'     => 'required|string',
            'plaque'      => 'nullable|string|max:20',
            'unit'        => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:20',
            'mobile'      => 'nullable|string|max:20',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
        ]);
        if (!empty($validated['id'])) {
            $address = $buyer->addresses()->where('id', $validated['id'])->first();

            if (!$address) {
                return api_response([], 'آدرس یافت نشد.', 404);
            }

            $address->update($validated);
            return api_response([], 'آدرس با موفقیت ویرایش شد.');
        }
       $buyer->addresses()->create($validated);

        return api_response([], 'آدرس با موفقیت ذخیره شد.');
    }



    public function deleteAddress($id)
    {
        $user = Auth::user();
        $buyer = Buyer::where('user_id', $user->id)->first();

        if (!$buyer) {
            return response()->json(['message' => 'خریدار یافت نشد.'], 404);
        }

        $address = $buyer->addresses()->where('id', $id)->first();

        if (!$address) {
            return api_response([], 'آدرس یافت نشد.', 400);
        }

        $address->delete();

        return api_response([] ,'آدرس با موفقیت حذف شد.');
    }




}
