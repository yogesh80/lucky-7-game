<?php

namespace Database\Seeders;
use App\Models\User;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'id'             => 1,
                'name'     => 'Super Admin',
                'user_type'=>1,
                'about_me'=>"Owner of webApp",
                'phone'           => '+919079794224',
                'email'          => 'admin@gmail.com',
                'password'       => '$2y$10$bvYhN6JwpRJ/3e8mwEzpiu9YUxDi0QfbJM3GjQAa9fiz0QE5SACB6',
                'remember_token' => null,
                'is_active' =>'1',
            ],

        ];

        User::insert($users);
    }
}
