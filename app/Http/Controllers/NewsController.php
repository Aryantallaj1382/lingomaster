<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    // نمایش تمام خبرها به ترتیب order
    public function index()
    {
        $news = News::select('order', 'id', 'image', 'title', 'created_at')
            ->orderByRaw('`order` IS NULL, `order` ASC')
            ->latest()
            ->take(4)
            ->get();

        $news = $news->values()->all();
        $formatted = [];
        foreach ($news as $index => $item) {
            $formatted[$index + 1] = $item;
        }

        return api_response($formatted);
    }


    public function index1()
    {
        $fixedNews = News::select('order', 'id', 'image', 'title', 'created_at')
            ->latest()
            ->take(2)
            ->get();

        $fixedIds = $fixedNews->pluck('id')->toArray();

        $pagedNews = News::select('order', 'id', 'image', 'title', 'created_at')
            ->whereNotIn('id', $fixedIds)
            ->latest()
            ->paginate(8);

        return api_response([
            'fixed' => $fixedNews,
            'news' => $pagedNews,
        ]);
    }


    public function index2()
    {
        $fixedNews = News::select('order', 'id', 'image', 'title', 'created_at')
            ->orderBy('view', 'desc')
            ->take(2)
            ->get();

        $fixedIds = $fixedNews->pluck('id')->toArray();

        $pagedNews = News::select('order', 'id', 'image', 'title', 'created_at')
            ->whereNotIn('id', $fixedIds)
            ->orderBy('view', 'desc')
            ->paginate(8);

        return api_response([
            'fixed' => $fixedNews,
            'news' => $pagedNews,
        ]);
    }


    public function show($id)
    {
        $news = News::findOrFail($id);

        $news->increment('view');

        $mostViewedNews = News::orderBy('view', 'desc')->take(3)->get(['id', 'image',  'title', 'created_at']);

        $latestNews = News::orderBy('created_at', 'desc')->take(3)->get(['id', 'image',  'title', 'created_at']);

        $n = [
            'id' => $news->id,
            'image' => $news->image,
            'title' => $news->title,
            'created_at' => $news->created_at,
            'tags' => $news->tag,
            'text' => $news->text,
            'view' => $news->view,
            'most_viewed' => $mostViewedNews, // سه خبر پر بازدید
            'news' => $latestNews, // سه خبر جدید
        ];


        return api_response($n);
    }
}
