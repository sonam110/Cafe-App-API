<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\PaymentQrCode;
use App\Models\User;
use DB;  
use Log;
use Validator;

class PaymentQrCodeController extends Controller
{
	public function paymentQrCodes(Request $request)
	{
		try {
			$query = PaymentQrCode::orderBy('id','DESC');
			if(!empty($request->priority_rank)){
				$query = $query->orderBy('priority_rank', 'asc');
			}else{ 
				$query = $query->orderBy('id', 'desc');
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
			return prepareResult(true,'PaymentQrCode Records Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		$validation = Validator::make($request->all(), [
			"qr_code_image_path"  => "required"
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {    
			$paymentQrCode = new PaymentQrCode;
			$paymentQrCode->cafe_id = auth()->id();
			$paymentQrCode->user_id = auth()->id();
			$paymentQrCode->qr_code_image_path = $request->qr_code_image_path;
			$paymentQrCode->save();

			DB::commit();
			return prepareResult(true,'PaymentQrCode Created successfully' , $paymentQrCode, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			"qr_code_image_path"  => "required"
		]);

		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try { 
			$paymentQrCode = PaymentQrCode::find($id);
			if(empty($paymentQrCode))
			{
				return prepareResult(false,'PaymentQrCode Not Found' ,[], 500);
			}
			$paymentQrCode->cafe_id = auth()->id();
			$paymentQrCode->user_id = auth()->id();
			$paymentQrCode->qr_code_image_path = $request->qr_code_image_path;
			$paymentQrCode->save();
			DB::commit();
			return prepareResult(true,'PaymentQrCode Updated successfully' ,$paymentQrCode, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$paymentQrCode = PaymentQrCode::find($id);
			if($paymentQrCode)
			{
				return prepareResult(true,'PaymentQrCode Fatched Successfully' ,$paymentQrCode, 200); 
			}
			return prepareResult(false,'PaymentQrCode Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$paymentQrCode = PaymentQrCode::find($id);
			if($paymentQrCode)
			{
				$result = $paymentQrCode->delete();
				return prepareResult(true,'PaymentQrCode Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'PaymentQrCode Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
