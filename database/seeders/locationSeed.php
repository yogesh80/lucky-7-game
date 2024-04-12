<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class locationSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $store = [
            [
                'id'             => 1,
                'slug'          =>'0_500',
                'title'          =>'From 0 to 500 rupees',
                'delivery_fee'     =>'90',
            ],
            [
                'id'             => 2,
                'slug'          =>'0_1000',
                'title'          =>'From 0 to 1000 rupees',
                'delivery_fee'     =>'90',
            ],
            [
                'id'             => 3,
                'slug'          =>'1000_1500',
                'title'          =>'From 1000 to 1500 rupees',
                'delivery_fee'     =>'90',
            ],
            [
                'id'             => 4,
                'slug'          =>'1500_2000',
                'title'          =>'From 1500 to 2000 rupees',
                'delivery_fee'     =>'90',
            ],
            [
                'id'             => 5,
                'slug'          =>'2000_2500',
                'title'          =>'From 2000 to 2500 rupees',
                'delivery_fee'     =>'90',
            ],
            [
                'id'             => 6,
                'slug'          =>'2500_5000',
                'title'          =>'From 2500 to 5000 rupees',
                'delivery_fee'     =>'90',
            ],
            [
                'id'             => 7,
                'slug'          =>'5000_10000',
                'title'          =>'From 5000 to 10000 rupees',
                'delivery_fee'     =>'90',
            ],
            [
                'id'             => 8,
                'slug'          =>'above_10000',
                'title'          =>'Above 10000 rupees',
                'delivery_fee'     =>'90',
            ],
            

        ];

        Location::insert($store);
    }
}
