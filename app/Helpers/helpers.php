<?php

use App\Models\Unit;
use App\Models\StockManage;
use App\Models\Menu;
use App\Models\Product;
use App\Models\EmployeeAttendence;
use App\Models\AttendenceList;
use App\Models\Category;
use App\Models\OrderDetail;
use App\Models\Expense;
use App\Models\Order;
use App\Models\RecipeContains;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Packaging;
use App\Models\PackagingContents;
use App\Models\StockManageHistory;


function prepareResult($status, $message, $payload, $status_code)
{
	if(empty($payload)) {
		$payload = new stdClass();
	} else {
		$payload = $payload;
	}
	return response()->json(['success' => $status, 'message' => $message, 'payload' => $payload, 'code' => $status_code],$status_code);
}


	// function getUser() {
	// 	return auth('api')->user();
	// }


function unitConversion($unitID, $quantity) {

	$unitName = Unit::where('id', $unitID)->get('name')->first();
		// return $unitName->name;
	
	if((strtolower($unitName->name) == "kilogram") || (strtolower($unitName->name) == "liter") || (strtolower($unitName->name) == "litre"))
	{
		$value = $quantity*1000;
		return $value;
	}
	if ((strtolower($unitName->name) == "gram") || (strtolower($unitName->name) == "millilitre") || (strtolower($unitName->name) == "pack") || (strtolower($unitName->name) == "piece")) 
	{
		$value = $quantity;
		return $value;
	}
	if ((strtolower($unitName->name) == "dozen")) 
	{
		$value = $quantity*12;
		return $value;
	}

}

function unitSimilarTypeCheck($unitID, $productID) {
	if(empty($unit))
	{
	    return 'required';
	}
	$unit = Unit::find($unitID);

	$unitName = strtolower($unit->name);
	$product = Product::find($productID);
	
	$productUnitName = strtolower($product->unit->name);
	if($unitID != $product->unit_id)
	{
		if((($unitName == "gram") && ($productUnitName == "kilogram")) || (($unitName == "kilogram") && ($productUnitName == "gram")) || (($unitName == "litre") && ($productUnitName == "millilitre")) || (($unitName == "millilitre") && ($productUnitName == "litre")) || (($unitName == "piece") && ($productUnitName == "dozen")) || (($unitName == "dozen") && ($productUnitName == "piece")))
		{
			return 'required';
		}
		return 'required|declined:false';
	}
	return 'required';
}

function convertQuantity($unitID, $productID, $quantity) {

	
	$unitName = strtolower(Unit::find($unitID)->name);
	$product = Product::withoutGlobalScope('cafe_id')->where('id',$productID)->first();
	$productUnitName = strtolower($product->unit->name);

		if($unitID != $product->unit_id)
		{
			if(($productUnitName == "kilogram") && ($unitName == "gram"))
			{
				$quantity = $quantity/1000;
			}
			if(($productUnitName == "gram") && ($unitName == "kilogram"))
			{
				$quantity = $quantity*1000;
			}
			if(($productUnitName == "litre") && ($unitName == "millilitre"))
			{
				$quantity = $quantity/1000;
			}
			if(($productUnitName == "millilitre") && ($unitName == "litre"))
			{
				$quantity = $quantity*1000;
			}
			if(($productUnitName == "dozen") && ($unitName == "piece"))
			{
				$quantity = $quantity/12;
			}
			if(($productUnitName == "piece") && ($unitName == "dozen"))
			{
				$quantity = $quantity*1000;
			}
		}
        return $quantity;
	}



function getUser() {
	return auth('api')->user();
}

