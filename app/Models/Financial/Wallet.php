<?php
namespace App\Models\Financial;

use App\Models\payment\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'balance',
    ];
    protected $hidden = ['id', 'user_id'];



    // رابطه با مدل User
    // در مدل Wallet
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // رابطه با مدل Transaction
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function payment()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

}
