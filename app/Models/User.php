<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
//use App\Models\Constants\role_value;
use App\Models\Buyer\Buyer;
use App\Models\contacts\role_value;
use App\Models\Financial\Wallet;
use App\Models\Professors\Professor;
use App\Models\Professors\Reservation;
use App\Models\Professors\TeacherTimeSlot;
use App\Models\shop\Dislike;
use App\Models\shop\TextComment;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    use HasRoles {
        roles as parentRoles;
        permissions as parentPermissions;
    }

    protected $fillable = [
        'first_name','last_name', 'email', 'mobile', 'sms_sent_code', 'sms_sent_date', 'sms_sent_tries','password','profile_image'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'sms_sent_date',
        'sms_sent_tries',
        'sms_sent_code'
    ];



    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'sms_sent_date' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function professor(): HasOne
    {
        return $this->hasOne(Professor::class);
    }
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function seller(): HasOne
    {
        return $this->hasOne(Seller\Seller::class);
    }




//    public function is_super_admin(): bool
//    {
//        return $this->hasRole(role_value::adminController);
//    }
//    public function reporter(): bool
//    {
//        return $this->hasRole(role_value::reporter);
//    }
//    public function super_admin(): bool
//    {
//        return $this->hasRole(role_value::super_admin);
//    }
//    public function pro_admin(): bool
//    {
//        return $this->hasRole(role_value::pro_admin);
//    }
    public function professor_role(): bool
    {
        return $this->hasRole(role_value::professor);
    }

    public function student_role(): bool
    {
        return $this->hasRole(role_value::student);
    }
//    public function vendor(): bool
//    {
//        return $this->hasRole(role_value::vendor);
//    }


        public function roles(): BelongsToMany
        {
            return $this->parentRoles()->withTimestamps();
        }

    public function permissions(): BelongsToMany
    {
        return $this->parentPermissions()->withTimestamps();
    }
    public function textComments() : HasMany
    {
        return $this->hasMany(TextComment::class);
    }


//    public function is_super_admin(): bool
//    {
//        return $this->hasRole(role_value::adminController);
//    }


//    public function shopping_carts(): HasMany
//    {
//        return $this->hasMany(ShoppingCart::class, 'user_id');
//    }
//
//    public function addresses(): HasMany
//    {
//        return $this->hasMany(Address::class, 'user_id');
//    }
//
//    public function favorites(): HasMany
//    {
//        return $this->hasMany(Favorite::class, 'user_id');
//    }

    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function teacherTimeSlots(): HasMany
    {
        return $this->hasMany(TeacherTimeSlot::class, 'teacher_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'student_id');
    }


    public function likes()
    {
        return $this->hasMany(Like::class, 'user_id');
    }
    public function full_name(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }


    public function dislikes()
    {
        return $this->hasMany(Dislike::class, 'user_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    // در مدل User
    public function wallet(): HasOne|User
    {
        return $this->hasOne(Wallet::class);
    }


    public function buyer()
    {
        return $this->hasOne(Buyer::class);
    }



}
