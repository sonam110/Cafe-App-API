<?php

namespace App\Http\Controllers\Api\Common;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\ProductMenu;
use App\Models\Order;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\User;
use App\Models\Attendence;
use App\Models\ManageWastage;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;



class DashboardController extends Controller
{
	public function dashboard()
	{
		try {
			$data = [];
			

			$data['todays_sale_online'] = (Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->sum('online_amount'));

			$data['todays_sale_offline'] = (Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->sum('cash_amount'));

			$data['todays_sale_udhari'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->where('payment_mode',3)->sum('udhaar_amount');

			$data['todays_sale_amount'] = $data['todays_sale_online']+ $data['todays_sale_offline'] + $data['todays_sale_udhari'];


			$data['todays_order_completed'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->count();
			$data['todays_order_pending'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',1)->count();
			$data['todays_order_confirmed'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',2)->count();
			$data['todays_order_canceled'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',4)->count();
			$data['todays_present_employees_count'] = Attendence::where('attendence',2)->whereDate('created_at', date("Y-m-d"))->count();
			$data['todays_half_day_employees_count'] = Attendence::where('attendence',3)->whereDate('created_at', date("Y-m-d"))->count();
			$data['todays_absent_employees_count'] = Attendence::where('attendence',1)->whereDate('created_at', date("Y-m-d"))->count();
			$data['totalEmployee'] = User::whereIn('role_id',[3,4])->count();
			$data['total_expense'] = Expense::whereDate('expense_date', date('Y-m-d'))->sum('total_expense');
			$data['total_wastage_menu'] = ManageWastage::whereDate('date', date('Y-m-d'))->whereNotNull('menu_id')->sum('quantity');
			$data['total_wastage_product'] = ManageWastage::whereDate('date', date('Y-m-d'))->whereNotNull('product_id')->sum('quantity');
			return prepareResult(true,'Dashboard data Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function dashboardGraph(Request $request)
	{
		try {
			$data = [];
			$day = !empty($request->day) ? $request->day : 7;
			$dates = [];
			$labels = [];
			if(!empty($request->start_date) && !empty($request->end_date)) 
			{
				$diffrece = dateDifference($request->start_date, $request->end_date) + 1;
				for($i = $diffrece; $i>=1; $i--)
				{
					$dates[] = date("Y-m-d", strtotime('-'.($i-1).' days', strtotime($request->end_date)));

				}
			}
			else
			{
				for($i = $day; $i>=1; $i--)
				{
					$dates[] = date('Y-m-d',strtotime('-'.($i-1).' days'));
				}
			}
		   
			foreach ($dates as $key => $date) {
				$data['labels'][] = $date; 

				$sale_total = Order::withoutGlobalScope('cafe_id')
				->whereDate('orders.created_at', $date)
				->where('orders.order_status',3)
				->select([
					\DB::raw('SUM(CASE
						WHEN `orders`.`order_status`= 3 THEN `orders`.`cash_amount`
						ELSE 0
						END) AS sale_offline'),
					\DB::raw('SUM(CASE
						WHEN `orders`.`order_status`= 3 THEN `orders`.`online_amount`
						ELSE 0
						END) AS sale_online'),
					\DB::raw('SUM(CASE
						WHEN `orders`.`order_status`= 3 THEN `orders`.`udhaar_amount`
						ELSE 0
						END) AS sale_udhari'),

					
				]);

				if(auth()->user()->cafe_id != 1)
				{
					$sale_total = $sale_total->where('orders.cafe_id',auth()->user()->cafe_id);
				}
			   // return $sale_total->toSql();

				if(!empty($request->cafe_id))
				{
					$sale_total = $sale_total->where('orders.cafe_id',$request->cafe_id);
				}

				if(!empty($request->menu_id)) {
                    $sale_total = $sale_total->join('order_details as od1', function ($join) {
                        $join->on('orders.id', '=', 'od1.order_id');
                    })
                    ->where('od1.menu_id', $request->menu_id);
                }
                
                if(!empty($request->category_id)) {
                    $sale_total = $sale_total->join('order_details as od2', function ($join) {
                        $join->on('orders.id', '=', 'od2.order_id');
                    })
                    ->whereJsonContains('od2.menu_detail->category_id', $request->category_id);
                }

				$sale_total = $sale_total->first();
			
				if(!empty($request->category_id) || !empty($request->menu_id) || !empty($request->product_id)){
                    $orders_count = Order::withoutGlobalScope('cafe_id')
                    ->whereDate('orders.created_at', $date)
                    ->select([
                        \DB::raw('COUNT(IF(orders.order_status = 3, 1, NULL)) as order_completed'),
                        \DB::raw('COUNT(IF(orders.order_status = 2, 1, NULL)) as order_confirmed'),
                        \DB::raw('COUNT(IF(orders.order_status = 1, 1, NULL)) as order_pending'),
                        \DB::raw('COUNT(IF(orders.order_status = 4, 1, NULL)) as order_canceled'),
                        \DB::raw('SUM(IF(orders.order_status = 3, od1.quantity, 0)) as total_sale_quantity'),
                         \DB::raw('SUM(IF(orders.order_status = 3, od1.sub_total, 0)) as sale_amount'),
                         \DB::raw('SUM(IF(orders.order_status = 3, od1.tax, 0)) as tax_amount'),
                         \DB::raw('SUM(IF(orders.order_status = 3, od1.discount_amount, 0)) as discount_amount'),
                    ])
                    ->join('order_details as od1', 'orders.id', '=', 'od1.order_id');
                
               
                    
                } else{
                   $orders_count = Order::withoutGlobalScope('cafe_id')
                    ->whereDate('orders.created_at', $date)
                    ->select([
                        \DB::raw('COUNT(IF(orders.order_status = 3, 1, NULL)) as order_completed'),
                        \DB::raw('COUNT(IF(orders.order_status = 2, 1, NULL)) as order_confirmed'),
                        \DB::raw('COUNT(IF(orders.order_status = 1, 1, NULL)) as order_pending'),
                        \DB::raw('COUNT(IF(orders.order_status = 4, 1, NULL)) as order_canceled'),
                        \DB::raw('SUM(IF(orders.order_status = 3, od1.quantity, 0)) as total_sale_quantity'),
                    ])
                    ->join('order_details as od1', 'orders.id', '=', 'od1.order_id');
                }
				if(auth()->user()->cafe_id != 1) {
                    $orders_count->where('orders.cafe_id', auth()->user()->cafe_id);
                }
                
                if(!empty($request->cafe_id)) {
                    $orders_count->where('orders.cafe_id', $request->cafe_id);
                }
                
                if(!empty($request->menu_id)) {
                    $orders_count->where('od1.menu_id', $request->menu_id);
                }
                
                if(!empty($request->category_id)) {
                    $orders_count->whereJsonContains('od1.menu_detail->category_id', $request->category_id);
                }
                if(!empty($request->product_id)) {
                    $orders_count->whereJsonContains('od1.menu_detail->product_id', $request->product_id);
                }
				$orders_count = $orders_count->first(); 


				$attendence = Attendence::whereDate('created_at', $date)
				->select([
					\DB::raw('COUNT(IF(attendence=3,0, NULL)) as half_day_employees_count'),
					\DB::raw('COUNT(IF(attendence=2,0, NULL)) as present_employees_count'),
					\DB::raw('COUNT(IF(attendence=1,0, NULL)) as absent_employees_count')
				]);
				if(!empty($request->cafe_id))
				{
					$attendence = $attendence->where('cafe_id',$request->cafe_id);
				}
				if(!empty($request->employee_id))
				{
					$attendence = $attendence->where('employee_id',$request->employee_id);
				}
				$attendence = $attendence->first(); 


				if(!empty($request->category_id) || !empty($request->menu_id)){
				    $sale_amount =  $orders_count->sale_amount+$orders_count->tax_amount;
				    $sale_online = 0;
				    $sale_udhari = 0;
				    $sale_offline = 0;
                } else{
                    $sale_amount = $sale_total->sale_online+ $sale_total->sale_udhari+ $sale_total->sale_offline;
                    $sale_online = $sale_total->sale_online;
				    $sale_udhari = $sale_total->sale_udhari;
				    $sale_offline =$sale_total->sale_offline;
                }
				$data['sale_amount'][] = $sale_amount;
				$data['sale_online'][] = $sale_online;
				$data['sale_udhari'][] = $sale_udhari;
				$data['sale_offline'][] = $sale_offline;
				$data['total_sale_quantity'][] = $orders_count->total_sale_quantity;
				$data['order_completed'][] = $orders_count->order_completed;
				$data['order_pending'][] = $orders_count->order_pending;
				$data['order_confirmed'][] = $orders_count->order_confirmed;
				$data['order_canceled'][] = $orders_count->order_canceled;
				$data['present_employees_count'][] = $attendence->present_employees_count;
				$data['half_day_employees_count'][] = $attendence->half_day_employees_count;
				$data['absent_employees_count'][] = $attendence->absent_employees_count;
			}

			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}

	public function dashboardTable(Request $request)
	{
		try {
			$day = !empty($request->day) ? $request->day : 7;
			$dates = [];
			$labels = [];
			if(!empty($request->start_date) && !empty($request->end_date)) 
			{
				$diffrece = dateDifference($request->start_date, $request->end_date) + 1;
				for($i = $diffrece; $i>=1; $i--)
				{
					$dates[] = date("Y-m-d", strtotime('-'.($i-1).' days', strtotime($request->end_date)));

				}
			}
			else
			{
				for($i = 1; $i<=$day; $i++)
				{
					$dates[] = date('Y-m-d',strtotime('-'.($i-1).' days'));
				}
			}
			foreach ($dates as $key => $date) {
				// $data['labels'][] = $date; 

				$sale_total = Order::withoutGlobalScope('cafe_id')
				->whereDate('orders.created_at', $date)
				->where('orders.order_status',3)
				->select([
					\DB::raw('SUM(CASE
						WHEN `orders`.`order_status`= 3 THEN `orders`.`cash_amount`
						ELSE 0
						END) AS sale_offline'),
					\DB::raw('SUM(CASE
						WHEN `orders`.`order_status`= 3 THEN `orders`.`online_amount`
						ELSE 0
						END) AS sale_online'),
					\DB::raw('SUM(CASE
						WHEN `orders`.`order_status`= 3 THEN `orders`.`udhaar_amount`
						ELSE 0
						END) AS sale_udhari'),

				]);

				if(auth()->user()->cafe_id != 1)
				{
					$sale_total = $sale_total->where('orders.cafe_id',auth()->user()->cafe_id);
				}

				if(!empty($request->cafe_id))
				{
					$sale_total = $sale_total->where('orders.cafe_id',$request->cafe_id);
				}

				if(!empty($request->menu_id)) {
                    $sale_total = $sale_total->join('order_details as od1', function ($join) {
                        $join->on('orders.id', '=', 'od1.order_id');
                    })
                    ->where('od1.menu_id', $request->menu_id);
                }
                
                if(!empty($request->category_id)) {
                    $sale_total = $sale_total->join('order_details as od2', function ($join) {
                        $join->on('orders.id', '=', 'od2.order_id');
                    })
                    ->whereJsonContains('od2.menu_detail->category_id', $request->category_id);
                }

				$sale_total = $sale_total->first();

                if(!empty($request->category_id) || !empty($request->menu_id) || !empty($request->product_id)){
                    $orders_count = Order::withoutGlobalScope('cafe_id')
                    ->whereDate('orders.created_at', $date)
                    ->select([
                        \DB::raw('COUNT(IF(orders.order_status = 3, 1, NULL)) as order_completed'),
                        \DB::raw('COUNT(IF(orders.order_status = 2, 1, NULL)) as order_confirmed'),
                        \DB::raw('COUNT(IF(orders.order_status = 1, 1, NULL)) as order_pending'),
                        \DB::raw('COUNT(IF(orders.order_status = 4, 1, NULL)) as order_canceled'),
                        \DB::raw('SUM(IF(orders.order_status = 3, od1.quantity, 0)) as total_sale_quantity'),
                         \DB::raw('SUM(IF(orders.order_status = 3, od1.sub_total, 0)) as sale_amount'),
                         \DB::raw('SUM(IF(orders.order_status = 3, od1.tax, 0)) as tax_amount'),
                         \DB::raw('SUM(IF(orders.order_status = 3, od1.discount_amount, 0)) as discount_amount'),
                    ])
                    ->join('order_details as od1', 'orders.id', '=', 'od1.order_id');
                
               
                    
                } else{
                   $orders_count = Order::withoutGlobalScope('cafe_id')
                    ->whereDate('orders.created_at', $date)
                    ->select([
                        \DB::raw('COUNT(IF(orders.order_status = 3, 1, NULL)) as order_completed'),
                        \DB::raw('COUNT(IF(orders.order_status = 2, 1, NULL)) as order_confirmed'),
                        \DB::raw('COUNT(IF(orders.order_status = 1, 1, NULL)) as order_pending'),
                        \DB::raw('COUNT(IF(orders.order_status = 4, 1, NULL)) as order_canceled'),
                        \DB::raw('SUM(IF(orders.order_status = 3, od1.quantity, 0)) as total_sale_quantity'),
                    ])
                    ->join('order_details as od1', 'orders.id', '=', 'od1.order_id');
                }
               
				if(auth()->user()->cafe_id != 1) {
                    $orders_count->where('orders.cafe_id', auth()->user()->cafe_id);
                }
                
                if(!empty($request->cafe_id)) {
                    $orders_count->where('orders.cafe_id', $request->cafe_id);
                }
                 //return $orders_count->toSql();
                
                if(!empty($request->menu_id)) {
                    $orders_count->where('od1.menu_id', $request->menu_id);
                }
                
                if(!empty($request->category_id)) {
                    $orders_count->whereJsonContains('od1.menu_detail->category_id', $request->category_id);
                }
                if(!empty($request->product_id)) {
                    $orders_count->whereJsonContains('od1.menu_detail->product_id', $request->product_id);
                }
                
                $orders_count = $orders_count->first();
                if(!empty($request->category_id) || !empty($request->menu_id) || !empty($request->product_id)){
				    $sale_amount =  $orders_count->sale_amount+$orders_count->tax_amount;
				    $sale_online = 0;
				    $sale_udhari = 0;
				    $sale_offline = 0;
                } else{
                    $sale_amount = $sale_total->sale_online+ $sale_total->sale_udhari+ $sale_total->sale_offline;
                    $sale_online = $sale_total->sale_online;
				    $sale_udhari = $sale_total->sale_udhari;
				    $sale_offline =$sale_total->sale_offline;
                }
				$data[] = [
							'date' => $date, 
							'total_sale_quantity' => $orders_count->total_sale_quantity, 
							'order_completed' => $orders_count->order_completed, 
							'order_pending' => $orders_count->order_pending, 
							'order_confirmed' => $orders_count->order_confirmed, 
							'order_canceled' => $orders_count->order_canceled,
							'sale_amount' => $sale_amount, 
							'sale_online' => $sale_online, 
							'sale_udhari' => $sale_udhari, 
							'sale_offline' => $sale_offline 
						];
			}

			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}
	public function orderReport(Request $request)
	{
		try {
			$day = !empty($request->day) ? $request->day : 7;
			$dates = [];
			$labels = [];
			if(!empty($request->start_date) && !empty($request->end_date)) 
			{
				$diffrece = dateDifference($request->start_date, $request->end_date) + 1;
				for($i = $diffrece; $i>=1; $i--)
				{
					$dates[] = date("Y-m-d", strtotime('-'.($i-1).' days', strtotime($request->end_date)));

				}
			}
			else
			{
				for($i = 1; $i<=$day; $i++)
				{
					$dates[] = date('Y-m-d',strtotime('-'.($i-1).' days'));
				}
			}
			foreach ($dates as $key => $date) {
				$orders_count = Order::withoutGlobalScope('cafe_id')
				    ->whereDate('orders.created_at', $date)
				    ->select([
				        \DB::raw('SUM(IF(orders.order_status = 3, od1.quantity, 0)) as total_sale_quantity'),
				        \DB::raw('SUM(IF(orders.order_status = 3, od1.sub_total, 0)) as sale_amount'),
				    ])
				    ->join('order_details as od1', 'orders.id', '=', 'od1.order_id');

				if(auth()->user()->cafe_id != 1) {
				    $orders_count->where('orders.cafe_id', auth()->user()->cafe_id);
				}

				if(!empty($request->cafe_id)) {
				    $orders_count->where('orders.cafe_id', $request->cafe_id);
				}

				if(!empty($request->menu_id)) {
				    $orders_count->where('od1.menu_id', $request->menu_id);
				}

				if(!empty($request->category_id)) {
				    $orders_count->whereJsonContains('od1.menu_detail->category_id', $request->category_id);
				}

				$orders_count = $orders_count->first();
				$data[] = [
							'date' => $date, 
							'total_sale_quantity' => $orders_count->total_sale_quantity, 
							'sale_amount' => $orders_count->sale_amount, 
							
						];
			}

			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}
	public function orderReportTimeWise(Request $request)
	{
		try {
			$dates = [];
            $labels = [];
            $day = 1;
           
            if (!empty($request->start_datetime) && !empty($request->end_datetime)) {
                // If start and end datetime are provided, use them
                $start = new \DateTime($request->start_datetime);
                $end = new \DateTime($request->end_datetime);
            } else {
                // If start and end datetime are not provided, default to today 12 PM to 11:55 PM
                $now = new \DateTime(); // Current date and time
                $start = new \DateTime($now->format('Y-m-d 00:00:00')); // Today at 12:00 AM
                $end = new \DateTime($now->format('Y-m-d 23:55:00')); // Today at 11:55 PM
            }
            
            // Adjust $start and $end to the nearest hour (optional, if needed)
            $start->setTime($start->format('H'), 0, 0);
            $end->setTime($end->format('H'), 55, 0);
            
            $interval = new \DateInterval('PT1H'); // 1 hour interval
            $period = new \DatePeriod($start, $interval, $end);
            
            foreach ($period as $date) {
                $dates[] = $date->format('Y-m-d H:i:s');
            }
            
            // Output or use $dates array as needed
           
            
            $data = [];
            
            foreach ($dates as $key => $datetime) {
                $orders_count = OrderDetail::withoutGlobalScope('cafe_id')
                    ->where('order_details.created_at', '>=', $datetime)
                    ->where('order_details.created_at', '<', date('Y-m-d H:i:s', strtotime($datetime . ' +1 hour')))
                    ->with('menu')
                    ->select([
                        \DB::raw('SUM(IF(od1.order_status = 3, order_details.quantity, 0)) as total_sale_quantity'),
                        \DB::raw('SUM(IF(od1.order_status = 3, order_details.sub_total, 0)) as sale_amount'),
                         \DB::raw('SUM(IF(od1.order_status = 3, order_details.tax, 0)) as tax_amount'),
                    ])
                    ->join('orders as od1', 'order_details.order_id', '=', 'od1.id');
            
                if(auth()->user()->cafe_id != 1) {
                    $orders_count->where('od1.cafe_id', auth()->user()->cafe_id);
                }
            
                if(!empty($request->cafe_id)) {
                    $orders_count->where('od1.cafe_id', $request->cafe_id);
                }
            
                if(!empty($request->menu_id)) {
                    $orders_count->where('order_details.menu_id', $request->menu_id);
                }
            
                if(!empty($request->category_id)) {
                    $orders_count->whereJsonContains('order_details.menu_detail->category_id', $request->category_id);
                }
                if(!empty($request->product_id))
				{
					$orders_count->whereJsonContains('order_details.menu_detail->product_id', $request->product_id);
				}
            
                $orders_count = $orders_count->first();
                
            
                // Only include data if it exists for the datetime
                    
                    $data[] = [
                        'date' => $datetime, 
                        'total_sale_quantity' => $orders_count->total_sale_quantity , 
                        'sale_amount' => $orders_count->sale_amount+ $orders_count->tax_amount, 
                    ];
                
            }


			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}
	public function wastageManageTable(Request $request)
	{
		try {
			$day = !empty($request->day) ? $request->day : 7;
			$dates = [];
			$labels = [];
			if(!empty($request->start_date) && !empty($request->end_date)) 
			{
				$diffrece = dateDifference($request->start_date, $request->end_date) + 1;
				for($i = $diffrece; $i>=1; $i--)
				{
					$dates[] = date("Y-m-d", strtotime('-'.($i-1).' days', strtotime($request->end_date)));

				}
			}
			else
			{
				for($i = 1; $i<=$day; $i++)
				{
					$dates[] = date('Y-m-d',strtotime('-'.($i-1).' days'));
				}
			}
			
			foreach ($dates as $key => $date) {
				// $data['labels'][] = $date; 

				$managewaste = ManageWastage::withoutGlobalScope('cafe_id')
                ->whereDate('date', $date)
                ->select([
                    'product_id',
                    'menu_id',
                    'unit_id',
                    \DB::raw('SUM(CASE WHEN `menu_id` IS NOT NULL THEN `quantity` ELSE 0 END) AS total_wastage_menu'),
                    \DB::raw('SUM(CASE WHEN `product_id` IS NOT NULL THEN `quantity` ELSE 0 END) AS total_wastage_product'),
                ])->groupBy('product_id','menu_id' ,'unit_id')->with('unit','product','menu');
    
                if (auth()->user()->cafe_id != 1) {
                    $managewaste->where('cafe_id', auth()->user()->cafe_id);
                }
            
                if (!empty($request->cafe_id)) {
                    $managewaste->where('cafe_id', $request->cafe_id);
                }
            
                if (!empty($request->menu_id)) {
                    $managewaste->where('menu_id', $request->menu_id);
                }
            
                if (!empty($request->product_id)) {
                    $managewaste->where('product_id', $request->product_id);
                }
            
                $managewaste = $managewaste->first();
                
               
				$data[] = [
							'date' => $date, 
							'product' => @$managewaste->product->name, 
							'unit' => @$managewaste->unit->name, 
							'total_wastage_menu' => @$managewaste->total_wastage_menu, 
							'total_wastage_product' => @$managewaste->total_wastage_product, 
						];
			}

			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}


	public function categoryWiseList(Request $request)
	{
		try {
			$data = getDetails($request->start_date, $request->end_date, $request->category, $request->cafe_id);
			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function dashboardGraphByName(Request $request)
	{
		try {
			$data = getLast30details($request->day , $request->startDate, $request->endDate, $request->cafe_id);
			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}

}
