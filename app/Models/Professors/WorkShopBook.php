<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShopBook extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'work_shop_books';


    public function bookLearning()
    {
        return $this->belongsTo(BookLearning::class, 'book_learning_id');
    }
}

