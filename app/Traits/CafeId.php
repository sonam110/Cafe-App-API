<?php

namespace App\Traits;
use Illuminate\Database\Eloquent\Builder;
trait CafeId {

	protected static function bootCafeId()
    {
    	if (auth()->guard('api')->check()) 
    	{
	        // if user is admin - role_id 1 
	        if ((auth()->guard('api')->user()->role_id == 1)) 
	        {
	        	//nothing heppen
        		// static::creating(function ($model) {
		        //     $model->cafe_id = auth()->guard('api')->user()->cafe_id;
		        // });
	        }
	        else
	        {	        	
        		static::creating(function ($model) {
		            $model->cafe_id = auth()->guard('api')->user()->cafe_id;
		        });
        		static::addGlobalScope('cafe_id', function (Builder $builder) {
	                $builder->where('cafe_id', auth()->guard('api')->user()->cafe_id);
	            });
	        }
	    }
    }
}
