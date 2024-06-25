<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerAccount;
use App\Models\User;
use DB;
use Log;
use Validator;


class CustomerAccountController extends Controller
{
	public function customerAccounts(Request $request)
	{
		try {
			$query = CustomerAccount::select('*')
			->with('customer:id,name,role_id,account_balance')
			->orderBy('id', 'desc');

            // below query is to search inside join function 
			$name = $request->name;
			if(!empty($name))
			{
				$query->whereHas('customer',function ($query) use ($name) {
					$query->Where('name', 'LIKE', "%{$name}%");
				});    
			} 

			if(!empty($request->transaction_type))
			{
				$query->where('transaction_type', $request->transaction_type);
			}
			if(!empty($request->customer_id))
			{
				$query->where('customer_id', $request->customer_id);
			}
                    // date wise filter from here
			if(!empty($request->from_date) && !empty($request->end_date))
			{
				$query->whereDate('created_at', '>=', $request->from_date)->whereDate('created_at', '<=', $request->end_date);
			}

			if(!empty($request->from_date) && !empty($request->end_date) && !empty($request->customer_id))
			{
				$query->where('customer_id', $request->customer_id)->whereDate('created_at', '>=', $request->from_date)->whereDate('created_at', '<=', $request->end_date);
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
			'customer_id' => 'required|numeric',
		]);

		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}
		DB::beginTransaction();
		try {       
			$customer = User::where('role_id',5)->where('id', $request->customer_id)->first();
			if(empty($customer))
			{
				return prepareResult(false,'Customer not found' ,[], 500);
			}

			$sale = 0; //$request->sale

			$customerAccount = new CustomerAccount;
			$customerAccount->customer_id = $request->customer_id;

            // storing customer stock from product customerAccounts stock table
			$customerAccount->previous_balance = $customer->account_balance;
			$customerAccount->sale = $sale;
			$customerAccount->payment_received = $request->payment_received ;

            // stock in/out calculation
            // $customerAccount->new_balance = strtolower($request->transaction_type) == "credit" 
            // ? $customer->account_balance + $request->change_in_balance 
            // : $customer->account_balance - $request->change_in_balance;

			$customerAccount->new_balance = ($request->payment_received + $customer->account_balance) - ($sale);
			$customerAccount->mode_of_transaction = $request->mode_of_transaction;
			$customerAccount->save();

            // updating the Customer table as well
			$customer->account_balance = $customerAccount->new_balance;
			$customer->save();

			DB::commit();
			return prepareResult(true,'Your data has been saved successfully' , $customerAccount, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'customer_id' => 'required|numeric',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {
			$sale = 0; //$request->sale
			$customer = User::where('role_id',5)->where('id', $request->customer_id)->first();
			if(empty($customer))
			{
				return prepareResult(false,'Customer not found' ,[], 500);
			}
			$customerAccount = CustomerAccount::find($id);
			if (empty($customerAccount)) {
			 	return prepareResult(false,'Record not found' ,[], 500);
			} 

			//adjust old customer account data
			$customer = User::where('id', $customerAccount->customer_id)->first();
			if(empty($customer))
			{
				return prepareResult(false,'Customer not found' ,[], 500);
			}
			//-----------------------------//

			$customerAccount->customer_id = $request->customer_id;

            // storing customer stock from product customerAccounts stock table
			$customerAccount->previous_balance = $customerAccount->previous_balance;
			$customerAccount->sale = $sale;
			$customerAccount->payment_received = $request->payment_received ;

            // stock in/out calculation
            // $customerAccount->new_balance = strtolower($request->transaction_type) == "credit" 
            // ? $customer->account_balance + $request->change_in_balance 
            // : $customer->account_balance - $request->change_in_balance;

			$customerAccount->new_balance = ($request->payment_received + $customerAccount->previous_balance) - ($sale);
			$customerAccount->mode_of_transaction = $request->mode_of_transaction;
			$customerAccount->save();

            // updating the Customer table as well
			$customer->account_balance = $customerAccount->new_balance;
			$customer->save();
			
			
			DB::commit();
			return prepareResult(true,'Your data has been Updated successfully' ,$customerAccount, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$customerAccount = CustomerAccount::with('customer:id,name,role_id,account_balance')->find($id);
			if($customerAccount)
			{
				return prepareResult(true,'Record Fatched Successfully' ,$customerAccount, 200); 
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
			$customerAccount = CustomerAccount::find($id);
			if($customerAccount)
			{
				$result=$customerAccount->delete();
				return prepareResult(true,'Record Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Record Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}  
}
