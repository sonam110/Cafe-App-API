<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Unit;
use App\Traits\CafeId;
class ManageWastage extends Model
{
    use HasFactory,CafeId;
    public function product()
    {
         return $this->belongsTo(Product::class,'product_id','id');
    }
    public function menu()
    {
         return $this->belongsTo(Menu::class,'menu_id','id');
    }
    public function unit()
    {
         return $this->belongsTo(Unit::class,'unit_id','id');
    }
}
