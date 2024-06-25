<?php

namespace App\Http\Controllers\Api\Cafe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use DB;
use Log;
use Validator;
use Hash;
use Str;

class CustomerController extends Controller
{
	public function customers(Request $request)
	{
		try {
			$query = User::select('id','cafe_id','name','email','mobile','address','gender','account_balance')
			->where('role_id', 5)
			->orderBy('id', 'desc');                   
			if(!empty($request->id))
			{
				$query->where('id', $request->id);
			}
			if(!empty($request->mobile))
			{
				$query->where('mobile', 'LIKE', '%'.$request->mobile.'%');
			}
			if(!empty($request->email))
			{
				$query->where('email', 'LIKE', '%'.$request->email.'%');
			}
			if(!empty($request->name))
			{
				$query->where('name', 'LIKE', '%'.$request->name.'%');
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
		$validation = Validator::make($request->all(),  [
			'name' => 'required',
			'mobile' => 'required|numeric|digits_between:10,10',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}  
		DB::beginTransaction();
		$random = \Str::random(5);
		try {
			if (!empty($request->email)) {
				$validation = Validator::make($request->all(),  [
					'email' => 'email:rfc,dns',
				]);
				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				} 
				$email = auth()->id().'.'.$request->email;
				$email = $request->email;
			}
			else
			{
				// $email = auth()->id().'.'.$request->mobile.'@cafe.in';
				$email = 'emptyEmail'.$random.'@cafe.in';

			}
			$checkUser = User::withoutGlobalScope('cafe_id')->where('email',$email)->first();
			if(!empty($checkUser))
			{
				return prepareResult(false,'Customer Already exists in your system!' ,$checkUser, 500);
			}
			$user = new User;
			$user->role_id = 5;
			$user->uuid = Str::uuid();
			$user->name = $request->name;
			$user->email  = $email;
			$user->mobile = $request->mobile;
			$user->password = bcrypt($request->mobile);
			$user->gender = $request->gender;
			$user->address = $request->address;
			$user->account_balance = $request->account_balance;
			$user->save();
			DB::commit();
			return prepareResult(true,'Customer created successfully' , $user, 200);
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
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {
		    $email = $request->email ? $request->email : 'emptyEmail@cafe.in';
			$user = User::select('id','cafe_id','name','email','mobile','address','gender','account_balance')->where('role_id',5)->find($id);
			if (empty($user)) {
				return prepareResult(false,'Record Not Found' ,[], 500);
			}
			$checkUser = User::withoutGlobalScope('cafe_id')->where('id','!=',$id)->where('email',$email)->first();
			if(!empty($checkUser))
			{
				return prepareResult(false,'Customer Already exists in your system!' ,$checkUser, 500);
			}
			
			$user->role_id = 5;
			$user->name = $request->name;
			$user->email  = $email;
			$user->mobile = $request->mobile;
			$user->gender = $request->gender;
			$user->address = $request->address;
			$user->account_balance = $request->account_balance;
			$user->save();

			DB::commit();
			return prepareResult(true,'Customer Updated successfully' ,$user, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$info = User::select('id','cafe_id','name','email','mobile','address','gender','account_balance')->where('role_id',5)->find($id);
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
			$info = User::where('role_id',5)->find($id);
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