function stockManageAdd($request,$stock_operation,$product_id,$unit_id,$quantity,$resource,$price=NULL, $expense_id=NULL, $order_id=NULL,$menu_id=NULL)
{


	$stockManage = new StockManage;
	$stockManage->product_id = $product_id;
	$stockManage->expense_id = $expense_id;
	$stockManage->order_id = $order_id;
	$stockManage->menu_id = $menu_id;
	$stockManage->unit_id = $unit_id;
	$stockManage->quantity = $quantity;
	$stockManage->price = $price;
	$stockManage->stock_operation = $stock_operation;
	$stockManage->resource = $resource;
	$stockManage->shop_name = @$request->shop_name;
	$stockManage->date = @$request->date;
	$stockManage->bill_no = @$request->bill_no;
	$stockManage->address = @$request->address;
	$stockManage->purchase_by = @$request->purchase_by;
	$stockManage->recieved_by = @$request->recieved_by;
	$stockManage->transfer_cafe_id = @$request->transfer_cafe_id;
	$stockManage->comment = @$request->comment;
	$stockManage->save();
	

    // updating the productinfo table as well
	$product = Product::find( $product_id);
	$quantity = convertQuantity($unit_id,$product_id,$quantity);
	if(strtolower($stock_operation) == 'in')
	{
		$product->current_quanitity = $product->current_quanitity + $quantity;
		$current_quanitity = $product->current_quanitity + $quantity;
	}
	else
	{
		$product->current_quanitity = $product->current_quanitity - $quantity;
		$current_quanitity = $product->current_quanitity - $quantity;
	}
	$product->save();

	/*--------------------Stock History-------------------------*/
	$checkOldData = StockManageHistory::where('cafe_id',auth()->user()->cafe_id)->where('product_id',$product_id)->orderBy('id','DESC')->where('stock_operation',$stock_operation)->first();
	$stockHistory = new StockManageHistory;
	$stockHistory->stock_manage_id  = $stockManage->id;
	$stockHistory->cafe_id  = auth()->user()->cafe_id;
	$stockHistory->product_id = $product_id;
	$stockHistory->expense_id = $expense_id;
	$stockHistory->order_id = $order_id;
	$stockHistory->menu_id = $menu_id;
	$stockHistory->unit_id = $unit_id;
	$stockHistory->quantity = $quantity;
	$stockHistory->price = $price;
	$stockHistory->old_quantity = (!empty($checkOldData)) ? $checkOldData->quantity :0;
	$stockHistory->old_price = (!empty($checkOldData)) ? $checkOldData->price :0;
	$stockHistory->current_quanitity = $current_quanitity;
	$stockHistory->stock_operation = $stock_operation;
	$stockHistory->resource = $resource;
	$stockHistory->shop_name = @$request->shop_name;
	$stockHistory->date = @$request->date;
	$stockHistory->bill_no = @$request->bill_no;
	$stockHistory->address = @$request->address;
	$stockHistory->purchase_by = @$request->purchase_by;
	$stockHistory->recieved_by = @$request->recieved_by;
	$stockHistory->transfer_cafe_id = @$request->transfer_cafe_id;
	$stockHistory->comment = @$request->comment;
	$stockHistory->save();


	return $stockManage;
}


function stockManageUpdate($request,$stockManage,$stock_operation,$product_id,$unit_id,$quantity,$resource,$price=NULL,$expense_id=NULL, $order_id=NULL, $menu_id=NULL)
{
	//---------------------adjust-stock------------------//
	$oldProduct = Product::find( $stockManage->product_id);
	$oldQuantity = convertQuantity($stockManage->unit_id,$stockManage->product_id,$stockManage->quantity);
	if($stockManage->stock_operation == 'In')
	{
		$oldProduct->current_quanitity = $oldProduct->current_quanitity - $oldQuantity;
		$current_quanitity = $oldProduct->current_quanitity - $oldQuantity;
	}
	else
	{
		$oldProduct->current_quanitity = $oldProduct->current_quanitity + $oldQuantity;
		$current_quanitity = $oldProduct->current_quanitity + $oldQuantity;
	}
	$oldProduct->save();
	//---------------------------------------------------//

	$stockManage->product_id = $product_id;
	$stockManage->expense_id = $expense_id;
	$stockManage->order_id = $order_id;
	$stockManage->menu_id = $menu_id;
	$stockManage->unit_id = $unit_id;
	$stockManage->quantity = $quantity;
	$stockManage->price = $price;
	$stockManage->stock_operation = $stock_operation;
	$stockManage->resource = $resource;
	$stockManage->shop_name = @$request->shop_name;
	$stockManage->date = @$request->date;
	$stockManage->bill_no = @$request->bill_no;
	$stockManage->address = @$request->address;
	$stockManage->purchase_by = @$request->purchase_by;
	$stockManage->recieved_by = @$request->recieved_by;
	$stockManage->transfer_cafe_id = @$request->transfer_cafe_id;
	$stockManage->comment = @$request->comment;
	$stockManage->save();

	// updating the productinfo table as well
	$product = Product::find( $product_id);
	$quantity = convertQuantity($unit_id,$product_id,$quantity);
	if(strtolower($stock_operation) == 'in')
	{
		$product->current_quanitity = $product->current_quanitity + $quantity;
	}
	else
	{
		$product->current_quanitity = $product->current_quanitity - $quantity;
	}
	$product->save();



	/*--------------------Stock History-------------------------*/
	$checkOldData = StockManageHistory::where('cafe_id',auth()->user()->cafe_id)->where('product_id',$product_id)->where('stock_operation',$stock_operation)->orderBy('id','DESC')->first();
	$stockHistory =  StockManageHistory::where('stock_manage_id',$stockManage->id)->first();
	if(!empty($stockHistory)){
		$stockHistory =  StockManageHistory::find($stockHistory->id);
		$stockHistory->cafe_id  = auth()->user()->cafe_id;
		$stockHistory->product_id = $product_id;
		$stockHistory->expense_id = $expense_id;
		$stockHistory->order_id = $order_id;
		$stockHistory->menu_id = $menu_id;
		$stockHistory->unit_id = $unit_id;
		$stockHistory->quantity = $quantity;
		$stockHistory->price = $price;
		$stockHistory->old_quantity = (!empty($checkOldData)) ? $checkOldData->quantity :0;
		$stockHistory->old_price = (!empty($checkOldData)) ? $checkOldData->price :0;
		$stockHistory->current_quanitity = $current_quanitity;
		$stockHistory->stock_operation = $stock_operation;
		$stockHistory->resource = $resource;
		$stockHistory->shop_name = @$request->shop_name;
		$stockHistory->date = @$request->date;
		$stockHistory->bill_no = @$request->bill_no;
		$stockHistory->address = @$request->address;
		$stockHistory->purchase_by = @$request->purchase_by;
		$stockHistory->recieved_by = @$request->recieved_by;
		$stockHistory->transfer_cafe_id = @$request->transfer_cafe_id;
		$stockHistory->comment = @$request->comment;
		$stockHistory->save();
	}
	return $stockManage;
}

