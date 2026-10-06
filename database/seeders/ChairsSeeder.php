<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Beach;
use App\Models\Chair;

class ChairsSeeder extends Seeder
{
    public function run()
    {
        $columns = ['A', 'B', 'C'];
        $rows = 20;
        $types = ['vip', 'adult', 'family'];

        $beaches = Beach::all();

        foreach ($beaches as $beach) {
            foreach ($types as $type) {

                foreach ($columns as $col) {

                    for ($row = 1; $row <= $rows; $row++) {

                        $code = $col . str_pad($row, 2, '0', STR_PAD_LEFT);

                        Chair::create([
                            'beach_id'       => $beach->id,
                            'code'           => $code,
                            'type'           => $type,
                            'column_letter'  => $col,
                            'row_number'     => $row,
                            'price'          => $type === 'adult' ? 20.00 : 35.00,
                            'status'         => $row % 2 == 0 ? 'available' : 'reserved',
                        ]);
                    }
                }
            }
        }
    }
}
