<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmountRecieved extends Model
{
    use HasFactory;
    public function cafe()
    {
    	return $this->belongsTo(User::class,'cafe_id','id');
    }
    public function recievedBy()
    {
    	return $this->belongsTo(User::class,'recieved_by','id');
    }
    public function subscription()
    {
    	return $this->belongsTo(CafeSubscription::class,'subscription_id','id');
    }
}
