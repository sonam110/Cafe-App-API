<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
// use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        /*------------Default Role-----------------------------------*/
        // $role1 = Role::create([
        //     'id' => '1',
        //     'cafe_id' => '1',
        //     'name' => 'Admin',
        //     'guard_name' => 'api'
        // ]);
        // $role2 = Role::create([
        //     'id' => '2',
        //     'cafe_id' => '1',
        //     'name' => 'Cafe',
        //     'guard_name' => 'api'
        // ]);
        // $role3 = Role::create([
        //     'id' => '3',
        //     'cafe_id' => '1',
        //     'name' => 'AdminEmployee',
        //     'guard_name' => 'api'
        // ]);
        // $role4 = Role::create([
        //     'id' => '4',
        //     'cafe_id' => '1',
        //     'name' => 'Employee',
        //     'guard_name' => 'api'
        // ]);
        // $role5 = Role::create([
        //     'id' => '5',
        //     'cafe_id' => '1',
        //     'name' => 'Customer',
        //     'guard_name' => 'api'
        // ]);
         // $role6 = Role::create([
        //     'id' => '6',
        //     'cafe_id' => '1',
        //     'name' => 'SubCafe',
        //     'guard_name' => 'api'
        // ]);
       
        /*-----------Create Admin-------------*/
        // $adminUser = new User();
        // $adminUser->id                      = '1';
        // $adminUser->uuid                    = Str::uuid();
        // $adminUser->role_id                 = '1';
        // $adminUser->cafe_id                 = '1';
        // $adminUser->name                    = 'Admin';
        // $adminUser->email                   = 'admin@gmail.com';
        // $adminUser->password                = \Hash::make(12345678);
        // $adminUser->save();

        $adminRole = Role::where('id','1')->first();
        // $adminUser->assignRole($adminRole);
        
        /*-----------Create Cafe-------------*/
        // $cafeUser = new User();
        // $cafeUser->id                      = '2';
        // $cafeUser->uuid                    = Str::uuid();
        // $cafeUser->role_id                 = '2';
        // $adminUser->cafe_id                 = '2';
        // $cafeUser->subscription_status     = '1';
        // $cafeUser->name                    = 'Cafe';
        // $cafeUser->email                   = 'cafe@gmail.com';
        // $cafeUser->password                = \Hash::make(12345678);
        // $cafeUser->save();

        $cafeRole = Role::where('id','2')->first();
        // $cafeUser->assignRole($cafeRole);

       /*-----------Create AdminEmployee-------------*/
       // $adminEmployeeUser = new User();
       // $adminEmployeeUser->id                      = '3';
       // $adminEmployeeUser->uuid                    = Str::uuid();
       // $adminEmployeeUser->role_id                 = '3';
       // $adminUser->cafe_id                          = '1';
       // $adminEmployeeUser->name                    = 'AdminEmployee';
       // $adminEmployeeUser->email                   = 'adminemployee@gmail.com';
       // $adminEmployeeUser->password                = \Hash::make(12345678);
       // $adminEmployeeUser->save();

        $adminEmployeeRole = Role::where('id','3')->first();
        // $adminEmployeeUser->assignRole($adminEmployeeRole);

        /*-----------Create Employee-------------*/
        // $employeeUser = new User();
        // $employeeUser->id                      = '4';
        // $employeeUser->uuid                    = Str::uuid();
        // $employeeUser->role_id                 = '4';
        // $adminUser->cafe_id                     = '2';
        // $employeeUser->name                    = 'Employee';
        // $employeeUser->email                   = 'employee@gmail.com';
        // $employeeUser->password                = \Hash::make(12345678);
        // $employeeUser->save();

        $employeeRole = Role::where('id','4')->first();
        $subCafeRole = Role::where('id','6')->first();
        // $employeeUser->assignRole($employeeRole);

        /*-----------Create Customer-------------*/
        // $customerUser = new User();
        // $customerUser->id                      = '5';
        // $customerUser->role_id                 = '5';
        // $customerUser->name                    = 'Customer';
        // $customerUser->email                   = 'customer@gmail.com';
        // $customerUser->password                = \Hash::make(12345678);
        // $customerUser->save();

        // $customerRole = Role::where('id','5')->first();
        // $customerUser->assignRole($customerRole);

           

        /*-----------Assigne Permission------------------*/
        $adminPermissions = Permission::select('id','name')->whereIn('belongs_to',['1','3','4','5'])->get();
        foreach ($adminPermissions as $key => $permission) {
            $addedPermission = $permission->name;
            $adminRole->givePermissionTo($addedPermission);
        }

        $cafePermissions = Permission::select('id','name')->whereIn('belongs_to',['2','3','5','6'])->get();
        foreach ($cafePermissions as $key => $permission) {
            $addedPermission = $permission->name;
            $cafeRole->givePermissionTo($addedPermission);

        }
        $subCafePermissions = Permission::select('id','name')->whereIn('belongs_to',['2','3','5'])->get();
        foreach ($subCafePermissions as $key => $permission) {
            $addedPermission = $permission->name;
            $subCafeRole->givePermissionTo($addedPermission);

        }

        $adminEmployeePermissions = Permission::select('id','name')->whereIn('belongs_to',['4','5'])->get();
        foreach ($adminEmployeePermissions as $key => $permission) {
            $addedPermission = $permission->name;
            $adminEmployeeRole->givePermissionTo($addedPermission);

        }
    }
}