function stockManageDelete($stockManage)
{
	$unitData = Unit::find($stockManage->unit_id);

	$product = Product::find( $stockManage->product_id);
	$quantity = convertQuantity($stockManage->unit_id,$stockManage->product_id,$stockManage->quantity);
	if(strtolower($stockManage->stock_operation) == 'in')
	{
		$product->current_quanitity = $product->current_quanitity - $quantity;
	}
	else
	{
		$product->current_quanitity = $product->current_quanitity + $quantity;
	}
	$product->save();
	
	$stockManage->delete();
	return $stockManage;
}

function dateDifference($start_date, $end_date, $differenceFormat = '%d' )
{
    $start = strtotime($start_date);
    $end = strtotime($end_date);

    $days_between = ceil(abs($end - $start) / 86400);
    return $days_between;
}



function getLast30TotalSale($day, $startDate , $endDate, $subcategory, $cafe_id)
{
	if(!empty($day))
	{

		$today     = new \DateTime();
				// $begin     = $today->sub(new \DateInterval('P30D'));

		if(($day == 1 ))
		{
			$begin = $today->sub(new \DateInterval('P0D'));
		}
		elseif (($day == 7)) 
		{
			$begin= $today->sub(new \DateInterval('P7D'));
		}
		elseif (($day == 30 )) 
		{
			$begin= $today->sub(new \DateInterval('P30D'));
		}

		$end       = new \DateTime();
		$end       = $end->modify('+1 day');
		$interval  = new \DateInterval('P1D');
		$daterange = new \DatePeriod($begin, $interval, $end);
		$totalSale =[];
		foreach ($daterange as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice'); 
				}else{
					$salesSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice'); 
				}
			}
			else{
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice');
				}			
			}
			$totalSale[] = $salesSum;
		}
	}


	if(!empty( $startDate))
	{

		$rangArray = []; 
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);

		for ($currentDate = $startDate; $currentDate <= $endDate; 
			$currentDate += (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

		$totalSale =[];
		foreach ($rangArray as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			else{
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			$totalSale[] = $salesSum;
		}
		
	}



			// $data = implode(', ', $totalSale);
	return $totalSale;
}

function getLast30TotalOnlineSale($day, $startDate , $endDate, $subcategory, $cafe_id)
{
	if(!empty($day))
	{

		$today     = new \DateTime();
				// $begin     = $today->sub(new \DateInterval('P30D'));

		if(($day == 1 ))
		{
			$begin = $today->sub(new \DateInterval('P0D'));
		}
		elseif (($day == 7)) 
		{
			$begin= $today->sub(new \DateInterval('P7D'));
		}
		elseif (($day == 30 )) 
		{
			$begin= $today->sub(new \DateInterval('P30D'));
		}

		$end       = new \DateTime();
		$end       = $end->modify('+1 day');
		$interval  = new \DateInterval('P1D');
		$daterange = new \DatePeriod($begin, $interval, $end);
		$totalSale =[];
		foreach ($daterange as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->where('mode_of_transaction', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice'); 
				}else{
					$salesSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice'); 
				}	
				
			}
			else{
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->where('mode_of_transaction', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			$totalSale[] = $salesSum;
		}
	}


	if(!empty( $startDate))
	{

		$rangArray = []; 
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);

		for ($currentDate = $startDate; $currentDate <= $endDate; 
			$currentDate += (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

		$totalSale =[];
		foreach ($rangArray as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			else{
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			$totalSale[] = $salesSum;
		}
		
	}



			// $data = implode(', ', $totalSale);
	return $totalSale;
}

function getLast30TotalCashSale($day, $startDate , $endDate, $subcategory, $cafe_id)
{
	if(!empty($day))
	{

		$today     = new \DateTime();
				// $begin     = $today->sub(new \DateInterval('P30D'));

		if(($day == 1 ))
		{
			$begin = $today->sub(new \DateInterval('P0D'));
		}
		elseif (($day == 7)) 
		{
			$begin= $today->sub(new \DateInterval('P7D'));
		}
		elseif (($day == 30 )) 
		{
			$begin= $today->sub(new \DateInterval('P30D'));
		}

		$end       = new \DateTime();
		$end       = $end->modify('+1 day');
		$interval  = new \DateInterval('P1D');
		$daterange = new \DatePeriod($begin, $interval, $end);
		$totalSale =[];
		foreach ($daterange as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->where('mode_of_transaction', 1)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice'); 
				}else{
					$salesSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice'); 
				}	

				
			}
			else{
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->where('mode_of_transaction', 1)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			$totalSale[] = $salesSum;
		}
	}


	if(!empty( $startDate))
	{

		$rangArray = []; 
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);

		for ($currentDate = $startDate; $currentDate <= $endDate; 
			$currentDate += (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

		$totalSale =[];
		foreach ($rangArray as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 1)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			else{
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 1)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			$totalSale[] = $salesSum;
		}
		
	}



			// $data = implode(', ', $totalSale);
	return $totalSale;
}

function getLast30TotalRecurringSale($day, $startDate , $endDate, $subcategory, $cafe_id)
{
	if(!empty($day))
	{

		$today     = new \DateTime();
				// $begin     = $today->sub(new \DateInterval('P30D'));

		if(($day == 1 ))
		{
			$begin = $today->sub(new \DateInterval('P0D'));
		}
		elseif (($day == 7)) 
		{
			$begin= $today->sub(new \DateInterval('P7D'));
		}
		elseif (($day == 30 )) 
		{
			$begin= $today->sub(new \DateInterval('P30D'));
		}

		$end       = new \DateTime();
		$end       = $end->modify('+1 day');
		$interval  = new \DateInterval('P1D');
		$daterange = new \DatePeriod($begin, $interval, $end);
		$totalSale =[];
		foreach ($daterange as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->where('mode_of_transaction', 3)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice'); 
				}else{
					$salesSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice'); 
				}	
			}
			else{
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->where('mode_of_transaction', 3)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			$totalSale[] = $salesSum;
		}
	}


	if(!empty( $startDate))
	{

		$rangArray = []; 
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);

		for ($currentDate = $startDate; $currentDate <= $endDate; 
			$currentDate += (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

		$totalSale =[];
		foreach ($rangArray as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 3)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}				
			}
			else{
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 3)->select('id')->get();
				if(!empty($cafe_id)){
					$salesSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}else{
					$salesSum = OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				}	

			}
			$totalSale[] = $salesSum;
		}
		
	}



			// $data = implode(', ', $totalSale);
	return $totalSale;
}


