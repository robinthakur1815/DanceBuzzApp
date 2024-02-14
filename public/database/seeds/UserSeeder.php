<?php

use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->truncate();
        DB::table('users')->insert([
            'name' => 'Robin',
            'email' => 'Robin@dancebuzz.com',
            'password' => bcrypt('admin@123'),
            'created_at' => '2019-02-08 06:57:12',
            'updated_at' => '2019-02-13 06:54:12',
            'email_verified_at' => '2019-02-13 06:54:12',
            'role_id' => 1,
            'is_active' => true,
        ]);
        DB::table('users')->insert([
            'name' => 'Rahul',
            'email' => 'rahult@dancebuzz.com',
            'password' => bcrypt('admin@123'),
            'created_at' => '2019-02-08 06:57:12',
            'updated_at' => '2019-02-13 06:54:12',
            'email_verified_at' => '2019-02-13 06:54:12',
            'role_id' => 1,
            'phone' => '9717344925',
            'is_active' => true,
        ]);
    }
}
