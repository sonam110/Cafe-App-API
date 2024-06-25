<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;
class Menu extends Model
{
    use HasFactory,CafeId;
    protected $appends = ['recipes_without_product'];

    public function recipes()
    {
        return $this->hasMany(Recipe::class,'menu_id','id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class,'category_id','id');
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class,'unit_id','id');
    }

    public function getRecipesWithoutProductAttribute()
    {
        $data = [];
        $recipes = $this->recipes;
        foreach ($recipes as $key => $recipe) {
            if ($recipe->product == null) {
                $data[] = ['recipe_id' => $recipe->id, 'product_id' => $recipe->product_id];
            }
        }
        return $data;
    }
}
