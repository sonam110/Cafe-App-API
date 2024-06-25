<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\SalaryManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    //
	public function employees(Request $request)
	{
		try {
			$query = User::select('id','cafe_id','role_id','name','email','password','mobile','address','profile_image_path','designation','document_number','document_type','joining_date','birth_date','status','gender','salary','salary_balance')
			->whereNotIn('role_id',['1','2','5','6'])
			->with('role:id,name,se_name')
			->orderBy('id', 'desc');
			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->role_id))
			{
				$query->where('role_id', $request->role_id);
			}
			if(!empty($request->name))
			{
				$query->where('name', 'LIKE', '%'.$request->name.'%');
			}
			if(!empty($request->email))
			{
				$query->where('email', 'LIKE', '%'.$request->email.'%');
			}
			if(!empty($request->mobile))
			{
				$query->where('mobile', 'LIKE', '%'.$request->mobile.'%');
			}
			if(!empty($request->designation))
			{
				$query->where('designation', 'LIKE', '%'.$request->designation.'%');
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
			return prepareResult(true,'Records Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		$validation = Validator::make($request->all(),  [
            'name'                      => 'required',
            'mobile'                      => 'required|numeric|digits_between:10,10|unique:App\Models\User,mobile',
            'email'                      => 'required|email|unique:App\Models\User,email',
            'document_number'                      => 'required|unique:App\Models\User,document_number',
            'designation'                   => 'required',
            'address'             => 'required',
            'password'              => 'required|min:6|max:25',    
            'salary'      => 'required',
            'joining_date'      => 'required|date|after_or_equal:'.\Carbon\Carbon::now()->subYears(1)->format('Y-m-d'),
            'birth_date' => 'required|date|before_or_equal:'.\Carbon\Carbon::now()->subYears(14)->format('Y-m-d'),       
        ]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();

		try {
			$employee = new User;
			$employee->uuid = Str::uuid();
			if(auth()->user()->cafe_id == 1)
			{
				$role_id = (!empty($request->role_id)) ? $request->role_id : 3;
				$employee->role_id = $role_id;
			}
			else{
				$role_id = (!empty($request->role_id)) ? $request->role_id : 4;
				$employee->role_id = $role_id;
			}
			if($request->document_type == 'Pan Card')
			{
				$validation = Validator::make($request->all(),  [
		            'document_number' => 'regex:/[A-Z]{5}[0-9]{4}[A-Z]{1}/',      
		        ]);
				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				}
			}

			if($request->document_type == 'Aadhar Card')
			{
				$validation = Validator::make($request->all(),  [  
		            'document_number' => 'digits:12',      
		        ]);
				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				}
			}

			$employee->cafe_id 				= auth()->user()->cafe_id;
			$employee->name 				= $request->name;
			$employee->email  				= $request->email;
			$employee->password 			= Hash::make($request->password);
			$employee->mobile 				= $request->mobile;
			$employee->address 				= $request->address;
			$employee->profile_image_path 	= $request->profile_image_path;
			$employee->designation 			= $request->designation;
			$employee->document_type 		= $request->document_type;
			$employee->document_number 		= $request->document_number;
			$employee->joining_date 		= $request->joining_date;
			$employee->birth_date 			= $request->birth_date;
			$employee->gender 				= $request->gender;
			$employee->salary 				= $request->salary;
			$employee->salary_balance 		= $request->salary_balance ? $request->salary_balance : 0;
			$employee->save();

			 //Role and permission sync
            $role = Role::where('id', $role_id)->first();
            $permissions = $role->permissions->pluck('name');
            
            $employee->assignRole($role->name);
            foreach ($permissions as $key => $permission) {
                $employee->givePermissionTo($permission);
            }


			DB::commit();
			return prepareResult(true,'Employee created successfully' , $employee, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'name' => 'required',
			'mobile' => 'required|numeric|digits_between:10,10|unique:users,mobile,'.$id,
			'email' => 'email:rfc,dns|required|unique:users,email,'.$id,
			'document_number' => 'required|unique:users,document_number,'.$id,
			'designation'                   => 'required',
            'address'                       => 'required',
            'birth_date' => 'required|date|before_or_equal:'.\Carbon\Carbon::now()->subYears(14)->format('Y-m-d'),
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {
			$employee = User::whereNotIn('role_id',['1','2','5','6'])->find($id);
			if (empty($employee)) {
				return prepareResult(false,'Record not found' ,[], 500);
			}
			if(auth()->user()->cafe_id == 1)
			{
				$role_id = (!empty($request->role_id)) ? $request->role_id : 3;
				$employee->role_id = $role_id;
			}
			else{
				$role_id = (!empty($request->role_id)) ? $request->role_id : 4;
				$employee->role_id = $role_id;
			}
			$employee->name 				= $request->name;
			$employee->email  				= $request->email;
			$employee->password 			= Hash::make($request->password);
			$employee->mobile 				= $request->mobile;
			$employee->address 				= $request->address;
			$employee->profile_image_path 	= $request->profile_image_path;
			$employee->designation 			= $request->designation;
			$employee->document_type 		= $request->document_type;
			$employee->document_number 		= $request->document_number;
			$employee->joining_date 		= $request->joining_date;
			$employee->birth_date 			= $request->birth_date;
			$employee->gender 				= $request->gender;
			$employee->salary 				= $request->salary;
			$employee->salary_balance 		= $request->salary_balance;
			$employee->save();

			 //delete old role and permissions
            DB::table('model_has_roles')->where('model_id', $employee->id)->delete();
            DB::table('model_has_permissions')->where('model_id', $employee->id)->delete();

            //Role and permission sync
            $role = Role::where('id', $role_id)->first();
            $permissions = $role->permissions->pluck('name');
            
            $employee->assignRole($role->name);
            foreach ($permissions as $key => $permission) {
                $employee->givePermissionTo($permission);
            }
           
			DB::commit();
			return prepareResult(true,'Employee Updated successfully' ,$employee, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$employee = User::whereNotIn('role_id',['1','2','5','6'])->find($id);
			if($employee)
			{
				return prepareResult(true,'Employee Detail Fatched Successfully' ,$employee, 200); 
			}
			return prepareResult(false,'Employee not found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$employee = User::whereNotIn('role_id',['1','2','5','6'])->find($id);
			if($employee)
			{
				$result = $employee->delete();
				return prepareResult(true,'Employee Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Employee Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

}
