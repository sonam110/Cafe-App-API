<?php

namespace App\Http\Controllers\Api\Common;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Attendence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\PDF;
// use PDF;
use App\Models\SalaryManagement;
use App\Models\AdvanceManagement;
use App\Models\User;

class AttendenceController extends Controller
{

	public function attendences(Request $request)
	{
		try {
			$query = Attendence::select('attendences.*')->join('users', function ($join) {
				$join->on('users.id', '=', 'attendences.employee_id');
			})
			->orderBy('attendences.id', 'desc')
			->withoutGlobalScope('cafe_id')
			->where('attendences.cafe_id',auth()->user()->cafe_id)
			->with('employee:id,cafe_id,role_id,name,email,password,mobile,address,profile_image_path,designation,document_number,document_type,joining_date,birth_date,status,gender,salary,salary_balance');

			if(!empty($request->name))
			{
				$query->where('users.name', 'LIKE', '%'.$request->name.'%');
			}
			
			if(!empty($request->cafe_id))
			{
				$query->where('attendences.cafe_id', $request->cafe_id);
			}
			if(!empty($request->date))
			{
				$query->where('attendences.date', $request->date);
			}
			if(!empty($request->employee_id))
			{
				$query->where('attendences.employee_id', $request->employee_id);
			}
			if(!empty($request->from_date))
			{
				$query->whereDate('attendences.date', '>=', $request->from_date);
			}
			if(!empty($request->end_date))
			{
				$query->whereDate('attendences.date', '<=', $request->end_date);
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
			return prepareResult(true,'Record Fatched Successfully' ,['attendences'=>$query], 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function store(Request $request)
	{
		$validation = Validator::make($request->all(), [
			'date' => 'required',
			'attendences' => 'required|array'
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}
		DB::beginTransaction();
		try {
			$date = $request->date;
			$attendence_ids = [];
			if($request->attendences){
				foreach ($request->attendences as $key => $value) {
					$attendenceCheck = Attendence::whereDate('date', $date)->where('employee_id', $value['employee_id'])->first();

					// $validation = Validator::make($request->all(),[      
					// 	"attendences.*.employee_id"  => $attendenceCheck ? 'required|declined:false' : 'required', 
					// ],
					// [
					// 	'attendences.*.employee_id.declined' => 'Attendence already exists',
					// ]);
					// if ($validation->fails()) {
					// 	return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
					// } 

					$employee = User::find($value['employee_id']);
					if (empty($attendenceCheck)) {
						$attendenceList = new Attendence;
					}
					else {
						$attendenceList = $attendenceCheck;
					}
					// $attendenceList = new Attendence;
					$attendenceList->cafe_id =  auth()->id();
					$attendenceList->date = $date;
					$attendenceList->created_by = auth()->id();
					$attendenceList->employee_id = $value['employee_id'];
					$attendenceList->attendence = $value['attendence']?$value['attendence']:'1';
					$attendenceList->save();
					$attendence_ids[] = $attendenceList->id; 

					if (SalaryManagement::where('employee_id',$value['employee_id'])->count() <= 0) {
						$salaryManagement = new SalaryManagement;
						$salaryManagement->cafe_id = auth()->user()->cafe_id;
						$salaryManagement->employee_id = auth()->id();
						$salaryManagement->date = date('Y-m-01');
						$salaryManagement->total_salary = $employee->salary ? $employee->salary : 0;
						$salaryManagement->previous_balance = $employee->salary_balance ? $employee->salary_balance : 0;
						$salaryManagement->calculated_salary = 0;
						$salaryManagement->current_month_advance = 0;
						$salaryManagement->new_balance = 0;
						$salaryManagement->save();
					}
				}
			}
			DB::commit();
			$data = Attendence::whereIn('id',$attendence_ids)->get();
			return prepareResult(true,'Your data has been saved successfully' , $data, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'date' => 'required',
			'employee_id'=>'required',
			'attendence'=>'required'
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);

		} 
		DB::beginTransaction();
		try {
			$attendenceCheck = Attendence::whereDate('date', $request->date)->where('employee_id', $request->employee_id)->where('id','!=',$id)->first();

			$validation = Validator::make($request->all(),[      
				"employee_id"  => $attendenceCheck ? 'required|declined:false' : 'required', 
			],
			[
				'employee_id.declined' => 'Attendence already exists',
			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			} 
			$attendenceList = Attendence::find($id);
			if(empty($attendenceList))
			{
				return prepareResult(false,'Record not found' ,[], 500);
			}
			$attendenceList->cafe_id =  auth()->id();
			$attendenceList->date = $request->date;
			$attendenceList->employee_id = $request->employee_id;
			$attendenceList->attendence = $request->attendence;
			$attendenceList->updated_by = auth()->id();
			$attendenceList->save();
			DB::commit();
			return prepareResult(true,'Your data has been Updated successfully' ,$attendenceList, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function multipleUpdate(Request $request)
	{
		$validation = Validator::make($request->all(), [
			'date' => 'required',
			'attendences'=>'required|array',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {
			$attendence_ids = [];
			if($request->attendences){
				foreach ($request->attendences as $key => $value) {
					$attendenceCheck = Attendence::whereDate('date', $request->date)->where('employee_id', $request->employee_id)->where('id','!=',$value['id'])->first();
					$validation = Validator::make($request->all(),[      
						"attendences.*.employee_id"  => $attendenceCheck ? 'required|declined:false' : 'required', 
					],
					[
						'attendences.*.employee_id.declined' => 'Attendence already exists',
					]);
					if ($validation->fails()) {
						return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
					} 
					$attendenceList = Attendence::find($value['id']);
					if(empty($attendenceList))
					{
						return prepareResult(false,'Record not found' ,[], 500);
					}
					$attendenceList->date = $request->date;
					$attendenceList->employee_id = $value['employee_id'];
					$attendenceList->attendence = $value['attendence'];
					$attendenceList->updated_by = auth()->id();
					$attendenceList->save();
					$attendence_ids[] = $attendenceList->id; 
				}
			}
			$data = Attendence::whereIn('id',$attendence_ids)->get();
			DB::commit();
			return prepareResult(true,'Your data has been Updated successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$info = Attendence::with('employee:id,cafe_id,role_id,name,email,password,mobile,address,profile_image_path,designation,document_number,document_type,joining_date,birth_date,status,gender,salary,salary_balance')->find($id);
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
			$info = Attendence::find($id);
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

	public function employeeID()
	{
		try {
			$data = [];
			$data['attendences'] = User::select('id as employee_id')->where('role_id', 3)->get();
			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function monthlyAttendence(Request $request) 
	{
		try {
			$data = [];
			$user = User::find($request->employee_id);
			if (empty($user)) {
				return prepareResult(false,'Employee Not Found' ,[], 500);
			}
			$date = $request->year_month."-01";
			$date = date("Y-m-t", strtotime($date));
			$joining_date = $user->joining_date;
			$joining_dates = substr($joining_date, -13,-6);
			if(($request->year_month) < ($joining_dates))
			{
				return prepareResult(false,'Employee did not Joined on given date' ,null, 500); 
			}
			$total_days = cal_days_in_month(CAL_GREGORIAN,date('m',strtotime($date)),date('Y',strtotime($date)));
			// $absent_days = Attendence::where('employee_id', $user->id)->where('attendence',1)->whereMonth('date', $date )->whereYear('date', $date )->count();
			$absent_days = Attendence::where('employee_id', $user->id)->where('attendence',1)->where('date','like', '%'.$request->year_month.'%' )->count();
			$half_days = Attendence::where('employee_id', $user->id)->where('attendence',3)->where('date','like', '%'.$request->year_month.'%' )->count();
			$full_days = Attendence::where('employee_id', $user->id)->where('attendence',2)->where('date','like', '%'.$request->year_month.'%' )->count();
			// $present_days = $total_days - $absent_days - ($half_days/2);
			$present_days = $full_days + ($half_days/2);
			$current_month_advance = AdvanceManagement::where('employee_id',$request->employee_id)
	 		->where('date','like','%'.$request->year_month.'%')
	 		->sum('paid_amount');

			$user_salary = $user->salary;
			$data['year_month'] = $request->year_month;
			$data['days_in_month'] = $total_days;
			$data['total_days_present'] =  $present_days;
			$data['total_full_day'] = $full_days;
			$data['total_days_halfday'] = $half_days;
			$data['total_days_absent'] = $absent_days;
			$data['employeeSalary'] = $user_salary;
			$data['currentMonthSalary'] = $user_salary * ($present_days/$total_days);
			$data['currentMonthAdvance'] = $current_month_advance;
			return prepareResult(true,'MonthWise Data Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
