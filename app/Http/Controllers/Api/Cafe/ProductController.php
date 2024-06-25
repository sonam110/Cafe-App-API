<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\User;
use App\Models\StockManage;
use App\Imports\StockImport;
use App\Exports\ProductExport;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use Log;
use Validator;

class ProductController extends Controller
{
	public function products(Request $request)
	{
		try {
			$query = Product::select('*')
			->with('unit:id,name,minvalue')
			->orderBy('id', 'desc');

			if(!empty($request->unit_id))
			{
				$query->where('unit_id',  $request->unit_id);
			}
			if(!empty($request->current_quanitity))
			{
				$query->where('current_quanitity',  $request->current_quanitity);
			}
			if(!empty($request->name))
			{
				$query->where('name','LIKE', '%'.$request->name.'%');
			}
			if(!empty($request->description))
			{
				$query->where('description', 'LIKE', '%'.$request->description.'%');
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
			return prepareResult(true,'Products List Fatched Successfully' ,$query, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		DB::beginTransaction();
		try {
			$nameCheck = Product::where('name', $request->name)->first();
			$validation = Validator::make($request->all(), [
				'name' => $nameCheck ? 'required|declined:false' : 'required',
				'unit_id' => 'required|numeric',
				'current_quanitity' => 'required|gte:0.1',
			],
			[
				'name.declined' =>          'Name already exists',
			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}       
			$product = new Product;
			$product->name = $request->name;
			$product->description = $request->description;
			$product->unit_id = $request->unit_id;
			$product->price = $request->price;
			$product->image_path = $request->image_path;
			$product->current_quanitity = $request->current_quanitity;
			$product->alert_quanitity = $request->alert_quanitity;
			$product->status = $request->status ? $request->status : 1;
			$product->save();

			if($request->create_menu == true)
			{
				$menu = new Menu;
				$menu->category_id 		= $request->category_id;
				$menu->unit_id 			= $request->unit_id;
				$menu->quantity 		= $request->quantity;
				$menu->name 			= $request->name;
				$menu->description 		= $request->description;
				$menu->price 			= $request->menu_price;
				$menu->order_duration 	= $request->order_duration;
				$menu->priority_rank 	= $request->priority_rank;
				$menu->image_path 		= $request->image_path;
				$menu->create_menu      = true;
				$menu->save();

				$recipe = new Recipe;
				$recipe->menu_id =  $menu->id;
				$recipe->product_id = $product->id;
				$recipe->quantity = $request->quantity;
				$recipe->unit_id = $request->unit_id;
				$recipe->save();
			}
			DB::commit();
			return prepareResult(true,'Product Created successfully' , $product, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'name' => 'required',
			'unit_id' => 'required|numeric',
			'current_quanitity'           => 'required|gte:0.1',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		}   
		DB::beginTransaction();
		try { 
			$dublicate = Product::where('name',$request->name)->where('id','!=',$id)->count();
			if($dublicate > 0)
			{
				return prepareResult(false,'The name has already been taken.' ,[], 500);
			}
			$product = Product::find($id);
			if (empty($product)) {
				return prepareResult(false,'Record Not Found' ,[], 500);
			}
			$product->name = $request->name;
			$product->description = $request->description;
			$product->price = $request->price;
			$product->unit_id = $request->unit_id;
			$product->image_path = $request->image_path;
			$product->current_quanitity = $request->current_quanitity;
			$product->alert_quanitity = $request->alert_quanitity;
			$product->status = $request->status ? $request->status : 1;
			$product->save();

			DB::commit();
			return prepareResult(true,'Product Updated successfully' ,$product, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$product = Product::find($id);
			if($product)
			{
				return prepareResult(true,'Product Detail Fatched Successfully' ,$product, 200); 
			}
			return prepareResult(false,'Product Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$product = Product::find($id);
			if($product)
			{
				$delStockManage = StockManage::where('product_id',$id)->delete();
				$recipesCount = Recipe::where('product_id',$id)->count();
				if ($recipesCount > 0) {
					$menus = Recipe::where('product_id',$id)->get(['menu_id']);
					$menusData = [];
					foreach ($menus as $key => $value) {
						$menu = Menu::select('name')->find($value->menu_id);
						$menusData[] = ['menu_id' => $value->menu_id, 'menu_name' => $menu->name];
					}
					return prepareResult(false,'Product Used in below Menus Recipe! Cannot be deleted. First delete these menus' ,$menusData, 500);
				}
				$result = $product->delete();
				return prepareResult(true,'Product Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Product Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function excelImport(Request $request)
	{
		DB::beginTransaction();
		try {

			$validation = Validator::make($request->all(),
				[
					'file' => 'required',   
				]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			} 
			$file = $request->file;
			$extension = $file->getClientOriginalExtension();
			$allowedExt = ['xlsx'];
			if (!in_array($extension, $allowedExt)) {
				return prepareResult(false, 'Only XLSX file extension allowed.',[], 500);
			}
			$products = Excel::toArray(new ProductImport(), $file);
			$excalRow   = $products[0];
			$errorShow = false;
			$error = null;
			foreach($excalRow as $key => $stockProduct)
			{
				$nameCheck = Product::where('name', $stockProduct['item'])->first();

				if(empty($nameCheck))
				{
					$product = new Product;
					$product->name = $stockProduct['item'];
					$product->current_quanitity = $stockProduct['quantity'];
					$product->alert_quanitity = 0;
					$product->status = 1;
					$product->save();
				}
			}  
			DB::commit();
			return prepareResult(true,'Product successfully imported' , [], 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function productsExport(Request $request)
	{
		DB::beginTransaction();
		try {
		    $filters = $request->only(['unit_id', 'name', 'description', 'current_quanitity']);
		    $fileName = 'product-' . time() . '.xlsx'; // Specify the file extension
		    $destinationPath = 'uploads/';

		    Excel::store(new ProductExport($filters), $fileName, 'customer_uploads');
		    $fileArray[] = [
		        'file_name'         => env('CDN_DOC_URL') . $destinationPath . $fileName,
		        'file_extension'    => 'xlsx',
		    ];

		    return prepareResult(true, 'Download successful', $fileArray, 200);
		} catch (\Throwable $e) {
		    Log::error($e);
		    return prepareResult(false, 'Oops! Something went wrong.', $e->getMessage(), 500);
		}


	}
	public function productListForPull(Request $request)
	{
		try {
			if(auth()->user()->is_parent != NULL){
				$excludedProductNames = Product::withoutGlobalScope('cafe_id')
				    ->where('cafe_id', auth()->user()->cafe_id)
				    ->pluck('name');
				$query = Product::withoutGlobalScope('cafe_id')->select('*')
				->where('cafe_id',auth()->user()->is_parent)
				->whereNotIn('name', $excludedProductNames)
				->with('unit:id,name,minvalue')
				->orderBy('id', 'desc');
			} else{
				$subCafes = User::withoutGlobalScope('cafe_id')->where('is_parent', auth()->user()->cafe_id)->pluck('cafe_id')->toArray();

				$excludedProductNames = Product::withoutGlobalScope('cafe_id')
				    ->where('cafe_id', auth()->user()->cafe_id)
				    ->pluck('name');

				$query = Product::withoutGlobalScope('cafe_id')->select('*')
				->whereIn('cafe_id',$subCafes)
				->whereNotIn('name', $excludedProductNames)
				->with('unit:id,name,minvalue')
				->orderBy('id', 'desc');
				
			}
			
			if(!empty($request->cafe_ids) )
			{
				$cafe_ids = explode(',',$request->cafe_ids);
				$query->whereIn('cafe_id', $query);
			}
			if(!empty($request->unit_id))
			{
				$query->where('unit_id',  $request->unit_id);
			}
			if(!empty($request->current_quanitity))
			{
				$query->where('current_quanitity',  $request->current_quanitity);
			}
			if(!empty($request->name))
			{
				$query->where('name','LIKE', '%'.$request->name.'%');
			}
			if(!empty($request->description))
			{
				$query->where('description', 'LIKE', '%'.$request->description.'%');
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
			return prepareResult(true,'Products List Fatched Successfully' ,$query, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
