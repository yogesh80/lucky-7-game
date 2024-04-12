<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cms;

class Cmsseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cms = [
            [
                'id'    => '1',
                'title' => 'About Us',
                'body'  => 'Content About you',
            ],
            [
                'id'    => '2',
                'title' => 'Contact Us',
                'body'  => 'Contact info',
            ],
            [
                'id'    => '3',
                'title' => 'Privacy Policy',
                'body'  => 'policies',
            ],
            [
                'id'    => '4',
                'title' => 'Terms & Conditions',
                'body'  => 'Terms & Conditions',
            ],
             [
                'id'    => '5',
                'title' => 'refund & Conditions',
                'body'  => 'Terms & Conditions',
            ],
             [
                'id'    => '6',
                'title' => 'delivery & shipping Conditions',
                'body'  => 'Terms & Conditions',
            ],


        ];
        Cms::insert($cms);
    }
}
