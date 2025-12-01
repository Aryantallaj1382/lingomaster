<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\shop\Cover;
use Illuminate\Http\Request;

class CoverController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'coverable_id' => 'required|integer',
            'coverable_type' => 'required|string',
            'path' => 'required|image|max:2048', // محدودیت اندازه عکس
        ]);

        // ذخیره عکس کاور
        $path = $request->file('path')->store('covers', 'public');

        $cover = Cover::create([
            'path' => $path,
            'coverable_id' => $request->coverable_id,
            'coverable_type' => $request->coverable_type,
        ]);

        return response()->json($cover, 201);
    }

    public function destroy(Cover $cover)
    {
        // حذف عکس کاور
        $cover->delete();
        return response()->json(null, 204);
    }
}
