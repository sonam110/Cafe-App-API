<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Unit;


class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
         /*------------Default Unit-----------------------------------*/
         $Unit1 = Unit::create([
            'id' => '1',
            // 'cafe_id' => '1',
            'name' => 'Kilogram',
            'abbreiation' => 'kg',
            'minvalue' => '1000'
        ]);
        $Unit2 = Unit::create([
            'id' => '2',
            // 'cafe_id' => '1',
            'name' => 'Gram',
            'abbreiation' => 'g',
            'minvalue' => '1'
        ]);
        $Unit3 = Unit::create([
            'id' => '3',
            // 'cafe_id' => '1',
            'name' => 'Litre',
            'abbreiation' => 'litre',
            'minvalue' => '1000'
        ]);
        $Unit4 = Unit::create([
            'id' => '4',
            'cafe_id' => '1',
            'name' => 'Millilitre',
            'abbreiation' => 'ml',
            'minvalue' => '1'
        ]);
        $Unit5 = Unit::create([
            'id' => '5',
            'cafe_id' => '1',
            'name' => 'Pack',
            'abbreiation' => 'pk',
            'minvalue' => '1'
        ]);
        $Unit6 = Unit::create([
            'id' => '6',
            'cafe_id' => '1',
            'name' => 'Piece',
            'abbreiation' => 'pc',
            'minvalue' => '1'
        ]);
        $Unit7 = Unit::create([
            'id' => '7',
            // 'cafe_id' => '1',
            'name' => 'Dozen',
            'abbreiation' => 'dz',
            'minvalue' => '12'
        ]);
    }
}