function getLast30TotalProduct($day, $startDate , $endDate, $subcategory, $cafe_id)
{
	if(!empty($day))
	{
		$today     = new \DateTime();
				// $begin     = $today->sub(new \DateInterval('P30D'));

		if(($day == 1 ))
		{
			$begin = $today->sub(new \DateInterval('P0D'));
		}
		elseif (($day == 7)) 
		{
			$begin= $today->sub(new \DateInterval('P7D'));
		}
		elseif (($day == 30 )) 
		{
			$begin= $today->sub(new \DateInterval('P30D'));
		}

		$end       = new \DateTime();
		$end       = $end->modify('+1 day');
		$interval  = new \DateInterval('P1D');
		$daterange = new \DatePeriod($begin, $interval, $end);
		$totalProduct =[];
		foreach ($daterange as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$productSum = OrderContain::where('product_menu_id',$subcategory)->where('cafe_id',$cafe_id)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('quantity'); 
				}else{
					$productSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('quantity'); 
				}	
				
			}
			else{
				$orderid = Order::whereDate('created_at',$date->format('Y-m-d'))->where('order_status', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$productSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('quantity');
				}else{
					$productSum = OrderContain::whereDate('created_at',$date->format('Y-m-d'))->whereIn('order_id',$orderid)->sum('quantity');
				}	

					// $productSum = OrderContain::whereDate('created_at',$date->format('Y-m-d'))->sum('quantity'); 

			}
			$totalProduct[] = $productSum;
		}

		
	}

	if(!empty( $startDate))
	{

		$rangArray = []; 
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);

		for ($currentDate = $startDate; $currentDate <= $endDate; 
			$currentDate += (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

		$totalProduct =[];
		foreach ($rangArray as $date) {
			if(!empty( $subcategory)){
				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->select('id')->get();
				if(!empty($cafe_id)){
					$productSum = OrderContain::where('cafe_id',$cafe_id)->where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity'); 
				}else{
					$productSum = OrderContain::where('product_menu_id',$subcategory)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity'); 
				}						
			}
			else{

				$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->select('id')->get();
				if(!empty( $subcategory)){
					$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->select('id')->get();
					if(!empty($cafe_id)){
						$productSum = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity');
					}else{
						$productSum = OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity');
					}						
				}

					// $productSum = OrderContain::whereDate('created_at',$date)->sum('quantity'); 
			}
			$totalProduct[] = $productSum;
		}
		
	}


			// $data = implode(', ', $totalProduct);
	return $totalProduct;
}

