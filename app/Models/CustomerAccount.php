<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;

class CustomerAccount extends Model
{
    use HasFactory,CafeId;

    public function customer()
    {
    	return $this->belongsTo(User::class,'customer_id','id');
    }
}
