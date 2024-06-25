<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\StockManage;
use DB;
use Log;
use Validator;

class ExpenseController extends Controller
{

	public function expenses(Request $request)
	{
		try {
			$query = Expense::select('id','item','description','total_expense','expense_date','created_at','updated_at')->orderBy('id', 'desc');
			// ->with('product:id,name')
			// if(!empty($request->product_id))
			// {
			// 	$query->where('product_id', $request->product_id);
			// }
			if(!empty($request->total_expense))
			{
				$query->where('total_expense', $request->total_expense);
			}
			if(!empty($request->description))
			{
				$query->where('description', $request->description);
			}
			if(!empty($request->expense_date))
			{
				$query->where('expense_date', $request->expense_date);
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
			return prepareResult(true,'Expences List Fatched Successfully' ,$query, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function store(Request $request)
	{
		$validation = Validator::make($request->all(), [
			'description' => 'required',
			'total_expense' => 'required|numeric',
			'expense_date' => 'required',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}  
		DB::beginTransaction();
		try {     
			$expense = new Expense;
			// $expense->product_id = $request->product_id;
			// $expense->unit_id = $request->unit_id;
			// $expense->price = $request->price;
			// $expense->quantity = $request->quantity;
			$expense->item = $request->item;
			$expense->description = $request->description;
			$expense->expense_date = $request->expense_date;
			$expense->total_expense = $request->total_expense;
			$expense->save();

			// if(!empty($request->product_id))
			// {
			// 	$stockManage = stockManageAdd('in',$request->product_id,$request->unit_id,$request->quantity,'Seller',$request->price,$expense->id,null,null);
			// }
			DB::commit();
			return prepareResult(true,'Expense data saved successfully' , $expense, 200);

		} catch (\Throwable $e)
		{
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'description' => 'required',
			'total_expense' => 'required',
			'expense_date'  => 'required',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}  
		DB::beginTransaction();
		try {
			$expense = Expense::select('id','item','description','total_expense','expense_date','created_at','updated_at')->find($id);
			if(empty($expense))
			{
				return prepareResult(false,'Expense Data Not Found' ,[], 500);
			}
			// $expense->product_id = $request->product_id;
			// $expense->unit_id = $request->product_id;
			// $expense->price = $request->price;
			// $expense->quantity = $request->quantity;
			$expense->item = $request->item;
			$expense->description = $request->description;
			$expense->expense_date = $request->expense_date;
			$expense->total_expense = $request->total_expense;
			$expense->save();

			// $stockManage = StockManage::where('expense_id',$expense->id)->first();
			// $stockManageDelete = stockManageDelete($stockManage);
			// $stockManage = stockManageAdd('in',$request->product_id,$request->unit_id,$request->quantity,'Seller',$request->price,$expense->id,null,null);
			DB::commit();
			return prepareResult(true,'Expense Updated successfully' ,$expense, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$expense = Expense::select('id','item','description','total_expense','expense_date','created_at','updated_at')->find($id);
			if($expense)
			{
				return prepareResult(true,'Expense Fatched Successfully' ,$expense, 200); 
			}
			return prepareResult(false,'Expense Data Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$expense = Expense::find($id);
			if($expense)
			{
				// $stockManage = StockManage::where('expense_id',$id)->first();
				// $stockManageDelete = stockManageDelete($stockManage);
				$expense->delete();
				return prepareResult(true,'Expense Deleted Successfully' ,[], 200); 
			}
			return prepareResult(false,'Expense Data Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}    
}
