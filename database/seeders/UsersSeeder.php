<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User();
        $user->user_name = 'nguyendacchien';
        $user->email = 'nguyendachien@gmail.com';
        $user->password = bcrypt('654321');
        $user->phone = '0969686222';
        $user->role = 'renter';
        $user->save();
    }
}
