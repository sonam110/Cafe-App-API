<?php

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\CafeSubscription;
use App\Models\PaymentQrCode;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;


class CafeSubscriptionController extends Controller
{
	public function cafeSubscriptions(Request $request)
	{
		try {
			$query = CafeSubscription::with('cafe:id,cafe_id,uuid,role_id,name,email,mobile,address,profile_image_path,contact_person_name,contact_person_email,contact_person_phone,description,website,status,subscription_status')
			->orderBy('id', 'desc');

			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->subscription_charge))
			{
				$query->where('subscription_charge', $request->subscription_charge);
			}
			if(!empty($request->subscription_type))
			{
				$query->where('subscription_type', $request->subscription_type);
			}
			// date wise filter from here
			if(!empty($request->subscription_start_date))
			{
				$query->whereDate('subscription_start_date', '>=', $request->subscription_start_date);
			}

			if(!empty($request->subscription_end_date))
			{
				$query->whereDate('subscription_end_date', '<=', $request->subscription_end_date);
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
				'subscription_type' => 'required',
				'cafe_id' => 'required|exists:users,id',
			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}  

			$cafeSubscription = new CafeSubscription;
			$cafeSubscription->cafe_id 				= $request->cafe_id;
			$cafeSubscription->subscription_type 	= $request->subscription_type;
			$cafeSubscription->subscription_charge 	= $request->subscription_charge;
			$cafeSubscription->subscription_start_date= $request->subscription_start_date;
			$cafeSubscription->subscription_end_date = $request->subscription_end_date;
			$cafeSubscription->save();

			$cafe = User::find($request->cafe_id);
			$cafe->subscription_status = $request->subscription_status ? $request->subscription_status : 1;
			$cafe->save();

			DB::commit();
			return prepareResult(true,'Your data has been saved successfully' , $cafeSubscription, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'subscription_type' => 'required',
			'cafe_id' => 'required|exists:users,id',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}
		DB::beginTransaction();
		try {

			$cafeSubscription = CafeSubscription::find($id);
			if (empty($cafeSubscription)) {
				return prepareResult(false,'Record Not Found' ,[], 500);
			}
			$cafeSubscription->cafe_id 				= $request->cafe_id;
			$cafeSubscription->subscription_type 	= $request->subscription_type;
			$cafeSubscription->subscription_charge 	= $request->subscription_charge;
			$cafeSubscription->subscription_start_date= $request->subscription_start_date;
			$cafeSubscription->subscription_end_date = $request->subscription_end_date;
			$cafeSubscription->save();

			$cafe = User::find($request->cafe_id);
			$cafe->subscription_status = $request->subscription_status ? $request->subscription_status : 1;
			$cafe->save();

			DB::commit();
			return prepareResult(true,'Your data has been Updated successfully' ,$cafeSubscription, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$info = CafeSubscription::with('cafe:id,cafe_id,uuid,role_id,name,email,mobile,address,profile_image_path,contact_person_name,contact_person_email,contact_person_phone,description,website,status,subscription_status')->find($id);
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

	public function destroy($id)
	{
		try {
			$info = CafeSubscription::find($id);
			if($info)
			{
				$result = $info->delete();
				return prepareResult(true,'Record Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Record Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
