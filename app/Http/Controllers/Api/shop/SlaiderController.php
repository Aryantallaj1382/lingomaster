<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\SliderRequest;
use App\Models\shop\Slaider;


class SlaiderController extends Controller
{
    public function index()
{
    $sliders = Slaider::with('images')->get();
    $sliderData = $sliders->map(function ($slider) {

        $imageUrls = [
            'mobil_image' => null,
            'tablet_image' => null,
            'laptop_image' => null,
        ];


        foreach ($slider->images as $image) {
            $imageName = basename($image->path);

            if (strpos($imageName, 'mobil_') !== false) {
                $imageUrls['mobil_image'] = asset('storage/' . $image->path);
            } elseif (strpos($imageName, 'tablet_') !== false) {
                $imageUrls['tablet_image'] = asset('storage/' . $image->path);
            } elseif (strpos($imageName, 'laptop_') !== false) {
                $imageUrls['laptop_image'] = asset('storage/' . $image->path);
            }
        }

        return [
            'id' => $slider->id,
            'link' => $slider->link,
            'mobil_image' => $imageUrls['mobil_image'],
            'tablet_image' => $imageUrls['tablet_image'],
            'laptop_image' => $imageUrls['laptop_image'],
        ];
    });

    return api_response($sliderData, 'لیست اسلایدها با موفقیت بارگذاری شد');
}


    public function store(SliderRequest $request)
    {
        $slider = slaider::create($request->validated());

        if ($request->hasFile('mobil_image')) {
            $image = $request->file('mobil_image');
            $mobilImageUrl = $image->storeAs('/images/slider' . $slider->title, 'mobil_' . $image->getClientOriginalName(), 'public');
            $slider->images()->create(['path' => $mobilImageUrl]);
        }

            if ($request->hasFile('tablet_image')) {
            $image = $request->file('tablet_image');
            $tabletImageUrl = $image->storeAs('/images/slider' . $slider->title, 'tablet_' . $image->getClientOriginalName(), 'public');
            $slider->images()->create(['path' => $tabletImageUrl]);
        }

        if ($request->hasFile('laptop_image')) {
            $image = $request->file('laptop_image');
            $laptopImageUrl = $image->storeAs('/images/slider' . $slider->title, 'laptop_' . $image->getClientOriginalName(), 'public');
            $slider->images()->create(['path' => $laptopImageUrl]);
        }

        return api_response([
            'id' => $slider->id,
            'link' => $slider->link,
        ], 'محصول با موفقیت ثبت شد');
    }

}
