<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;

class OrderDetail extends Model
{
    use HasFactory,CafeId;
    protected $fillable = ['cafe_id','order_id','menu_id','menu_detail','quantity','price','sub_total','total','discount_per','discount_amount','instructions','preparation_duration','auth_id'];

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'menu_id', 'menu_id')->with('product:id,name','unit:id,name');
    }
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id')->with('category:id,name,tax');
    }
}
