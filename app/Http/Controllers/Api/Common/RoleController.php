<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use DB;
use Str;
use Log;
use App\Models\User;

class RoleController extends Controller
{
   
    public function __construct()
    {
        /*$this->middleware('permission:role-browse',['except' => ['roles']]);
        $this->middleware('permission:role-add', ['only' => ['store']]);
        $this->middleware('permission:role-edit', ['only' => ['update','action']]);
        $this->middleware('permission:role-read', ['only' => ['show']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);*/

    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //listing roles
    public function roles(Request $request)
    {
        
        try {
            
             //Role and permission sync
            
            $column = 'id';
            $dir = 'Desc';
            if(!empty($request->sort))
            {
                if(!empty($request->sort['column']))
                {
                    $column = $request->sort['column'];
                }
                if(!empty($request->sort['dir']))
                {
                    $dir = $request->sort['dir'];
                }
            }
            $query = Role::select('*')
            ->with('permissions')
            ->where('id','!=',1)
            ->where('cafe_id',auth()->user()->cafe_id)
            //->orWhere('is_default','1')
            ->orderBy($column,$dir);

            if(!empty($request->name))
            {
                $query->where('name', 'LIKE', '%'.$request->name.'%');
            }

            if(!empty($request->se_name))
            {
                $query->where('se_name', 'LIKE', '%'.$request->se_name.'%');
            }
            
            if(!empty($request->status))
            {
                $query->where('status',$request->status);
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
        } catch(Exception $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //creating new role
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'se_name'   => 'required|unique:roles|regex:/^[a-zA-Z0-9-_ ]+$/',
            'permissions' => 'required'
        ]);
        if ($validator->fails()) {
            return prepareResult(false,$validator->errors()->first() ,$validator->errors(), 500);
        }
        
        DB::beginTransaction();
        try 
        {
            $role = new Role;
            $role->name = \Str::slug(substr($request->se_name, 0, 20));
            $role->cafe_id  = auth()->user()->cafe_id;
            $role->se_name  = $request->se_name;
            $role->guard_name  = 'api';
            $role->status = $request->status ? $request->status : 1;
            $role->save();
            DB::commit();
            if($role) {
                $role->syncPermissions($request->permissions);
             
            }
            
           return prepareResult(true,'Role created Ssuccessfully' , $role, 200);
        } catch(Exception $e) {
          Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //view role
    public function show(Role $role)
    {
        try {
            $roleInfo = Role::with('permissions');

            $roleInfo = $roleInfo->where('cafe_id',auth()->user()->cafe_id)->find($role->id);
            if($roleInfo)
            {
                return prepareResult(true,'Role Fatched Successfully' ,$roleInfo, 200); 
            }
            return prepareResult(false,'Role Not Found' ,[], 500);

        } catch(Exception $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //update roles data
    public function update(Request $request, Role $role)
    {
        $validator = \Validator::make($request->all(), [
            'se_name'   => 'required|regex:/^[a-zA-Z0-9-_ ]+$/',
            'permissions' => 'required'
        ]);
        if ($validator->fails()) {
            return prepareResult(false,$validator->errors()->first() ,$validator->errors(), 500);
        }
        $roleInfo = Role::select('*');
        $roleInfo = $roleInfo->where('cafe_id',auth()->user()->cafe_id)->find($role->id);
        $old = $role;
        $old['permissions'] = $role->permissions;
        DB::beginTransaction();
        try {
            
            if($roleInfo)
            {
                $roleInfo->se_name  = $request->se_name;
                $roleInfo->status = $request->status ? $request->status : 1;
                $roleInfo->save();
                DB::commit();
                if($roleInfo) {
                    $roleInfo->syncPermissions($request->permissions);

                    // sync new role permissions with user
                    $roleUsers = DB::table('model_has_roles')
                    ->where('role_id',$roleInfo->id)
                    ->get();
                    foreach ($roleUsers as $key => $value) 
                    {
                        $user = User::find($value->model_id);
                        if($user)
                        {
                            $user->syncPermissions($request->permissions);
                        }
                    }
                    
                }
              
                return prepareResult(true,'Role Updated Successfully' , $roleInfo, 200);
            }
           return prepareResult(false,'Role Not Found' ,[], 500);
        } catch(Exception $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //delete role
    public function destroy(Role $role)
    {
        try {
            $roleInfo = Role::select('*');
            
            $roleInfo = $roleInfo->where('cafe_id',auth()->user()->cafe_id)->find($role->id);
            $old = $roleInfo;
            if($roleInfo)
            {
                $roleInfo->delete();

                return prepareResult(true,'Role Deleted Successfully' ,[], 200); 
            }
            return prepareResult(false,'Role Not Found' ,[], 500);
            
        } catch(Exception $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
            
        }
    }

    /**
     * Action on the specified resource from storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    //action performed on role
    
}
