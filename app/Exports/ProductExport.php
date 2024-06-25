<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductExport implements FromCollection, WithHeadings
{

    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
            $query = Product::select('*')
            ->with('unit:id,name,minvalue')
            ->orderBy('id', 'desc');

            if (isset($this->filters['name'])) {
                $query->where('name', 'like', '%' . $this->filters['name'] . '%');
            }

            if (isset($this->filters['description'])) {
                $query->where('description', 'like', '%' . $this->filters['description'] . '%');
            }
            if (isset($this->filters['unit_id'])) {
                $query->where('unit_id', $this->filters['description'] );
            }
            if (isset($this->filters['current_quanitity'])) {
                $query->where('current_quanitity', $this->filters['current_quanitity'] );
            }



        $allProducts =  $query->get();

         return $array = $allProducts->map(function ($b, $key) {
                    return [
                      'ID'                     => $b->id,
                      'Name'            => $b->name,
                      'Description'            => $b->description,
                      'Unit'             => @$b->unit->name,
                      'Price'        => $b->price,
                      'Current quanitity'        => $b->current_quanitity,
                      'Alter quanitity'        => $b->alert_quanitity,
                     
                    ];
                });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Unit',
            'Price',
            'Current quanitity',
            'Alter quanitity',
           
        ];
    }
}
