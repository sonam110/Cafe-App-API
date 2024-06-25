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


class CafeController extends Controller
{
	public function cafes(Request $request)
	{
		try {
			$query = User::withoutGlobalScope('cafe_id')->select('id','cafe_id','uuid','role_id','name','email','password','mobile','address','profile_image_path','contact_person_name','contact_person_email','contact_person_phone','description','website','status','no_of_subcafe','subscription_status')
			->where('role_id', 2)
			->with('cafeSubscriptions:cafe_id,subscription_type,subscription_charge,subscription_start_date,subscription_end_date','paymentQrCodes')
			->orderBy('id', 'desc');

			if(!empty($request->name))
			{
				$query->where('name', 'LIKE', '%'.$request->name.'%');
			}
			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->mobile))
			{
				$query->where('mobile', 'LIKE', '%'.$request->mobile.'%');
			}
			if(!empty($request->email))
			{
				$query->where('email', 'LIKE', '%'.$request->email.'%');
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
				'name' => 'required',
				'mobile' => 'required|numeric|digits_between:10,10',
				'email' => 'required|email:rfc,dns|unique:users,email',
				'contact_person_email' => 'required',
				'contact_person_name' => 'required',
				'contact_person_phone' => 'required',
				'password' => 'required|min:6|max:25',
				'address' => 'required'
			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}  

			$user = new User;
			$user->role_id 				= 2;
			$user->uuid 				= Str::uuid();
			$user->name 				= $request->name;
			$user->email  				= $request->email;
			$user->password 			= Hash::make($request->password);
			$user->mobile 				= $request->mobile;
			$user->address 				= $request->address;
			$user->profile_image_path 	= $request->profile_image_path;
			$user->description  		= $request->description;
			$user->website     			= $request->website;
			$user->contact_person_email = $request->contact_person_email; 
			$user->contact_person_name 	= $request->contact_person_name;
			$user->contact_person_phone = $request->contact_person_phone;
			$user->no_of_subcafe 		= 	$request->no_of_subcafe;
			$user->status 				= $request->status ? $request->status : 1;
			$user->subscription_status 	= $request->subscription_status ? $request->subscription_status : 0;
			$user->save();
			$updateCafeId = User::where('id',$user->id)->update(['cafe_id'=> $user->id]);

			if (!empty($request->qr_codes) && is_array($request->qr_codes)) {
				foreach ($request->qr_codes as $key => $qr_code) {
					$paymentQrCode = new PaymentQrCode;
					$paymentQrCode->cafe_id = $user->id;
					$paymentQrCode->user_id = $user->id;
					$paymentQrCode->qr_code_image_path = $qr_code;
					$paymentQrCode->save();

				}
			}

			DB::commit();
			return prepareResult(true,'Your data has been saved successfully' , $user, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'name'  => 'required',
			'mobile' => 'required|numeric|digits_between:10,10',
			'email' => 'email|required|unique:users,email,'.$id,
			'contact_person_email' => 'required',
			'contact_person_name' => 'required',
			'contact_person_phone' => 'required',
			'address' => 'required'
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}
		DB::beginTransaction();
		try {

			$user = User::withoutGlobalScope('cafe_id')->where('role_id',2)->find($id);
			if (empty($user)) {
				return prepareResult(false,'user not found' ,[], 500);
			}

			$user->name = $request->name;
			$user->email  = $request->email;
			if(!empty($request->password))
			{
				$user->password = Hash::make($request->password);
			}
			$user->mobile = $request->mobile;
			$user->address = $request->address;
			$user->profile_image_path =  $request->profile_image_path;
			$user->description  		= $request->description;
			$user->website     			= $request->website;
			$user->contact_person_email = $request->contact_person_email; 
			$user->contact_person_name 	= $request->contact_person_name;
			$user->contact_person_phone = $request->contact_person_phone;
			$user->no_of_subcafe = $request->no_of_subcafe;
			$user->status = $request->status ? $request->status : $user->status;
			$user->subscription_status =  $request->subscription_status ? $request->subscription_status : $user->subscription_status;
			$user->save();
			if (!empty($request->qr_codes) && is_array($request->qr_codes)) {
				PaymentQrCode::where('user_id',$id)->delete();
				foreach ($request->qr_codes as $key => $qr_code) {
					$paymentQrCode = new PaymentQrCode;
					$paymentQrCode->cafe_id = $user->id;
					$paymentQrCode->user_id = $user->id;
					$paymentQrCode->qr_code_image_path = $qr_code;
					$paymentQrCode->save();

				}
			}

			DB::commit();
			return prepareResult(true,'Your data has been Updated successfully' ,$user, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$info = User::withoutGlobalScope('cafe_id')->where('role_id',2)->with('cafeSubscriptions')->find($id);
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
			$info = User::withoutGlobalScope('cafe_id')->where('role_id',2)->find($id);
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

	public function childLogin(Request $request)
	{
		$validation = Validator::make($request->all(),  [
			'account_uuid'      => 'required'
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}
		try {
			$parent_key = null;
			if(empty($request->is_back_to_self_account))
			{
				$parent_key = base64_encode(auth()->user()->uuid);
			}

			$user = User::withoutGlobalScope('cafe_id')->where('uuid', base64_decode($request->account_uuid))
			->withoutGlobalScope('cafe_id')
			->first();
			if (!$user)  {
				return prepareResult(false,'Record not found' ,[], 500);
			}
			$user['token'] = $user->createToken('authToken')->accessToken;
			$user['parent_key'] =  $parent_key;
			$user['cafe_subscriptions'] =  $user->cafeSubscriptions;
			$role   = Role::where('id', $user->role_id)->first();
			$user['permissions']  = $role->permissions()->select('id','se_name', 'group_name','belongs_to')->get();
			return prepareResult(true,'request_successfully_submitted' ,$user, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function cafeSubscription(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'subscription_type' => 'required',
		]);

		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try { 
			$cafeSubscription = CafeSubscription::where('cafe_id',$id)->latest()->first();
			if (empty($cafeSubscription)) {
				$cafeSubscription = new CafeSubscription;
			}
			$cafeSubscription->cafe_id 				= $id;
			$cafeSubscription->subscription_type 	= $request->subscription_type;
			$cafeSubscription->subscription_charge 	= $request->subscription_charge;
			$cafeSubscription->subscription_start_date= $request->subscription_start_date;
			$cafeSubscription->subscription_end_date = $request->subscription_end_date;
			$cafeSubscription->save();
			DB::commit();
			return prepareResult(true,'Subscription Updated successfully' ,$cafeSubscription, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
