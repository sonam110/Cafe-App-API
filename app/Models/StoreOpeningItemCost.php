<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class StoreOpeningItemCost extends Model
{
    use HasFactory,SoftDeletes;
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }
    public function recievedBy()
    {
        return $this->belongsTo(User::class,'recieved_by','id');
    }
}
