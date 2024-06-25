<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalaryManagement;
use App\Models\AdvanceManagement;
use App\Models\User;

class GenerateSalary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:salary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employees = User::withoutGlobalScope('cafe_id')->whereIn('role_id',['3','4'])->where('status',1)->get();
        foreach ($employees as $key => $employee) {
            if(date('Y-m-d') == date('Y-m-01'))
            {
                $salaryManagement = new SalaryManagement;
                $salaryManagement->cafe_id = $employee->cafe_id;
                $salaryManagement->employee_id = $employee->id;
                $salaryManagement->date = date('Y-m-d');
                $salaryManagement->total_salary = $employee->salary ? $employee->salary : 0;
                $salaryManagement->previous_balance = $employee->salary_balance ? $employee->salary_balance : 0;
                $salaryManagement->calculated_salary = 0;
                $salaryManagement->current_month_advance = 0;
                $salaryManagement->new_balance = 0;
                $salaryManagement->save();
            }
            // if(date('Y-m-d') == date('Y-m-t'))
            // {
            //     $salaryManagement = SalaryManagement::withoutGlobalScope('cafe_id')->where('employee_id',$employee->id)->whereMonth('date',date('m'))->whereYear('date',date('Y'))->first();
            //     if (!empty($salaryManagement)) {
            //         $total_days = cal_days_in_month(CAL_GREGORIAN,date('m',strtotime($salaryManagement->date)),date('Y',strtotime($salaryManagement->date)));

            //         $absent_days = Attendence::where('employee_id', $employee->id)->where('attendence',1)->where('date','like', '%'.date('Y-m').'%' )->count();
            //         $half_days = Attendence::where('employee_id', $employee->id)->where('attendence',3)->where('date','like', '%'.date('Y-m').'%' )->count();
            //         $full_days = Attendence::where('employee_id', $employee->id)->where('attendence',2)->where('date','like', '%'.date('Y-m').'%' )->count();
            //         // $present_days = $total_days - $absent_days - ($half_days/2);
            //         $present_days = $full_days + ($half_days/2);
            //         $calculated_salary = $salaryManagement->total_salary * ($present_days/$total_days);

            //         $new_balance = $salaryManagement->previous_balance - $salaryManagement->current_month_advance + $calculated_salary;


            //         $salaryManagement->calculated_salary = $calculated_salary;
            //         $salaryManagement->new_balance = $new_balance;
            //         $salaryManagement->save();

            //         $employee->update(['salary_balance'=>$new_balance]);
            //     }
            // }
        }
        return;
    }
}
