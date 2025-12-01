<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Professors\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{


//Notification::create([
//'professor_id' => $professor_id, // شناسه استادی که کلاس برای او ثبت شده
//'text' => $student->name . ' یک کلاس خصوصی ثبت کرد.',
//]);

    public function indexProfessor(Request $request)
    {
        $id = auth()->user()->id;
        $query = Notification::where('user_id', $id)->latest();

        if ($request->filled('search')) {
            $query->where('text', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $notif = $query->paginate();

        // فقط نوتیفیکیشن‌هایی که دیده نشده‌اند را seen کنیم
        collect($notif->items())
            ->where('is_seen', false)
            ->each(function ($item) {
                $item->update(['is_seen' => true]);
            });

        $notif->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->text,
                'date' => $item->created_at->format('Y-m-d'),
                'time' => $item->created_at->format('H:i'),
                'type' => $item->type
            ];
        });

        return api_response($notif, 'ok');
    }


    // ذخیره نوتیفیکیشن جدید
    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
        ]);

        $notification = Notification::create([
            'text' => $request->input('text'),
        ]);

        return response()->json($notification, 201);
    }

    // نمایش نوتیفیکیشن خاص
    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        return response()->json($notification);
    }

    // حذف نوتیفیکیشن
    public function destroy($id)
    {
        Notification::destroy($id);
        return response()->json(['message' => 'Deleted successfully']);
    }
}
