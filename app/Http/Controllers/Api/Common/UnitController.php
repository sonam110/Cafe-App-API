<?php

namespace App\Http\Controllers\Api\Common;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class UnitController extends Controller
{
    public function units(Request $request)
    {
        try {

            $query = Unit::select('*')
            ->orderBy('id', 'desc');
            if(!empty($request->id))
            {
                $query->where('id', $request->id);
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

            return prepareResult(true,'Records Fatched Successfully' ,$query, 200);
            
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }


    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'abbreiation' => 'required',
        ]);
        if ($validation->fails()) {
            return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
        } 
        DB::beginTransaction();
        try {     
            $unit = new Unit;
            $unit->name = $request->name;
            $unit->abbreiation = $request->abbreiation;
            $unit->minvalue = $request->minvalue;
            $unit->save();
            DB::commit();
            return prepareResult(true,'Your data has been saved successfully' , $unit, 200);

        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'abbreiation' => 'required',
        ]);
        if ($validation->fails()) {
            return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
        }   
        DB::beginTransaction();
        try { 

         $unit = Unit::find($id);
         if (empty($unit)) {
             return prepareResult(false,'Record Not Found' ,[], 500);
         }
         $unit->name = $request->name;
         $unit->abbreiation = $request->abbreiation;
         $unit->minvalue = $request->minvalue;
         $unit->save();
         DB::commit();
         return prepareResult(true,'Your data has been Updated successfully' ,$unit, 200);

     } catch (\Throwable $e) {
        Log::error($e);
        return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
    }
}

public function show($id)
{
    try {
        $unit = Unit::find($id);
        if($unit)
        {
            return prepareResult(true,'Record Fatched Successfully' ,$unit, 200); 
        }
        return prepareResult(false,'Record Not Found' ,[], 500);
    } catch (\Throwable $e) {
        Log::error($e);
        return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
    }
}

public function destroy($id)
{
    try {
        $unit = Unit::find($id);
        if($unit)
        {
            $result=$unit->delete();
            return prepareResult(true,'Record Deleted Successfully' ,$result, 200); 
        }
        return prepareResult(false,'Record Not Found' ,[], 500);
    } catch (\Throwable $e) {
        Log::error($e);
        return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
    }
}
}