function getLast30TotalExpense($day, $startDate , $endDate, $cafe_id)
{
	if(!empty($day))
	{

		$today     = new \DateTime();
					// // $begin     = $today->sub(new \DateInterval('P30D'));

		if(($day == 1 ))
		{
			$begin = $today->sub(new \DateInterval('P0D'));
		}
		elseif (($day == 7)) 
		{
			$begin= $today->sub(new \DateInterval('P7D'));
		}
		elseif (($day == 30 )) 
		{
			$begin= $today->sub(new \DateInterval('P30D'));
		}

		$end       = new \DateTime();
		$end       = $end->modify('+1 day');
		$interval  = new \DateInterval('P1D');
		$daterange = new \DatePeriod($begin, $interval, $end);

		$totalExpense =[];
		foreach ($daterange as $date) {
			if(!empty($cafe_id)){
				$expenseSum = Expense::where('cafe_id',$cafe_id)->whereDate('expense_date',$date->format('Y-m-d'))->sum('totalExpense'); 
			}else{
				$expenseSum = Expense::whereDate('expense_date',$date->format('Y-m-d'))->sum('totalExpense'); 
			}			
			$totalExpense[] = $expenseSum;
		}

	}

	if(!empty( $startDate))
	{

		$rangArray = []; 
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);

		for ($currentDate = $startDate; $currentDate <= $endDate; 
			$currentDate += (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

		$totalExpense =[];
		foreach ($rangArray as $date) {
			if(!empty($cafe_id)){
				$expenseSum = Expense::where('cafe_id',$cafe_id)->whereDate('expense_date',$date)->sum('totalExpense'); 
			}else{
				$expenseSum = Expense::whereDate('expense_date',$date)->sum('totalExpense'); 
			}			
			$totalExpense[] = $expenseSum;
		}
		
	}

			// $data = implode(', ', $totalRevenue);
	return $totalExpense;

}


function getLast30DaysList($day, $startDate , $endDate)
{
	if(!empty($day))
	{
		$today     = new \DateTime();
				// $begin     = $today->sub(new \DateInterval('P0D'));

		if(($day == 1 ))
		{
			$begin = $today->sub(new \DateInterval('P0D'));
		}
		elseif (($day == 7)) 
		{
			$begin= $today->sub(new \DateInterval('P7D'));
		}
		elseif (($day == 30 )) 
		{
			$begin= $today->sub(new \DateInterval('P30D'));
		}
		$end       = new \DateTime();
		$end       = $end->modify('+1 day');
		$interval  = new \DateInterval('P1D');
		$daterange = new \DatePeriod($begin, $interval, $end);
		foreach ($daterange as $date) {
			$dateList[] = ''.$date->format("Y-m-d").'';
		}
	}

	if(!empty( $startDate))
	{
		$dateList = []; 
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);

		for ($currentDate = $startDate; $currentDate <= $endDate; 
			$currentDate += (86400)) {

			$date = date('Y-m-d', $currentDate);
			$dateList[] = $date;
		}
		
	}

	return $dateList;
}

function getCategoryName($categoryDay)
{



	$categorySearch= DB::table("order_contains")->select('category_id')->whereDate('created_at','>', now()->subDays(30)->endOfDay())->unique()->all();
	return $categorySearch;
}

