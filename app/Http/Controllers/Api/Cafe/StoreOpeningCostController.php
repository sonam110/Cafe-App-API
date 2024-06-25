<?php

namespace App\Http\Controllers\Api\Cafe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StoreOpeningItemCost;;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StoreOpeningCostController extends Controller
{
    public function storeItemsCost(Request $request)
    {
        try {
            $query = StoreOpeningItemCost::select('*')
            ->with('unit','recievedBy:id,name')
            ->orderBy('id', 'desc');

            if(!empty($request->id))
            {
                $query->where('id', $request->id);
            }
            // below query is to search inside join function 
            $name = $request->name;
            if(!empty($request->name))
            {
                    $query->Where('item_name', 'LIKE', "%{$name}%");
                   
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
                'item_name' => 'required',
                'quantity'   => 'required',
                'price' => 'required',
            ]);
            if ($validation->fails()) { 
                return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
            }

            
            
            $addStoreItem = new StoreOpeningItemCost;
            $addStoreItem->cafe_id = auth()->user()->cafe_id;
            $addStoreItem->item_name = $request->item_name;
            $addStoreItem->quantity = $request->quantity;
            $addStoreItem->unit_id = $request->unit_id;
            $addStoreItem->price = $request->price;
            $addStoreItem->shop_name = $request->shop_name;
            $addStoreItem->date = $request->date;
            $addStoreItem->bill_no = $request->bill_no;
            $addStoreItem->address = $request->address;
            $addStoreItem->purchase_by = $request->purchase_by;
            $addStoreItem->recieved_by = $request->recieved_by;
            $addStoreItem->save();
        
            DB::commit();
            return prepareResult(true,'created Ssuccessfully' , $addStoreItem, 200);
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
                'item_name' => 'required',
                'quantity'   => 'required',
                'price' => 'required',
            ]);
            if ($validation->fails()) { 
                return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
            }
            
            $editStoreItem = StoreOpeningItemCost::find($id);
            if(empty($editStoreItem))
            {
                return prepareResult(false,'Record Not Found' ,[], 500);
            }

            $editStoreItem = new StoreOpeningItemCost;
            $editStoreItem->cafe_id = auth()->user()->cafe_id;
            $editStoreItem->item_name = $request->item_name;
            $editStoreItem->quantity = $request->quantity;
            $editStoreItem->unit_id = $request->unit_id;
            $editStoreItem->price = $request->price;
            $editStoreItem->shop_name = $request->shop_name;
            $editStoreItem->date = $request->date;
            $editStoreItem->bill_no = $request->bill_no;
            $editStoreItem->address = $request->address;
            $editStoreItem->purchase_by = $request->purchase_by;
            $editStoreItem->recieved_by = $request->recieved_by;
            $editStoreItem->save();
        

            DB::commit();
            return prepareResult(true,'Updated Successfully' , $editStoreItem, 200);
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $StoreItem = StoreOpeningItemCost::with('unit','recievedBy:id,name')->find($id);
            if($StoreItem)
            {
                return prepareResult(true,'Data Fatched Successfully' ,$StoreItem, 200); 
            }
            return prepareResult(false,'Data Not Found' ,[], 500);
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $storeItem = StoreOpeningItemCost::find($id);
            if($storeItem)
            {
                $storeItem = StoreOpeningItemCost::where('id',$id)->delete();
                DB::commit();
                return prepareResult(true,'Data Deleted Successfully' ,[], 200); 
            }
            return prepareResult(false,'Data Not Found' ,[], 500);
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }
}
