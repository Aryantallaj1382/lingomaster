<?php
namespace App\Models\Student;

use App\Models\Country;
use App\Models\iran_regions\IranCity;
use App\Models\Professors\GroupClass;
use App\Models\Professors\Professor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAddress extends Model
{
    protected $guarded=[];
    protected $table = 'student_addresses';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(IranCity::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

}
