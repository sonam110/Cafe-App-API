<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;
class Category extends Model
{
    use HasFactory,CafeId;

    public function menus()
    {
        return $this->hasMany(Menu::class, 'category_id', 'id');
    }
}
