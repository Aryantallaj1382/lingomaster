<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportBook extends Model
{
    protected $table = 'report_book';

    protected $fillable = [
        'title',
        'start',
        'end',
        'report_registration_id',
    ];

    public function reportRegistration()
    {
        return $this->belongsTo(ReportRegistration::class);
    }
}
