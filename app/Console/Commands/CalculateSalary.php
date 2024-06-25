<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalaryManagement;
use App\Models\AdvanceManagement;
use App\Models\User;

class CalculateSalary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:salary';

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
        if(date('Y-m-d') == date('Y-m-t'))
        {
            $datas = SalaryManagement::withoutGlobalScope('cafe_id')->whereMonth('date',date('m'))->whereYear('date',date('Y'))->first();
            foreach ($datas as $key => $data) {
                $total_days = cal_days_in_month(CAL_GREGORIAN,date('m',strtotime($data->date)),date('Y',strtotime($data->date)));

                $absent_days = Attendence::where('employee_id', $data->employee_id)->where('attendence',1)->where('date','like', '%'.date('Y-m').'%' )->count();
                $half_days = Attendence::where('employee_id', $data->employee_id)->where('attendence',3)->where('date','like', '%'.date('Y-m').'%' )->count();
                $full_days = Attendence::where('employee_id', $data->employee_id)->where('attendence',2)->where('date','like', '%'.date('Y-m').'%' )->count();
                    // $present_days = $total_days - $absent_days - ($half_days/2);
                $present_days = $full_days + ($half_days/2);
                $calculated_salary = $data->total_salary * ($present_days/$total_days);

                $new_balance = $data->previous_balance - $data->current_month_advance + $calculated_salary;

                $data->calculated_salary = $calculated_salary;
                $data->new_balance = $new_balance;
                $data->save();

                User::withoutGlobalScope()->where('id',$data->employee_id)->update(['salary_balance'=>$new_balance]);
            }
        }
        return;
    }
}
