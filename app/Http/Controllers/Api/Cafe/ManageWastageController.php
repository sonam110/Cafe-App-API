<?php

namespace App\Http\Controllers\Api\Cafe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ManageWastage;;
use App\Models\RecipeContains;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductInfo;
use App\Models\ProductMenu;
use App\Models\Menu;
use App\Models\Unit;
class ManageWastageController extends Controller
{
    public function manageWastages(Request $request)
    {
        try {
            $query = ManageWastage::select('*')
            ->with('product:name,cafe_id,id','menu','unit')
            ->orderBy('id', 'desc');

            if(!empty($request->id))
            {
                $query->where('id', $request->id);
            }
            // below query is to search inside join function 
            $name = $request->name;
            if(!empty($request->name))
            {
                $query->whereHas('product',function ($query) use ($name) {
                    $query->Where('name', 'LIKE', "%{$name}%");
                });    
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
                'quantity' => 'required',
                'unit_id'   => 'required',
                'date' => 'required',
            ]);
            if ($validation->fails()) { 
                return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
            }

            
            if(!empty($request->product_id)){
                $validation = Validator::make($request->all(), [
                    'product_id' => 'exists:products,id',
                ]);
                if ($validation->fails()) { 
                    return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
                }

            }
            if(!empty($request->menu_id)){
                $validation = Validator::make($request->all(), [
                    'menu_id' => 'exists:menus,id',
                ]);
                if ($validation->fails()) { 
                    return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
                }

            }
            $addWastage = new ManageWastage;
            $addWastage->cafe_id = auth()->user()->cafe_id;
            $addWastage->menu_id = $request->menu_id;
            $addWastage->product_id = $request->product_id;
            $addWastage->unit_id = $request->unit_id;
            $addWastage->quantity = $request->quantity;
            $addWastage->date = $request->date;
            $addWastage->image = $request->image;
            $addWastage->reason = $request->reason;
            $addWastage->save();
            /*if(!empty($request->menu_id)){
                $menu = Menu::with('recipes')->find( $request->menu_id);
                if(empty($menu))
                {
                    return prepareResult(false,'Menu Not Found' ,[], 500);
                }

                $recipes = $menu->recipes;
                foreach ($recipes as $key => $recipe) {
                    $stockManage = stockManageAdd($request,'out',$recipe->product_id,$recipe->unit_id,($recipe->quantity)*$request->quantity,'Waste',null,null,null,$recipe->menu_id);
                }

            }
            if(!empty($request->product_id)){
                 $stockManage = stockManageAdd($request,'out',$request->product_id,$request->unit_id,$request->quantity,'Waste',null,null,null,$request->menu_id);


            }*/
            DB::commit();
            return prepareResult(true,'Wastage created Ssuccessfully' , $addWastage, 200);
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
                'quantity' => 'required',
                'unit_id'   => 'required',
                'date' => 'required',
            ]);
            if ($validation->fails()) { 
                return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
            }
            if(!empty($request->product_id)){
                $validation = Validator::make($request->all(), [
                    'product_id' => 'exists:products,id',
                ]);
                if ($validation->fails()) { 
                    return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
                }

            }
            if(!empty($request->menu_id)){
                $validation = Validator::make($request->all(), [
                    'menu_id' => 'exists:menus,id',
                ]);
                if ($validation->fails()) { 
                    return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
                }

            }
            $editWastage = ManageWastage::find($id);
            if(empty($editWastage))
            {
                return prepareResult(false,'Record Not Found' ,[], 500);
            }

            $editWastage->cafe_id = auth()->user()->cafe_id;
            $editWastage->menu_id = $request->menu_id;
            $editWastage->product_id = $request->product_id;
            $editWastage->unit_id = $request->unit_id;
            $editWastage->quantity = $request->quantity;
            $editWastage->date = $request->date;
            $editWastage->image = $request->image;
            $editWastage->reason = $request->reason;
            $editWastage->save();

            /*if(!empty($request->menu_id)){
                $menu = Menu::with('recipes')->find( $request->menu_id);
                if(empty($menu))
                {
                    return prepareResult(false,'Menu Not Found' ,[], 500);
                }

                $recipes = $menu->recipes;
                foreach ($recipes as $key => $recipe) {
                    $stockManage = stockManageAdd($request,'out',$recipe->product_id,$recipe->unit_id,($recipe->quantity)*$request->quantity,'Waste',null,null,$id,$recipe->menu_id);
                }

            }
            if(!empty($request->product_id)){
                 $stockManage = stockManageAdd($request,'out',$request->product_id,$request->unit_id,$request->quantity,'Waste',null,null,null,$request->menu_id);


            }*/

            DB::commit();
            return prepareResult(true,'Wastage Updated Successfully' , $editWastage, 200);
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $manageWastage = ManageWastage::with('product:id,name','unit:id,name','menu')->find($id);
            if($manageWastage)
            {
                return prepareResult(true,'Wastage Data Fatched Successfully' ,$manageWastage, 200); 
            }
            return prepareResult(false,'Wastage Data Not Found' ,[], 500);
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $manageWastage = ManageWastage::find($id);
            if($manageWastage)
            {
                $manageWastage = ManageWastage::where('id',$id)->delete();
                DB::commit();
                return prepareResult(true,'Wastage Data Deleted Successfully' ,[], 200); 
            }
            return prepareResult(false,'Stock Data Not Found' ,[], 500);
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }
}
