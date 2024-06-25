<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;


class PaymentQrCode extends Model
{
    use HasFactory,CafeId;
}
