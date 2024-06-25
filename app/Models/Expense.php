<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;
use App\Models\Product;

class Expense extends Model
{
    use HasFactory,CafeId;
    protected $fillable = ['cafe_id','product_id','quantity','price','total_expense','expense_date','description','unit_id','auth_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
