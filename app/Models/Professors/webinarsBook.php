<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class webinarsBook extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function groupClass()
    {
        return $this->belongsTo(GroupClass::class);
    }


    public function bookLearning()
    {
        return $this->belongsTo(BookLearning::class, 'book_learning_id');
    }
}

