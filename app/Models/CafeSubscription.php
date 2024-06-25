<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeSubscription extends Model
{
    use HasFactory;

    public function cafe()
    {
    	return $this->belongsTo(User::class,'cafe_id','id');
    }
}
