<?php

namespace App\Http\Controllers\Api\Cafe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
class SubCafeController extends Controller
{
    public function subcafes(Request $request)
    {
        try {

            if(auth()->user()->is_parent != NULL){
                $query = User::withoutGlobalScope('cafe_id')->select('id','cafe_id','uuid','role_id','is_parent','name','email','password','mobile','address','profile_image_path','contact_person_name','contact_person_email','contact_person_phone','description','website','status','subscription_status')
                ->where('role_id', 6)
                ->where('is_parent',auth()->user()->is_parent)
                ->orWhere('id',auth()->user()->is_parent)
                ->whereNot('id',auth()->user()->cafe_id)
                ->with('parent:id,name,email,mobile')
                ->orderBy('id', 'desc');
            } else{
                $query = User::withoutGlobalScope('cafe_id')->select('id','cafe_id','uuid','role_id','is_parent','name','email','password','mobile','address','profile_image_path','contact_person_name','contact_person_email','contact_person_phone','description','website','status','subscription_status')
                ->where('role_id', 6)
                ->where('is_parent',auth()->user()->cafe_id)
                ->with('parent:id,name,email,mobile')
                ->orderBy('id', 'desc');
            }

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
            $user = User::find(auth()->user()->cafe_id);
            $totalSubCafePerm = (!empty($user)) ? $user->no_of_subcafe : 0;
            $totalSubCafeExist  = User::withoutGlobalScope('cafe_id')->where('is_parent',auth()->user()->cafe_id)->where('role_id','6')->count();
           
            if($totalSubCafeExist >= $totalSubCafePerm){

                return prepareResult(false,'You have exceeded your limit to create a sub-cafe' ,[], 500);
            }
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
            $user->role_id              = 6;
            $user->uuid                 = Str::uuid();
            $user->is_parent            = auth()->user()->cafe_id;
            $user->name                 = $request->name;
            $user->email                = $request->email;
            $user->password             = Hash::make($request->password);
            $user->mobile               = $request->mobile;
            $user->address              = $request->address;
            $user->profile_image_path   = $request->profile_image_path;
            $user->description          = $request->description;
            $user->website              = $request->website;
            $user->contact_person_email = $request->contact_person_email; 
            $user->contact_person_name  = $request->contact_person_name;
            $user->contact_person_phone = $request->contact_person_phone;
            $user->status               = $request->status ? $request->status : 1;
            $user->save();
            $updateCafeId = User::where('id',$user->id)->update(['cafe_id'=> $user->id]);

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

            $user = User::withoutGlobalScope('cafe_id')->where('role_id',6)->where('is_parent',auth()->user()->cafe_id)->find($id);
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
            $user->description          = $request->description;
            $user->website              = $request->website;
            $user->contact_person_email = $request->contact_person_email; 
            $user->contact_person_name  = $request->contact_person_name;
            $user->contact_person_phone = $request->contact_person_phone;
            $user->status = $request->status ? $request->status : $user->status;
            $user->save();
           
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
            $info = User::withoutGlobalScope('cafe_id')->where('role_id',6)->where('is_parent',auth()->user()->cafe_id)->with('parent:id,name,email,mobile')->find($id);
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
            $info = User::withoutGlobalScope('cafe_id')->where('role_id',6)->where('is_parent',auth()->user()->cafe_id)->find($id);
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
