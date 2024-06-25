<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Menu;
// use App\Traits\CafeId;
class Recipe extends Model
{
    use HasFactory;
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
