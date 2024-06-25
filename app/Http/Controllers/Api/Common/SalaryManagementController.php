<?php

namespace App\Http\Controllers\Api\Common;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\SalaryManagement;
use App\Models\AdvanceManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
// use App\Models\Employee;
use App\Models\User;

class SalaryManagementController extends Controller
{
	public function salaryManagements(Request $request)
	{
		try {
			$query = AdvanceManagement::select('*')
			->with('employee:id,name,salary')
			->orderBy('id', 'desc');

			if(!empty($request->id))
			{
				$query->where('id', $request->id);
			}
			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->employee_id))
			{
				$query->where('employee_id', $request->employee_id);
			}

			if(!empty($request->from_date))
			{
				$query->whereDate('date', '>=', $request->from_date);
			}

			if(!empty($request->end_date))
			{
				$query->whereDate('date', '<=', $request->end_date);
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
			'paid_amount' => 'required|numeric',
			'employee_id' => 'required|numeric|exists:users,id',
			'date' => 'required'
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}    
		DB::beginTransaction();
		try {
			$date = $request->date ? $request->date : date('Y-m-d');
			$user = User::find( $request->employee_id);
			$advanceManagement = new AdvanceManagement;
			$advanceManagement->cafe_id = auth()->user()->cafe_id;
			$advanceManagement->employee_id = $request->employee_id;
			$advanceManagement->previous_balance = $user->salary_balance;
			$advanceManagement->paid_amount = $request->paid_amount;
			$advanceManagement->new_balance = $user->salary_balance + $request->paid_amount;
			$advanceManagement->date = $date;
			$advanceManagement->remarks = $request->remarks;
			$advanceManagement->save();

			$advance = AdvanceManagement::where('employee_id',$request->employee_id)
	 		->where('date','like','%'.date('Y-m',strtotime($date)).'%')
	 		->sum('paid_amount');
	 		
	 		$salary_paid = $request->paid_amount;
	 		$emp_salary = User::find($request->employee_id)->salary;
	 		$balance = $emp_salary - ($salary_paid + $advance);

            // update salary management table
            $salaryManagement = SalaryManagement::withoutGlobalScope('cafe_id')->where('employee_id',$request->employee_id)->whereMonth('date',date('m'))->whereYear('date',date('Y'))->first();
            if (!empty($salaryManagement)) {
                $salaryManagement->current_month_advance = $advance;
                $salaryManagement->save();
            }

            // updating the user table as well
			$user->salary_balance =  $advanceManagement->new_balance;
			$user->save();

			DB::commit();
			return prepareResult(true,'Your data has been saved successfully', $advanceManagement, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'paid_amount'                   => 'required|numeric',
			'employee_id'                         => 'required|numeric|exists:users,id',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);

		} 
		DB::beginTransaction();
		try {
			$date = $request->date ? $request->date : date('Y-m-d');
			$user = User::find( $request->employee_id);
			$advanceManagement = AdvanceManagement::find($id);
			$advanceManagement->employee_id = $request->employee_id;
			$advanceManagement->paid_amount = $request->paid_amount;
			$advanceManagement->new_balance = $advanceManagement->previous_balance + $request->paid_amount;
			$advanceManagement->date = $date;
			$advanceManagement->remarks = $request->remarks;
			$advanceManagement->save();

			$advance = AdvanceManagement::where('employee_id',$request->employee_id)
	 		->where('date','like','%'.date('Y-m',strtotime($date)).'%')
	 		->sum('paid_amount');

            // update salary management table
            $salaryManagement = SalaryManagement::withoutGlobalScope('cafe_id')->where('employee_id',$request->employee_id)->whereMonth('date',date('m'))->whereYear('date',date('Y'))->first();
            if (!empty($salaryManagement)) {
                $salaryManagement->current_month_advance = $advance;
                $salaryManagement->save();
            }

            // updating the user table as well
			$user->salary_balance =  $advanceManagement->new_balance;
			$user->save();
			DB::commit();
			return prepareResult(true,'Your data has been Updated successfully' ,$advanceManagement, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$salaryManagement = AdvanceManagement::find($id);
			if($salaryManagement)
			{
				return prepareResult(true,'Record Fatched Successfully' ,$salaryManagement, 200); 
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
			$salaryManagement = AdvanceManagement::find($id);
			if($salaryManagement)
			{
				$result = $salaryManagement->delete();
				return prepareResult(true,'Record Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Record Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
