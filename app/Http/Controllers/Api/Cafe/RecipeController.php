<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeContains;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductInfo;
use App\Models\ProductMenu;
use App\Models\Unit;

class RecipeController extends Controller
{

	public function recipes(Request $request)
	{
		try {
			$query = Recipe::select('*')
			->with('recipeMethods:recipe_id,name,quantity,unit_id,product_info_stock_id,unit_name,unit_minValue','productMenu:name,cafe_id,id')
			->orderBy('id', 'desc');

			if(!empty($request->id))
			{
				$query->where('id', $request->id);
			}
            // below query is to search inside join function 
			$name = $request->name;
			if(!empty($request->name))
			{
				$query->whereHas('productMenu',function ($query) use ($name) {
					$query->Where('name', 'LIKE', "%{$name}%");
				});    
			}     
			if(!empty($request->recipe_status))
			{
				$query->where('recipe_status', $request->recipe_status);
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
		DB::beginTransaction();
		try {
			$validation = Validator::make($request->all(), [
				'recipe_status'                    => 'required|numeric',
				'product_menu_id'                => 'required|unique:App\Models\Recipe,product_menu_id',
				"recipe_methods.*.quantity" => "required|numeric", 

			]);

			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}
	     // to check if product is to be made without recipe
			$checkRecipe =  ProductMenu::find($request->product_menu_id);
			if($checkRecipe->without_recipe == 1)
			{
				return prepareResult(false,'Recipe for this product already exits' ,[], 500);
			} 

			if($request->recipe_methods){
				foreach ($request->recipe_methods as $key => $recipe1) {
					$oldValue1 = ProductInfo::where('product_infos.id', $recipe1['product_info_stock_id'])->get('current_quanitity')->first();

					$validation = Validator::make($request->all(),[      
						"recipe_methods.*.quantity"  => ($oldValue1->current_quanitity < unitConversion($recipe1['unit_id'], $recipe1['quantity']) ) ? 'required|declined:false' : 'required|gte:1', 

						"recipe_methods.*.unit_id"  => unitSimilarTypeCheck($recipe1['unit_id'], $recipe1['product_info_stock_id']),
						],
						[
							'recipe_methods.*.quantity.declined' => 'Less value left in stock',
							'recipe_methods.*.unit_id.declined' => 'Invalid Unit Type',
						]
					);
					if ($validation->fails()) {
						return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
					}
				}
			}
			$info = new Recipe;
			$info->name = $request->name;
			$info->product_menu_id = $request->product_menu_id;
			$info->description = $request->description;
			$info->recipe_status = $request->recipe_status;
			$info->save();

			foreach ($request->recipe_methods as $key => $recipe) {
				$product_info_name = ProductInfo::where('product_infos.id', $recipe['product_info_stock_id'])->get('name')->first();

				$unitInfo = Unit::find( $recipe['unit_id']);

				$addRecipe = new RecipeContains;
				$addRecipe->recipe_id =  $info->id;
	        //    $addRecipe->name = $recipe['name'];
				$addRecipe->name = $product_info_name->name; 
				$addRecipe->product_info_stock_id = $recipe['product_info_stock_id'];
				$addRecipe->quantity = $recipe['quantity'];
				$addRecipe->unit_id = $recipe['unit_id'];
				$addRecipe->unit_name = $unitInfo->name;
				$addRecipe->unit_minValue = $unitInfo->minvalue;
				$addRecipe->save();

			}

			DB::commit();
			$info['recipe_contains'] = $info->recipeMethods;
			return prepareResult(true,'Your data has been saved successfully' , $info, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function update(Request $request, $id)
	{
		DB::beginTransaction();
		try {
			$email_product_menu_id = Recipe::where('id',  $id)->get('product_menu_id')->first();


			$validation = Validator::make($request->all(), [

				'product_menu_id'             => $email_product_menu_id->product_menu_id == $request->product_menu_id ? 'required' : 'required|unique:App\Models\Recipe,product_menu_id',
				'recipe_status'                    => 'required|numeric',
				"recipe_methods.*.unit_id"  => "required|numeric", 
				"recipe_methods.*.quantity" => "required|numeric", 

			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}   
			if($request->recipe_methods){

				foreach ($request->recipe_methods as $key => $recipe1) {
					$oldValue1 = ProductInfo::where('product_infos.id', $recipe1['product_info_stock_id'])->get('current_quanitity')->first();

					$validation = Validator::make($request->all(),[      
						"recipe_methods.*.quantity"  => ($oldValue1->current_quanitity < unitConversion($recipe1['unit_id'], $recipe1['quantity']) ) ? 'required|declined:false' : 'required|gte:1', 
						"recipe_methods.*.unit_id"  => unitSimilarTypeCheck($recipe1['unit_id'], $recipe1['product_info_stock_id']), 

					],
					[
						'recipe_methods.*.quantity.declined' => 'Less value left in stock',
						'recipe_methods.*.unit_id.declined' => 'Invalid Unit Type',
					]
				);

				}

				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				} 
			}
			$info = Recipe::find($id);
			if (empty($recipe)) {
				return prepareResult(false,'Record Not Found' ,[], 500);
			}
			$info->name = $request->name;
			$info->product_menu_id = $request->product_menu_id;
			$info->description = $request->description;
			$info->recipe_status = $request->recipe_status;
			$info->save();


			$deletOld = RecipeContains::where('recipe_id', $id)->delete();
			foreach ($request->recipe_methods as $key => $recipe) {


				$product_info_name = ProductInfo::where('product_infos.id', $recipe['product_info_stock_id'])->get('name')->first();

				$unitInfo = Unit::find( $recipe['unit_id']);

				$addRecipe = new RecipeContains;
				$addRecipe->recipe_id =  $info->id;
	        //    $addRecipe->name = $recipe['name'];
				$addRecipe->name = $product_info_name->name; 
				$addRecipe->product_info_stock_id = $recipe['product_info_stock_id'];
				$addRecipe->quantity = $recipe['quantity'];
				$addRecipe->unit_id = $recipe['unit_id'];
				$addRecipe->unit_name = $unitInfo->name;
				$addRecipe->unit_minValue = $unitInfo->minvalue;
				$addRecipe->save();



			}

			DB::commit();
			$info['recipe_contains'] = $info->recipeMethods;
			return prepareResult(true,'Your data has been Updated successfully' ,$info, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$recipe =Recipe::with('recipeMethods')->find($id);
			if($recipe)
			{
				return prepareResult(true,'Record Fatched Successfully' ,$recipe, 200); 
			}
			return prepareResult(false,'Record Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$recipe = Recipe::find($id);
			if($recipe)
			{
				$result = $recipe->delete();
				return prepareResult(true,'Record Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Record Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

}
