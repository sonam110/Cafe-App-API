<?php

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\AmountRecieved;
use App\Models\PaymentQrCode;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;


class AmountRecievedController extends Controller
{
	public function amountRecieveds(Request $request)
	{
		try {
			$query = AmountRecieved::with('cafe:id,cafe_id,uuid,role_id,name,email,mobile,address,profile_image_path,contact_person_name,contact_person_email,contact_person_phone,description,website,status,subscription_status','subscription','recievedBy:id,name')
			->orderBy('id', 'desc');

			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->subscription_id))
			{
				$query->where('subscription_id', $request->subscription_id);
			}
			if(!empty($request->amount_recieved))
			{
				$query->where('amount_recieved', $request->amount_recieved);
			}

			if(!empty($request->per_page_record))
			{
				$perPage = $request->per_page_record;
				$page = $request->input('page', 1);
				$total = $query->count();
				$result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

				$pagination =  [
					'data' => $result,
					'total' => $total,
					'current_page' => $page,
					'per_page' => $perPage,
					'last_page' => ceil($total / $perPage)
				];
				$query = $pagination;
			}
			else
			{
				$query = $query->get();
			}
			return prepareResult(true,'Record Fatched Successfully' ,$query, 200); 
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		DB::beginTransaction();
		try {
			$validation = Validator::make($request->all(),  [
				'amount_recieved' => 'required',
				'cafe_id' => 'required|exists:users,id',
				'subscription_id' => 'required|exists:cafe_subscriptions,id',
			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}  

			$amountRecieved = new AmountRecieved;
			$amountRecieved->cafe_id 			= $request->cafe_id;
			$amountRecieved->recieved_by 		= $request->recieved_by;
			$amountRecieved->amount_recieved 	= $request->amount_recieved;
			$amountRecieved->subscription_id 	= $request->subscription_id;
			$amountRecieved->save();

			DB::commit();
			return prepareResult(true,'Your data has been saved successfully' , $amountRecieved, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$info = AmountRecieved::find($id);
			if($info)
			{
				return prepareResult(true,'Record Fatched Successfully' ,$info, 200); 
			}
			return prepareResult(false,'Record not found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