function getDetails($startDate , $endDate, $category, $cafe_id)
{
	if(!empty($startDate))
	{
		if(!empty($category)){

			
			$date = Carbon::createFromFormat('Y-m-d', $endDate);
				// $daysToAdd = 1;
			$date = $date->addDays(1);
			if(!empty($cafe_id)){
				$a = 	DB::table('order_contains as w')
				->join("product_menus", "w.product_menu_id", "=", "product_menus.id")
				->where('w.category_id', $category)
				->where('w.cafe_id', $cafe_id)
				->whereBetween('w.created_at', [$startDate, date_format($date, "Y-m-d")])
				// ->whereBetween('w.created_at', ["2022-07-26", "2022-08-26"])
				->select(array(DB::Raw('sum(w.quantity) as total_quantity'), DB::Raw('sum(w.netPrice) as total_netPrice'), DB::Raw('DATE(w.created_at) date'), 'w.product_menu_id', 'product_menus.name'))
				->groupBy(['date', 'w.product_menu_id', 'product_menus.name'])
				->orderBy('w.created_at', 'desc')
				->get();
			}else{
				$a = 	DB::table('order_contains as w')
				->join("product_menus", "w.product_menu_id", "=", "product_menus.id")
				->where('w.category_id', $category)
				->whereBetween('w.created_at', [$startDate, date_format($date, "Y-m-d")])
				// ->whereBetween('w.created_at', ["2022-07-26", "2022-08-26"])
				->select(array(DB::Raw('sum(w.quantity) as total_quantity'), DB::Raw('sum(w.netPrice) as total_netPrice'), DB::Raw('DATE(w.created_at) date'), 'w.product_menu_id', 'product_menus.name'))
				->groupBy(['date', 'w.product_menu_id', 'product_menus.name'])
				->orderBy('w.created_at', 'desc')
				->get();
			}
			$orderDetails = $a;
			return  $orderDetails;
		}else{
			$date = Carbon::createFromFormat('Y-m-d', $endDate);
				// $daysToAdd = 1;
			$date = $date->addDays(1);
			if(!empty($cafe_id)){
				$a = 	DB::table('order_contains as w')
				->join("product_menus", "w.product_menu_id", "=", "product_menus.id")
					// ->where('w.category_id', $category)
				->where('w.cafe_id', $cafe_id)
				->whereBetween('w.category_id', [1, 1000])
				->whereBetween('w.created_at', [$startDate, date_format($date, "Y-m-d")])
					// ->whereBetween('w.created_at', ["2022-07-26", "2022-08-26"])
				->select(array(DB::Raw('sum(w.quantity) as total_quantity'), DB::Raw('sum(w.netPrice) as total_netPrice'), DB::Raw('DATE(w.created_at) date'), 'w.product_menu_id', 'product_menus.name'))
				->groupBy(['date', 'w.product_menu_id', 'product_menus.name'])
				->orderBy('w.created_at', 'desc')
				->get();
			}else{
				$a = 	DB::table('order_contains as w')
				->join("product_menus", "w.product_menu_id", "=", "product_menus.id")
					// ->where('w.category_id', $category)
					// ->where('w.cafe_id', $cafe_id)
					// if(!empty($cafe_id)){
					// 	$a->where('w.cafe_id', $cafe_id);
					// }
				->whereBetween('w.category_id', [1, 1000])
				->whereBetween('w.created_at', [$startDate, date_format($date, "Y-m-d")])
					// ->whereBetween('w.created_at', ["2022-07-26", "2022-08-26"])
				->select(array(DB::Raw('sum(w.quantity) as total_quantity'), DB::Raw('sum(w.netPrice) as total_netPrice'), DB::Raw('DATE(w.created_at) date'), 'w.product_menu_id', 'product_menus.name'))
				->groupBy(['date', 'w.product_menu_id', 'product_menus.name'])
				->orderBy('w.created_at', 'desc')
				->get();	
			}

			$orderDetails = $a;
			return  $orderDetails;

		}

	}
	elseif(!empty($category)){
		$startDate = date('Y-m-d');
			// echo $startDate;
		$toDay = Carbon::createFromFormat('Y-m-d', $startDate);
		$daysToAdd = 1;
		$toDay = $toDay->addDays($daysToAdd);
		$enddate = Carbon::createFromFormat('Y-m-d', $startDate);
		$daysToAdd = -30;
		$enddate = $enddate->addDays($daysToAdd);
		if(!empty($cafe_id)){
			$a = 	DB::table('order_contains as w')
			->join("product_menus", "w.product_menu_id", "=", "product_menus.id")
			->where('w.category_id', $category)
			->where('w.cafe_id', $cafe_id)
			->whereBetween('w.created_at', [date_format($enddate, "Y-m-d"), date_format($toDay, "Y-m-d")])
			->select(array(DB::Raw('sum(w.quantity) as total_quantity'), DB::Raw('sum(w.netPrice) as total_netPrice'), DB::Raw('DATE(w.created_at) date'), 'w.product_menu_id', 'product_menus.name'))
			->groupBy(['date', 'w.product_menu_id', 'product_menus.name'])
			->orderBy('w.created_at', 'desc')
			->get();
		}else{
			$a = 	DB::table('order_contains as w')
			->join("product_menus", "w.product_menu_id", "=", "product_menus.id")
			->where('w.category_id', $category)
				// ->where('w.cafe_id', $cafe_id)
				// if(!empty($cafe_id)){
				// 	$a->where('w.cafe_id', $cafe_id);
				// }
			->whereBetween('w.created_at', [date_format($enddate, "Y-m-d"), date_format($toDay, "Y-m-d")])
			->select(array(DB::Raw('sum(w.quantity) as total_quantity'), DB::Raw('sum(w.netPrice) as total_netPrice'), DB::Raw('DATE(w.created_at) date'), 'w.product_menu_id', 'product_menus.name'))
			->groupBy(['date', 'w.product_menu_id', 'product_menus.name'])
			->orderBy('w.created_at', 'desc')
			->get();
		}
		
		$orderDetails = $a;
		return  $orderDetails;
	}
	else{
		$startDate = date('Y-m-d');
			// $startDate = "2022-09-25";
			// echo $startDate;
		$toDay = Carbon::createFromFormat('Y-m-d', $startDate);
		$daysToAdd = 1;
		$toDay = $toDay->addDays($daysToAdd);
		$enddate = Carbon::createFromFormat('Y-m-d', $startDate);
		$daysToAdd = -30;
		$enddate = $enddate->addDays($daysToAdd);
		if(!empty($cafe_id)){
			$a = 	DB::table('order_contains as w')
			->join("product_menus", "w.product_menu_id", "=", "product_menus.id")
			->where('w.cafe_id', $cafe_id)
				// if(!empty($cafe_id)){
				// 	$a->where('w.cafe_id', $cafe_id);
				// }
			->whereBetween('w.category_id', [1, 1000])
			->whereBetween('w.created_at', [date_format($enddate, "Y-m-d"), date_format($toDay, "Y-m-d")])
			->select(array(DB::Raw('sum(w.quantity) as total_quantity'), DB::Raw('sum(w.netPrice) as total_netPrice'), DB::Raw('DATE(w.created_at) date'), 'w.product_menu_id', 'product_menus.name'))
			->groupBy(['date', 'w.product_menu_id', 'product_menus.name'])
			->orderBy('w.created_at', 'desc')
			->get();
		}else{
			$a = 	DB::table('order_contains as w')
			->join("product_menus", "w.product_menu_id", "=", "product_menus.id")
				// ->where('w.cafe_id', $cafe_id)
				// if(!empty($cafe_id)){
				// 	$a->where('w.cafe_id', $cafe_id);
				// }
			->whereBetween('w.category_id', [1, 1000])
			->whereBetween('w.created_at', [date_format($enddate, "Y-m-d"), date_format($toDay, "Y-m-d")])
			->select(array(DB::Raw('sum(w.quantity) as total_quantity'), DB::Raw('sum(w.netPrice) as total_netPrice'), DB::Raw('DATE(w.created_at) date'), 'w.product_menu_id', 'product_menus.name'))
			->groupBy(['date', 'w.product_menu_id', 'product_menus.name'])
			->orderBy('w.created_at', 'desc')
			->get();
		}

		$orderDetails = $a;
		return  $orderDetails;

	}

	
}


