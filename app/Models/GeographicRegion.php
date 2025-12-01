<?php
namespace App\Models;

use App\Models\Professors\Professor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GeographicRegion extends Model
{
    protected $fillable = ['title'];
    protected $table = 'geographic_regions';
    /**
     * هر منطقه می‌تواند توسط چند استاد انتخاب شود
     */

}
