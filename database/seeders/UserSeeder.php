<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create(['name' => 'Naufal Hady', 'username' => 'naufal_hady', 'email' => 'naufalhady08@yahoo.com', 'phonenumber' => '0809210371']);
        User::factory()->create(['name' => 'Raihan', 'username' => 'surya_rehan', 'email' => 'suryarehan@gmail.com', 'phonenumber' => '0809210372']);
        User::factory()->create(['name' => 'Tsalsabila Jilhan Haura', 'username' => 'jilhanhaura', 'email' => 'jilhanhaura@gmail.com', 'phonenumber' => '0809210373']);
    }
}
