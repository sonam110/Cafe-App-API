<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\CafeId;
use App\Models\CafeSubscription;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class User extends Authenticatable
{
   use HasApiTokens, HasFactory, Notifiable,HasRoles,CafeId;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_balance',
        'salary_balance'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function cafeSubscriptions()
    {
        return $this->hasMany(CafeSubscription::class, 'cafe_id', 'id');
    }

    public function paymentQrCodes()
    {
        return $this->hasMany(PaymentQrCode::class, 'user_id', 'id');
    }
    public function parent()
    {
        return $this->belongsTo(User::class,'is_parent','id');
    }
    public function role()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }
}
