<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        app()['cache']->forget('spatie.permission.cache');
        // create roles and assign existing permissions

         // 1 common for admin
        Permission::create(['name' => 'adminEmployee-read', 'guard_name' => 'api','group_name'=>'adminEmployee','se_name'=>'adminEmployee-read','belongs_to'=>'1']);
    	Permission::create(['name' => 'adminEmployee-add', 'guard_name' => 'api','group_name'=>'adminEmployee','se_name'=>'adminEmployee-create','belongs_to'=>'1']);
    	Permission::create(['name' => 'adminEmployee-edit', 'guard_name' => 'api','group_name'=>'adminEmployee','se_name'=>'adminEmployee-edit','belongs_to'=>'1']);
    	Permission::create(['name' => 'adminEmployee-delete', 'guard_name' => 'api','group_name'=>'adminEmployee','se_name'=>'adminEmployee-delete','belongs_to'=>'1']);
    	Permission::create(['name' => 'adminEmployee-browse', 'guard_name' => 'api','group_name'=>'adminEmployee','se_name'=>'adminEmployee-browse','belongs_to'=>'1']);

        // 4 for admin,admin employee
    	Permission::create(['name' => 'cafe-read', 'guard_name' => 'api','group_name'=>'cafe','se_name'=>'cafe-read','belongs_to'=>'4']);
    	Permission::create(['name' => 'cafe-add', 'guard_name' => 'api','group_name'=>'cafe','se_name'=>'cafe-create','belongs_to'=>'4']);
    	Permission::create(['name' => 'cafe-edit', 'guard_name' => 'api','group_name'=>'cafe','se_name'=>'cafe-edit','belongs_to'=>'4']);
    	Permission::create(['name' => 'cafe-delete', 'guard_name' => 'api','group_name'=>'cafe','se_name'=>'cafe-delete','belongs_to'=>'4']);
    	Permission::create(['name' => 'cafe-browse', 'guard_name' => 'api','group_name'=>'cafe','se_name'=>'cafe-browse','belongs_to'=>'4']);

        //4 is for common of adimn and cafe ,adminEmployee
         // Permission::create(['name' => 'dashboard-read', 'guard_name' => 'api','group_name'=>'dashboard','se_name'=>'dashboard-read','belongs_to'=>'3']);
         // Permission::create(['name' => 'dashboard-add', 'guard_name' => 'api','group_name'=>'dashboard','se_name'=>'dashboard-create','belongs_to'=>'3']);
         // Permission::create(['name' => 'dashboard-edit', 'guard_name' => 'api','group_name'=>'dashboard','se_name'=>'dashboard-edit','belongs_to'=>'3']);
         // Permission::create(['name' => 'dashboard-delete', 'guard_name' => 'api','group_name'=>'dashboard','se_name'=>'dashboard-delete','belongs_to'=>'3']);
         Permission::create(['name' => 'dashboard-browse', 'guard_name' => 'api','group_name'=>'dashboard','se_name'=>'dashboard-browse','belongs_to'=>'5']);// 5 for admin,admin employee,cafe

         // 2 is for cafe
         Permission::create(['name' => 'product-read', 'guard_name' => 'api','group_name'=>'product','se_name'=>'product-read','belongs_to'=>'2']);
         Permission::create(['name' => 'product-add', 'guard_name' => 'api','group_name'=>'product','se_name'=>'product-create','belongs_to'=>'2']);
         Permission::create(['name' => 'product-edit', 'guard_name' => 'api','group_name'=>'product','se_name'=>'product-edit','belongs_to'=>'2']);
         Permission::create(['name' => 'product-delete', 'guard_name' => 'api','group_name'=>'product','se_name'=>'product-delete','belongs_to'=>'2']);
         Permission::create(['name' => 'product-browse', 'guard_name' => 'api','group_name'=>'product','se_name'=>'product-browse','belongs_to'=>'2']);

         Permission::create(['name' => 'employee-read', 'guard_name' => 'api','group_name'=>'employee','se_name'=>'employee-read','belongs_to'=>'2']);
         Permission::create(['name' => 'employee-add', 'guard_name' => 'api','group_name'=>'employee','se_name'=>'employee-create','belongs_to'=>'2']);
         Permission::create(['name' => 'employee-edit', 'guard_name' => 'api','group_name'=>'employee','se_name'=>'employee-edit','belongs_to'=>'2']);
         Permission::create(['name' => 'employee-delete', 'guard_name' => 'api','group_name'=>'employee','se_name'=>'employee-delete','belongs_to'=>'2']);
         Permission::create(['name' => 'employee-browse', 'guard_name' => 'api','group_name'=>'employee','se_name'=>'employee-browse','belongs_to'=>'2']);

         Permission::create(['name' => 'recipe-read', 'guard_name' => 'api','group_name'=>'recipe','se_name'=>'recipe-read','belongs_to'=>'2']);
         Permission::create(['name' => 'recipe-add', 'guard_name' => 'api','group_name'=>'recipe','se_name'=>'recipe-create','belongs_to'=>'2']);
         Permission::create(['name' => 'recipe-edit', 'guard_name' => 'api','group_name'=>'recipe','se_name'=>'recipe-edit','belongs_to'=>'2']);
         Permission::create(['name' => 'recipe-delete', 'guard_name' => 'api','group_name'=>'recipe','se_name'=>'recipe-delete','belongs_to'=>'2']);
         Permission::create(['name' => 'recipe-browse', 'guard_name' => 'api','group_name'=>'recipe','se_name'=>'recipe-browse','belongs_to'=>'2']);

         Permission::create(['name' => 'expense-read', 'guard_name' => 'api','group_name'=>'expense','se_name'=>'expense-read','belongs_to'=>'2']);
         Permission::create(['name' => 'expense-add', 'guard_name' => 'api','group_name'=>'expense','se_name'=>'expense-create','belongs_to'=>'2']);
         Permission::create(['name' => 'expense-edit', 'guard_name' => 'api','group_name'=>'expense','se_name'=>'expense-edit','belongs_to'=>'2']);
         Permission::create(['name' => 'expense-delete', 'guard_name' => 'api','group_name'=>'expense','se_name'=>'expense-delete','belongs_to'=>'2']);
         Permission::create(['name' => 'expense-browse', 'guard_name' => 'api','group_name'=>'expense','se_name'=>'expense-browse','belongs_to'=>'2']);

         Permission::create(['name' => 'attendence-read', 'guard_name' => 'api','group_name'=>'attendence','se_name'=>'attendence-read','belongs_to'=>'3']);
         Permission::create(['name' => 'attendence-add', 'guard_name' => 'api','group_name'=>'attendence','se_name'=>'attendence-create','belongs_to'=>'3']);
         Permission::create(['name' => 'attendence-edit', 'guard_name' => 'api','group_name'=>'attendence','se_name'=>'attendence-edit','belongs_to'=>'3']);
         Permission::create(['name' => 'attendence-delete', 'guard_name' => 'api','group_name'=>'attendence','se_name'=>'attendence-delete','belongs_to'=>'3']);
         Permission::create(['name' => 'attendence-browse', 'guard_name' => 'api','group_name'=>'attendence','se_name'=>'attendence-browse','belongs_to'=>'3']);

         Permission::create(['name' => 'customerAccount-read', 'guard_name' => 'api','group_name'=>'customerAccount','se_name'=>'customerAccount-read','belongs_to'=>'2']);
         Permission::create(['name' => 'customerAccount-add', 'guard_name' => 'api','group_name'=>'customerAccount','se_name'=>'customerAccount-create','belongs_to'=>'2']);
         Permission::create(['name' => 'customerAccount-edit', 'guard_name' => 'api','group_name'=>'customerAccount','se_name'=>'customerAccount-edit','belongs_to'=>'2']);
         Permission::create(['name' => 'customerAccount-delete', 'guard_name' => 'api','group_name'=>'customerAccount','se_name'=>'customerAccount-delete','belongs_to'=>'2']);
         Permission::create(['name' => 'customerAccount-browse', 'guard_name' => 'api','group_name'=>'customerAccount','se_name'=>'customerAccount-browse','belongs_to'=>'2']);

         Permission::create(['name' => 'salary-read', 'guard_name' => 'api','group_name'=>'salary','se_name'=>'employeeSalary-read','belongs_to'=>'2']);
         Permission::create(['name' => 'salary-add', 'guard_name' => 'api','group_name'=>'salary','se_name'=>'employeeSalary-create','belongs_to'=>'2']);
         Permission::create(['name' => 'salary-edit', 'guard_name' => 'api','group_name'=>'salary','se_name'=>'employeeSalary-edit','belongs_to'=>'2']);
         Permission::create(['name' => 'salary-delete', 'guard_name' => 'api','group_name'=>'salary','se_name'=>'employeeSalary-delete','belongs_to'=>'2']);
         Permission::create(['name' => 'salary-browse', 'guard_name' => 'api','group_name'=>'salary','se_name'=>'employeeSalary-browse','belongs_to'=>'2']);

         Permission::create(['name' => 'stockManage-read', 'guard_name' => 'api','group_name'=>'stockManage','se_name'=>'stockManage-read','belongs_to'=>'2']);
         Permission::create(['name' => 'stockManage-add', 'guard_name' => 'api','group_name'=>'stockManage','se_name'=>'stockManage-create','belongs_to'=>'2']);
         Permission::create(['name' => 'stockManage-edit', 'guard_name' => 'api','group_name'=>'stockManage','se_name'=>'stockManage-edit','belongs_to'=>'2']);
         Permission::create(['name' => 'stockManage-delete', 'guard_name' => 'api','group_name'=>'stockManage','se_name'=>'stockManage-delete','belongs_to'=>'2']);
         Permission::create(['name' => 'stockManage-browse', 'guard_name' => 'api','group_name'=>'stockManage','se_name'=>'stockManage-browse','belongs_to'=>'2']);

           
        Permission::create(['name' => 'unit-read', 'guard_name' => 'api','group_name'=>'unit','se_name'=>'unit-read','belongs_to'=>'2']);
        Permission::create(['name' => 'unit-add', 'guard_name' => 'api','group_name'=>'unit','se_name'=>'unit-create','belongs_to'=>'2']);
        Permission::create(['name' => 'unit-edit', 'guard_name' => 'api','group_name'=>'unit','se_name'=>'unit-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'unit-delete', 'guard_name' => 'api','group_name'=>'unit','se_name'=>'unit-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'unit-browse', 'guard_name' => 'api','group_name'=>'unit','se_name'=>'unit-browse','belongs_to'=>'2']);

        Permission::create(['name' => 'customer-read', 'guard_name' => 'api','group_name'=>'customer','se_name'=>'customer-read','belongs_to'=>'2']);
        Permission::create(['name' => 'customer-add', 'guard_name' => 'api','group_name'=>'customer','se_name'=>'customer-create','belongs_to'=>'2']);
        Permission::create(['name' => 'customer-edit', 'guard_name' => 'api','group_name'=>'customer','se_name'=>'customer-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'customer-delete', 'guard_name' => 'api','group_name'=>'customer','se_name'=>'customer-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'customer-browse', 'guard_name' => 'api','group_name'=>'customer','se_name'=>'customer-browse','belongs_to'=>'2']);

        Permission::create(['name' => 'category-read', 'guard_name' => 'api','group_name'=>'category','se_name'=>'category-read','belongs_to'=>'2']);
        Permission::create(['name' => 'category-add', 'guard_name' => 'api','group_name'=>'category','se_name'=>'category-create','belongs_to'=>'2']);
        Permission::create(['name' => 'category-edit', 'guard_name' => 'api','group_name'=>'category','se_name'=>'category-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'category-delete', 'guard_name' => 'api','group_name'=>'category','se_name'=>'category-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'category-browse', 'guard_name' => 'api','group_name'=>'category','se_name'=>'category-browse','belongs_to'=>'2']);

        Permission::create(['name' => 'menu-read', 'guard_name' => 'api','group_name'=>'menu','se_name'=>'menu-read','belongs_to'=>'2']);
        Permission::create(['name' => 'menu-add', 'guard_name' => 'api','group_name'=>'menu','se_name'=>'menu-create','belongs_to'=>'2']);
        Permission::create(['name' => 'menu-edit', 'guard_name' => 'api','group_name'=>'menu','se_name'=>'menu-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'menu-delete', 'guard_name' => 'api','group_name'=>'menu','se_name'=>'menu-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'menu-browse', 'guard_name' => 'api','group_name'=>'menu','se_name'=>'menu-browse','belongs_to'=>'2']);

        Permission::create(['name' => 'order-read', 'guard_name' => 'api','group_name'=>'order','se_name'=>'order-read','belongs_to'=>'2']);
        Permission::create(['name' => 'order-add', 'guard_name' => 'api','group_name'=>'order','se_name'=>'order-create','belongs_to'=>'2']);
        Permission::create(['name' => 'order-edit', 'guard_name' => 'api','group_name'=>'order','se_name'=>'order-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'order-delete', 'guard_name' => 'api','group_name'=>'order','se_name'=>'order-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'order-browse', 'guard_name' => 'api','group_name'=>'order','se_name'=>'order-browse','belongs_to'=>'2']);

        
        Permission::create(['name' => 'role-browse', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-browse','belongs_to'=>'3']);
    	Permission::create(['name' => 'role-read', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-read','belongs_to'=>'3']);
    	Permission::create(['name' => 'role-add', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-add','belongs_to'=>'3']);
    	Permission::create(['name' => 'role-edit', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-edit','belongs_to'=>'3']);
    	Permission::create(['name' => 'role-delete', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-delete','belongs_to'=>'3']);



        Permission::create(['name' => 'subcafe-read', 'guard_name' => 'api','group_name'=>'OnlyForCafe','se_name'=>'subcafe-read','belongs_to'=>'6']);
        Permission::create(['name' => 'subcafe-add', 'guard_name' => 'api','group_name'=>'OnlyForCafe','se_name'=>'subcafe-create','belongs_to'=>'6']);
        Permission::create(['name' => 'subcafe-edit', 'guard_name' => 'api','group_name'=>'OnlyForCafe','se_name'=>'subcafe-edit','belongs_to'=>'6']);
        Permission::create(['name' => 'subcafe-delete', 'guard_name' => 'api','group_name'=>'OnlyForCafe','se_name'=>'subcafe-delete','belongs_to'=>'6']);
        Permission::create(['name' => 'subcafe-browse', 'guard_name' => 'api','group_name'=>'OnlyForCafe','se_name'=>'subcafe-browse','belongs_to'=>'6']);

        Permission::create(['name' => 'wastage-read', 'guard_name' => 'api','group_name'=>'wastage','se_name'=>'wastage-read','belongs_to'=>'2']);
        Permission::create(['name' => 'wastage-add', 'guard_name' => 'api','group_name'=>'wastage','se_name'=>'wastage-create','belongs_to'=>'2']);
        Permission::create(['name' => 'wastage-edit', 'guard_name' => 'api','group_name'=>'wastage','se_name'=>'wastage-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'wastage-delete', 'guard_name' => 'api','group_name'=>'wastage','se_name'=>'wastage-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'wastage-browse', 'guard_name' => 'api','group_name'=>'wastage','se_name'=>'wastage-browse','belongs_to'=>'2']);


         Permission::create(['name' => 'stock-history-browse', 'guard_name' => 'api','group_name'=>'stock-history-browse','se_name'=>'stock-history-browse','belongs_to'=>'2']);

         Permission::create(['name' => 'report-browse', 'guard_name' => 'api','group_name'=>'report-browse','se_name'=>'report-browse','belongs_to'=>'3']);

        Permission::create(['name' => 'stock-transfer-read', 'guard_name' => 'api','group_name'=>'stock-transfer','se_name'=>'stock-transfer-read','belongs_to'=>'2']);
        Permission::create(['name' => 'stock-transfer-add', 'guard_name' => 'api','group_name'=>'stock-transfer','se_name'=>'stock-transfer-create','belongs_to'=>'2']);
        Permission::create(['name' => 'stock-transfer-edit', 'guard_name' => 'api','group_name'=>'stock-transfer','se_name'=>'stock-transfer-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'stock-transfer-delete', 'guard_name' => 'api','group_name'=>'stock-transfer','se_name'=>'stock-transfer-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'stock-transfer-browse', 'guard_name' => 'api','group_name'=>'stock-transfer','se_name'=>'stock-transfer-browse','belongs_to'=>'2']);

    

        Permission::create(['name' => 'pull-data-read', 'guard_name' => 'api','group_name'=>'pull-data','se_name'=>'pull-data-read','belongs_to'=>'2']);
        Permission::create(['name' => 'pull-data-add', 'guard_name' => 'api','group_name'=>'pull-data','se_name'=>'pull-data-create','belongs_to'=>'2']);
        Permission::create(['name' => 'pull-data-edit', 'guard_name' => 'api','group_name'=>'pull-data','se_name'=>'pull-data-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'pull-data-delete', 'guard_name' => 'api','group_name'=>'pull-data','se_name'=>'pull-data-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'pull-data-browse', 'guard_name' => 'api','group_name'=>'pull-data','se_name'=>'pull-data-browse','belongs_to'=>'2']);
    	
         Permission::create(['name' => 'opening-stock-read', 'guard_name' => 'api','group_name'=>'opening-stock','se_name'=>'opening-stock-read','belongs_to'=>'2']);
        Permission::create(['name' => 'opening-stock-add', 'guard_name' => 'api','group_name'=>'opening-stock','se_name'=>'opening-stock-create','belongs_to'=>'2']);
        Permission::create(['name' => 'opening-stock-edit', 'guard_name' => 'api','group_name'=>'opening-stock','se_name'=>'opening-stock-edit','belongs_to'=>'2']);
        Permission::create(['name' => 'opening-stock-delete', 'guard_name' => 'api','group_name'=>'opening-stock','se_name'=>'opening-stock-delete','belongs_to'=>'2']);
        Permission::create(['name' => 'opening-stock-browse', 'guard_name' => 'api','group_name'=>'opening-stock','se_name'=>'opening-stock-browse','belongs_to'=>'2']);


/////////////////Changes End///////////////
        // $roles = Role::all();
        // foreach ($roles as $key => $role) {
        //     $permissions = Permission::where('belongs_to',$role->id)->get();
        //     $role->syncPermissions($permissions);
        // }

        // $adminRole = Role::where('id',1)->get();
        // $adminPermissions = Permission::whereIn('belongs_to',[1,3])->get();
        // $adminRole->syncPermissions($adminPermissions);

        // $storeRole = Role::where('id',2)->get();
        // $storePermissions = Permission::whereIn('belongs_to',[2,3])->get();
        // $storeRole->syncPermissions($storePermissions);
    
    }
}
