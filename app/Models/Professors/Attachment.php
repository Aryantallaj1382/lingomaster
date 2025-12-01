<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'category',
        'attachable_id',
        'attachable_type',
    ];

    public function attachable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }


    const CATEGORY_FOREIGN_LANGUAGE_CERTIFICATE = 'foreign_language_certificate';
    const CATEGORY_LOCAL_LANGUAGE_CERTIFICATE = 'local_language_certificate';
    const CATEGORY_UNIVERSITY_DEGREE = 'university_degree';
    const CATEGORY_STUDENT_CARD = 'student_card';
    const RESUME = 'resume';

    public static function categories(): array
    {
        return [
            self::CATEGORY_FOREIGN_LANGUAGE_CERTIFICATE,
            self::CATEGORY_LOCAL_LANGUAGE_CERTIFICATE,
            self::CATEGORY_UNIVERSITY_DEGREE,
            self::CATEGORY_STUDENT_CARD,
        ];
    }
}
