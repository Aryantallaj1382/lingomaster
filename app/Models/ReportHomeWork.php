<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportHomeWork extends Model
{
    protected $table = 'report_home_work';

    protected $guarded = [];

    public function reportRegistration()
    {
        return $this->belongsTo(ReportRegistration::class);
    }
}
