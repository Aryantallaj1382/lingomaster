<?php

namespace App\Http\Controllers;

use App\Models\shop\Image;
use Illuminate\Support\Facades\File;

class TestController extends Controller
{
    public function map_images()
    {
        $temp_dir = base_path('public/images/paintings/temp');

        if (!File::exists($temp_dir))
            return 'source is not exists';

        $files = File::files($temp_dir);

        foreach ($files as $file) {

            $image = Image::where('path', 'like', '%/' . $file->getFilename())->first();
            if (blank($image))
                continue;

            $painting = $image->imagable;

            $destination = base_path('public/images/paintings/' . $painting->title);

            File::copy($file->getPathname(), $destination . '/' . $file->getFilename());
        }

        return 'files moved successfully';
    }
}
