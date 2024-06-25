<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use App\Models\AssignedLeave;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AssignedLeaveController extends Controller
{
    //
	public function assignedLeaves(Request $request)
	{
		try {
			$query = AssignedLeave::select('*')
			->orderBy('id', 'desc')->with('employee:id,name');

			if (empty($request->year)) {
				$query->where('year', date('Y'));
			}
			else
			{
				$query->where('year', $request->year);
			}
			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->employee_id))
			{
				$query->where('employee_id', $request->employee_id);
			}
			if(!empty($request->month))
			{
				$query->where('month', $request->month);
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
			return prepareResult(true,'Records Fatched Successfully' ,['leaves'=>$query], 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		$validation = Validator::make($request->all(),  [
            'year_month' => 'required',
            'leaves' => 'required|array'           
        ]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {
			$date = $request->year_month.'-1';
			$month = date('m',strtotime($date));
			$year = date('Y',strtotime($date));
			$eLeave_ids = [];
			foreach ($request->leaves as $key => $leave) {
				$validation = Validator::make($request->all(),[
					"leaves.*.no_of_leaves"  => 'required|integer|min:0|max:30',
					"leaves.*.employee_id"  => 'required',
				]);
				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				}
				$oldData = AssignedLeave::where('month',$month)->where('year',$year)->where('employee_id',$leave['employee_id'])->first();
				if (empty($oldData)) {
					$eLeave = new AssignedLeave;
				}
				else {
					$eLeave = $oldData;
				}
				$eLeave->cafe_id = auth()->user()->cafe_id;
				$eLeave->employee_id = $leave['employee_id'];
				$eLeave->no_of_leaves  = $leave['no_of_leaves'];
				$eLeave->month = $month;
				$eLeave->year = $year;
				$eLeave->save();
				$eLeave_ids[] = $eLeave->id;
			}
			$eLeaves = AssignedLeave::whereIn('id',$eLeave_ids)->get();
			DB::commit();
			return prepareResult(true,'Your data has been saved successfully' , $eLeaves, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function update(Request $request)
	{
		$validation = Validator::make($request->all(),  [
            'year_month' => 'required',
            'leaves' => 'required|array'           
        ]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {
			$date = $request->year_month.'-1';
			$month = date('m',strtotime($date));
			$year = date('Y',strtotime($date));
			$eLeave_ids = [];
			foreach ($request->leaves as $key => $leave) {
				$validation = Validator::make($request->all(),[
					"leaves.*.no_of_leaves"  => 'required|integer|min:0|max:30',
					"leaves.*.employee_id"  => 'required',
				]);
				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				}
				$oldData = AssignedLeave::where('month',$month)->where('year',$year)->where('employee_id',$leave['employee_id'])->where('id','!=',$leave['id'])->first();
				if (!empty($oldData)) {
					return prepareResult(false,'Record already found with this month year ('.$request->year_month.') of this employee (employee id '.$leave['employee_id'].')' ,[], 500);
				}
				$eLeave = AssignedLeave::find($leave['id']);
				if (empty($eLeave)) {
					return prepareResult(false,'Record not found' ,[], 500);
				}
				$eLeave->cafe_id = auth()->user()->cafe_id;
				$eLeave->employee_id = $leave['employee_id'];
				$eLeave->no_of_leaves  = $leave['no_of_leaves'];
				$eLeave->month = $month;
				$eLeave->year = $year;
				$eLeave->save();
				$eLeave_ids[] = $eLeave->id;
			}
			$eLeaves = AssignedLeave::whereIn('id',$eLeave_ids)->get();
			DB::commit();
			return prepareResult(true,'Your data has been updated successfully' , $eLeaves, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$info = AssignedLeave::find($id);
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
			$info = AssignedLeave::find($id);
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
