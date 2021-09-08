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
        $user->user_name = 'manager';
        $user->avatar = 'https://firebasestorage.googleapis.com/v0/b/home-from-home-93499.appspot.com/o/RoomsImages%2Favatar-default.jpg?alt=media&token=ef61ecd6-070f-4b21-bb27-47b3926c8f72';
        $user->email = 'buithanhtung100496@gmail.com';
        $user->password = bcrypt('123456');
        $user->phone = '0359303276';
        $user->role = 'manager';
        $user->save();

        $user = new User();
        $user->user_name = 'user';
        $user->avatar = 'https://firebasestorage.googleapis.com/v0/b/home-from-home-93499.appspot.com/o/RoomsImages%2Favatar-default.jpg?alt=media&token=ef61ecd6-070f-4b21-bb27-47b3926c8f72';
        $user->email = 'buitungptit@gmail.com';
        $user->password = bcrypt('123456');
        $user->phone = '0362528696';
        $user->role = 'user';
        $user->save();

    }
}
