<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Recipe;
use DB;  
use Log;
use Validator;

class MenuController extends Controller
{
	public function menus(Request $request)
	{
		try {
			$query = Menu::orderBy('priority_rank', 'asc')
			->with('category:id,name,tax','unit:id,name','recipes.product:id,name','recipes.unit:id,name')
			->withCount('recipes');
			// if(!empty($request->priority_rank)){
			// 	$query = $query->orderBy('priority_rank', 'asc');
			// }else{ 
			// 	$query = $query->orderBy('id', 'desc');
			// }
			if(!empty($request->priority_rank)){
				$query = $query->where('priority_rank', $request->priority_rank);
			}

			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->name))
			{
				$query->where('name', 'LIKE', '%'.$request->name.'%');
			}
			if(!empty($request->category_id))
			{
				$query->where('category_id', $request->category_id);
			}
			if(!empty($request->price))
			{
				$query->where('price', $request->price);
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
			return prepareResult(true,'Menu Records Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		$validation = Validator::make($request->all(), [
			"name"  => "required", 
			"price"  => "required|numeric", 
			"order_duration"  => "required|numeric", 
			"category_id"  => "required|numeric",
			"recipes" => 'required|array',
			// "priority_rank" => 'unique:menus,priority_rank' 
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {   
			// $dublicate = Menu::where('priority_rank',$request->priority_rank)->count();
			// if($dublicate > 0)
			// {
			// 	return prepareResult(false,'The priority_rank has already been taken.' ,[], 500);
			// }  
			$menu = new Menu;
			$menu->category_id =  $request->category_id;
			$menu->unit_id =  $request->unit_id;
			$menu->quantity = $request->quantity;
			$menu->name =  $request->name;
			$menu->description =  $request->description;
			$menu->price =  $request->price;
			$menu->order_duration =  $request->order_duration;
			$menu->priority_rank =  $request->priority_rank;
			$menu->image_path =  $request->image_path;
			$menu->create_menu      = 2;
			$menu->save();
			foreach ($request->recipes as $key => $value) 
			{
				$product = Product::find(@$value['product_id']);
				if (empty($product)) {
					return prepareResult(false,'Product Not Found' ,[], 500);
				}
				$validation = Validator::make($request->all(),[
					"recipes.*.unit_id"  => unitSimilarTypeCheck(@$value['unit_id'], $value['product_id']),
					],
					[
						'recipes.*.unit_id.declined' => 'Invalid Unit Type',
					]
				);
				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				}
				$recipe = new Recipe;
				$recipe->menu_id =  $menu->id;
				$recipe->product_id = $value['product_id'];
				$recipe->quantity = $value['quantity'];
				$recipe->unit_id = $value['unit_id'];
				$recipe->save();
			}

			DB::commit();
			return prepareResult(true,'Menu Created successfully' , $menu, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			"name"  => "required",  
			"price"  => "required|numeric", 
			"order_duration"  => "required|numeric", 
			"category_id"  => "required|numeric",
			"recipes" =>"required|array",
			// "priority_rank" => 'unique:menus,priority_rank'.$id
		]);

		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try { 
			$menu = Menu::find($id);
			if(empty($menu))
			{
				return prepareResult(false,'Menu Not Found' ,[], 500);
			}
			// $dublicate = Menu::where('priority_rank',$request->priority_rank)->where('id','!=',$id)->count();
			// if($dublicate > 0)
			// {
			// 	return prepareResult(false,'The priority_rank has already been taken.' ,[], 500);
			// }
			$menu->category_id =  $request->category_id;
			$menu->unit_id =  $request->unit_id;
			$menu->quantity = $request->quantity;
			$menu->name =  $request->name;
			$menu->description =  $request->description;
			$menu->price =  $request->price;
			$menu->order_duration =  $request->order_duration;
			$menu->priority_rank =  $request->priority_rank;
			$menu->image_path =  $request->image_path;
			$menu->create_menu      = 2;
			$menu->save();
			Recipe::where('menu_id',$id)->delete();
			foreach ($request->recipes as $key => $value) 
			{
				$product = Product::find($value['product_id']);
				if (empty($product)) {
					return prepareResult(false,'Product Not Found' ,[], 500);
				}
				$validation = Validator::make($request->all(),[
					"recipes.*.unit_id"  => unitSimilarTypeCheck($value['unit_id'], $value['product_id']),
					],
					[
						'recipes.*.unit_id.declined' => 'Invalid Unit Type',
					]
				);
				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				}
				$recipe = new Recipe;
				$recipe->menu_id =  $menu->id;
				$recipe->product_id = $value['product_id'];
				$recipe->quantity = $value['quantity'];
				$recipe->unit_id = $value['unit_id'];
				$recipe->save();
			}
			DB::commit();
			return prepareResult(true,'Menu Updated successfully' ,$menu, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$menu = Menu::with('category:id,name,tax','unit:id,name','recipes')->find($id);
			if($menu)
			{
				return prepareResult(true,'Menu Fatched Successfully' ,$menu, 200); 
			}
			return prepareResult(false,'Menu Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$menu = Menu::find($id);
			if($menu)
			{
				Recipe::where('menu_id',$id)->delete();
				$result = $menu->delete();
				return prepareResult(true,'Menu Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Menu Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function categoryWiseMenus(Request $request)
	{
		try {
			$query = Category::with('menus')
			->orderBy('id', 'desc');
			if(!empty($request->id))
			{
				$query->where('id', $request->id);
			}
			if(!empty($request->name))
			{
				$query->where('name', 'LIKE', '%'.$request->name.'%');
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
			return prepareResult(true,'Cetegory Wise Menu List Fatched Successfully' ,$query, 200);
		} 
		catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Error while fatching Records' ,$e->getMessage(), 500);
		}
	}
	public function menuListForPull(Request $request)
	{
		try {
			if(auth()->user()->is_parent != NULL){
				$excludedMenuNames = Menu::withoutGlobalScope('cafe_id')
				    ->where('cafe_id', auth()->user()->cafe_id)
				    ->pluck('name');

				$query = Menu::withoutGlobalScope('cafe_id')->orderBy('priority_rank', 'asc')
				->with('category:id,name,tax','unit:id,name','recipes.product:id,name','recipes.unit:id,name')
				->where('cafe_id',auth()->user()->is_parent)
				->whereNotIn('name', $excludedMenuNames)
				->withCount('recipes');

			} else{
				$subCafes = User::withoutGlobalScope('cafe_id')->where('is_parent', auth()->user()->cafe_id)->pluck('cafe_id')->toArray();
				$excludedMenuNames = Menu::withoutGlobalScope('cafe_id')
				    ->where('cafe_id', auth()->user()->cafe_id)
				    ->pluck('name');
				$query = Menu::withoutGlobalScope('cafe_id')->orderBy('priority_rank', 'asc')
				->with('category:id,name,tax','unit:id,name','recipes.product:id,name','recipes.unit:id,name')
				->whereIn('cafe_id',$subCafes)
				->whereNotIn('name', $excludedMenuNames)
				->withCount('recipes');

			}
			// if(!empty($request->priority_rank)){
			// 	$query = $query->orderBy('priority_rank', 'asc');
			// }else{ 
			// 	$query = $query->orderBy('id', 'desc');
			// }
			if(!empty($request->priority_rank)){
				$query = $query->where('priority_rank', $request->priority_rank);
			}
			if(!empty($request->cafe_ids) )
			{
				$cafe_ids = explode(',',$request->cafe_ids);
				$query->whereIn('cafe_id', $query);
			}
			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->name))
			{
				$query->where('name', 'LIKE', '%'.$request->name.'%');
			}
			if(!empty($request->category_id))
			{
				$query->where('category_id', $request->category_id);
			}
			if(!empty($request->price))
			{
				$query->where('price', $request->price);
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
			return prepareResult(true,'Menu Records Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function savePullData(Request $request)
	{
		$validation = Validator::make($request->all(), [
			"type"  => "required", 
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {  
			if(!empty($request->category_ids)){
				$category_ids = explode(',',$request->category_ids);
				
				if(is_array($category_ids) && count($category_ids) > 0 ){
					foreach ($category_ids as $key => $value) {
						$category_data = Category::withoutGlobalScope('cafe_id')->where('id',$value)->first();

						$checkExist = Category::where('name',$category_data->name)->first();

						if(empty($checkExist)) {
							$category = new Category;
							$category->image = $category_data->image;
							$category->name = $category_data->name;
							$category->tax = $category_data->tax;
							$category->save();
						}
						
						
					}
				}
				
			} 
			if(!empty($request->menu_ids)){
				$menu_ids = explode(',',$request->menu_ids);
				if(is_array($menu_ids) && count($menu_ids) > 0 ){
					foreach ($menu_ids as $key => $man) {
						$menu_data = Menu::withoutGlobalScope('cafe_id')->where('id',$man)->with('recipes')->first();

						$category_data = Category::withoutGlobalScope('cafe_id')->where('id',$menu_data->category_id)->first();

						$checkCatExist = Category::where('name',$category_data->name)->first();

						if(empty($checkCatExist)) {
							$category = new Category;
							$category->image = $category_data->image;
							$category->name = $category_data->name;
							$category->tax = $category_data->tax;
							$category->save();
							$category_id = $category->id;

						} else{
							$category_id = $checkCatExist->id;
						}
						$checkMenuExit = Menu::where('name',$menu_data->name)->with('recipes')->first();
						if(empty($checkMenuExit)){
							$menu = new Menu;
							$menu->category_id =  $category_id;
							$menu->unit_id =  $menu_data->unit_id;
							$menu->quantity = $menu_data->quantity;
							$menu->name =  $menu_data->name;
							$menu->description =  $menu_data->description;
							$menu->price =  $menu_data->price;
							$menu->order_duration =  $menu_data->order_duration;
							$menu->priority_rank =  $menu_data->priority_rank;
							$menu->image_path =  $menu_data->image_path;
							$menu->create_menu      =  $menu_data->create_menu;
							$menu->save();
							foreach ($menu_data->recipes as $key => $value) 
							{
								$product_data = Product::withoutGlobalScope('cafe_id')->where('id',$value->product_id)->first();
								$checkproExist = Product::where('name',$product_data->name)->first();
								if(empty($checkproExist)) {
									$product = new Product;
									$product->name = $product_data->name;
									$product->description = $product_data->description;
									$product->unit_id = $product_data->unit_id;
									$product->price = $product_data->price;
									$product->image_path = $product_data->image_path;
									$product->current_quanitity = $product_data->current_quanitity;
									$product->alert_quanitity = $product_data->alert_quanitity;
									$product->status = $product_data->status ;
									$product->save();
									$product_id = $product->id;

								} else{
									$product_id = $checkproExist->id;
								}

								
								$recipe = new Recipe;
								$recipe->menu_id =  $menu->id;
								$recipe->product_id = $product_id;
								$recipe->quantity = $value->quantity;
								$recipe->unit_id = $value->unit_id;
								$recipe->save();
							}

						}

						
					}
				}

			}
			if(!empty($request->product_ids)){
				$product_ids = explode(',',$request->product_ids);
				if(is_array($product_ids) && count($product_ids) > 0 ){
					foreach ($product_ids as $key => $prod) {
						$product_data = Product::withoutGlobalScope('cafe_id')->where('id',$prod)->first();
						

						$checkproExist = Product::where('name',$product_data->name)->first();
						if(empty($checkproExist)) {
							$product = new Product;
							$product->name = $product_data->name;
							$product->description = $product_data->description;
							$product->unit_id = $product_data->unit_id;
							$product->price = $product_data->price;
							$product->image_path = $product_data->image_path;
							$product->current_quanitity = $product_data->current_quanitity;
							$product->alert_quanitity = $product_data->alert_quanitity;
							$product->status = $product_data->status ;
							$product->save();
						}
					}
				}
			}


			DB::commit();
			return prepareResult(true,'Data Save successfully' , [], 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

}
