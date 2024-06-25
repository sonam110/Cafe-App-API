<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;

class StockManage extends Model
{
    use HasFactory,CafeId;

    protected $fillable = ['cafe_id','product_id','unit_id','quantity','stock_operation','resource','shop_name','date','bill_no','address','purchase_by','recieved_by','transfer_cafe_id','comment','auth_id'];

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
        return $this->belongsTo(User::class,'transfer_cafe_id','id')->withoutGlobalScope('cafe_id');
    }
}