function getLast30details($day, $startDate , $endDate, $cafe_id)
{
	if(!empty($day))
	{
		$rangArray = []; 
		$startDate = date('Y-m-d');
		$toDay = Carbon::createFromFormat('Y-m-d', $startDate);
		$daysToAdd = 0;
		$toDay = $toDay->addDays($daysToAdd);
				// return $toDay;
		$toDay = strtotime($toDay);
				// return $toDay;
		$endDay = Carbon::createFromFormat('Y-m-d', $startDate);
		$daysToAdd = -($day-1);
		$endDay = $endDay->addDays($daysToAdd);
				// return $endDate;
		$endDate = strtotime($endDay);
				// return $endDate;


		for ($currentDate = $toDay; $currentDate >= $endDate; 
			$currentDate -= (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

				// $orderDetails =[];
		foreach ($rangArray as $date) {
			$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->select('id')->get();
			$orderidCash = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 1)->select('id')->get();
			$orderidOnline = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 2)->select('id')->get();
			$orderidrecurring = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 3)->select('id')->get();
			$orders['date']= $date;
			if(!empty($cafe_id)){
				$orders['sales_online']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidOnline)->sum('netPrice');
				$orders['sales_cash']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidCash)->sum('netPrice');
				$orders['sales_recurring']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidrecurring)->sum('netPrice');
				$orders['sales']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				$orders['product'] = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity'); 
				$orders['expense']= Expense::where('cafe_id',$cafe_id)->whereDate('expense_date',$date)->sum('totalExpense');
			}else{
				$orders['sales_online']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidOnline)->sum('netPrice');
				$orders['sales_cash']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidCash)->sum('netPrice');
				$orders['sales_recurring']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidrecurring)->sum('netPrice');
				$orders['sales']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				$orders['product'] = OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity'); 
				$orders['expense']= Expense::whereDate('expense_date',$date)->sum('totalExpense');
			}	


			$orderDetails[] = $orders;

		}
		return $orderDetails;

		
	}
			// $expenseSum = Expense::whereDate('expense_date',$date->format('Y-m-d'))->sum('totalExpense'); 
	elseif(!empty( $startDate))
	{

		$rangArray = []; 
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);

		for ($currentDate = $endDate; $currentDate >= $startDate; 
			$currentDate -= (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

				// $orderDetails =[];
		foreach ($rangArray as $date) {
			$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->select('id')->get();
			$orderidCash = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 1)->select('id')->get();
			$orderidOnline = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 2)->select('id')->get();
			$orderidrecurring = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 3)->select('id')->get();
			$orders['date']= $date;
			if(!empty($cafe_id)){
				$orders['sales_online']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidOnline)->sum('netPrice');
				$orders['sales_cash']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidCash)->sum('netPrice');
				$orders['sales_recurring']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidrecurring)->sum('netPrice');
				$orders['sales']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				$orders['product'] = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity'); 
				$orders['expense']= Expense::where('cafe_id',$cafe_id)->whereDate('expense_date',$date)->sum('totalExpense');
			}else{
				$orders['sales_online']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidOnline)->sum('netPrice');
				$orders['sales_cash']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidCash)->sum('netPrice');
				$orders['sales_recurring']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidrecurring)->sum('netPrice');
				$orders['sales']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				$orders['product'] = OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity'); 
				$orders['expense']= Expense::whereDate('expense_date',$date)->sum('totalExpense');
			}			


			$orderDetails[] = $orders;

		}
		return $orderDetails;
	}
	else{
		$rangArray = []; 
		$startDate = date('Y-m-d');
		$toDay = Carbon::createFromFormat('Y-m-d', $startDate);
		$daysToAdd = 0;
		$toDay = $toDay->addDays($daysToAdd);
				// return $toDay;
		$toDay = strtotime($toDay);
				// return $toDay;
		$endDay = Carbon::createFromFormat('Y-m-d', $startDate);
		$daysToAdd = -(7);
		$endDay = $endDay->addDays($daysToAdd);
				// return $endDate;
		$endDate = strtotime($endDay);
				// return $endDate;


		for ($currentDate = $toDay; $currentDate >= $endDate; 
			$currentDate -= (86400)) {

			$date = date('Y-m-d', $currentDate);
			$rangArray[] = $date;
		}

				// $orderDetails =[];
		foreach ($rangArray as $date) {
			$orderid = Order::whereDate('created_at',$date)->where('order_status', 2)->select('id')->get();
			$orderidCash = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 1)->select('id')->get();
			$orderidOnline = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 2)->select('id')->get();
			$orderidrecurring = Order::whereDate('created_at',$date)->where('order_status', 2)->where('mode_of_transaction', 3)->select('id')->get();
			$orders['date']= $date;

			if(!empty($cafe_id)){
				$orders['sales_online']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidOnline)->sum('netPrice');
				$orders['sales_cash']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidCash)->sum('netPrice');
				$orders['sales_recurring']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderidrecurring)->sum('netPrice');
				$orders['sales']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				$orders['product'] = OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity'); 
				$orders['revenue']= OrderContain::where('cafe_id',$cafe_id)->whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');

			}else{
				$orders['sales_online']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidOnline)->sum('netPrice');
				$orders['sales_cash']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidCash)->sum('netPrice');
				$orders['sales_recurring']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderidrecurring)->sum('netPrice');
				$orders['sales']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');
				$orders['product'] = OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('quantity'); 
				$orders['revenue']= OrderContain::whereDate('created_at',$date)->whereIn('order_id',$orderid)->sum('netPrice');

			}		

			$orderDetails[] = $orders;

		}
		return $orderDetails;

	}


			// $data = implode(', ', $totalProduct);
}

