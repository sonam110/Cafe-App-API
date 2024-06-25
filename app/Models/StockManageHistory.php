<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class StockManageHistory extends Model
{
    use HasFactory,SoftDeletes;
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function recievedBy()
    {
        return $this->belongsTo(User::class,'recieved_by','id');
    }
    public function transferInfo()
    {
        return $this->belongsTo(User::class,'transfer_cafe_id','id');
    }
}
