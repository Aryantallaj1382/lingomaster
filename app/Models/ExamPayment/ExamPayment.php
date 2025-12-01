<?php

namespace App\Models\ExamPayment;
use App\Models\contacts\api;
use Illuminate\Database\Eloquent\Model;
class ExamPayment extends Model
{
    protected $table = 'exam_payments' ;

    public function getImageAttribute($value)
    {
        return $value ? api::api . $value : null;
    }
}
