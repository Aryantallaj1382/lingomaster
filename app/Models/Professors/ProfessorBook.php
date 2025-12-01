<?php

namespace App\Models\Professors;

use App\Models\AgeGroup;
use App\Models\Languages;
use App\Models\shop\Image;
use App\Models\shop\Subjects;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProfessorBook extends Model
{
    use HasFactory;

    protected $table = 'professor_book';
    protected $fillable = ['professor_id', 'book_id'];

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subjects::class, 'subject_id');
    }    public function age_group(): BelongsTo
{
    return $this->belongsTo(AgeGroup::class, 'age_group_id');
}

    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class, 'language_id');
    }
}
