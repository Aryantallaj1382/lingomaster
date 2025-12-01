<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\SubjectRequest;
use App\Models\shop\Subjects;

class SubjectsController extends Controller
{
    public function index()
    {
        $subject = Subjects::latest()->paginate();

        return api_response($subject);
    }

    public function store(SubjectRequest $request)
    {
        $subject = Subjects::create($request->validated());

        return api_response($subject , 'موضوع با موفقیت ساخته شد');
    }

    public function update(SubjectRequest $request, Subjects $subjects)
    {
        $subjects->update($request->validated());

        return api_response($subjects, 'اطلاعات موضوع با موفقیت ویرایش شد');
    }

    public function destroy(Subjects $subjects)
    {
        $subjects->delete();

        return api_response([], 'اطلاعات موضوع با موفقیت حذف شد');
    }
}
