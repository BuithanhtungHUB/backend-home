<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getHouseList()
    {
        $id = auth()->user()->id;
        $user = User::find($id);
        $houses = House::with('category')->where('user_id', $id)->get();
        $data = ['user' => $user, 'houses' => $houses];
        return response()->json($data);
    }
}
