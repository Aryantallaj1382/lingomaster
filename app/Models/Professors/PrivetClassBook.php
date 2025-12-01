<?php

namespace App\Models\Professors;

use App\Models\shop\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivetClassBook extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'privet_class_books';

    public function privetClass()
    {
        return $this->belongsTo(PrivetClassReservation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function bookLearning()
    {
        return $this->belongsTo(BookLearning::class, 'book_learning_id');
    }

}