function recipeDeductionValidation($productID, $quantity)
{

	$deletOld  = RecipeContains::where('recipe_id', $productID)->get();
			// return $deletOld;
	$recipeStock = []; 
	foreach ($deletOld as $key => $value) {


		$updateStock = Product::find($value->product_info_stock_id);

		$lessQuantity=  $updateStock->current_quanitity - (unitConversion($value->unit_id, $value->quantity) * $quantity );

					// return $lessQuantity;
		if($lessQuantity < 0)
		{
			return 'required|declined:false';
		}
		else
		{
			return 'required';
		}
	}

			// return "hello";
}

function withoutRecipeDeductionValidation($product_info_stock_id, $quantity)
{
	$updateStock = Product::find($product_info_stock_id);
	$lessQuantity =  $updateStock->current_quanitity - $quantity ;

					// return $lessQuantity;
	if($lessQuantity < 0)
	{
		return 'required|declined:false';
	}
	else
	{
		return 'required';
	}
}



function recipeDeduction($productID, $quantity)
{

	$deletOld  = RecipeContains::where('recipe_id', $productID)->get();
	$recipeStock = []; 
	foreach ($deletOld as $key => $value) {


		$updateStock = Product::find($value->product_info_stock_id);

		$updateStock->current_quanitity =  $updateStock->current_quanitity - (unitConversion($value->unit_id, $value->quantity) * $quantity );
		$updateStock->save();


				//    below code is for debugging purpose, getting output of array

				// $recipeStock[] = [
				// 	// 'emp_id' =>  $getCurrentQuantity->current_quanitity,
				// 	'emp_2' =>  $updateStock->current_quanitity,				
				// ];
	}

			// return $recipeStock;
}    

function packagingDeduction($packagingID, $quantity)
{

	$deletOld  = PackagingContents::where('packaging_id', $packagingID)->get();
	foreach ($deletOld as $key => $value) {


		$updateStock = Product::find($value->product_info_stock_id);

		$updateStock->current_quanitity =  $updateStock->current_quanitity - ($value->quantity * $quantity) ;
		$updateStock->save();


				//    below code is for debugging purpose, getting output of array

				// $recipeStock[] = [
				// 	// 'emp_id' =>  $getCurrentQuantity->current_quanitity,
				// 	'emp_2' =>  $updateStock->current_quanitity,				
				// ];
	}

			// return $recipeStock;
}    
function withoutRecipeDeduction($product_info_stock_id, $quantity)
{

	$updateStock = Product::find($product_info_stock_id);
	$productMenuQuanitity  = ProductMenu::where('product_info_stock_id', $product_info_stock_id)->first();
	$updateStock->current_quanitity =  $updateStock->current_quanitity -  ($quantity * $productMenuQuanitity->quantity);
	$updateStock->save();

}    
