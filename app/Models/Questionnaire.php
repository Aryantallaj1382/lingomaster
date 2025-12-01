<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questionnaire extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        // boolean fields
        'english_teaching'     => 'boolean',
        'english_literature'   => 'boolean',
        'english_translation'  => 'boolean',
        'is_cooperating_now'   => 'boolean',
        'is_fulltime'          => 'boolean',
        'is_willing_ttc'       => 'boolean',
        'can_teach_general'    => 'boolean',
        'can_teach_specialized'=> 'boolean',
        'wants_improvement'    => 'boolean',

        // json fields
        'online_teaching'  => 'array',
        'teaching_hours'   => 'array',
        'age_groups'       => 'array',
        'teaching_levels'  => 'array',
        'class_types'      => 'array',
        'ttc_type'         => 'array',
    ];
}
