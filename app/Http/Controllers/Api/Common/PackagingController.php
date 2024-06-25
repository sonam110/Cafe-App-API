<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Packaging;
use App\Models\PackagingContents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PackagingController extends Controller
{
    //
	public function packagings(Request $request)
	{
		try {
			$query = Packaging::select('*')
			->with('packagingMaterial:packaging_id,quantity,product_info_stock_id','categoryName:id,name','packagingMaterial.productName:id,name')
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
			return prepareResult(true,'Records Fatched Successfully' ,$query, 200);

		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function store(Request $request)
	{
		$validation = Validator::make($request->all(), [
			'category_id' => 'required|numeric',
			"packaging_content.*.product_info_stock_id" => "required|numeric", 
			"packaging_content.*.quantity" => "required|numeric", 
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try { 
			$packaging = new Packaging;
			$packaging->category_id = $request->category_id;
			$packaging->save();

			foreach ($request->packaging_content as $key => $content) {
				$addcontent = new PackagingContents;
				$addcontent->packaging_id =  $packaging->id;
				$addcontent->product_info_stock_id = $content['product_info_stock_id'];
				$addcontent->quantity = $content['quantity'];
				$addcontent->save();
			}

			$packaging['packaging_contents'] = $packaging->packagingMaterial;
			DB::commit();
			return prepareResult(true,'Your data has been saved successfully' , $packaging, 200);

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
				'category_id'  => 'required|numeric',
				"packaging_content.*.product_info_stock_id" => "required|numeric", 
				"packaging_content.*.quantity" => "required|numeric", 
			]);
			if ($validation->fails()) {
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			} 

			$packaging = Packaging::find($id);
			if (empty($packaging)) {
				return prepareResult(false,'Record Not Found' ,[], 500);
			}
			$packaging->category_id = $request->category_id;
			$packaging->save();

			$deletOld = PackagingContents::where('packaging_id', $id)->delete();
			foreach ($request->packaging_content as $key => $content) {
				$addcontent = new PackagingContents;
				$addcontent->packaging_id =  $packaging->id;
				$addcontent->product_info_stock_id = $content['product_info_stock_id'];
				$addcontent->quantity = $content['quantity'];
				$addcontent->save();
			}
			DB::commit();
			return prepareResult(true,'Your data has been Updated successfully' ,$packaging, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$packaging = Packaging::find($id);
			if($packaging)
			{
				return prepareResult(true,'Record Fatched Successfully' ,$packaging, 200); 
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
			$packaging = Packaging::find($id);
			if($packaging)
			{
				$result=$packaging->delete();
				return prepareResult(true,'Record Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Record Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
