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
        if (auth()->user()->role == 'manager') {
            $id = auth()->user()->id;
            $user = User::find($id);
            $houses = House::with('category')->where('user_id', $id)->get();
            $data = ['user' => $user, 'houses' => $houses];
            return response()->json($data);
        } else {
            return response()->json(['error' => 'ban khong phai manager']);
        }
    }

    public function updateHouse($id, Request $request)
    {
        $house = House::find($id);
        if ($house->status != $request->status) {
            $house->status = $request->status;
            $house->save();
            return response()->json(['success' => 'update thanh cong']);
        }
        return response()->json(['error' => 'house dang o trang thai: ' . $request->status]);
    }
}
