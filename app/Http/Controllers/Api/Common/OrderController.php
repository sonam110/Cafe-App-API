<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\CustomerAccount;
use App\Models\User;
use App\Models\StockManage;
use App\Models\Menu;
use App\Models\Packaging;
use App\Models\PackagingContents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PDF;
use App\Models\Recipe;
use App\Models\RecipeDetails;


class OrderController extends Controller
{

	public function orders(Request $request)
	{
		try {

			$query = Order::select ('*')
			->with('orderDetails','cafe:id,cafe_id,email,mobile,profile_image_path,contact_person_email,contact_person_name,contact_person_phone,address','cafe.paymentQrCodes','customer:id,name,email,account_balance')
			->orderBy('id', 'desc');

			if(!empty($request->id))
			{
				$query->where('id', $request->id);
			}
			if(!empty($request->table_number))
			{
				$query->where('table_number', $request->table_number);
			}
			if(!empty($request->order_status))
			{
				$query->where('order_status', $request->order_status);
			}

           // date wise filter from here
			if(!empty($request->start_date))
			{
				$query->whereDate('created_at', '>=', $request->start_date);
			}
			if(!empty($request->end_date))
			{
				$query->whereDate('created_at', '<=', $request->end_date);
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
			return prepareResult(true,'Orders Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
	     dd($request->all());
		DB::beginTransaction();
		try {
		    
			$validation = Validator::make($request->all(), [
				'order_status' => 'nullable|numeric',
				'order_details' => 'required|array'
			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			} 

// 			$lastOrder = Order::orderBy('id','desc')->first();

// 			if (!empty($lastOrder)) {
// 				$order_number = $lastOrder->order_number + 1;
// 			}
// 			else
// 			{
// 				$order_number = env('ORDER_START',1000) + 1;
// 			}

			$todays_last_order = \DB::table('orders')
			->select('order_number')
			->where('order_number','like','%'.date('ymd').'%')
			->where('cafe_id',auth()->user()->cafe_id)
			->orderBy('id','DESC')
			->first();
			if(empty($todays_last_order))
			{
				$order_number = date('ymd').'-1';
			}
			else
			{
				$order_number = date('ymd').'-'.((substr($todays_last_order->order_number,7)) + 1);
			}

			$total_amount = $request->total_amount * (100 - $request->discount)/100;
			$payable_amount = $request->tax + $total_amount;
            
			$order = new Order;
			$order->cafe_id         = auth()->user()->cafe_id;
			$order->order_number 	= $order_number;
			$order->table_number 	= $request->table_number;
			$order->customer_id 	= $request->customer_id;
			$order->total_amount 	= $request->total_amount;
			$order->total_quantity 	= $request->total_quantity ? $request->total_quantity : count($request->order_details);
			$order->discount 		= $request->discount;
			$order->tax_amount 		= $request->tax_amount;
			$order->payment_mode 	= $request->payment_mode;
			$order->payable_amount 	= $payable_amount;
			$order->cash_amount 	=  $request->cash_amount;
			$order->online_amount 	= $request->online_amount ;
			$order->udhaar_amount 	=  $request->udhaar_amount ;
			$order->order_type 		= $request->order_type;
			$order->order_duration 	= $request->order_duration;
			$order->order_status 	= $request->order_status;;
			$order->save(); 

			foreach ($request->order_details as $key => $value) {
				$menu = Menu::with('recipes')->find( $value['menu_id']);
				if(empty($menu))
				{
					return prepareResult(false,'Menu Not Found' ,[], 500);
				}

				$orderDetail = new OrderDetail;
				$orderDetail->order_id =  $order->id;
				$orderDetail->cafe_id =  $order->cafe_id;
				$orderDetail->menu_id = $value['menu_id'];
				// $orderDetail->category_id = $value['category_id'];
	            // $orderDetail->unit_id = $menu->unit_id;
				$orderDetail->preparation_duration = $value['preparation_duration'] ? $value['preparation_duration'] : 0;
				$orderDetail->instructions = $value['instructions'] ?$value['instructions']:NULL;
				$orderDetail->menu_detail = json_encode($menu);
				$orderDetail->quantity = $value['quantity'];
	            // $orderDetail->price = $menu->price;
				$orderDetail->price = $value['price'];
				$orderDetail->tax = $value['tax'];
				// $orderDetail->sub_total = $value['quantity'] * ($value['price'] + ($value['price'] * $value['tax']/100));
				$orderDetail->sub_total = $value['sub_total'];
				$orderDetail->save();

				if($request->order_status == 2 || $request->order_status == 3)
				{
					$recipes = $menu->recipes;
					foreach ($recipes as $key => $recipe) {
						$stockManage = stockManageAdd($request,'out',$recipe->product_id,$recipe->unit_id,($recipe->quantity)*$value['quantity'],'Kitchen',null,null,$order->id,$recipe->menu_id);
						
					}
				}
				if($request->order_type == 'Percel')
				{
					// $packagingID = Packaging::where('category_id', $order['category_id'])->get('id')->first();
					// if($packagingID){
					// 	$request->order_type==1 ? packagingDeduction($packagingID->id, $order['quantity']) : '';
					// }
				}

				
			}
                
               
			if (($request->order_status == 3) && ($request->payment_mode == 3)) {
				$payment_received = $request->payment_received ? $request->payment_received : 0;
				$customer_id = $request->customer_id ? $request->customer_id : $request->customer_id;
				if (empty($customer_id)) {
					return prepareResult(false,'Customer Id  Required' ,[], 500);
				}

				$customer = User::where('role_id',5)->where('id', $customer_id)->first();
				if(empty($customer))
				{
					return prepareResult(false,'Customer not found' ,[], 500);
				}
				$customerAccount = new CustomerAccount;
				$customerAccount->customer_id = $customer_id;
				

				// storing customer stock from product customerAccounts stock table
				$customerAccount->previous_balance = $customer->account_balance;
				$customerAccount->sale = $order->payable_amount;
				$customerAccount->payment_received = $payment_received ;
				$customerAccount->new_balance = ($payment_received + $customer->account_balance) - ($order->payable_amount);
				$customerAccount->mode_of_transaction = $request->mode_of_transaction;
				$customerAccount->save();
				
				// updating the Customer table as well
				$customer->account_balance = $customerAccount->new_balance;
				$customer->save();
			}
			DB::commit();
			$order['order_details'] = $order->orderDetails;
			return prepareResult(true,'Order placed successfully' , $order, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function update(Request $request, $id)
	{
		DB::beginTransaction();
		try {
			$validation = Validator::make($request->all(), [
				'order_status'                   => 'nullable|numeric',
				'tax_amount'                      => 'nullable|numeric',
				'order_details' => 'required|array'
			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			} 
			$order = Order::find($id);
			$old_status = $order->status;

			//----------adjust stock from previous order-----------//
			if($order->status == 2 || $order->status == 3)
			{
				$stockManages = StockManage::where('order_id',$id)->get();
				foreach ($stockManages as $key => $stockManage) {
					$stockManageDelete = stockManageDelete($stockManage);
				}
			}
			//-----------------------------------------------------//

			$total_amount = $request->total_amount * (100 - $request->discount)/100;
			$payable_amount = $request->tax + $total_amount;

			$order->table_number 	= $request->table_number;
			$order->customer_id 	= $request->customer_id;
			$order->total_amount 	= $request->total_amount;
			$order->discount 		= $request->discount;
			$order->tax_amount 		= $request->tax_amount;
			$order->payment_mode 	= $request->payment_mode;
			$order->payable_amount 	= $payable_amount;
			$order->cash_amount 	=  $request->cash_amount;
			$order->online_amount 	= $request->online_amount ;
			$order->udhaar_amount 	=  $request->udhaar_amount ;
			$order->order_type 		= $request->order_type;
			$order->order_duration 	= $request->order_duration;
			$order->order_status 	= $request->order_status;;
			$order->save(); 

			//del old order data
			OrderDetail::where('order_id',$id)->delete();
			foreach ($request->order_details as $key => $value) {
				$menu = Menu::with('recipes')->find( $value['menu_id']);
				if(empty($menu))
				{
					return prepareResult(false,'Menu Not Found' ,[], 500);
				}

				$orderDetail = new OrderDetail;
				$orderDetail->order_id =  $order->id;
				$orderDetail->menu_id = $value['menu_id'];
				// $orderDetail->category_id = $value['category_id'];
	            // $orderDetail->unit_id = $menu->unit_id;
				$orderDetail->preparation_duration = $value['preparation_duration'] ? $value['preparation_duration'] : 0;
				$orderDetail->instructions = $value['instructions'] ?$value['instructions']:NULL;
				$orderDetail->menu_detail = json_encode($menu);
				$orderDetail->quantity = $value['quantity'];
	            // $orderDetail->price = $menu->price;
				$orderDetail->tax = $value['tax'];
				$orderDetail->price = $value['price'];
				$orderDetail->sub_total = $value['sub_total'];
				$orderDetail->save();
				if(($request->order_status == 2) || (($old_status == 1) && ($request->order_status == 3)))
				{
					$recipes = $menu->recipes;
					foreach ($recipes as $key => $recipe) {
						$stockManage = stockManageAdd($request,'out',$recipe->product_id,$recipe->unit_id,($recipe->quantity)*$value['quantity'],'Kitchen',null,null,$order->id,$recipe->menu_id);
					}
				}
				if($request->order_type == 'Percel')
				{
					// $packagingID = Packaging::where('category_id', $order['category_id'])->get('id')->first();
					// if($packagingID){
					// 	$request->order_type==1 ? packagingDeduction($packagingID->id, $order['quantity']) : '';
					// }
				}
				
			}
			if (($request->order_status == 3) && ($request->payment_mode == 3)) {
				$payment_received = $request->payment_received ? $request->payment_received : 0;
				$customer_id = $request->customer_id ? $request->customer_id : $request->customer_id;
				if (empty($customer_id)) {
					return prepareResult(false,'Customer Id  Required' ,[], 500);
				}

				$customer = User::where('role_id',5)->where('id', $customer_id)->first();
				if(empty($customer))
				{
					return prepareResult(false,'Customer not found' ,[], 500);
				}
				$customerAccount = new CustomerAccount;
				$customerAccount->customer_id = $customer_id;

				// storing customer stock from product customerAccounts stock table
				$customerAccount->previous_balance = $customer->account_balance;
				$customerAccount->sale = $order->payable_amount;
				$customerAccount->payment_received = $payment_received ;
				$customerAccount->new_balance = ($payment_received + $customer->account_balance) - ($order->payable_amount);
				$customerAccount->mode_of_transaction = $request->mode_of_transaction;
				$customerAccount->save();

				// updating the Customer table as well
				$customer->account_balance = $customerAccount->new_balance;
				$customer->save();
			}
			DB::commit();
			$order['order_contains'] = $order->orderDetails;
			return prepareResult(true,'Your data has been Updated successfully' ,$order, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$order = Order::with('orderDetails.menu:id,name,category_id','cafe:id,cafe_id,email,mobile,profile_image_path,contact_person_email,contact_person_name,contact_person_phone,address','cafe.paymentQrCodes','customer:id,name,email,account_balance')->find($id);
			if($order)
			{
				return prepareResult(true,'Order Detail Fatched Successfully' ,$order, 200); 
			}
			return prepareResult(false,'Order not found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$order = Order::find($id);
			if($order)
			{
				OrderDetail::where('order_id',$id)->delete();
				$result = $order->delete();
				return prepareResult(true,'Order Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Order Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function statusUpdate(Request $request,$id)
	{
		try {
			$order = Order::find($id);
			if($order)
			{
				//Confirm after pending Order
				if((($request->order_status == 2) || ($request->order_status == 3)) && ($order->order_status == 1))
				{
					foreach ($order->orderDetails as $key => $value) {
						$menu = Menu::with('recipes')->find( $value->menu_id);
						if(empty($menu))
						{
							return prepareResult(false,'Menu Not Found' ,[], 500);
						}

						$recipes = $menu->recipes;
						foreach ($recipes as $key => $recipe) {
							$stockManage = stockManageAdd($request,'out',$recipe->product_id,$recipe->unit_id,($recipe->quantity)*$value->quantity,'Kitchen',null,null,$id,$recipe->menu_id);
						}
					}
				}
				//Cancel after confirmed Order
				if(($request->order_status == 4) && ($order->order_status == 2 || $order->order_status == 3))
				{
					$validation = Validator::make($request->all(), [
						'recipes'                   => 'required|array'
					]);
					foreach ($request->recipes as $value) {
						$stockManage = StockManage::where('order_id',$id)->where('product_id',$value['product_id'])->where('menu_id',$value['menu_id'])->first();
						if($value['is_reusable'] == '1')
						{
							$stockReuse = stockManageDelete($stockManage);
						}
						else
						{
							$stockManage->update(['resource'=>'Waste']);
						}
					}
				}

				if ($request->order_status == 4) {
					$order->cancel_reason = $request->cancel_reason;
				}

				if (($request->order_status == 3) && ($order->payment_mode == 3)) {
					$payment_received = $request->payment_received ? $request->payment_received : 0;
					$customer_id = $request->customer_id ? $request->customer_id : $order->customer_id;
					if (empty($customer_id)) {
						return prepareResult(false,'Customer Id  Required' ,[], 500);
					}

					$customer = User::where('role_id',5)->where('id', $customer_id)->first();
					if(empty($customer))
					{
						return prepareResult(false,'Customer not found' ,[], 500);
					}
					$customerAccount = new CustomerAccount;
					$customerAccount->customer_id = $customer_id;

					// storing customer stock from product customerAccounts stock table
					$customerAccount->previous_balance = $customer->account_balance;
					$customerAccount->sale = $order->payable_amount;
					$customerAccount->payment_received = $payment_received ;
					$customerAccount->new_balance = ($payment_received + $customer->account_balance) - ($order->payable_amount);
					$customerAccount->mode_of_transaction = $request->mode_of_transaction;
					$customerAccount->save();

					// updating the Customer table as well
					$customer->account_balance = $customerAccount->new_balance;
					$customer->save();
				}
				
				$cash_amount = $request->cash_amount;
				if($request->payment_mode==1){
					$cash_amount = $order->payable_amount;
				}
				$online_amount = $request->online_amount;
				if($request->payment_mode==2){
					$online_amount = $order->payable_amount;
				}

				$order->order_status 	= $request->order_status;
				$order->cash_amount 	=  $cash_amount;
				$order->online_amount 	= $online_amount ;
				$order->udhaar_amount 	=  $request->udhaar_amount ;
				$order->save();


				return prepareResult(true,'Order Status updated Successfully' ,$order, 200); 
			}
			return prepareResult(false,'Order Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
	
	public function printOrder($id) 
	{
		try {

			$order = Order::find($id);
			if($order)
			{
				$filename = $id."-".time().".pdf";
				// $filename = $order->order_number.".pdf";
				$data =[
					'order_id'=>$id,
				];
				$customPaper = array(0,0,280,960);
				$pdf = PDF::loadView('order_pdf', $data)->setPaper( $customPaper);
				$pdf->save('invoices/'.$filename);
				$url = env('CDN_DOC_URL').'invoices/'.$filename;

				$order = Order::find($id);
				$order->invoice_path = $url;
				$order->save();
				return prepareResult(true,'print out successful' ,$url, 200); 
			}
			return prepareResult(false,'Order Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function getOrderRecipe($id) 
	{
		try {
			$data = ['order_id'=>$id];
			$orderDetails = OrderDetail::where('order_id',$id)->get(['menu_id']);
			if($orderDetails)
			{
				foreach ($orderDetails as $key => $value) {
					foreach ($value->recipes as $key => $recipe) {
						$data['recipes'][] = $recipe;
					}
				}
				return prepareResult(true,'Recipe List fetched successfully!' ,$data, 200); 
			}
			return prepareResult(false,'Order Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
