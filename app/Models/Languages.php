<?php

namespace App\Models;

use App\Models\contacts\api;
use App\Models\Professors\ClassType;
use App\Models\Professors\NearestClass;
use App\Models\Professors\Professor;
use App\Models\Professors\ProfessorBook;
use App\Models\Professors\ProfessorExamLanguage;
use App\Models\Professors\professorJob;
use App\Models\Professors\ProfessorSkill;
use App\Models\Professors\ProfessorTest;
use App\Models\Professors\ProfessorTranslation;
use App\Models\Professors\ProfessorUniversityLanguage;
use App\Models\shop\Accent;
use App\Models\shop\Product;
use App\Models\shop\Tools;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Languages extends Model
{
    use HasFactory;
    protected  $table = 'languages';
    protected $hidden = ['pivot'];

    public function getIconAttribute()
    {
        return $this->flag ? api::api.$this->flag : null;

    }
    protected $guarded = [];

    public function product(): HasMany
    {
        return $this->hasMany(Product::class, 'language_id');
    }
    public function professor_book(): HasMany
    {
        return $this->hasMany(ProfessorBook::class, 'language_id');
    }

    public function Tools(): HasMany
    {
        return $this->hasMany(Tools::class, 'language_id');
    }

    // زبان به چندین استاد مربوط است
    public function professors()
    {
        return $this->belongsToMany(Professor::class, 'professor_languages', 'language_id', 'professor_id');
    }

    public function jobs()
    {
        return $this->hasMany(ProfessorJob::class);
    }

    // زبان به چند تست مربوط است
    public function tests()
    {
        return $this->hasMany(ProfessorTest::class);
    }

    // زبان به چند ترجمه مربوط است
    public function translations()
    {
        return $this->hasMany(ProfessorTranslation::class);
    }

    // زبان به چند زبان آزمون مربوط است
    public function examLanguages()
    {
        return $this->hasMany(ProfessorExamLanguage::class);
    }

    // زبان به چند زبان دانشگاهی مربوط است
    public function universityLanguages()
    {
        return $this->hasMany(ProfessorUniversityLanguage::class);
    }

    // زبان به چند مهارت مربوط است
    public function skills()
    {
        return $this->hasMany(ProfessorSkill::class);
    }
    public function accents(): HasMany
    {
        return $this->hasMany(Accent::class);
    }



    // تعریف رابطه با کلاس‌های نزدیک
    public function nearestClasses()
    {
        return $this->hasMany(NearestClass::class);
    }
    public function classTypes()
    {
        return $this->hasMany(ClassType::class, 'language_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_language')
            ->withPivot('id')
            ->withTimestamps();
    }
    public function getProductCategoryTitleAttribute()
    {
        return $this->product?->category?->title;
    }
}
