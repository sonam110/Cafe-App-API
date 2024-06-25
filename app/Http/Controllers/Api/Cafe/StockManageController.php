<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\StockManage;
use App\Models\StockManageHistory;
use App\Models\Menu;
use App\Models\Unit;
use App\Models\Product;
use DB;
use Log;
use Validator;
use App\Enums\ServerStatus;
use Illuminate\Validation\Rules\Enum;

class StockManageController extends Controller
{
	public function stockManages(Request $request)
	{
		try {
			$query = StockManage::select('stock_manages.*')->join('products', function ($join) {
				$join->on('products.id', '=', 'stock_manages.product_id');
			})
			->withoutGlobalScope('cafe_id')
			->where('stock_manages.cafe_id',auth()->user()->cafe_id)
			->with('product:id,name','unit:id,name','recievedBy:id,name','transferInfo:id,name,email,mobile,address')
			->orderBy('stock_manages.id', 'desc');
			if(!empty($request->product_id))
			{
				$query->where('stock_manages.product_id', $request->product_id);
			}   

			if(!empty($request->stock_operation))
			{
				$query->where('stock_manages.stock_operation', $request->stock_operation);
			}
			if(!empty($request->resource))
			{
				$query->where('stock_manages.resource', $request->resource);
			}
			if(!empty($request->transfer_cafe_id))
			{
				$query->where('stock_manages.transfer_cafe_id', $request->transfer_cafe_id);
			}
			if(!empty($request->product))
			{
				$query->where('products.name', 'LIKE', '%'.$request->product.'%');
			}

			// date wise filter from here
			if(!empty($request->from_date))
			{
				$query->whereDate('stock_manages.created_at', '>=', $request->from_date);
			}

			if(!empty($request->end_date))
			{
				$query->whereDate('stock_manages.created_at', '<=', $request->end_date);
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
			return prepareResult(true,'Stock Data Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		DB::beginTransaction();
		try {
			$validation = Validator::make($request->all(), [
				'stock_operation' => 'required',
				'product_id' => 'required|exists:products,id',
				'resource' => 'required',
				'quantity' => 'required',
				'unit_id'   => unitSimilarTypeCheck($request->unit_id,$request->product_id),
			],
			[
				'unit_id.declined' => 'Invalid Unit Type'
			]);
			if ($validation->fails()) { 
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}
			
			$product_id = $request->product_id;
			// $expense_id = $request->expense_id;
			// $order_id 	= $request->order_id;
			$resource 	= $request->resource;
			$price 	= $request->price;
			$stock_operation = $request->stock_operation;
			$quantity = $request->quantity;
			$unit_id = $request->unit_id;

			if($request->resource =='Transfer'){
				$validation = Validator::make($request->all(), [
					'transfer_cafe_id' => 'required|exists:users,id',
				
				]);
				if ($validation->fails()) { 
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				}

			}
			// $stockManage = stockManage($stock_operation,$product_id,$unit_id,$quantity,$resource,$expense_id,$order_id);
			$stockManageData = stockManageAdd($request,$stock_operation,$product_id,$unit_id,$quantity,$resource,$price);
			if($request->resource =='Transfer'){
				$product_data = Product::withoutGlobalScope('cafe_id')->where('id',$product_id)->first();

				$checkProductExist = Product::withoutGlobalScope('cafe_id')->where('cafe_id',$request->transfer_cafe_id)->where('name',$product_data->name)->first();
				if(empty($checkProductExist)){
					$product = new Product;
					$product->name = $product_data->name;
					$product->description = $product_data->description;
					$product->unit_id = $product_data->unit_id;
					$product->price = $product_data->price;
					$product->image_path = $product_data->image_path;
					$product->current_quanitity = 0;
					$product->alert_quanitity = 0;
					$product->status = $product_data->status ;
					$product->save();
					$productid = $product->id;
					$updatePrdCafeid = Product::where('id',$product->id)->update(['cafe_id'=>$request->transfer_cafe_id]);
				} else{
					$product = $checkProductExist;
					$productid = $checkProductExist->id;
				}
				$stockManage = new StockManage;
				$stockManage->cafe_id = $request->transfer_cafe_id;
				$stockManage->product_id = $productid;
				$stockManage->unit_id = $unit_id;
				$stockManage->quantity = $quantity;
				$stockManage->price = $price;
				$stockManage->stock_operation = 'In';
				$stockManage->resource = $resource;
				$stockManage->shop_name = @$request->shop_name;
				$stockManage->date = @$request->date;
				$stockManage->bill_no = @$request->bill_no;
				$stockManage->address = @$request->address;
				$stockManage->purchase_by = @$request->purchase_by;
				$stockManage->recieved_by = @$request->recieved_by;
				$stockManage->transfer_cafe_id = auth()->user()->cafe_id;
				$stockManage->comment = @$request->comment;
				$stockManage->save();
				$updateCafeid = StockManage::where('id',$stockManage->id)->update(['cafe_id'=>$request->transfer_cafe_id]);

			    // updating the productinfo table as well
				$quantity = convertQuantity($unit_id,$productid,$quantity);
				$getproduct = Product::withoutGlobalScope('cafe_id')->where('id',$productid)->first();
				
				$getproduct->current_quanitity = $getproduct->current_quanitity + $quantity;
				$current_quanitity = $getproduct->current_quanitity + $quantity;
			
				$getproduct->save();

				/*--------------------Stock History-------------------------*/
				$checkOldData = StockManageHistory::where('cafe_id',$request->transfer_cafe_id)->where('product_id',$productid)->orderBy('id','DESC')->first();
				$stockHistory = new StockManageHistory;
				$stockHistory->stock_manage_id  = $stockManage->id;
				$stockHistory->cafe_id  = $request->transfer_cafe_id;
				$stockHistory->product_id = $productid;
				$stockHistory->unit_id = $unit_id;
				$stockHistory->quantity = $quantity;
				$stockHistory->price = $price;
				$stockHistory->old_quantity = (!empty($checkOldData)) ? $checkOldData->quantity :0;
				$stockHistory->old_price = (!empty($checkOldData)) ? $checkOldData->price :0;
				$stockHistory->current_quanitity = $current_quanitity;
				$stockHistory->stock_operation = 'In';
				$stockHistory->resource = $resource;
				$stockHistory->shop_name = @$request->shop_name;
				$stockHistory->date = @$request->date;
				$stockHistory->bill_no = @$request->bill_no;
				$stockHistory->address = @$request->address;
				$stockHistory->purchase_by = @$request->purchase_by;
				$stockHistory->recieved_by = @$request->recieved_by;
				$stockHistory->transfer_cafe_id = auth()->user()->cafe_id;
				$stockHistory->comment = @$request->comment;
				$stockHistory->save();



			}

			DB::commit();
			return prepareResult(true,'Stock created Ssuccessfully' , $stockManageData, 200);
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
				'stock_operation' => 'required',
				'product_id' => 'required|exists:products,id',
				'resource' => 'required',
				'quantity' => 'required',
				'unit_id'   => unitSimilarTypeCheck($request->unit_id,$request->product_id)
			],
			[
				'unit_id.declined' => 'Invalid Unit Type'
			]);
			if ($validation->fails()) { 
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}
			$stockManage = StockManage::find($id);
		
			if(empty($stockManage))
			{
				return prepareResult(false,'Stock Data Not Found' ,[], 500); 
			}
			

			$product_id = $request->product_id;
			// $expense_id = $request->expense_id;
			// $order_id 	= $request->order_id;
			$resource 	= $request->resource;
			$price 	= $request->price;
			$stock_operation = $request->stock_operation;
			$quantity = $request->quantity;
			$unit_id = $request->unit_id;

			$stockManageData = stockManageUpdate($request,$stockManage,$stock_operation,$product_id,$unit_id,$quantity,$resource,$price);
			

			DB::commit();
			return prepareResult(true,'Stock Manage Updated Successfully' , $stockManage, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$stockManage = StockManage::with('product:id,name','unit:id,name','transferInfo:id,name,email,mobile,address')->find($id);
			if($stockManage)
			{
				return prepareResult(true,'Stock Data Fatched Successfully' ,$stockManage, 200); 
			}
			return prepareResult(false,'Stock Data Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			DB::beginTransaction();
			$stockManage = StockManage::find($id);
			if($stockManage)
			{
				$stockManage = stockManageDelete($stockManage);
				DB::commit();
				return prepareResult(true,'Stock Data Deleted Successfully' ,[], 200); 
			}
			return prepareResult(false,'Stock Data Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function stockManageGraph(Request $request)
	{
		

	}

	function getLast30Days()
    {
        $today     = new \DateTime();
        $begin     = $today->sub(new \DateInterval('P10D'));
        $end       = new \DateTime();
        $end       = $end->modify('+1 day');
        $interval  = new \DateInterval('P1D');
        $daterange = new \DatePeriod($begin, $interval, $end);
        foreach ($daterange as $date) {
            $dateList[] = '"'.$date->format("Y-m-d").'"';
        }
        $allDates = implode(', ', $dateList);
        //dd($allDates);
        return $allDates;
    }

    public function stockManageHistory(Request $request)
	{
		try {
			$query = StockManageHistory::select('stock_manage_histories.*')->join('products', function ($join) {
				$join->on('products.id', '=', 'stock_manage_histories.product_id');
			})
			->withoutGlobalScope('cafe_id')
			->where('stock_manage_histories.cafe_id',auth()->user()->cafe_id)
			->with('product:id,name','unit:id,name','recievedBy:id,name')
			->orderBy('stock_manage_histories.id', 'desc');
			if(!empty($request->product_id))
			{
				$query->where('stock_manage_histories.product_id', $request->product_id);
			}   

			if(!empty($request->stock_operation))
			{
				$query->where('stock_manage_histories.stock_operation', $request->stock_operation);
			}
			if(!empty($request->product))
			{
				$query->where('products.name', 'LIKE', '%'.$request->product.'%');
			}

			// date wise filter from here
			if(!empty($request->from_date))
			{
				$query->whereDate('stock_manage_histories.created_at', '>=', $request->from_date);
			}

			if(!empty($request->end_date))
			{
				$query->whereDate('stock_manage_histories.created_at', '<=', $request->end_date);
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
			return prepareResult(true,'Stock Data history Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

}
