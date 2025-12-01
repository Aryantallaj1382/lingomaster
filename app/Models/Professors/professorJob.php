<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class professorJob extends Model
{
    protected $table = 'professor_job';

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    // ارتباط با شغل
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class, 'language_id');
    }
}
