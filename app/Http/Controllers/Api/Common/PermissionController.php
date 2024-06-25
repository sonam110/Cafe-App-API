<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PermissionExtend;
use DB;
use Log;


class PermissionController extends Controller
{
    protected $intime;
    public function __construct()
    {
        $this->intime = \Carbon\Carbon::now();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //listing permission
    public function permissions(Request $request)
    {
        try 
        {
            $query = Permission::select('*');
            if(auth()->user()->role_id!='1')
            {
                $query->whereIn('belongs_to', [2,3,5,6]);
            }
            if(!empty($request->belongs_to))
            {
                $query->where('belongs_to',$request->belongs_to);
            }
            
            if(!empty($request->name))
            {
                $query->where('name', 'LIKE', '%'.$request->name.'%');
            }

            if(!empty($request->se_name))
            {
                $query->where('se_name', 'LIKE', '%'.$request->se_name.'%');
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //creating new permission
    public function store(Request $request)
    {
        $validation = \Validator::make($request->all(),[
            'name'      => 'required|unique:permissions,name',
            'se_name'   => 'required|unique:permissions,se_name',
            'group_name'=> 'required|regex:/^[a-zA-Z0-9-_ ]+$/'
        ]);
        if ($validation->fails()) {
             return prepareResult(false,$validator->errors()->first() ,$validator->errors(), 500);
        }

        DB::beginTransaction();
        try {
            $permission = new Permission;
            $permission->group_name  = $request->group_name;
            $permission->guard_name    = 'api';
            $permission->name = $request->name;
            $permission->se_name  = $request->se_name;
            $permission->belongs_to  = empty($request->belongs_to) ? 1 : $request->belongs_to;
            $permission->save();

            if($request->belongs_to == '3')
            {
            	$roleUsers = DB::table('model_has_roles')->get();
            }
            else
            {
            	$roleUsers = DB::table('model_has_roles')->where('role_id',$request->belongs_to)->get();
            }
            DB::commit();
            return prepareResult(true,'Permission created Ssuccessfully' , $role, 200);
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    /**
     * Display the  specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //view permission
    public function show(Permission $permission)
    {
        try 
        {
            if($permission)
            {
                if(auth()->user()->role_id != 1 && $permission->belongs_to == 1)
                {
                    return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
                }
                return prepareResult(true,'Permission Fatched Successfully' ,$permission, 200); 
            }
            return prepareResult(false,'Permission Not Found' ,[], 500);
        } catch (\Throwable $e) {
            \Log::error($e);
            return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //update permission
    public function update(Request $request, Permission $permission)
    {
        $validation = \Validator::make($request->all(),[
            'name'      => 'required|unique:permissions,name,'.$permission->id,
            'se_name'   => 'required|unique:permissions,se_name,'.$permission->id,
            'group_name'=> 'required|regex:/^[a-zA-Z0-9-_ ]+$/'
        ]);

        if ($validation->fails()) {
          return prepareResult(false,$validator->errors()->first() ,$validator->errors(), 500);
        }

        DB::beginTransaction();
        try {

            if(auth()->user()->role_id != 1 && $permission->belongs_to == 1)
            {
                return prepareResult(false,'Permission Not Found' ,[], 500);
            }
            $permission->group_name  = $request->group_name;
            $permission->name = $request->name;
            $permission->se_name  = $request->se_name;
            $permission->belongs_to  = empty($request->belongs_to) ? 1 : $request->belongs_to;
            $permission->save();
            DB::commit();
            return prepareResult(true,'Permission Updated Successfully' , $roleInfo, 200);
        } catch (\Throwable $e) {
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
    //delete permission
    public function destroy(Permission $permission)
    {
        //Temporary enabled, after deployment removed this function
        try {
            
            if(auth()->user()->role_id != 1 && $permission->belongs_to == 1)
            {
                  return prepareResult(false,'Permission Not Found' ,[], 500);
            }
            $permission->delete();
            return prepareResult(true,'Permission Deleted Successfully' ,[], 200); 
        } catch (\Throwable $e) {
            \Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }
}
