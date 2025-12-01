<?php

namespace App\Models\Professors;

use App\Models\AgeGroup;
use App\Models\Languages;
use App\Models\shop\Product;
use App\Models\shop\Subjects;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookLearning extends Model
{
    use HasFactory;

    protected $table = 'book_learning';
    protected $guarded = [];
    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Languages::class,'language_id');
    }

    public function ageGroup()
    {
        return $this->belongsTo(AgeGroup::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subjects::class);
    }

    public function subgoal()
    {
        return $this->belongsTo(LearningSubgoal::class);
    }

    public function languageLevel()
    {
        return $this->belongsTo(LanguageLevel::class);
    }
    public function professors()
    {
        return $this->belongsTo(Professor::class, 'professor_id'); // فرض بر اینه که فیلدش professor_id هست
    }

    public function groupClassBooks()
    {
        return $this->hasMany(GroupClassBook::class, 'book_learning_id');
    }


}
