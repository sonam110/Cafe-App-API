<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;

class Order extends Model
{
    use HasFactory,CafeId;
    protected $fillable = [
    	'cafe_id','customer_id','table_number','order_status','order_type','payment_mode','total_amount','tax_amount','payble_amount','cash_amount','online_amount','udhaar_amount','order_duration','invoice_path','name','email','mobile','cancel_reason','auth_id'
    ];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

    public function cafe()
    {
        return $this->belongsTo(User::class, 'cafe_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }
}
