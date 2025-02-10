<?php

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
        DB::table('users')->insert([
            'full_name'  => 'Yoga Maulana',
            'email'      => 'yogam@gmail.com',
            'username'   => 'admin',
            'password'   => bcrypt('yoga123'),
            'avatar'     => '898192462.png'
        ]);
    }
}
