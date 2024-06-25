<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CafeId;
use App\Models\User;

class SalaryManagement extends Model
{
    use HasFactory,CafeId;

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }
}
